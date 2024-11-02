<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Imports\ExpenseImporter;
use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->label('Import from file')
                ->importer(ExpenseImporter::class)
                ->color(Color::Amber),
            Actions\CreateAction::make(),
        ];
    }
}
