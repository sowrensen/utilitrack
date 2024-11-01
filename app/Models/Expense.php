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

    public function unit(): Attribute
    {
        return Attribute::set(fn ($value) => strtoupper($value));
    }
}
