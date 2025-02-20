<?php

namespace App\Models;

use App\Services\GoogleSheetService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'price',
        'usable',
        'leftover',
        'purchase_date',
        'usage_date',
        'interval',
        'usage_per_day',
        'note',
        'appended_at',
    ];

    protected $appends = [
        'interval_months',
        'is_appended',
    ];

    protected $with = [
        'category',
    ];

    protected $hidden = [
        'appended_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'usage_date' => 'date',
            'appended_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value / 100, 2),
            set: fn ($value) => $value * 100
        );
    }

    public function usable(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value / 100, 2),
            set: fn ($value) => $value * 100
        );
    }

    public function leftover(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value / 100, 2),
            set: fn ($value) => $value * 100
        );
    }

    public function usagePerDay(): Attribute
    {
        return Attribute::get(fn ($value) => round($value / 100, 2));
    }

    public function intervalMonths(): Attribute
    {
        return Attribute::get(fn ($value, $attributes) => round($attributes['interval'] / 30));
    }

    public function isAppended(): Attribute
    {
        return Attribute::get(fn ($value, $attributes) => ! empty($attributes['appended_at']));
    }

    protected static function booted(): void
    {
        // We will calculate the interval and usage per day automatically,
        // based on the previous record we have for the same category.
        $closure = function (Expense $expense) {
            $category = Category::find($expense->category_id);
            $previous = Expense::query()
                ->where('category_id', $expense->category_id)
                ->where('usage_date', '<', $expense->usage_date ?? $expense->purchase_date)
                ->orderBy('usage_date', 'desc')
                ->take(1)
                ->first();

            $interval = $previous ? (int) round($previous->purchase_date->diffInDays($expense->purchase_date)) : 0;
            if ($category->has_usage_per_day && $interval > 0) {
                $usagePerDay = round(($previous->usable + $previous->leftover - $expense->leftover) / $interval, 2) * 100;
            }
            $expense->interval = $interval;
            $expense->usage_per_day = $usagePerDay ?? 0;
        };

        static::creating($closure);
        static::saving($closure);
    }

    public function appendToExcel(): void
    {
        $service = new GoogleSheetService(config('services.google.sheet_id'));

        try {
            $service->appendCellValues([
                [
                    $this->category?->name ?? 'Undefined',
                    $this->price,
                    $this->usable,
                    $this->leftover,
                    $this->category?->unit,
                    $this->usage_date->format('M d, Y'),
                    $this->interval,
                    $this->interval_months,
                    $this->usage_per_day,
                    implode(' ', ['(Appended from app)', $this->note]),
                ],
            ], 'Sheet1');

            $this->update(['appended_at' => now()]);

            Notification::make()
                ->title('Success')
                ->body('Your data has been appended to the sheet')
                ->success()
                ->sendToDatabase(auth()->user());
        } catch (Exception $e) {
            Notification::make()
                ->title('Data appending failed')
                ->body($e->getMessage())
                ->danger()
                ->sendToDatabase(auth()->user());
        }
    }
}
