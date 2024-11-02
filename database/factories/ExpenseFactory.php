<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'price' => fake()->numberBetween(100, 200),
            'usable' => fake()->numberBetween(100, 200),
            'leftover' => 0,
            'unit' => fake()->randomElement(['BDT', 'L']),
            'purchase_date' => now(),
            'usage_date' => now(),
            'note' => null,
        ];
    }
}