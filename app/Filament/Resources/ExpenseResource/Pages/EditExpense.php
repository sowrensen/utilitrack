<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('append')
                ->label('Append to Excel')
                ->color(Color::Amber)
                ->requiresConfirmation(fn (Expense $e) => $e->is_appended)
                ->modalDescription(fn (Expense $e) => $e->is_appended ? 'Data is already appended, are you sure?' : null)
                ->action(function (Expense $expense) {
                    $expense->appendToExcel();

                    Notification::make()
                        ->title('Success')
                        ->body('Your data will be appended to the sheet')
                        ->success()
                        ->send();
                }),
        ];
    }
}
