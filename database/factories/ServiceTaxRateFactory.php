<?php

namespace Database\Factories;

use App\Models\ServiceTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTaxRateFactory extends Factory
{
    protected $model = ServiceTaxRate::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'tax_jurisdiction_id' => \App\Models\TaxJurisdiction::factory(),
            'service_type' => $this->faker->numberBetween(1, 5),
            'tax_type' => $this->faker->numberBetween(1, 5),
            'tax_name' => $this->faker->words(3, true),
            'authority_name' => $this->faker->words(3, true),
            'tax_code' => $this->faker->randomFloat(2, 0, 1000),
            'description' => $this->faker->optional()->sentence,
            'regulatory_code' => $this->faker->word,
            'rate_type' => $this->faker->randomElement(['percentage', 'fixed', 'tiered', 'per_line', 'per_minute', 'per_unit']),
            'percentage_rate' => $this->faker->optional()->randomFloat(4, 0, 99),
            'fixed_amount' => $this->faker->randomFloat(2, 0, 999.99),
            'minimum_threshold' => $this->faker->optional()->randomFloat(4, 0, 999.99),
            'maximum_amount' => $this->faker->randomFloat(2, 0, 999.99),
            'calculation_method' => $this->faker->randomElement(['standard', 'compound', 'inclusive', 'exclusive']),
            'service_types' => $this->faker->numberBetween(1, 5),
            'conditions' => $this->faker->optional()->randomNumber(),
            'is_active' => $this->faker->boolean(70),
            'is_recoverable' => $this->faker->boolean(70),
            'is_compound' => $this->faker->boolean(70),
            'priority' => $this->faker->numberBetween(1, 100),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'source' => $this->faker->optional()->randomNumber(),
            'last_updated_from_source' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'metadata' => json_encode([])
        ];
    }
}
