<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Enums\StatusUsulan as S;
use App\Models\Pegawai;
use App\Models\UsulanKgb;
use App\Support\Golongan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatistikKgb extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->role === Role::Asn) {
            return $this->statistikAsn();
        }

        $usulan = fn () => UsulanKgb::query()->terlihatOleh($user);
        $aktif = [S::Draft->value, S::Diajukan->value, S::Perbaikan->value, S::Terverifikasi->value, S::Disetujui->value];

        $tugas = match ($user->role) {
            Role::AdminOpd => [S::Draft, S::Perbaikan],
            Role::Verifikator => [S::Diajukan],
            Role::Kabid => [S::Terverifikasi],
            Role::Kepala => [S::Disetujui],
            default => [S::Diajukan, S::Terverifikasi, S::Disetujui],
        };
        $nTugas = $usulan()->whereIn('status', array_map(fn ($s) => $s->value, $tugas))->count();

        $jatuhTempo = Pegawai::query()->terlihatOleh($user)->where('aktif', true)->where('jenis_asn', 'PNS')
            ->whereDate('tmt_kgb_terakhir', '<=', now()->addMonths(3)->endOfMonth()->subYears(2))->count();
        $lewat = $usulan()->whereIn('status', $aktif)->whereDate('tmt_baru', '<', today())->count();
        $terbitBulanIni = $usulan()->where('status', S::Selesai->value)->whereBetween('selesai_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $proses = $usulan()->whereIn('status', $aktif)->count();

        // Rata-rata lama proses (hari) dari diajukan s.d. SK terbit
        $selesai = $usulan()->where('status', S::Selesai->value)->whereNotNull('diajukan_at')->get(['diajukan_at', 'selesai_at']);
        $rata = $selesai->isEmpty() ? null : round($selesai->avg(fn ($u) => $u->diajukan_at->diffInHours($u->selesai_at) / 24), 1);

        return [
            Stat::make('Perlu tindakan Anda', $nTugas)
                ->description($user->role === Role::SuperAdmin ? 'Usulan di tahap BKPSDM' : 'Menunggu diproses')
                ->color($nTugas ? 'danger' : 'success')->icon('heroicon-o-bell-alert'),
            Stat::make('Usulan dalam proses', $proses)->description("{$lewat} sudah lewat TMT")
                ->color($lewat ? 'warning' : 'primary')->icon('heroicon-o-arrow-path'),
            Stat::make('Pegawai jatuh tempo ≤ 3 bln', $jatuhTempo)->description('Termasuk yang terlewat')->icon('heroicon-o-calendar-days'),
            Stat::make('SK terbit bulan ini', $terbitBulanIni)
                ->description($rata !== null ? "Rata-rata proses {$rata} hari" : 'Belum ada data')
                ->color('success')->icon('heroicon-o-document-check'),
        ];
    }

    protected function statistikAsn(): array
    {
        $p = auth()->user()->pegawai;
        if (! $p) {
            return [Stat::make('Data pegawai', 'Belum terhubung')->description('Hubungi admin BKPSDM')];
        }
        $aktif = $p->usulanKgbs()->latest('id')->first();

        return [
            Stat::make('KGB berikutnya', $p->tmt_kgb_berikutnya->translatedFormat('d F Y'))
                ->description($p->tmt_kgb_berikutnya->isPast() ? 'Sudah jatuh tempo' : 'Sekitar '.now()->diffForHumans($p->tmt_kgb_berikutnya, ['parts' => 1, 'syntax' => 1]).' lagi')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Gaji pokok saat ini', Golongan::rupiah($p->gaji_pokok))->description("{$p->golongan} · MKG {$p->mkg_tahun} tahun")
                ->icon('heroicon-o-banknotes'),
            Stat::make('Status usulan terakhir', $aktif?->status->labelSingkat() ?? '-')
                ->description($aktif ? $aktif->nomor_usulan.' · '.$aktif->status->getLabel() : 'Belum ada usulan')
                ->color($aktif?->status->getColor() ?? 'gray')->icon('heroicon-o-clipboard-document-check'),
        ];
    }
}
