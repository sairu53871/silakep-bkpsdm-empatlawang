<?php

namespace App\Filament\Resources\UsulanKgbs\Pages;

use App\Filament\Resources\UsulanKgbs\AksiUsulan;
use App\Filament\Resources\UsulanKgbs\UsulanKgbResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUsulanKgb extends ViewRecord
{
    protected static string $resource = UsulanKgbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AksiUsulan::make('ajukan'),
            AksiUsulan::make('verifikasi'),
            AksiUsulan::make('setujui'),
            AksiUsulan::make('tandatangani'),
            AksiUsulan::make('kembalikan'),
            AksiUsulan::make('tolak'),
            AksiUsulan::hitungUlang(),
            AksiUsulan::unduhSk(),
        ];
    }
}
