<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Models\Category;
use App\Models\Expense;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ExpenseChart extends ApexChartWidget
{
    protected static ?string $chartId = 'expenseChart';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    protected function getHeading(): ?string
    {
        return $this->filterFormData['category'].' Usage';
    }

    protected array $filterMap = [
        'Electricity' => [
            'date_column' => 'usage_date',
            'aggregate_column' => 'usage_per_day',
            'aggregate' => 'average',
            'label' => 'Usage/day',
        ],
        'Gas' => [
            'date_column' => 'usage_date',
            'aggregate_column' => 'usage_per_day',
            'aggregate' => 'average',
            'label' => 'Usage/day',
        ],
        'Internet' => [
            'date_column' => 'usage_date',
            'aggregate_column' => 'price',
            'aggregate' => 'sum',
            'label' => 'Price',
        ],
        'Water Filter' => [
            'date_column' => 'usage_date',
            'aggregate_column' => 'price',
            'aggregate' => 'sum',
            'label' => 'Price',
        ],
    ];

    protected function getFormSchema(): array
    {
        return [
            Select::make('category')
                ->selectablePlaceholder(false)
                ->options(Category::all()->pluck('name', 'name'))
                ->default('Electricity'),
            DatePicker::make('usage_from')
                ->native(false)
                ->default(today()->startOfMonth()->subMonths(11)),
            DatePicker::make('usage_until')
                ->native(false)
                ->default(today()),
        ];
    }

    protected function getOptions(): array
    {
        $cacheKey = 'usage_'.collect($this->filterFormData)->values()->join('_');
        $category = $this->filterFormData['category'];

        $data = Cache::flexible($cacheKey, [now()->addHour(), now()->addHour()->addMinutes(5)], function () use ($category) {
            $catId = Category::query()->where('name', $category)->first()->id;

            return Trend::query(Expense::query()->where('category_id', $catId))
                ->dateColumn($this->filterMap[$category]['date_column'])
                ->between(
                    Carbon::parse($this->filterFormData['usage_from']),
                    Carbon::parse($this->filterFormData['usage_until']),
                )
                ->perMonth()
                ->{$this->filterMap[$category]['aggregate']}($this->filterMap[$category]['aggregate_column']);
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'redrawOnParentResize' => true,
            ],
            'series' => [
                [
                    'name' => $this->filterMap[$category]['label'],
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
