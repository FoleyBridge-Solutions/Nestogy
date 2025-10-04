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
            'company_id' => \App\Models\Company::factory(),
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
            'status' => $this->faker->randomElement(['active', 'trialing', 'past_due', 'canceled', 'suspended']),
            'max_users' => $this->faker->numberBetween(1, 100),
            'current_user_count' => $this->faker->numberBetween(0, 100),
            'monthly_amount' => $this->faker->randomFloat(2, 0, 10000),
            'stripe_subscription_id' => $this->faker->optional()->regexify('sub_[A-Za-z0-9]{24}'),
            'stripe_customer_id' => $this->faker->optional()->regexify('cus_[A-Za-z0-9]{14}'),
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'current_period_start' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'current_period_end' => $this->faker->dateTimeBetween('now', '+30 days'),
            'canceled_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'suspended_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'grace_period_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'features' => json_encode([]),
            'metadata' => json_encode([]),
        ];
    }
}
