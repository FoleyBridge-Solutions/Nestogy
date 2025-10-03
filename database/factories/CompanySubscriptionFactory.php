<?php

namespace Database\Factories;

use App\Models\CompanySubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanySubscriptionFactory extends Factory
{
    protected $model = CompanySubscription::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'status' => 'active',
            'max_users' => $this->faker->optional()->word,
            'current_user_count' => $this->faker->optional()->word,
            'monthly_amount' => $this->faker->randomFloat(2, 0, 10000),
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'current_period_start' => $this->faker->optional()->word,
            'current_period_end' => $this->faker->optional()->word,
            'canceled_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'suspended_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'grace_period_ends_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'features' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word
        ];
    }
}
