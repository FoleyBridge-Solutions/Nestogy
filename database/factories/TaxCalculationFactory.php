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
            'calculable_type' => $this->faker->numberBetween(1, 5),
            'engine_type' => $this->faker->numberBetween(1, 5),
            'category_type' => $this->faker->numberBetween(1, 5),
            'calculation_type' => $this->faker->numberBetween(1, 5),
            'base_amount' => $this->faker->randomFloat(2, 0, 10000),
            'quantity' => $this->faker->optional()->word,
            'input_parameters' => $this->faker->optional()->word,
            'customer_data' => $this->faker->optional()->word,
            'service_address' => $this->faker->optional()->word,
            'total_tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'final_amount' => $this->faker->randomFloat(2, 0, 10000),
            'effective_tax_rate' => $this->faker->optional()->word,
            'tax_breakdown' => $this->faker->optional()->word,
            'api_enhancements' => $this->faker->optional()->word,
            'jurisdictions' => $this->faker->optional()->word,
            'exemptions_applied' => $this->faker->optional()->word,
            'engine_metadata' => $this->faker->optional()->word,
            'api_calls_made' => $this->faker->optional()->word,
            'validated' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'validated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'validated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'validation_notes' => $this->faker->optional()->sentence,
            'status' => 'active',
            'status_history' => 'active',
            'created_by' => $this->faker->optional()->word,
            'updated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'change_log' => $this->faker->optional()->word,
            'calculation_time_ms' => $this->faker->optional()->word,
            'api_calls_count' => $this->faker->optional()->word,
            'api_cost' => $this->faker->optional()->word
        ];
    }
}
