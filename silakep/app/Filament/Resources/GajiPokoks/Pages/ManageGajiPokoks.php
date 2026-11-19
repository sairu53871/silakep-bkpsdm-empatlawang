<?php

namespace App\Filament\Resources\GajiPokoks\Pages;

use App\Filament\Resources\GajiPokoks\GajiPokokResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGajiPokoks extends ManageRecords
{
    protected static string $resource = GajiPokokResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
