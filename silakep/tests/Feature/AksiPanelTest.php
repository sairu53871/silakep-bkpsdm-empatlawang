<?php

namespace Tests\Feature;

use App\Enums\StatusUsulan;
use App\Filament\Resources\Pegawais\Pages\ListPegawais;
use App\Filament\Resources\UsulanKgbs\Pages\ListUsulanKgbs;
use App\Filament\Resources\UsulanKgbs\Pages\ViewUsulanKgb;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanKgb;
use App\Services\PegawaiImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/** Menguji tombol aksi di panel (bukan hanya service). */
class AksiPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_opd_mengajukan_dari_halaman_detail(): void
    {
        $usulan = UsulanKgb::where('status', StatusUsulan::Draft)->first();
        $admin = User::where('username', 'opd.'.strtolower($usulan->opd->kode))->first();
        $this->actingAs($admin);

        Livewire::test(ViewUsulanKgb::class, ['record' => $usulan->getRouteKey()])
            ->assertActionVisible('ajukan')
            ->assertActionHidden('verifikasi')
            ->callAction('ajukan', data: ['catatan' => 'Berkas sudah dicek OPD'])
            ->assertHasNoActionErrors();

        $this->assertSame(StatusUsulan::Diajukan, $usulan->fresh()->status);
    }

    public function test_kembalikan_wajib_alasan(): void
    {
        $usulan = UsulanKgb::where('status', StatusUsulan::Diajukan)->first();
        $this->actingAs(User::where('username', 'verifikator')->first());

        Livewire::test(ViewUsulanKgb::class, ['record' => $usulan->getRouteKey()])
            ->callAction('kembalikan', data: ['catatan' => ''])
            ->assertHasActionErrors(['catatan' => 'required']);

        $this->assertSame(StatusUsulan::Diajukan, $usulan->fresh()->status);
    }

    public function test_verifikasi_massal_dari_tabel(): void
    {
        $diajukan = UsulanKgb::where('status', StatusUsulan::Diajukan)->get();
        $this->actingAs(User::where('username', 'verifikator')->first());

        Livewire::test(ListUsulanKgbs::class)
            ->callTableBulkAction('verifikasi_massal', $diajukan);

        $this->assertSame(0, UsulanKgb::where('status', StatusUsulan::Diajukan)->count());
        $this->assertSame($diajukan->count(), UsulanKgb::whereIn('id', $diajukan->pluck('id'))->where('status', StatusUsulan::Terverifikasi)->count());
    }

    public function test_tandatangani_massal_menerbitkan_sk(): void
    {
        $disetujui = UsulanKgb::where('status', StatusUsulan::Disetujui)->get();
        $this->actingAs(User::where('username', 'kepala')->first());

        Livewire::test(ListUsulanKgbs::class)
            ->callTableBulkAction('tandatangani_massal', $disetujui);

        $hasil = UsulanKgb::whereIn('id', $disetujui->pluck('id'))->get();
        $this->assertTrue($hasil->every(fn ($u) => $u->status === StatusUsulan::Selesai && $u->nomor_sk && $u->token_verifikasi));
        $this->assertSame($hasil->count(), $hasil->pluck('nomor_sk')->unique()->count(), 'Nomor SK harus unik');
    }

    public function test_impor_excel_template(): void
    {
        $importer = app(PegawaiImporter::class);
        $file = $importer->template();

        // Template berisi 2 contoh baris valid
        $hasil = $importer->impor($file);
        $this->assertSame(2, $hasil['baru'], implode("\n", $hasil['gagal']));
        $this->assertSame([], $hasil['gagal']);
        $this->assertSame('III/b', Pegawai::where('nip', '198501012010011001')->value('golongan'));

        // Impor ulang = update, bukan duplikat. Tambah baris rusak untuk cek laporan gagal.
        $book = IOFactory::load($file);
        $book->getSheet(0)->fromArray(['123', 'NIP SALAH', '', '', '', '', '', 'PNS', 'III/a', '', 'DIKBUD', '2025-01-01', 4], null, 'A4');
        $book->getSheet(0)->fromArray(['199101012019011001', 'GOL SALAH', '', '', '', '', '', 'PNS', 'V/z', '', 'DIKBUD', '2025-01-01', 4], null, 'A5');
        IOFactory::createWriter($book, 'Xlsx')->save($file);

        $hasil = $importer->impor($file);
        $this->assertSame(0, $hasil['baru']);
        $this->assertSame(2, $hasil['diperbarui']);
        $this->assertCount(2, $hasil['gagal']);
    }

    public function test_halaman_pegawai_dan_tombol_impor(): void
    {
        $this->actingAs(User::where('username', 'verifikator')->first());
        Livewire::test(ListPegawais::class)
            ->assertActionVisible('impor')
            ->assertActionVisible('template');

        $this->actingAs(User::where('username', 'opd.dikbud')->first());
        Livewire::test(ListPegawais::class)
            ->assertActionHidden('impor');
    }
}
