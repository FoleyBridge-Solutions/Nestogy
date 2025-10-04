<?php

namespace Database\Factories;

use App\Models\QuickActionFavorite;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuickActionFavoriteFactory extends Factory
{
    protected $model = QuickActionFavorite::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'system_action' => $this->faker->optional()->word,
            'position' => $this->faker->numberBetween(1, 100)
        ];
    }
}
