<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->numberBetween(1, 5),
            'color' => $this->faker->optional()->word,
            'icon' => $this->faker->optional()->word
        ];
    }
}
