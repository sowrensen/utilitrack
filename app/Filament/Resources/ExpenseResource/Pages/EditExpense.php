<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getHeading(): string|Htmlable
    {
        $color = $this->record?->is_appended ? 'bg-primary-500' : 'bg-gray-400';

        return new HtmlString("
             <span class='flex items-center'>
                <span>Edit Expense</span>
                <span class='mx-1 mt-1 rounded-full h-1.5 w-1.5 {$color}'/>
            </span>
        ");
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('append')
                ->label('Append to Excel')
                ->color('gray')
                ->requiresConfirmation(fn (Expense $e) => $e->is_appended)
                ->modalDescription(fn (Expense $e) => $e->is_appended ? 'Data is already appended, are you sure?' : null)
                ->visible(config('services.google.sheet_id') && config('services.google.cloud_config_path'))
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
