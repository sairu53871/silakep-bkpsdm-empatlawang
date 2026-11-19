<?php

namespace App\Filament\Resources\UsulanKgbs\Pages;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Filament\Resources\UsulanKgbs\UsulanKgbResource;
use App\Models\UsulanKgb;
use App\Services\KgbService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListUsulanKgbs extends ListRecords
{
    protected static string $resource = UsulanKgbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deteksi')
                ->label('Deteksi KGB Jatuh Tempo')
                ->icon(Heroicon::OutlinedMagnifyingGlassCircle)
                ->visible(fn () => auth()->user()->punyaPeran(Role::SuperAdmin, Role::Verifikator))
                ->requiresConfirmation()
                ->modalDescription('Sistem mencari pegawai PNS yang TMT KGB-nya jatuh tempo s.d. '.config('silakep.kgb.deteksi_bulan_ke_depan').' bulan ke depan (termasuk yang terlewat), lalu membuat usulan. Proses ini juga berjalan otomatis setiap hari.')
                ->action(function () {
                    $n = app(KgbService::class)->deteksiJatuhTempo();
                    Notification::make()
                        ->title($n ? "{$n} usulan KGB baru dibuat" : 'Tidak ada pegawai baru yang jatuh tempo')
                        ->success()->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $hitung = fn (array $status) => UsulanKgb::query()->terlihatOleh($user)
            ->whereIn('status', array_map(fn ($s) => $s->value, $status))->count();

        $tab = fn (string $label, array $status) => Tab::make($label)
            ->badge($hitung($status) ?: null)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', array_map(fn ($s) => $s->value, $status)));

        $tabs = ['semua' => Tab::make('Semua')];

        // Tab "Tugas Saya" sesuai peran
        $tugas = match ($user->role) {
            Role::AdminOpd => [StatusUsulan::Draft, StatusUsulan::Perbaikan],
            Role::Verifikator => [StatusUsulan::Diajukan],
            Role::Kabid => [StatusUsulan::Terverifikasi],
            Role::Kepala => [StatusUsulan::Disetujui],
            default => null,
        };
        if ($tugas) {
            $tabs = ['tugas' => $tab('Tugas Saya', $tugas)->badgeColor('danger')] + $tabs;
        }

        return $tabs + [
            'draft' => $tab('Draft', [StatusUsulan::Draft]),
            'proses' => $tab('Diproses', [StatusUsulan::Diajukan, StatusUsulan::Terverifikasi, StatusUsulan::Disetujui]),
            'perbaikan' => $tab('Perbaikan', [StatusUsulan::Perbaikan]),
            'selesai' => $tab('SK Terbit', [StatusUsulan::Selesai]),
            'ditolak' => $tab('Ditolak', [StatusUsulan::Ditolak]),
        ];
    }
}
