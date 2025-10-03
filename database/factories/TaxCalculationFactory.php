<?php

namespace Database\Factories;

use App\Models\TaxCalculation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxCalculationFactory extends Factory
{
    protected $model = TaxCalculation::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'calculable_type' => null,
            'engine_type' => null,
            'category_type' => null,
            'calculation_type' => null,
            'base_amount' => null,
            'quantity' => null,
            'input_parameters' => null,
            'customer_data' => null,
            'service_address' => null,
            'total_tax_amount' => null,
            'final_amount' => null,
            'effective_tax_rate' => null,
            'tax_breakdown' => null,
            'api_enhancements' => null,
            'jurisdictions' => null,
            'exemptions_applied' => null,
            'engine_metadata' => null,
            'api_calls_made' => null,
            'validated' => null,
            'validated_at' => null,
            'validated_by' => null,
            'validation_notes' => null,
            'status' => 'active',
            'status_history' => 'active',
            'created_by' => null,
            'updated_by' => null,
            'change_log' => null,
            'calculation_time_ms' => null,
            'api_calls_count' => null,
            'api_cost' => null
        ];
    }
}
