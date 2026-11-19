<?php

namespace App\Filament\Resources\Pegawais;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Filament\Resources\Pegawais\Pages\EditPegawai;
use App\Filament\Resources\Pegawais\Pages\ListPegawais;
use App\Filament\Resources\UsulanKgbs\UsulanKgbResource;
use App\Models\Pegawai;
use App\Services\KgbService;
use App\Support\Golongan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Data Pegawai';

    protected static ?string $slug = 'pegawai';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Data Kepegawaian';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== Role::Asn;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->punyaPeran(Role::SuperAdmin, Role::Verifikator);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('opd')->terlihatOleh(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        $bkpsdm = fn () => auth()->user()->role->lihatSemuaOpd();

        return $schema->components([
            Section::make('Identitas')->columns(3)->schema([
                TextInput::make('nip')->label('NIP')->required()->length(18)->numeric()->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation) => $operation === 'edit' && ! $bkpsdm()),
                TextInput::make('nama')->required()->columnSpan(2),
                TextInput::make('gelar_depan'),
                TextInput::make('gelar_belakang'),
                Select::make('jenis_kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
                TextInput::make('tempat_lahir'),
                DatePicker::make('tanggal_lahir'),
                TextInput::make('no_hp')->label('No. HP / WhatsApp')->tel(),
            ]),
            Section::make('Kepegawaian')->columns(3)->schema([
                Select::make('jenis_asn')->label('Jenis ASN')->options(['PNS' => 'PNS', 'PPPK' => 'PPPK'])->default('PNS')->required()
                    ->helperText('MVP: KGB otomatis hanya untuk PNS.'),
                Select::make('golongan')->options(Golongan::opsi())->required()->searchable(),
                TextInput::make('jabatan'),
                Select::make('opd_id')->label('Unit Kerja (OPD)')->relationship('opd', 'nama')->searchable()->preload()->required()
                    ->disabled(fn () => ! $bkpsdm())->columnSpan(2),
                Toggle::make('aktif')->default(true)->inline(false),
            ]),
            Section::make('Data KGB')
                ->description('Data ini menjadi dasar perhitungan KGB berikutnya. Pastikan sesuai SK KGB terakhir.')
                ->columns(4)->schema([
                    DatePicker::make('tmt_kgb_terakhir')->label('TMT KGB terakhir')->required(),
                    TextInput::make('mkg_tahun')->label('MKG (tahun)')->numeric()->minValue(0)->maxValue(40)->required(),
                    TextInput::make('mkg_bulan')->label('MKG (bulan)')->numeric()->minValue(0)->maxValue(11)->default(0),
                    TextInput::make('gaji_pokok')->label('Gaji pokok saat ini')->numeric()->prefix('Rp')->required(),
                    Select::make('nilai_skp')->label('Nilai SKP terakhir')->options(array_combine(config('silakep.nilai_skp'), config('silakep.nilai_skp'))),
                    TextInput::make('tahun_skp')->label('Tahun SKP')->numeric(),
                    DatePicker::make('hukdis_tunda_kgb_sampai')->label('Hukdis penundaan KGB s.d.')
                        ->helperText('Kosongkan jika tidak ada.')
                        ->disabled(fn () => ! $bkpsdm())->columnSpan(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $bkpsdm = fn () => auth()->user()->role->lihatSemuaOpd();

        return $table
            ->defaultSort('tmt_kgb_terakhir')
            ->columns([
                TextColumn::make('nama')->searchable(['nama', 'nip'])->description(fn (Pegawai $p) => $p->nip)->wrap(),
                TextColumn::make('opd.singkatan')->label('OPD')->visible($bkpsdm)->toggleable(),
                TextColumn::make('golongan')->label('Gol.')->sortable(),
                TextColumn::make('mkg_tahun')->label('MKG')->formatStateUsing(fn (Pegawai $p) => "{$p->mkg_tahun} th {$p->mkg_bulan} bl"),
                TextColumn::make('gaji_pokok')->label('Gaji Pokok')->formatStateUsing(fn ($state) => Golongan::rupiah($state))->alignEnd(),
                TextColumn::make('tmt_kgb_terakhir')->label('TMT KGB Terakhir')->date('d M Y')->sortable(),
                TextColumn::make('tmt_kgb_berikutnya')->label('KGB Berikutnya')
                    ->state(fn (Pegawai $p) => $p->tmt_kgb_berikutnya)
                    ->date('d M Y')->badge()
                    ->color(fn (Pegawai $p) => match (true) {
                        $p->tmt_kgb_berikutnya->isPast() => 'danger',
                        $p->tmt_kgb_berikutnya->lte(now()->addMonths(3)) => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('nilai_skp')->label('SKP')->badge()
                    ->color(fn ($state) => in_array($state, config('silakep.kgb.skp_memenuhi'), true) ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('jenis_asn')->label('Jenis')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('opd_id')->label('OPD')->relationship('opd', 'nama')->searchable()->preload()->visible($bkpsdm),
                SelectFilter::make('golongan')->options(Golongan::opsi()),
                Filter::make('jatuh_tempo')->label('KGB jatuh tempo ≤ 3 bulan')
                    ->query(fn (Builder $query) => $query->whereDate('tmt_kgb_terakhir', '<=', now()->addMonths(3)->subYears(2))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('buat_usulan')
                    ->label('Buat Usulan KGB')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->visible(fn (Pegawai $p) => $p->jenis_asn === 'PNS'
                        && ! $p->usulanKgbs()->whereNotIn('status', [StatusUsulan::Selesai->value, StatusUsulan::Ditolak->value])->exists())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Pegawai $p) => 'Buat usulan KGB manual dengan TMT '.$p->tmt_kgb_berikutnya->translatedFormat('d F Y').'?')
                    ->action(function (Pegawai $p) {
                        $svc = app(KgbService::class);
                        $hasil = $svc->hitung($p);
                        if ($p->usulanKgbs()->whereDate('tmt_baru', $hasil['tmt_baru'])->exists()) {
                            Notification::make()->title('Usulan untuk TMT ini sudah pernah dibuat.')->warning()->send();

                            return;
                        }
                        $u = $svc->buatUsulan($p, $hasil);
                        Notification::make()->title("Usulan {$u->nomor_usulan} dibuat")->success()
                            ->actions([Action::make('lihat')->url(UsulanKgbResource::getUrl('view', ['record' => $u]))])
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPegawais::route('/'),
            'edit' => EditPegawai::route('/{record}/edit'),
        ];
    }
}
