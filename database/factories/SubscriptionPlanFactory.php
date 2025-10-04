<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['starter', 'professional', 'enterprise', 'custom']),
            'slug' => $this->faker->unique()->regexify('[a-z]{3,10}-[a-z]{3,10}'),
            'stripe_price_id' => 'price_'.$this->faker->unique()->uuid(),
            'price_monthly' => $this->faker->randomFloat(2, 29, 299),
            'user_limit' => $this->faker->optional(0.7)->numberBetween(1, 50),
            'description' => $this->faker->sentence(10),
            'features' => [
                'core_psa_features',
                $this->faker->randomElement(['basic_reporting', 'advanced_reporting']),
                $this->faker->randomElement(['email_support', 'priority_support', 'dedicated_support']),
                'api_access',
            ],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the subscription plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a starter plan.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => SubscriptionPlan::PLAN_STARTER,
            'stripe_price_id' => 'price_starter_monthly',
            'price_monthly' => 49.00,
            'user_limit' => 10,
            'description' => 'Perfect for small teams getting started with MSP management',
            'features' => [
                'core_psa_features',
                'basic_reporting',
                'email_support',
                'api_access',
            ],
            'sort_order' => 1,
            'advanced_reporting' => false,
            'custom_branding' => false,
            'priority_support' => false,
        ]);
    }

    /**
     * Create a professional plan.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => SubscriptionPlan::PLAN_PROFESSIONAL,
            'stripe_price_id' => 'price_professional_monthly',
            'price_monthly' => 79.00,
            'user_limit' => null,
            'description' => 'Advanced features for growing MSPs',
            'features' => [
                'core_psa_features',
                'advanced_reporting',
                'custom_branding',
                'priority_support',
                'api_access',
                'integrations',
                'automated_workflows',
            ],
            'sort_order' => 2,
            'advanced_reporting' => true,
            'custom_branding' => true,
            'priority_support' => true,
        ]);
    }

    /**
     * Create an enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => SubscriptionPlan::PLAN_ENTERPRISE,
            'stripe_price_id' => 'price_enterprise_monthly',
            'price_monthly' => 149.00,
            'user_limit' => null,
            'description' => 'Full-featured platform for enterprise MSPs',
            'features' => [
                'core_psa_features',
                'advanced_reporting',
                'custom_branding',
                'dedicated_support',
                'api_access',
                'integrations',
                'automated_workflows',
                'white_label',
                'sla_guarantees',
                'custom_features',
            ],
            'sort_order' => 3,
            'advanced_reporting' => true,
            'custom_branding' => true,
            'priority_support' => true,
        ]);
    }

    /**
     * Set unlimited users for the plan.
     */
    public function unlimitedUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_limit' => null,
        ]);
    }

    /**
     * Set a specific price for the plan.
     */
    public function price(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_monthly' => $price,
        ]);
    }
}
