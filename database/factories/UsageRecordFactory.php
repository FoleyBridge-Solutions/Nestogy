<?php

namespace Database\Factories;

use App\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'usage_type' => $this->faker->numberBetween(1, 5),
            'service_type' => $this->faker->numberBetween(1, 5),
            'usage_category' => $this->faker->optional()->word,
            'billing_category' => $this->faker->optional()->word,
            'quantity' => $this->faker->optional()->word,
            'unit_type' => $this->faker->numberBetween(1, 5),
            'duration_seconds' => $this->faker->optional()->word,
            'line_count' => $this->faker->optional()->word,
            'data_volume_mb' => $this->faker->optional()->word,
            'origination_number' => $this->faker->optional()->word,
            'destination_number' => $this->faker->optional()->word,
            'origination_country' => $this->faker->optional()->word,
            'destination_country' => $this->faker->optional()->word,
            'origination_state' => $this->faker->optional()->word,
            'destination_state' => $this->faker->optional()->word,
            'route_type' => $this->faker->numberBetween(1, 5),
            'carrier_name' => $this->faker->words(3, true),
            'usage_start_time' => $this->faker->optional()->word,
            'usage_end_time' => $this->faker->optional()->word,
            'time_zone' => $this->faker->optional()->word,
            'is_peak_time' => $this->faker->boolean(70),
            'is_weekend' => $this->faker->boolean(70),
            'unit_rate' => $this->faker->optional()->word,
            'base_cost' => $this->faker->optional()->word,
            'markup_amount' => $this->faker->randomFloat(2, 0, 10000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'total_cost' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'call_quality' => $this->faker->optional()->word,
            'completion_status' => 'active',
            'status_reason' => 'active',
            'quality_score' => $this->faker->optional()->word,
            'processing_status' => 'active',
            'is_billable' => $this->faker->boolean(70),
            'is_validated' => $this->faker->boolean(70),
            'is_disputed' => $this->faker->boolean(70),
            'is_fraud_flagged' => $this->faker->boolean(70),
            'validation_notes' => $this->faker->optional()->sentence,
            'is_pooled_usage' => $this->faker->boolean(70),
            'allocated_from_pool' => $this->faker->optional()->word,
            'overage_amount' => $this->faker->randomFloat(2, 0, 10000),
            'usage_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'usage_hour' => $this->faker->optional()->word,
            'billing_period' => $this->faker->optional()->word,
            'is_aggregated' => $this->faker->boolean(70),
            'protocol' => $this->faker->optional()->word,
            'codec' => $this->faker->word,
            'technical_metadata' => $this->faker->optional()->word,
            'custom_attributes' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'cdr_source' => $this->faker->optional()->word,
            'cdr_received_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'processing_version' => $this->faker->optional()->word,
            'raw_cdr_data' => $this->faker->optional()->word,
            'regulatory_classification' => $this->faker->optional()->word,
            'requires_audit' => $this->faker->optional()->word,
            'compliance_notes' => $this->faker->optional()->sentence
        ];
    }
}
