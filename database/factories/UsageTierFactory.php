<?php

namespace Database\Factories;

use App\Models\UsageTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageTierFactory extends Factory
{
    protected $model = UsageTier::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'tier_name' => $this->faker->words(3, true),
            'tier_code' => $this->faker->word,
            'description' => $this->faker->optional()->sentence,
            'tier_order' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'usage_type' => $this->faker->numberBetween(1, 5),
            'service_type' => $this->faker->numberBetween(1, 5),
            'applicable_services' => $this->faker->optional()->word,
            'min_usage' => $this->faker->optional()->word,
            'max_usage' => $this->faker->optional()->word,
            'usage_unit' => $this->faker->optional()->word,
            'is_unlimited_tier' => $this->faker->boolean(70),
            'pricing_model' => $this->faker->optional()->word,
            'base_rate' => $this->faker->optional()->word,
            'per_unit_rate' => $this->faker->optional()->word,
            'block_size' => $this->faker->optional()->word,
            'block_rate' => $this->faker->optional()->word,
            'setup_fee' => $this->faker->optional()->word,
            'has_peak_pricing' => $this->faker->optional()->word,
            'peak_rate_multiplier' => $this->faker->optional()->word,
            'off_peak_rate_multiplier' => $this->faker->optional()->word,
            'weekend_rate_multiplier' => $this->faker->optional()->word,
            'peak_hours' => $this->faker->optional()->word,
            'time_zone_rules' => $this->faker->optional()->word,
            'has_geographic_pricing' => $this->faker->optional()->word,
            'geographic_rates' => $this->faker->optional()->word,
            'destination_rates' => $this->faker->optional()->word,
            'has_volume_discounts' => $this->faker->optional()->word,
            'volume_discount_rules' => $this->faker->optional()->word,
            'commitment_discount' => $this->faker->optional()->word,
            'loyalty_discount' => $this->faker->optional()->word,
            'overage_handling' => $this->faker->optional()->word,
            'overage_rate' => $this->faker->optional()->word,
            'allows_rollover' => $this->faker->optional()->word,
            'rollover_months' => $this->faker->optional()->word,
            'rollover_percentage' => $this->faker->optional()->word,
            'billing_frequency' => $this->faker->optional()->word,
            'is_prorated' => $this->faker->boolean(70),
            'proration_method' => $this->faker->optional()->word,
            'requires_advance_payment' => $this->faker->optional()->word,
            'advance_payment_days' => $this->faker->optional()->word,
            'tier_conditions' => $this->faker->optional()->word,
            'bundling_rules' => $this->faker->optional()->word,
            'exclusion_rules' => $this->faker->optional()->word,
            'is_taxable' => $this->faker->boolean(70),
            'tax_category_mapping' => $this->faker->optional()->word,
            'regulatory_compliance' => $this->faker->optional()->word,
            'effective_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'is_promotional' => $this->faker->boolean(70),
            'promotion_code' => $this->faker->word,
            'reporting_categories' => $this->faker->optional()->word,
            'track_detailed_usage' => $this->faker->optional()->word,
            'kpi_targets' => $this->faker->optional()->word,
            'billing_system_code' => $this->faker->word,
            'integration_metadata' => $this->faker->optional()->word,
            'created_by' => $this->faker->optional()->word,
            'updated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'change_reason' => $this->faker->optional()->word,
            'tier_history' => $this->faker->optional()->word
        ];
    }
}
