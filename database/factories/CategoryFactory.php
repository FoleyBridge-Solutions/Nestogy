<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->numberBetween(1, 5),
            'color' => $this->faker->optional()->randomNumber(),
            'icon' => $this->faker->optional()->randomNumber()
        ];
    }
}
