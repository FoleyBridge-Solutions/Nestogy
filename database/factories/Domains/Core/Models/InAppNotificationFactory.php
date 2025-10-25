<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\InAppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class InAppNotificationFactory extends Factory
{
    protected $model = InAppNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'type' => $this->faker->randomElement(['info', 'warning', 'success', 'error']),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->sentence(),
            'link' => $this->faker->optional()->url(),
            'icon' => $this->faker->optional()->randomElement(['fas fa-check', 'fas fa-exclamation', 'fas fa-times']),
            'color' => $this->faker->randomElement(['blue', 'green', 'red', 'yellow']),
        ];
    }
}
