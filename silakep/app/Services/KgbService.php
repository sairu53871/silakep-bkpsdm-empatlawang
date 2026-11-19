<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Models\GajiPokok;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanKgb;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Logika inti KGB:
 *  - TMT KGB baru  = TMT KGB terakhir + 2 tahun
 *  - MKG baru      = MKG lama + 2 tahun
 *  - Gaji baru     = tabel gaji pokok (golongan, MKG baru)
 *  - Syarat        = SKP minimal Baik & tidak sedang hukdis penundaan KGB
 */
class KgbService
{
    /**
     * Hitung rencana KGB berikutnya untuk seorang pegawai.
     *
     * @return array{tmt_baru: Carbon, mkg_baru_tahun: int, mkg_baru_bulan: int, gaji_baru: int, peringatan: list<string>}
     */
    public function hitung(Pegawai $pegawai): array
    {
        $tmtBaru = $pegawai->tmt_kgb_terakhir->copy()->addYears(2);
        $mkgBaru = $pegawai->mkg_tahun + 2;
        $peringatan = [];

        $baris = GajiPokok::cari($pegawai->golongan, $mkgBaru);
        $gajiBaru = $baris?->gaji ?? $pegawai->gaji_pokok;

        if (! $baris) {
            $peringatan[] = "Tabel gaji untuk golongan {$pegawai->golongan} MKG {$mkgBaru} tahun tidak ditemukan.";
        } elseif ($gajiBaru <= $pegawai->gaji_pokok) {
            $max = GajiPokok::mkgMaksimal($pegawai->golongan);
            $peringatan[] = $mkgBaru > $max
                ? "MKG sudah melewati batas maksimal golongan {$pegawai->golongan} ({$max} tahun). Gaji pokok tidak naik."
                : 'Gaji pokok baru tidak lebih besar dari gaji lama. Periksa data gaji/MKG pegawai.';
        }

        if (! in_array($pegawai->nilai_skp, config('silakep.kgb.skp_memenuhi'), true)) {
            $peringatan[] = 'Nilai SKP terakhir "'.($pegawai->nilai_skp ?: 'belum ada').'" — syarat KGB minimal "Baik".';
        }

        if ($pegawai->hukdis_tunda_kgb_sampai && $pegawai->hukdis_tunda_kgb_sampai->gte($tmtBaru)) {
            $peringatan[] = 'Sedang menjalani hukuman disiplin penundaan KGB s.d. '.$pegawai->hukdis_tunda_kgb_sampai->translatedFormat('d F Y').'.';
        }

        return [
            'tmt_baru' => $tmtBaru,
            'mkg_baru_tahun' => $mkgBaru,
            'mkg_baru_bulan' => $pegawai->mkg_bulan,
            'gaji_baru' => $gajiBaru,
            'peringatan' => $peringatan,
        ];
    }

    /**
     * Cari pegawai yang KGB-nya jatuh tempo dalam N bulan ke depan (termasuk yang sudah lewat)
     * dan buatkan usulan berstatus Draft bila belum ada.
     *
     * @return int jumlah usulan baru
     */
    public function deteksiJatuhTempo(?Carbon $hariIni = null, bool $kirimNotifikasi = true): int
    {
        $hariIni ??= now();
        $batas = $hariIni->copy()->addMonths(config('silakep.kgb.deteksi_bulan_ke_depan'))->endOfMonth();
        // TMT berikutnya <= batas  <=>  TMT terakhir <= batas - 2 tahun
        $batasTmtTerakhir = $batas->copy()->subYears(2);

        $dibuat = collect();

        Pegawai::query()
            ->where('aktif', true)
            ->where('jenis_asn', 'PNS')
            ->whereDate('tmt_kgb_terakhir', '<=', $batasTmtTerakhir)
            ->whereDoesntHave('usulanKgbs', fn ($q) => $q->whereIn('status', array_map(
                fn (StatusUsulan $s) => $s->value,
                array_filter(StatusUsulan::cases(), fn ($s) => $s->aktif())
            )))
            ->chunkById(200, function ($pegawais) use ($dibuat) {
                foreach ($pegawais as $pegawai) {
                    $hasil = $this->hitung($pegawai);

                    // Jangan buat ulang untuk TMT yang sama (mis. sudah pernah ditolak)
                    if ($pegawai->usulanKgbs()->whereDate('tmt_baru', $hasil['tmt_baru'])->exists()) {
                        continue;
                    }

                    $dibuat->push($this->buatUsulan($pegawai, $hasil));
                }
            });

        if ($kirimNotifikasi && $dibuat->isNotEmpty()) {
            $this->notifikasiUsulanBaru($dibuat);
        }

        return $dibuat->count();
    }

    public function buatUsulan(Pegawai $pegawai, ?array $hasil = null): UsulanKgb
    {
        $hasil ??= $this->hitung($pegawai);

        return DB::transaction(function () use ($pegawai, $hasil) {
            $usulan = UsulanKgb::create([
                'pegawai_id' => $pegawai->id,
                'opd_id' => $pegawai->opd_id,
                'status' => StatusUsulan::Draft,
                'golongan' => $pegawai->golongan,
                'tmt_lama' => $pegawai->tmt_kgb_terakhir,
                'mkg_lama_tahun' => $pegawai->mkg_tahun,
                'mkg_lama_bulan' => $pegawai->mkg_bulan,
                'gaji_lama' => $pegawai->gaji_pokok,
                'tmt_baru' => $hasil['tmt_baru'],
                'mkg_baru_tahun' => $hasil['mkg_baru_tahun'],
                'mkg_baru_bulan' => $hasil['mkg_baru_bulan'],
                'gaji_baru' => $hasil['gaji_baru'],
                'nilai_skp' => $pegawai->nilai_skp,
                'catatan_sistem' => $hasil['peringatan'] ?: null,
            ]);

            $usulan->riwayats()->create([
                'status_ke' => StatusUsulan::Draft,
                'catatan' => 'Dibuat otomatis oleh sistem: KGB jatuh tempo '.$hasil['tmt_baru']->translatedFormat('d F Y').'.',
            ]);

            return $usulan;
        });
    }

    /** Hitung ulang usulan dari data pegawai terbaru (dipakai setelah data pegawai diperbaiki). */
    public function hitungUlang(UsulanKgb $usulan): UsulanKgb
    {
        $pegawai = $usulan->pegawai->fresh();
        $hasil = $this->hitung($pegawai);

        $usulan->update([
            'golongan' => $pegawai->golongan,
            'tmt_lama' => $pegawai->tmt_kgb_terakhir,
            'mkg_lama_tahun' => $pegawai->mkg_tahun,
            'mkg_lama_bulan' => $pegawai->mkg_bulan,
            'gaji_lama' => $pegawai->gaji_pokok,
            'tmt_baru' => $hasil['tmt_baru'],
            'mkg_baru_tahun' => $hasil['mkg_baru_tahun'],
            'mkg_baru_bulan' => $hasil['mkg_baru_bulan'],
            'gaji_baru' => $hasil['gaji_baru'],
            'nilai_skp' => $pegawai->nilai_skp,
            'catatan_sistem' => $hasil['peringatan'] ?: null,
        ]);

        return $usulan;
    }

    /** Satu notifikasi ringkas per Admin OPD + notifikasi ke ASN bersangkutan. */
    protected function notifikasiUsulanBaru($usulans): void
    {
        $perOpd = $usulans->groupBy('opd_id');

        User::query()->where('role', Role::AdminOpd)->whereIn('opd_id', $perOpd->keys())->get()
            ->each(function (User $admin) use ($perOpd) {
                $jumlah = $perOpd[$admin->opd_id]->count();
                Notification::make()
                    ->title("{$jumlah} pegawai jatuh tempo KGB")
                    ->body('Silakan periksa dan ajukan usulan KGB pegawai di OPD Anda.')
                    ->info()
                    ->sendToDatabase($admin);
            });

        User::query()->where('role', Role::Asn)->whereIn('pegawai_id', $usulans->pluck('pegawai_id'))->get()
            ->each(function (User $asn) use ($usulans) {
                $u = $usulans->firstWhere('pegawai_id', $asn->pegawai_id);
                Notification::make()
                    ->title('KGB Anda jatuh tempo '.$u->tmt_baru->translatedFormat('d F Y'))
                    ->body("Usulan {$u->nomor_usulan} sudah dibuat dan menunggu pengajuan oleh Admin OPD.")
                    ->info()
                    ->sendToDatabase($asn);
            });
    }
}
