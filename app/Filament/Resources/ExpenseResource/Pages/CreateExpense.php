<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function afterCreate(): void
    {
        defer(function () {
            /** @var Expense $expense */
            $expense = $this->record;
            if ($this->data['append_to_google_sheets']) {
                $expense->appendToExcel();
            }
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        $body = $this->data['append_to_google_sheets']
            ? 'Your data will be appended to the sheet' : null;

        return Notification::make()
            ->title('Success')
            ->body($body)
            ->success()
            ->send();
    }
}
