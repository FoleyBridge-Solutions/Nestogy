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
            'usage_type' => null,
            'service_type' => null,
            'usage_category' => null,
            'billing_category' => null,
            'quantity' => null,
            'unit_type' => null,
            'duration_seconds' => null,
            'line_count' => null,
            'data_volume_mb' => null,
            'origination_number' => null,
            'destination_number' => null,
            'origination_country' => null,
            'destination_country' => null,
            'origination_state' => null,
            'destination_state' => null,
            'route_type' => null,
            'carrier_name' => $this->faker->words(3, true),
            'usage_start_time' => null,
            'usage_end_time' => null,
            'time_zone' => null,
            'is_peak_time' => true,
            'is_weekend' => true,
            'unit_rate' => null,
            'base_cost' => null,
            'markup_amount' => null,
            'discount_amount' => null,
            'tax_amount' => null,
            'total_cost' => null,
            'currency_code' => null,
            'call_quality' => null,
            'completion_status' => 'active',
            'status_reason' => 'active',
            'quality_score' => null,
            'processing_status' => 'active',
            'is_billable' => true,
            'is_validated' => true,
            'is_disputed' => true,
            'is_fraud_flagged' => true,
            'validation_notes' => null,
            'is_pooled_usage' => true,
            'allocated_from_pool' => null,
            'overage_amount' => null,
            'usage_date' => null,
            'usage_hour' => null,
            'billing_period' => null,
            'is_aggregated' => true,
            'protocol' => null,
            'codec' => null,
            'technical_metadata' => null,
            'custom_attributes' => null,
            'cdr_source' => null,
            'cdr_received_at' => null,
            'processed_at' => null,
            'processing_version' => null,
            'raw_cdr_data' => null,
            'regulatory_classification' => null,
            'requires_audit' => null,
            'compliance_notes' => null
        ];
    }
}
