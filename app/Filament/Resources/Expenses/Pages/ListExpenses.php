<?php

namespace App\Filament\Resources\Expenses\Pages;

use Filament\Actions\ImportAction;
use Filament\Actions\CreateAction;
use App\Filament\Imports\ExpenseImporter;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import from file')
                ->importer(ExpenseImporter::class)
                ->color(Color::Amber),
            CreateAction::make(),
        ];
    }
}
