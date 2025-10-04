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
            'company_id' => \App\Models\Company::factory(),
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'icon' => $this->faker->randomElement(['bolt', 'plus', 'pencil', 'trash', 'eye']),
            'color' => $this->faker->randomElement(['blue', 'green', 'red', 'yellow', 'purple', 'gray']),
            'type' => $this->faker->randomElement(['route', 'url']),
            'target' => 'model',
            'parameters' => json_encode([]),
            'open_in' => 'same_tab',
            'visibility' => $this->faker->randomElement(['private', 'role', 'company']),
            'allowed_roles' => json_encode([]),
            'permission' => $this->faker->optional()->word,
            'position' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(80),
            'usage_count' => $this->faker->numberBetween(0, 100),
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
