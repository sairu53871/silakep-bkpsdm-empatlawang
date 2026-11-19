<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $slug = 'pengguna';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === Role::SuperAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('username')->label('Username / NIP')->required()->unique(ignoreRecord: true),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            Select::make('role')->label('Peran')->options(Role::class)->required()->live(),
            Select::make('opd_id')->label('OPD')->relationship('opd', 'nama')->searchable()->preload()
                ->required(fn (Get $get) => in_array($get('role'), [Role::AdminOpd, Role::AdminOpd->value], true)),
            Select::make('pegawai_id')->label('Data pegawai (untuk ASN)')->relationship('pegawai', 'nama')
                ->getOptionLabelFromRecordUsing(fn ($r) => "{$r->nama} – {$r->nip}")
                ->searchable(['nama', 'nip'])
                ->required(fn (Get $get) => in_array($get('role'), [Role::Asn, Role::Asn->value], true)),
            TextInput::make('password')->password()->revealable()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Kosongkan jika tidak diubah.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('username')->searchable(),
                TextColumn::make('role')->label('Peran')->badge(),
                TextColumn::make('opd.singkatan')->label('OPD'),
            ])
            ->filters([SelectFilter::make('role')->label('Peran')->options(Role::class)])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
