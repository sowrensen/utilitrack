<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ExpenseExporter extends Exporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('category.name'),
            ExportColumn::make('price'),
            ExportColumn::make('usable'),
            ExportColumn::make('leftover'),
            ExportColumn::make('unit'),
            ExportColumn::make('purchase_date'),
            ExportColumn::make('usage_date'),
            ExportColumn::make('interval')
                ->label('Interval (days)'),
            ExportColumn::make('interval_months')
                ->label('Interval (months)'),
            ExportColumn::make('usage_per_day')->label('Usage/day'),
            ExportColumn::make('note'),
        ];
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
            ExportFormat::Xlsx,
        ];
    }

    public function getFileDisk(): string
    {
        return 'public';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your expense export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
