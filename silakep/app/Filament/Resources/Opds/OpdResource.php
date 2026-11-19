<?php

namespace App\Filament\Resources\Opds;

use App\Enums\Role;
use App\Models\Opd;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OpdResource extends Resource
{
    protected static ?string $model = Opd::class;

    protected static ?string $modelLabel = 'OPD';

    protected static ?string $pluralModelLabel = 'Perangkat Daerah (OPD)';

    protected static ?string $slug = 'opd';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === Role::SuperAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->required()->maxLength(20)->unique(ignoreRecord: true)
                ->helperText('Dipakai di kolom KODE_OPD saat impor Excel.'),
            TextInput::make('singkatan')->maxLength(50),
            TextInput::make('nama')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('kode')->searchable(),
                TextColumn::make('nama')->searchable()->wrap(),
                TextColumn::make('singkatan'),
                TextColumn::make('pegawais_count')->counts('pegawais')->label('Pegawai')->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOpds::route('/')];
    }
}
