<?php

namespace Database\Factories;

use App\Models\CustomQuickAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomQuickActionFactory extends Factory
{
    protected $model = CustomQuickAction::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'icon' => $this->faker->optional()->word,
            'color' => $this->faker->optional()->word,
            'type' => $this->faker->numberBetween(1, 5),
            'target' => $this->faker->optional()->word,
            'parameters' => $this->faker->optional()->word,
            'open_in' => $this->faker->optional()->word,
            'visibility' => $this->faker->optional()->word,
            'allowed_roles' => $this->faker->optional()->word,
            'permission' => $this->faker->optional()->word,
            'position' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'usage_count' => $this->faker->optional()->word,
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
