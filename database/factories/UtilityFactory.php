<?php

namespace Database\Factories;

use App\Models\Utility;
use Illuminate\Database\Eloquent\Factories\Factory;

class UtilityFactory extends Factory
{
    protected $model = Utility::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'parent_id' => null,
        ];
    }
}
