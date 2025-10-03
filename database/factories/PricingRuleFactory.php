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
            'pricing_model' => null,
            'discount_type' => null,
            'discount_value' => null,
            'price_override' => null,
            'min_quantity' => null,
            'max_quantity' => null,
            'quantity_increment' => null,
            'valid_from' => null,
            'valid_until' => null,
            'applicable_days' => null,
            'applicable_hours' => null,
            'is_promotional' => true,
            'promo_code' => null,
            'conditions' => null,
            'priority' => null,
            'is_active' => true,
            'is_combinable' => true,
            'max_uses' => null,
            'uses_count' => null,
            'max_uses_per_client' => null,
            'requires_approval' => null,
            'approval_threshold' => null
        ];
    }
}
