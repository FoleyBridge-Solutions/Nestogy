<?php

namespace Database\Factories;

use App\Models\InAppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class InAppNotificationFactory extends Factory
{
    protected $model = InAppNotification::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['info', 'warning', 'success', 'error']),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->sentence(),
        ];
    }
}
