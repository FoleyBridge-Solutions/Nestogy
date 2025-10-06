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
            'applicable_days' => $this->faker->optional()->passthrough(json_encode($this->faker->randomElements([0, 1, 2, 3, 4, 5, 6], $this->faker->numberBetween(1, 7)))),
            'applicable_hours' => $this->faker->optional()->passthrough(json_encode($this->faker->randomElements(range(0, 23), $this->faker->numberBetween(1, 24)))),
            'is_promotional' => $this->faker->boolean(70),
            'promo_code' => $this->faker->word,
            'conditions' => $this->faker->optional()->passthrough(json_encode(['min_total' => $this->faker->randomFloat(2, 0, 1000)])),
            'priority' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(70),
            'is_combinable' => $this->faker->boolean(70),
            'max_uses' => $this->faker->optional()->numberBetween(1, 10000),
            'uses_count' => $this->faker->numberBetween(0, 100),
            'max_uses_per_client' => $this->faker->optional()->numberBetween(1, 1000),
            'requires_approval' => $this->faker->boolean(),
            'approval_threshold' => $this->faker->optional()->randomFloat(2, 0, 99999)
        ];
    }
}
