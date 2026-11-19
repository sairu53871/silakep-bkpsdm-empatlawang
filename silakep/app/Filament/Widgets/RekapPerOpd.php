<?php

namespace App\Filament\Widgets;

use App\Enums\StatusUsulan as S;
use App\Models\Opd;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Rekap layanan KGB per OPD untuk pimpinan & verifikator. */
class RekapPerOpd extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Usulan KGB per OPD';

    public static function canView(): bool
    {
        return auth()->user()?->role->lihatSemuaOpd() ?? false;
    }

    public function table(Table $table): Table
    {
        $hitung = fn (array $status) => fn ($query) => $query->whereIn('status', array_map(fn ($s) => $s->value, $status));

        return $table
            ->query(fn () => Opd::query()
                ->withCount([
                    'usulanKgbs as draft_count' => $hitung([S::Draft, S::Perbaikan]),
                    'usulanKgbs as proses_count' => $hitung([S::Diajukan, S::Terverifikasi, S::Disetujui]),
                    'usulanKgbs as selesai_count' => $hitung([S::Selesai]),
                    'usulanKgbs as lewat_count' => fn ($query) => $query->whereNotIn('status', [S::Selesai->value, S::Ditolak->value])->whereDate('tmt_baru', '<', today()),
                ]))
            ->defaultSort('draft_count', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('nama')->label('OPD')->searchable()->wrap(),
                TextColumn::make('draft_count')->label('Belum diajukan / perbaikan')->sortable()->alignCenter()
                    ->color(fn ($state) => $state ? 'warning' : null),
                TextColumn::make('proses_count')->label('Diproses BKPSDM')->sortable()->alignCenter(),
                TextColumn::make('selesai_count')->label('SK terbit')->sortable()->alignCenter()->color('success'),
                TextColumn::make('lewat_count')->label('Lewat TMT')->sortable()->alignCenter()
                    ->badge()->color(fn ($state) => $state ? 'danger' : 'gray'),
            ]);
    }
}
