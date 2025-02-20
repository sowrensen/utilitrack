<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Models\Category;
use App\Models\Expense;
use Cache;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ElectricityExpenseChart extends ApexChartWidget
{
    protected static ?string $chartId = 'electricityExpenseChart';

    protected static ?string $heading = 'Electricity Usage';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('usage_from')
                ->native(false)
                ->default(today()->startOfYear()),
            DatePicker::make('usage_until')
                ->native(false)
                ->default(today()),
        ];
    }

    protected function getOptions(): array
    {
        $cacheKey = 'electricity_usage_'.collect($this->filterFormData)->values()->join('_');
        $data = Cache::flexible($cacheKey, [now()->addHour(), now()->addHour()->addMinutes(5)], function () {
            $electricity = Category::query()->where('name', 'Electricity')->first();

            return Trend::query(Expense::query()->where('category_id', $electricity->id))
                ->dateColumn('usage_date')
                ->between(
                    Carbon::parse($this->filterFormData['usage_from']),
                    Carbon::parse($this->filterFormData['usage_until']),
                )
                ->perMonth()
                ->average('usage_per_day');
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'redrawOnParentResize' => true,
            ],
            'series' => [
                [
                    'name' => 'Usage/day',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate / 100),
                ],
            ],
            'xaxis' => [
                'categories' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M y')),
                'labels' => [
                    'rotate' => -45,
                    'rotateAlways' => true,
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'stroke' => [
                'show' => true,
                'curve' => 'smooth',
                'width' => 2,
            ],
            'plotOptions' => [
                'bar' => [
                    'distributed' => true,
                    'columnWidth' => '50%',
                    'colors' => [
                        'backgroundBarOpacity' => 0,
                    ],
                ],
            ],
        ];
    }
}
