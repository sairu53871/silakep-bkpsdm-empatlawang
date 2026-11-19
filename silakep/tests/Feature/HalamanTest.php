<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanKgb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Smoke test: semua halaman utama terbuka tanpa error untuk tiap peran (pakai data dummy seeder). */
class HalamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed di dalam transaksi test supaya tidak bocor ke test lain
        $this->seed();
    }

    protected function masukSebagai(User $user): void
    {
        $this->flushSession();
        $this->actingAs($user);
    }

    public function test_halaman_publik(): void
    {
        $this->get('/')->assertOk()->assertSee('Lacak Status Usulan');
        $this->get('/admin/login')->assertOk()->assertSee('NIP / Username');

        $u = UsulanKgb::where('status', StatusUsulan::Selesai)->first();
        $this->get('/verifikasi/'.$u->token_verifikasi)->assertOk()->assertSee('SK terdaftar dan sah')->assertSee($u->nomor_sk);
        $this->get('/verifikasi?nomor_sk='.urlencode($u->nomor_sk))->assertRedirect('/verifikasi/'.$u->token_verifikasi);
        $this->get('/lacak?nomor='.$u->nomor_usulan.'&nip='.$u->pegawai->nip)->assertOk()->assertSee('SK Terbit');
    }

    public function test_halaman_panel_per_peran(): void
    {
        $contoh = UsulanKgb::first();

        foreach (['admin', 'verifikator', 'kabid', 'kepala', 'opd.'.strtolower($contoh->opd->kode)] as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->masukSebagai($user);

            $this->get('/admin')->assertOk();
            $this->get('/admin/usulan-kgb')->assertOk();
            $this->get('/admin/usulan-kgb/'.$contoh->id)->assertOk()->assertSee($contoh->nomor_usulan);
            $this->get('/admin/pegawai')->assertOk();
        }

        $this->masukSebagai(User::where('username', 'admin')->first());
        $this->get('/admin/gaji-pokok')->assertOk();
        $this->get('/admin/opd')->assertOk();
        $this->get('/admin/pengguna')->assertOk();
    }

    public function test_pembatasan_akses_admin_opd_dan_asn(): void
    {
        $adminOpd = User::where('role', Role::AdminOpd)->whereHas('opd', fn ($q) => $q->where('kode', 'DIKBUD'))->first();
        $lain = UsulanKgb::where('opd_id', '!=', $adminOpd->opd_id)->first();

        $this->masukSebagai($adminOpd);
        $this->get('/admin/usulan-kgb/'.$lain->id)->assertNotFound();
        $this->get('/admin/gaji-pokok')->assertForbidden();

        $asn = User::where('role', Role::Asn)->first();
        $this->masukSebagai($asn);
        $this->get('/admin')->assertOk();
        $this->get('/admin/usulan-kgb')->assertOk();
        $this->get('/admin/pegawai')->assertForbidden();
        $this->get('/admin/usulan-kgb/'.$lain->id)->assertNotFound();
        $this->get('/sk-kgb/'.$lain->id)->assertForbidden();
    }

    public function test_unduh_sk_pdf(): void
    {
        $u = UsulanKgb::where('status', StatusUsulan::Selesai)->first();
        $this->masukSebagai(User::where('username', 'kepala')->first());

        $res = $this->get('/sk-kgb/'.$u->id);
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_login_dengan_nip(): void
    {
        $asn = User::where('role', Role::Asn)->first();

        \Livewire\Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm(['username' => $asn->username, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($asn);
    }
}
