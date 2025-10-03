<?php

namespace Database\Factories;

use App\Models\ServiceTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTaxRateFactory extends Factory
{
    protected $model = ServiceTaxRate::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'service_type' => null,
            'tax_type' => null,
            'tax_name' => $this->faker->words(3, true),
            'authority_name' => $this->faker->words(3, true),
            'tax_code' => null,
            'description' => $this->faker->sentence,
            'regulatory_code' => null,
            'rate_type' => null,
            'percentage_rate' => null,
            'fixed_amount' => null,
            'minimum_threshold' => null,
            'maximum_amount' => null,
            'calculation_method' => null,
            'service_types' => null,
            'conditions' => null,
            'is_active' => true,
            'is_recoverable' => true,
            'is_compound' => true,
            'priority' => null,
            'effective_date' => null,
            'expiry_date' => null,
            'source' => null,
            'last_updated_from_source' => null,
            'metadata' => null
        ];
    }
}
