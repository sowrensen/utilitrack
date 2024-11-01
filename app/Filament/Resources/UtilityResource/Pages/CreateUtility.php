<?php

namespace App\Filament\Resources\UtilityResource\Pages;

use App\Filament\Resources\UtilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUtility extends CreateRecord
{
    protected static string $resource = UtilityResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
