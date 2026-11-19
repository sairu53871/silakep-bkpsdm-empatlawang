<?php

namespace App\Filament\Resources\UsulanKgbs;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Filament\Resources\UsulanKgbs\Pages\ListUsulanKgbs;
use App\Filament\Resources\UsulanKgbs\Pages\ViewUsulanKgb;
use App\Models\UsulanKgb;
use App\Support\Golongan;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class UsulanKgbResource extends Resource
{
    protected static ?string $model = UsulanKgb::class;

    protected static ?string $modelLabel = 'Usulan KGB';

    protected static ?string $pluralModelLabel = 'Usulan KGB';

    protected static ?string $slug = 'usulan-kgb';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Layanan KGB';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nomor_usulan';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->role === Role::Asn ? 'KGB Saya' : 'Usulan KGB';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $status = match ($user?->role) {
            Role::AdminOpd => [StatusUsulan::Draft, StatusUsulan::Perbaikan],
            Role::Verifikator => [StatusUsulan::Diajukan],
            Role::Kabid => [StatusUsulan::Terverifikasi],
            Role::Kepala => [StatusUsulan::Disetujui],
            default => null,
        };
        if (! $status) {
            return null;
        }
        $n = UsulanKgb::query()->terlihatOleh($user)->whereIn('status', $status)->count();

        return $n ?: null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Menunggu tindakan Anda';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['pegawai', 'opd'])
            ->terlihatOleh(auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $lihatSemua = fn () => auth()->user()->role->lihatSemuaOpd();

        return $table
            ->defaultSort('tmt_baru')
            ->columns([
                TextColumn::make('nomor_usulan')->label('No. Usulan')->searchable()->copyable()->weight('medium'),
                TextColumn::make('pegawai.nama')->label('Pegawai')
                    ->description(fn (UsulanKgb $r) => 'NIP '.$r->pegawai->nip)
                    ->searchable(['nama', 'nip'])->wrap(),
                TextColumn::make('opd.singkatan')->label('OPD')->visible($lihatSemua)->toggleable(),
                TextColumn::make('golongan')->label('Gol.')->alignCenter(),
                TextColumn::make('tmt_baru')->label('TMT KGB')->date('d M Y')->sortable()
                    ->color(fn (UsulanKgb $r) => $r->status->aktif() && $r->tmt_baru->isPast() ? 'danger' : null)
                    ->tooltip(fn (UsulanKgb $r) => $r->status->aktif() && $r->tmt_baru->isPast() ? 'Sudah lewat TMT, belum selesai' : null),
                TextColumn::make('gaji_baru')->label('Gaji Pokok Baru')
                    ->formatStateUsing(fn ($state) => Golongan::rupiah($state))
                    ->description(fn (UsulanKgb $r) => 'dari '.Golongan::rupiah($r->gaji_lama))
                    ->alignEnd(),
                IconColumn::make('catatan_sistem')->label('Cek')
                    ->state(fn (UsulanKgb $r) => ! $r->punyaPeringatan())
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)->falseIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('success')->falseColor('warning')
                    ->tooltip(fn (UsulanKgb $r) => $r->punyaPeringatan() ? implode("\n", $r->catatan_sistem) : 'Syarat terpenuhi'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('nomor_sk')->label('Nomor SK')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                TextColumn::make('updated_at')->label('Diperbarui')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('opd_id')->label('OPD')->relationship('opd', 'nama')->searchable()->preload()->visible($lihatSemua),
                SelectFilter::make('golongan')->options(Golongan::opsi()),
                Filter::make('lewat_tmt')->label('Sudah lewat TMT & belum selesai')
                    ->query(fn (Builder $query) => $query->whereDate('tmt_baru', '<', today())
                        ->whereNotIn('status', [StatusUsulan::Selesai->value, StatusUsulan::Ditolak->value])),
                TernaryFilter::make('peringatan')->label('Peringatan sistem')
                    ->trueLabel('Ada peringatan')->falseLabel('Tanpa peringatan')
                    ->queries(true: fn ($query) => $query->whereNotNull('catatan_sistem'), false: fn ($query) => $query->whereNull('catatan_sistem')),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                ActionGroup::make([
                    AksiUsulan::make('ajukan'),
                    AksiUsulan::make('verifikasi'),
                    AksiUsulan::make('setujui'),
                    AksiUsulan::make('tandatangani'),
                    AksiUsulan::make('kembalikan'),
                    AksiUsulan::make('tolak'),
                    AksiUsulan::unduhSk(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AksiUsulan::massal('ajukan'),
                    AksiUsulan::massal('verifikasi'),
                    AksiUsulan::massal('setujui'),
                    AksiUsulan::massal('tandatangani'),
                    AksiUsulan::unduhSkMassal(),
                ])->label('Proses Massal'),
            ])
            ->checkIfRecordIsSelectableUsing(fn () => auth()->user()->role !== Role::Asn)
            ->emptyStateHeading('Belum ada usulan KGB')
            ->emptyStateDescription('Usulan dibuat otomatis oleh sistem untuk pegawai yang KGB-nya jatuh tempo.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status Usulan')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('nomor_usulan')->label('No. Usulan')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('nomor_sk')->label('Nomor SK')->placeholder('Belum terbit'),
                    TextEntry::make('tanggal_sk')->label('Tanggal SK')->date('d F Y')->placeholder('-'),
                    TextEntry::make('catatan')->label('Catatan terakhir')->columnSpanFull()->placeholder('-')
                        ->visible(fn (UsulanKgb $r) => filled($r->catatan)),
                    TextEntry::make('catatan_sistem')->label('⚠ Peringatan sistem')->columnSpanFull()
                        ->listWithLineBreaks()->bulleted()->color('warning')
                        ->visible(fn (UsulanKgb $r) => $r->punyaPeringatan()),
                ]),
            Section::make('Data Pegawai')
                ->columns(3)
                ->schema([
                    TextEntry::make('pegawai.nama_lengkap')->label('Nama'),
                    TextEntry::make('pegawai.nip')->label('NIP')->copyable(),
                    TextEntry::make('opd.nama')->label('Unit Kerja'),
                    TextEntry::make('golongan')->label('Pangkat / Gol.')
                        ->formatStateUsing(fn (UsulanKgb $r) => $r->pangkat()." ({$r->golongan})"),
                    TextEntry::make('pegawai.jabatan')->label('Jabatan'),
                    TextEntry::make('nilai_skp')->label('Nilai SKP Terakhir')->badge()
                        ->color(fn ($state) => in_array($state, config('silakep.kgb.skp_memenuhi'), true) ? 'success' : 'danger'),
                ]),
            Grid::make(2)->columnSpanFull()->schema([
                Section::make('Kondisi Lama')->schema([
                    TextEntry::make('gaji_lama')->label('Gaji Pokok')->formatStateUsing(fn ($state) => Golongan::rupiah($state)),
                    TextEntry::make('mkg_lama_tahun')->label('Masa Kerja Golongan')
                        ->formatStateUsing(fn (UsulanKgb $r) => Golongan::mkg($r->mkg_lama_tahun, $r->mkg_lama_bulan)),
                    TextEntry::make('tmt_lama')->label('TMT')->date('d F Y'),
                ]),
                Section::make('Kondisi Baru (KGB)')->schema([
                    TextEntry::make('gaji_baru')->label('Gaji Pokok')->weight('bold')->color('success')
                        ->formatStateUsing(fn (UsulanKgb $r) => Golongan::rupiah($r->gaji_baru).'  (+'.Golongan::rupiah($r->gaji_baru - $r->gaji_lama).')'),
                    TextEntry::make('mkg_baru_tahun')->label('Masa Kerja Golongan')
                        ->formatStateUsing(fn (UsulanKgb $r) => Golongan::mkg($r->mkg_baru_tahun, $r->mkg_baru_bulan)),
                    TextEntry::make('tmt_baru')->label('TMT')->date('d F Y')->weight('bold'),
                ]),
            ]),
            Section::make('Berkas')
                ->columns(2)
                ->schema([
                    TextEntry::make('berkas_sk_terakhir')->label('SK KGB / Pangkat terakhir')
                        ->placeholder('Belum diunggah')
                        ->formatStateUsing(fn () => 'Lihat berkas')
                        ->url(fn (UsulanKgb $r) => $r->berkas_sk_terakhir ? route('berkas-kgb.lihat', [$r, 'sk_terakhir']) : null, true)
                        ->color('primary'),
                    TextEntry::make('berkas_skp')->label('SKP terakhir')
                        ->placeholder('Belum diunggah')
                        ->formatStateUsing(fn () => 'Lihat berkas')
                        ->url(fn (UsulanKgb $r) => $r->berkas_skp ? route('berkas-kgb.lihat', [$r, 'skp']) : null, true)
                        ->color('primary'),
                ]),
            Section::make('Riwayat / Tracking')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('riwayats')->hiddenLabel()->contained(false)->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('created_at')->hiddenLabel()->dateTime('d M Y H:i'),
                            TextEntry::make('status_ke')->hiddenLabel()->badge(),
                            TextEntry::make('user.name')->hiddenLabel()->placeholder('Sistem'),
                            TextEntry::make('catatan')->hiddenLabel()->placeholder('-'),
                        ]),
                    ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsulanKgbs::route('/'),
            'view' => ViewUsulanKgb::route('/{record}'),
        ];
    }

    /** Dipakai route berkas: hanya user yang berhak melihat usulan. */
    public static function berkasPath(UsulanKgb $usulan, string $jenis): ?string
    {
        $path = match ($jenis) {
            'sk_terakhir' => $usulan->berkas_sk_terakhir,
            'skp' => $usulan->berkas_skp,
            default => null,
        };

        return $path && Storage::disk('local')->exists($path) ? $path : null;
    }
}
