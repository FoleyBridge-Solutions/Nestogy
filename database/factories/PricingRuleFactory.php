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
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'pricing_model' => $this->faker->optional()->word,
            'discount_type' => $this->faker->numberBetween(1, 5),
            'discount_value' => $this->faker->optional()->word,
            'price_override' => $this->faker->randomFloat(2, 0, 10000),
            'min_quantity' => $this->faker->optional()->word,
            'max_quantity' => $this->faker->optional()->word,
            'quantity_increment' => $this->faker->optional()->word,
            'valid_from' => $this->faker->optional()->word,
            'valid_until' => $this->faker->optional()->word,
            'applicable_days' => $this->faker->optional()->word,
            'applicable_hours' => $this->faker->optional()->word,
            'is_promotional' => $this->faker->boolean(70),
            'promo_code' => $this->faker->word,
            'conditions' => $this->faker->optional()->word,
            'priority' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'is_combinable' => $this->faker->boolean(70),
            'max_uses' => $this->faker->optional()->word,
            'uses_count' => $this->faker->optional()->word,
            'max_uses_per_client' => $this->faker->optional()->word,
            'requires_approval' => $this->faker->optional()->word,
            'approval_threshold' => $this->faker->optional()->word
        ];
    }
}
