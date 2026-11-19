<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Enums\StatusUsulan as S;
use App\Filament\Resources\UsulanKgbs\UsulanKgbResource;
use App\Models\UsulanKgb;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Usulan aktif yang TMT-nya paling dekat / sudah lewat — supaya tidak ada KGB terlambat. */
class UsulanMendesak extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Usulan KGB Mendesak (TMT terdekat)';

    public static function canView(): bool
    {
        return auth()->user()?->role !== Role::Asn;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => UsulanKgb::query()->with(['pegawai', 'opd'])
                ->terlihatOleh(auth()->user())
                ->whereNotIn('status', [S::Selesai->value, S::Ditolak->value])
                ->orderBy('tmt_baru'))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->recordUrl(fn (UsulanKgb $r) => UsulanKgbResource::getUrl('view', ['record' => $r]))
            ->columns([
                TextColumn::make('nomor_usulan')->label('No. Usulan'),
                TextColumn::make('pegawai.nama')->label('Pegawai')->wrap(),
                TextColumn::make('opd.singkatan')->label('OPD'),
                TextColumn::make('tmt_baru')->label('TMT')->date('d M Y')
                    ->badge()->color(fn (UsulanKgb $r) => $r->tmt_baru->isPast() ? 'danger' : ($r->tmt_baru->lte(now()->addMonth()) ? 'warning' : 'gray')),
                TextColumn::make('status')->badge(),
            ]);
    }
}
