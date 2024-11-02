<?php

namespace App\Models;

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
        'unit',
        'purchase_date',
        'usage_date',
        'interval',
        'usage_per_day',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'usage_date' => 'date',
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

    public function unit(): Attribute
    {
        return Attribute::set(fn ($value) => strtoupper($value));
    }

    public function usagePerDay(): Attribute
    {
        return Attribute::get(fn ($value) => round($value / 100, 2));
    }

    protected static function booted(): void
    {
        $closure = function (Expense $expense) {
            $previous = Expense::query()
                ->where('category_id', $expense->category_id)
                ->where('usage_date', '<', $expense->usage_date)
                ->orderBy('usage_date', 'desc')
                ->take(1)
                ->first();

            $interval = $previous ? (int) round($previous->purchase_date->diffInDays($expense->purchase_date)) : 0;
            $usagePerDay = $interval > 0
                ? round(($previous->usable + $previous->leftover - $expense->leftover) / $interval, 2) * 100
                : 0;

            $expense->interval = $interval;
            $expense->usage_per_day = $usagePerDay;
        };

        static::creating($closure);
        static::saving($closure);
    }
}