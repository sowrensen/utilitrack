<?php

namespace App\Models;

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
