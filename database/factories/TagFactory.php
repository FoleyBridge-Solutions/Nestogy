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
            'type' => null,
            'color' => null,
            'icon' => null
        ];
    }
}
