<?php

namespace Database\Factories;

use App\Models\TaxCalculation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxCalculationFactory extends Factory
{
    protected $model = TaxCalculation::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'calculable_type' => 'App\\Models\\Invoice',
            'calculable_id' => 1,
            'calculation_id' => $this->faker->uuid,
            'engine_type' => $this->faker->randomElement(['avalara', 'taxjar', 'manual']),
            'category_type' => $this->faker->optional()->word,
            'calculation_type' => $this->faker->randomElement(['quote', 'invoice', 'preview', 'adjustment']),
            'base_amount' => $this->faker->randomFloat(2, 0, 10000),
            'quantity' => $this->faker->numberBetween(1, 100),
            'input_parameters' => json_encode([]),
            'customer_data' => $this->faker->optional()->randomNumber(),
            'service_address' => $this->faker->optional()->randomNumber(),
            'total_tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'final_amount' => $this->faker->randomFloat(2, 0, 10000),
            'effective_tax_rate' => $this->faker->randomFloat(6, 0, 1),
            'tax_breakdown' => json_encode([]),
            'api_enhancements' => $this->faker->optional()->randomNumber(),
            'jurisdictions' => $this->faker->optional()->randomNumber(),
            'exemptions_applied' => $this->faker->optional()->randomNumber(),
            'engine_metadata' => json_encode([]),
            'api_calls_made' => $this->faker->optional()->randomNumber(),
            'validated' => $this->faker->boolean,
            'validated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'validated_by' => null,
            'validation_notes' => $this->faker->optional()->sentence,
            'status' => $this->faker->randomElement(['draft', 'calculated', 'applied', 'adjusted', 'voided']),
            'status_history' => 'active',
            'created_by' => \App\Models\User::factory(),
            'updated_by' => null,
            'change_log' => $this->faker->optional()->randomNumber(),
            'calculation_time_ms' => $this->faker->optional()->randomNumber(),
            'api_calls_count' => $this->faker->numberBetween(0, 100),
            'api_cost' => $this->faker->randomFloat(2, 0, 10)
        ];
    }
}
