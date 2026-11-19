<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Models\GajiPokok;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanKgb;
use App\Services\KgbService;
use App\Services\UsulanKgbWorkflow;
use App\Support\Golongan;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data DUMMY untuk demo MVP KGB. Semua nama, NIP, dan angka adalah fiktif.
 * Password semua akun demo: "password".
 */
class DatabaseSeeder extends Seeder
{
    public const OPD = [
        ['SETDA', 'Sekretariat Daerah', 'Setda'],
        ['SETWAN', 'Sekretariat DPRD', 'Setwan'],
        ['INSP', 'Inspektorat Daerah', 'Inspektorat'],
        ['BKPSDM', 'Badan Kepegawaian dan Pengembangan SDM', 'BKPSDM'],
        ['BPKAD', 'Badan Pengelolaan Keuangan dan Aset Daerah', 'BPKAD'],
        ['BAPPEDA', 'Badan Perencanaan Pembangunan Daerah, Penelitian dan Pengembangan', 'Bappeda'],
        ['BAPENDA', 'Badan Pendapatan Daerah', 'Bapenda'],
        ['BPBD', 'Badan Penanggulangan Bencana Daerah', 'BPBD'],
        ['KESBANGPOL', 'Badan Kesatuan Bangsa dan Politik', 'Kesbangpol'],
        ['DIKBUD', 'Dinas Pendidikan dan Kebudayaan', 'Dikbud'],
        ['DINKES', 'Dinas Kesehatan', 'Dinkes'],
        ['RSUD', 'Rumah Sakit Umum Daerah', 'RSUD'],
        ['DPUPR', 'Dinas Pekerjaan Umum dan Penataan Ruang', 'PUPR'],
        ['DISKOMINFO', 'Dinas Komunikasi dan Informatika', 'Diskominfo'],
        ['DISDUKCAPIL', 'Dinas Kependudukan dan Pencatatan Sipil', 'Disdukcapil'],
        ['DINSOS', 'Dinas Sosial', 'Dinsos'],
        ['DPMD', 'Dinas Pemberdayaan Masyarakat dan Desa', 'DPMD'],
        ['DISTAN', 'Dinas Pertanian', 'Distan'],
        ['DISPERINDAG', 'Dinas Perindustrian dan Perdagangan', 'Disperindag'],
        ['DISHUB', 'Dinas Perhubungan', 'Dishub'],
        ['DLH', 'Dinas Lingkungan Hidup', 'DLH'],
        ['DPMPTSP', 'Dinas Penanaman Modal dan PTSP', 'DPMPTSP'],
        ['DISPAR', 'Dinas Pariwisata, Pemuda dan Olahraga', 'Dispar'],
        ['DISNAKER', 'Dinas Tenaga Kerja dan Transmigrasi', 'Disnaker'],
        ['SATPOLPP', 'Satuan Polisi Pamong Praja', 'Satpol PP'],
        ['DP3AKB', 'Dinas Pemberdayaan Perempuan, Perlindungan Anak dan KB', 'DP3AKB'],
        ['PERKIM', 'Dinas Perumahan dan Kawasan Permukiman', 'Perkim'],
        ['ARPUS', 'Dinas Kearsipan dan Perpustakaan', 'Arpus'],
        ['KEC-TT', 'Kecamatan Tebing Tinggi', 'Kec. Tebing Tinggi'],
        ['KEC-TP', 'Kecamatan Talang Padang', 'Kec. Talang Padang'],
        ['KEC-PAK', 'Kecamatan Pasemah Air Keruh', 'Kec. Paiker'],
        ['KEC-PDP', 'Kecamatan Pendopo', 'Kec. Pendopo'],
        ['KEC-UM', 'Kecamatan Ulu Musi', 'Kec. Ulu Musi'],
        ['KEC-LK', 'Kecamatan Lintang Kanan', 'Kec. Lintang Kanan'],
        ['KEC-MP', 'Kecamatan Muara Pinang', 'Kec. Muara Pinang'],
        ['KEC-PB', 'Kecamatan Pendopo Barat', 'Kec. Pendopo Barat'],
        ['KEC-SD', 'Kecamatan Sikap Dalam', 'Kec. Sikap Dalam'],
        ['KEC-SL', 'Kecamatan Saling', 'Kec. Saling'],
    ];

    public function run(): void
    {
        $this->call(GajiPokokSeeder::class);

        foreach (self::OPD as [$kode, $nama, $singkatan]) {
            Opd::create(compact('kode', 'nama', 'singkatan'));
        }

        $this->buatPegawai(400);
        $this->buatUser();

        // Jalankan deteksi otomatis seperti cron bulanan
        app(KgbService::class)->deteksiJatuhTempo();

        $this->simulasiAlur();
    }

    protected function buatPegawai(int $jumlah): void
    {
        $faker = Faker::create('id_ID');
        $faker->seed(2026);
        $opds = Opd::all();
        $hariIni = now()->startOfMonth();

        // Bobot golongan kira-kira seperti komposisi PNS daerah
        $golonganBobot = [
            'II/a' => 4, 'II/b' => 4, 'II/c' => 5, 'II/d' => 5,
            'III/a' => 12, 'III/b' => 14, 'III/c' => 12, 'III/d' => 14,
            'IV/a' => 14, 'IV/b' => 6, 'IV/c' => 2, 'I/c' => 1, 'I/d' => 1,
        ];
        $pool = collect($golonganBobot)->flatMap(fn ($b, $g) => array_fill(0, $b, $g))->all();
        $jabatan = ['Guru Ahli Pertama', 'Guru Ahli Muda', 'Perawat Terampil', 'Bidan Ahli Pertama', 'Analis Kepegawaian',
            'Pengadministrasi Umum', 'Penata Kelola Keuangan', 'Pranata Komputer', 'Arsiparis', 'Kepala Sub Bagian Umum',
            'Kepala Seksi', 'Pengawas Sekolah', 'Dokter Ahli Pertama', 'Penyuluh Pertanian', 'Pengelola Program'];

        $struktur = GajiPokokSeeder::struktur();

        for ($i = 1; $i <= $jumlah; $i++) {
            $gol = $faker->randomElement($pool);
            [$mkgs] = $struktur[$gol];
            // MKG pegawai mengikuti langkah tabel golongannya (bukan MKG terakhir agar masih bisa naik)
            $mkg = $faker->randomElement(array_slice($mkgs, 0, max(1, count($mkgs) - 2)));

            // TMT KGB terakhir: tanggal 1, tersebar 0–25 bulan lalu -> ada yang jatuh tempo tiap bulan
            $tmtKgb = $hariIni->copy()->subMonths($faker->numberBetween(0, 25));
            $lahir = Carbon::instance($faker->dateTimeBetween('-58 years', '-24 years'));
            $jk = $faker->randomElement(['L', 'P']);
            $tmtCpns = $tmtKgb->copy()->subYears($mkg)->max($lahir->copy()->addYears(19));
            $nip = $lahir->format('Ymd').$tmtCpns->format('Ym').($jk === 'L' ? '1' : '2').str_pad($i % 1000, 3, '0', STR_PAD_LEFT);

            $skp = $faker->randomElement(array_merge(array_fill(0, 30, 'Baik'), array_fill(0, 12, 'Sangat Baik'), ['Butuh Perbaikan']));

            Pegawai::create([
                'nip' => $nip,
                'nama' => strtoupper($jk === 'L' ? $faker->firstNameMale().' '.$faker->lastName() : $faker->firstNameFemale().' '.$faker->lastName()),
                'gelar_belakang' => str_starts_with($gol, 'III') || str_starts_with($gol, 'IV') ? $faker->randomElement(['S.Pd', 'S.E', 'S.Kom', 'S.Kep', 'S.H', 'S.Sos', 'M.Pd', 'S.T']) : null,
                'tempat_lahir' => $faker->randomElement(['Tebing Tinggi', 'Lahat', 'Pagar Alam', 'Palembang', 'Lubuklinggau', 'Talang Padang', 'Pendopo']),
                'tanggal_lahir' => $lahir,
                'jenis_kelamin' => $jk,
                'jenis_asn' => 'PNS',
                'golongan' => $gol,
                'jabatan' => $faker->randomElement($jabatan),
                'opd_id' => $opds->random()->id,
                'tmt_kgb_terakhir' => $tmtKgb,
                'mkg_tahun' => $mkg,
                'mkg_bulan' => 0,
                'gaji_pokok' => GajiPokok::cari($gol, $mkg)->gaji,
                'nilai_skp' => $skp,
                'tahun_skp' => now()->year - 1,
                'hukdis_tunda_kgb_sampai' => $i % 97 === 0 ? now()->addMonths(8)->startOfMonth() : null,
                'no_hp' => '08'.$faker->numerify('##########'),
            ]);
        }
    }

    protected function buatUser(): void
    {
        $bkpsdm = Opd::where('kode', 'BKPSDM')->first();

        $akun = [
            ['Super Admin', 'admin', Role::SuperAdmin, $bkpsdm->id],
            ['Verifikator KGB (Dummy)', 'verifikator', Role::Verifikator, $bkpsdm->id],
            ['Kabid Mutasi & Kepangkatan (Dummy)', 'kabid', Role::Kabid, $bkpsdm->id],
            ['Kepala BKPSDM (Dummy)', 'kepala', Role::Kepala, $bkpsdm->id],
        ];

        foreach ($akun as [$name, $username, $role, $opdId]) {
            User::create(compact('name', 'username', 'role') + ['opd_id' => $opdId, 'email' => "{$username}@silakep.test", 'password' => 'password']);
        }

        // Satu Admin OPD untuk setiap OPD: username opd.<kode>, mis. opd.dikbud
        foreach (Opd::all() as $opd) {
            $u = 'opd.'.strtolower($opd->kode);
            User::create([
                'name' => "Admin {$opd->singkatan}",
                'username' => $u,
                'email' => "{$u}@silakep.test",
                'password' => 'password',
                'role' => Role::AdminOpd,
                'opd_id' => $opd->id,
            ]);
        }

        // Akun ASN demo: pegawai Dikbud yang KGB-nya jatuh tempo bulan depan. Login pakai NIP.
        $dikbud = Opd::where('kode', 'DIKBUD')->first();
        $pegawai = Pegawai::where('opd_id', $dikbud->id)->first();
        $pegawai->update([
            'tmt_kgb_terakhir' => now()->addMonth()->startOfMonth()->subYears(2),
            'golongan' => 'III/b', 'mkg_tahun' => 10, 'mkg_bulan' => 0,
            'gaji_pokok' => GajiPokok::cari('III/b', 10)->gaji, 'nilai_skp' => 'Baik', 'hukdis_tunda_kgb_sampai' => null,
        ]);

        User::create([
            'name' => $pegawai->nama,
            'username' => $pegawai->nip,
            'email' => "{$pegawai->nip}@silakep.test",
            'password' => 'password',
            'role' => Role::Asn,
            'opd_id' => $pegawai->opd_id,
            'pegawai_id' => $pegawai->id,
        ]);
    }

    /** Majukan sebagian usulan ke berbagai tahap supaya demo terlihat hidup. */
    protected function simulasiAlur(): void
    {
        $wf = app(UsulanKgbWorkflow::class);
        $verifikator = User::where('username', 'verifikator')->first();
        $kabid = User::where('username', 'kabid')->first();
        $kepala = User::where('username', 'kepala')->first();
        $asnPegawaiId = User::where('role', Role::Asn)->value('pegawai_id');

        $usulans = UsulanKgb::query()
            ->where('status', StatusUsulan::Draft)
            ->where('pegawai_id', '!=', $asnPegawaiId)
            ->whereNull('catatan_sistem')
            ->orderBy('tmt_baru')
            ->get();

        foreach ($usulans as $i => $usulan) {
            $adminOpd = User::where('role', Role::AdminOpd)->where('opd_id', $usulan->opd_id)->first();
            $tahap = $i % 6; // 0 draft, 1 diajukan, 2 terverifikasi, 3 disetujui, 4 selesai, 5 perbaikan

            if ($tahap === 0) {
                continue;
            }
            $wf->jalankan('ajukan', $usulan, $adminOpd, null, false);
            if ($tahap === 5) {
                $wf->jalankan('kembalikan', $usulan, $verifikator, 'SKP tahun terakhir belum diunggah. Mohon lengkapi.', false);

                continue;
            }
            if ($tahap >= 2) {
                $wf->jalankan('verifikasi', $usulan, $verifikator, 'Berkas lengkap.', false);
            }
            if ($tahap >= 3) {
                $wf->jalankan('setujui', $usulan, $kabid, null, false);
            }
            if ($tahap >= 4) {
                $wf->jalankan('tandatangani', $usulan, $kepala, null, false);
            }
        }
    }
}
