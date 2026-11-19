<?php

namespace App\Services;

use App\Models\GajiPokok;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Support\Golongan;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Impor data pegawai dari Excel (format kolom PERKIRAAN, mengikuti ekspor SIASN/SIMPEG).
 * Baris dicocokkan berdasarkan NIP: jika sudah ada -> diperbarui, jika belum -> ditambahkan.
 */
class PegawaiImporter
{
    public const KOLOM = [
        'NIP', 'NAMA', 'GELAR_DEPAN', 'GELAR_BELAKANG', 'TEMPAT_LAHIR', 'TANGGAL_LAHIR', 'JENIS_KELAMIN',
        'JENIS_ASN', 'GOLONGAN', 'JABATAN', 'KODE_OPD', 'TMT_KGB_TERAKHIR', 'MKG_TAHUN', 'MKG_BULAN',
        'GAJI_POKOK', 'NILAI_SKP', 'TAHUN_SKP', 'NO_HP',
    ];

    public const WAJIB = ['NIP', 'NAMA', 'GOLONGAN', 'KODE_OPD', 'TMT_KGB_TERAKHIR', 'MKG_TAHUN'];

    /** @return array{baru:int, diperbarui:int, gagal: list<string>} */
    public function impor(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);
        $header = array_map(fn ($h) => strtoupper(trim((string) $h)), array_shift($rows) ?? []);

        $kurang = array_diff(self::WAJIB, $header);
        if ($kurang) {
            return ['baru' => 0, 'diperbarui' => 0, 'gagal' => ['Kolom wajib tidak ada: '.implode(', ', $kurang).'. Gunakan template impor.']];
        }

        $opd = Opd::pluck('id', 'kode')->mapWithKeys(fn ($id, $kode) => [strtoupper($kode) => $id]);
        $hasil = ['baru' => 0, 'diperbarui' => 0, 'gagal' => []];

        foreach ($rows as $i => $row) {
            $baris = $i + 2;
            $d = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            if (blank($d['NIP'] ?? null) && blank($d['NAMA'] ?? null)) {
                continue; // baris kosong
            }

            try {
                $nip = preg_replace('/\D/', '', (string) $d['NIP']);
                $golongan = $this->normalGolongan($d['GOLONGAN']);
                $opdId = $opd[strtoupper(trim((string) $d['KODE_OPD']))] ?? null;
                $mkg = (int) $d['MKG_TAHUN'];

                $error = match (true) {
                    strlen($nip) !== 18 => "NIP '{$d['NIP']}' harus 18 digit",
                    ! Golongan::valid($golongan) => "golongan '{$d['GOLONGAN']}' tidak dikenal",
                    ! $opdId => "KODE_OPD '{$d['KODE_OPD']}' tidak terdaftar",
                    default => null,
                };
                if ($error) {
                    throw new \RuntimeException($error);
                }

                $tmtKgb = $this->tanggal($d['TMT_KGB_TERAKHIR']) ?? throw new \RuntimeException('TMT_KGB_TERAKHIR tidak valid');
                $gaji = (int) preg_replace('/\D/', '', (string) ($d['GAJI_POKOK'] ?? ''))
                    ?: GajiPokok::cari($golongan, $mkg)?->gaji
                    ?: throw new \RuntimeException('GAJI_POKOK kosong & tidak ada di tabel gaji');

                $pegawai = Pegawai::updateOrCreate(['nip' => $nip], array_filter([
                    'nama' => trim((string) $d['NAMA']),
                    'gelar_depan' => $d['GELAR_DEPAN'] ?? null,
                    'gelar_belakang' => $d['GELAR_BELAKANG'] ?? null,
                    'tempat_lahir' => $d['TEMPAT_LAHIR'] ?? null,
                    'tanggal_lahir' => $this->tanggal($d['TANGGAL_LAHIR'] ?? null),
                    'jenis_kelamin' => in_array(strtoupper((string) ($d['JENIS_KELAMIN'] ?? '')), ['L', 'P'], true) ? strtoupper($d['JENIS_KELAMIN']) : null,
                    'jenis_asn' => strtoupper((string) ($d['JENIS_ASN'] ?? '')) ?: 'PNS',
                    'golongan' => $golongan,
                    'jabatan' => $d['JABATAN'] ?? null,
                    'opd_id' => $opdId,
                    'tmt_kgb_terakhir' => $tmtKgb,
                    'mkg_tahun' => $mkg,
                    'mkg_bulan' => (int) ($d['MKG_BULAN'] ?? 0),
                    'gaji_pokok' => $gaji,
                    'nilai_skp' => $d['NILAI_SKP'] ?? null,
                    'tahun_skp' => $d['TAHUN_SKP'] ?? null,
                    'no_hp' => $d['NO_HP'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''));

                $pegawai->wasRecentlyCreated ? $hasil['baru']++ : $hasil['diperbarui']++;
            } catch (Throwable $e) {
                $hasil['gagal'][] = "Baris {$baris}: ".$e->getMessage();
            }
        }

        return $hasil;
    }

    protected function normalGolongan(mixed $nilai): string
    {
        // Terima "III/a", "iii/a", "III-a", "3a", "III a"
        $s = strtoupper(trim((string) $nilai));
        $s = str_replace(['-', ' ', '.'], '/', $s);
        $s = preg_replace(['/^4/', '/^3/', '/^2/', '/^1/'], ['IV', 'III', 'II', 'I'], $s);
        if (preg_match('/^(IV|III|II|I)\/?([A-E])$/', $s, $m)) {
            return $m[1].'/'.strtolower($m[2]);
        }

        return (string) $nilai;
    }

    protected function tanggal(mixed $nilai): ?Carbon
    {
        if (blank($nilai)) {
            return null;
        }
        if (is_numeric($nilai)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $nilai))->startOfDay();
        }
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y'] as $format) {
            try {
                $tgl = Carbon::createFromFormat($format, trim($nilai));
                if ($tgl && $tgl->format($format) === trim($nilai)) {
                    return $tgl->startOfDay();
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    /** Buat file template Excel berisi header, contoh, dan daftar kode OPD. */
    public function template(): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Pegawai');
        $sheet->fromArray(self::KOLOM, null, 'A1');
        $sheet->fromArray([
            ['198501012010011001', 'BUDI SANTOSO', '', 'S.Kom', 'Tebing Tinggi', '1985-01-01', 'L', 'PNS', 'III/b', 'Pranata Komputer Ahli Pertama', 'DISKOMINFO', '2025-01-01', 10, 0, '', 'Baik', 2025, '081234567890'],
            ['199003152015032002', 'SITI AMINAH', '', 'S.Pd', 'Lahat', '15/03/1990', 'P', 'PNS', 'III/a', 'Guru Ahli Pertama', 'DIKBUD', '01/03/2025', 8, 0, 3153600, 'Sangat Baik', 2025, ''],
        ], null, 'A2');
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        foreach (range('A', 'R') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $ref = $book->createSheet()->setTitle('Kode OPD');
        $ref->fromArray(['KODE_OPD', 'NAMA OPD'], null, 'A1');
        $ref->fromArray(Opd::orderBy('nama')->get(['kode', 'nama'])->map->only(['kode', 'nama'])->map(fn ($r) => array_values($r))->all(), null, 'A2');
        $ref->getColumnDimension('A')->setAutoSize(true);
        $ref->getColumnDimension('B')->setAutoSize(true);

        $petunjuk = $book->createSheet()->setTitle('Petunjuk');
        $petunjuk->fromArray([
            ['Kolom wajib: '.implode(', ', self::WAJIB)],
            ['NIP ditulis sebagai teks (18 digit). Tanggal format YYYY-MM-DD atau DD/MM/YYYY.'],
            ['GOLONGAN contoh: III/a. NILAI_SKP: Sangat Baik / Baik / Butuh Perbaikan / Kurang / Sangat Kurang.'],
            ['GAJI_POKOK boleh kosong: akan diambil otomatis dari tabel gaji pokok.'],
            ['Jika NIP sudah ada, data akan DIPERBARUI.'],
        ], null, 'A1');

        $book->setActiveSheetIndex(0);
        $file = storage_path('app/template-impor-pegawai.xlsx');
        (new Xlsx($book))->save($file);

        return $file;
    }
}
