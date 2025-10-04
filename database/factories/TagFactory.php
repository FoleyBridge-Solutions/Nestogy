<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->numberBetween(1, 5),
            'color' => $this->faker->optional()->hexColor,
            'icon' => $this->faker->optional()->randomNumber()
        ];
    }
}
