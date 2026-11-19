<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Models\GajiPokok;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanKgb;
use App\Services\KgbService;
use App\Services\UsulanKgbWorkflow;
use Database\Seeders\GajiPokokSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class KgbTest extends TestCase
{
    use RefreshDatabase;

    protected Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-19 10:00:00');
        $this->seed(GajiPokokSeeder::class);
        $this->opd = Opd::create(['kode' => 'DIKBUD', 'nama' => 'Dinas Pendidikan', 'singkatan' => 'Dikbud']);
    }

    protected function pegawai(array $attr = []): Pegawai
    {
        return Pegawai::create($attr + [
            'nip' => '198501012010011001',
            'nama' => 'BUDI',
            'golongan' => 'III/a',
            'opd_id' => $this->opd->id,
            'tmt_kgb_terakhir' => '2025-01-01',
            'mkg_tahun' => 8,
            'mkg_bulan' => 0,
            'gaji_pokok' => GajiPokok::cari('III/a', 8)->gaji,
            'nilai_skp' => 'Baik',
        ]);
    }

    protected function user(Role $role, array $attr = []): User
    {
        static $i = 0;
        $i++;

        return User::create($attr + [
            'name' => $role->value.$i, 'username' => $role->value.$i, 'email' => "u{$i}@t.test",
            'password' => 'password', 'role' => $role, 'opd_id' => $this->opd->id,
        ]);
    }

    public function test_hitung_kgb_dasar(): void
    {
        $hasil = app(KgbService::class)->hitung($this->pegawai());

        $this->assertSame('2027-01-01', $hasil['tmt_baru']->toDateString());
        $this->assertSame(10, $hasil['mkg_baru_tahun']);
        $this->assertSame(GajiPokok::cari('III/a', 10)->gaji, $hasil['gaji_baru']);
        $this->assertGreaterThan(GajiPokok::cari('III/a', 8)->gaji, $hasil['gaji_baru']);
        $this->assertSame([], $hasil['peringatan']);
    }

    public function test_peringatan_skp_hukdis_dan_mkg_maksimal(): void
    {
        $p = $this->pegawai([
            'nilai_skp' => 'Butuh Perbaikan',
            'hukdis_tunda_kgb_sampai' => '2027-06-01',
            'mkg_tahun' => 32,
            'gaji_pokok' => GajiPokok::cari('III/a', 32)->gaji,
        ]);

        $peringatan = implode(' | ', app(KgbService::class)->hitung($p)['peringatan']);

        $this->assertStringContainsString('SKP', $peringatan);
        $this->assertStringContainsString('hukuman disiplin', $peringatan);
        $this->assertStringContainsString('maksimal', $peringatan);
    }

    public function test_golongan_ii_mkg_ganjil_memakai_baris_tabel_terdekat(): void
    {
        $p = $this->pegawai(['golongan' => 'II/c', 'mkg_tahun' => 5, 'gaji_pokok' => GajiPokok::cari('II/c', 5)->gaji]);
        $hasil = app(KgbService::class)->hitung($p);

        $this->assertSame(7, $hasil['mkg_baru_tahun']);
        $this->assertSame(GajiPokok::where('golongan', 'II/c')->where('mkg', 7)->value('gaji'), $hasil['gaji_baru']);
    }

    public function test_deteksi_hanya_yang_jatuh_tempo_dan_tidak_dobel(): void
    {
        $this->pegawai(); // TMT berikutnya 2027-01-01 -> masuk jendela 3 bulan
        $this->pegawai(['nip' => '199001012015011002', 'tmt_kgb_terakhir' => '2026-01-01']); // 2028 -> tidak
        $this->pegawai(['nip' => '199001012015011003', 'tmt_kgb_terakhir' => '2024-03-01']); // terlewat -> masuk
        $this->pegawai(['nip' => '199001012015011004', 'tmt_kgb_terakhir' => '2024-03-01', 'jenis_asn' => 'PPPK']); // PPPK -> tidak

        $svc = app(KgbService::class);
        $this->assertSame(2, $svc->deteksiJatuhTempo());
        $this->assertSame(0, $svc->deteksiJatuhTempo(), 'Deteksi ulang tidak boleh membuat usulan ganda');

        $u = UsulanKgb::first();
        $this->assertMatchesRegularExpression('/^KGB-\d{6}-\d{5}$/', $u->nomor_usulan);
        $this->assertSame(StatusUsulan::Draft, $u->status);
        $this->assertCount(1, $u->riwayats);
    }

    public function test_alur_lengkap_sampai_sk_terbit_dan_data_pegawai_terupdate(): void
    {
        $p = $this->pegawai();
        $usulan = app(KgbService::class)->buatUsulan($p);
        $wf = app(UsulanKgbWorkflow::class);

        $adminOpd = $this->user(Role::AdminOpd);
        $verifikator = $this->user(Role::Verifikator);
        $kabid = $this->user(Role::Kabid);
        $kepala = $this->user(Role::Kepala);

        // Peran yang salah tidak boleh melompati tahap
        $this->assertFalse($wf->bisa('verifikasi', $usulan, $verifikator));
        $this->assertFalse($wf->bisa('tandatangani', $usulan, $kepala));

        $wf->jalankan('ajukan', $usulan, $adminOpd);
        $wf->jalankan('kembalikan', $usulan, $verifikator, 'SKP belum ada');
        $this->assertSame(StatusUsulan::Perbaikan, $usulan->status);
        $wf->jalankan('ajukan', $usulan, $adminOpd);
        $wf->jalankan('verifikasi', $usulan, $verifikator);
        $wf->jalankan('setujui', $usulan, $kabid);
        $wf->jalankan('tandatangani', $usulan, $kepala);

        $usulan->refresh();
        $this->assertSame(StatusUsulan::Selesai, $usulan->status);
        $this->assertSame('800.1.11.1/0001/KGB/BKPSDM/2026', $usulan->nomor_sk);
        $this->assertNotNull($usulan->token_verifikasi);
        $this->assertCount(7, $usulan->riwayats);

        $p->refresh();
        $this->assertSame('2027-01-01', $p->tmt_kgb_terakhir->toDateString());
        $this->assertSame(10, $p->mkg_tahun);
        $this->assertSame($usulan->gaji_baru, $p->gaji_pokok);

        // Notifikasi terkirim ke pihak terkait
        $this->assertGreaterThan(0, $kepala->notifications()->count());
        $this->assertGreaterThan(0, $adminOpd->notifications()->count());
    }

    public function test_admin_opd_lain_tidak_boleh_mengajukan_dan_alasan_wajib(): void
    {
        $usulan = app(KgbService::class)->buatUsulan($this->pegawai());
        $opdLain = Opd::create(['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan']);
        $adminLain = $this->user(Role::AdminOpd, ['opd_id' => $opdLain->id]);
        $wf = app(UsulanKgbWorkflow::class);

        $this->assertFalse($wf->bisa('ajukan', $usulan, $adminLain));

        $wf->jalankan('ajukan', $usulan, $this->user(Role::AdminOpd));
        $this->expectException(InvalidArgumentException::class);
        $wf->jalankan('kembalikan', $usulan, $this->user(Role::Verifikator), '');
    }

    public function test_proses_massal_melewati_yang_tidak_sesuai_tahap(): void
    {
        $svc = app(KgbService::class);
        $a = $svc->buatUsulan($this->pegawai());
        $b = $svc->buatUsulan($this->pegawai(['nip' => '199001012015011009']));
        $wf = app(UsulanKgbWorkflow::class);
        $wf->jalankan('ajukan', $a, $this->user(Role::AdminOpd));

        [$ok, $gagal] = $wf->jalankanMassal('verifikasi', collect([$a, $b]), $this->user(Role::Verifikator));

        $this->assertSame(1, $ok);
        $this->assertSame([$b->nomor_usulan], $gagal);
    }

    public function test_lacak_publik_wajib_nip_cocok_dan_verifikasi_sk(): void
    {
        $p = $this->pegawai();
        $usulan = app(KgbService::class)->buatUsulan($p);

        $this->get('/lacak?nomor='.$usulan->nomor_usulan.'&nip='.$p->nip)->assertOk()->assertSee('Menunggu Pengajuan OPD');
        $this->get('/lacak?nomor='.$usulan->nomor_usulan.'&nip=199999999999999999')->assertOk()->assertSee('tidak ditemukan');

        $this->get('/verifikasi/00000000-0000-0000-0000-000000000000')->assertOk()->assertSee('tidak terdaftar');
    }
}
