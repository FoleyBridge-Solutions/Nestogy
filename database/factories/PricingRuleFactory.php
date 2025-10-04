<?php

namespace Database\Factories;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'product_id' => \App\Models\Product::factory(),
            'name' => $this->faker->words(3, true),
            'pricing_model' => $this->faker->randomElement(['fixed', 'tiered', 'volume', 'usage', 'package', 'custom']),
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed', 'override']),
            'discount_value' => $this->faker->optional()->numberBetween(1, 100),
            'price_override' => $this->faker->randomFloat(2, 0, 10000),
            'min_quantity' => $this->faker->optional()->numberBetween(1, 100),
            'max_quantity' => $this->faker->optional()->numberBetween(1, 100),
            'quantity_increment' => $this->faker->numberBetween(1, 100),
            'valid_from' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'applicable_days' => $this->faker->optional()->randomNumber(),
            'applicable_hours' => $this->faker->optional()->randomNumber(),
            'is_promotional' => $this->faker->boolean(70),
            'promo_code' => $this->faker->word,
            'conditions' => $this->faker->optional()->randomNumber(),
            'priority' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(70),
            'is_combinable' => $this->faker->boolean(70),
            'max_uses' => $this->faker->optional()->randomNumber(),
            'uses_count' => $this->faker->numberBetween(0, 100),
            'max_uses_per_client' => $this->faker->optional()->randomNumber(),
            'requires_approval' => $this->faker->boolean(),
            'approval_threshold' => $this->faker->optional()->randomNumber()
        ];
    }
}
