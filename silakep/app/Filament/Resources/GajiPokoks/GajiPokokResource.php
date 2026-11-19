<?php

namespace App\Filament\Resources\GajiPokoks;

use App\Enums\Role;
use App\Models\GajiPokok;
use App\Support\Golongan;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use UnitEnum;

class GajiPokokResource extends Resource
{
    protected static ?string $model = GajiPokok::class;

    protected static ?string $modelLabel = 'Gaji Pokok';

    protected static ?string $pluralModelLabel = 'Tabel Gaji Pokok';

    protected static ?string $slug = 'gaji-pokok';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public static function canAccess(): bool
    {
        return auth()->user()?->punyaPeran(Role::SuperAdmin, Role::Verifikator) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('golongan')->options(Golongan::opsi())->required(),
            TextInput::make('mkg')->label('MKG (tahun)')->numeric()->minValue(0)->maxValue(33)->required(),
            TextInput::make('gaji')->label('Gaji pokok')->numeric()->prefix('Rp')->required(),
            TextInput::make('dasar_hukum')->default(config('silakep.kgb.dasar_hukum_gaji')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⚠ Nilai saat ini PERKIRAAN (interpolasi dari PP 5/2024). Wajib dicocokkan dengan lampiran PP gaji resmi sebelum produksi.')
            ->defaultGroup(Group::make('golongan')->collapsible())
            ->defaultSort('mkg')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('golongan'),
                TextColumn::make('mkg')->label('MKG (tahun)')->sortable(),
                TextColumn::make('gaji')->label('Gaji Pokok')->formatStateUsing(fn ($state) => Golongan::rupiah($state))->alignEnd(),
                TextColumn::make('dasar_hukum')->color('gray')->toggleable(),
            ])
            ->filters([SelectFilter::make('golongan')->options(Golongan::opsi())])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageGajiPokoks::route('/')];
    }
}
