<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private array $categories = [
        ['name' => 'Electricity', 'has_usage_per_day' => true],
        ['name' => 'Gas', 'has_usage_per_day' => true],
        ['name' => 'Water Filter', 'has_usage_per_day' => false],
        ['name' => 'Internet', 'has_usage_per_day' => false],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect($this->categories)->sortBy('name')
            ->each(fn ($category) => Category::updateOrCreate($category));
    }
}
