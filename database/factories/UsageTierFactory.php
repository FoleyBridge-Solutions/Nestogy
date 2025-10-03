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
            'tier_code' => null,
            'description' => $this->faker->sentence,
            'tier_order' => null,
            'is_active' => true,
            'usage_type' => null,
            'service_type' => null,
            'applicable_services' => null,
            'min_usage' => null,
            'max_usage' => null,
            'usage_unit' => null,
            'is_unlimited_tier' => true,
            'pricing_model' => null,
            'base_rate' => null,
            'per_unit_rate' => null,
            'block_size' => null,
            'block_rate' => null,
            'setup_fee' => null,
            'has_peak_pricing' => null,
            'peak_rate_multiplier' => null,
            'off_peak_rate_multiplier' => null,
            'weekend_rate_multiplier' => null,
            'peak_hours' => null,
            'time_zone_rules' => null,
            'has_geographic_pricing' => null,
            'geographic_rates' => null,
            'destination_rates' => null,
            'has_volume_discounts' => null,
            'volume_discount_rules' => null,
            'commitment_discount' => null,
            'loyalty_discount' => null,
            'overage_handling' => null,
            'overage_rate' => null,
            'allows_rollover' => null,
            'rollover_months' => null,
            'rollover_percentage' => null,
            'billing_frequency' => null,
            'is_prorated' => true,
            'proration_method' => null,
            'requires_advance_payment' => null,
            'advance_payment_days' => null,
            'tier_conditions' => null,
            'bundling_rules' => null,
            'exclusion_rules' => null,
            'is_taxable' => true,
            'tax_category_mapping' => null,
            'regulatory_compliance' => null,
            'effective_date' => null,
            'expiry_date' => null,
            'is_promotional' => true,
            'promotion_code' => null,
            'reporting_categories' => null,
            'track_detailed_usage' => null,
            'kpi_targets' => null,
            'billing_system_code' => null,
            'integration_metadata' => null,
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => null,
            'tier_history' => null
        ];
    }
}
