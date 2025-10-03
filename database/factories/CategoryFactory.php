<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'type' => null,
            'color' => null,
            'icon' => null
        ];
    }
}
