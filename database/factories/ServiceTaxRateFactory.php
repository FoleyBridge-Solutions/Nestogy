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
            'service_type' => $this->faker->numberBetween(1, 5),
            'tax_type' => $this->faker->numberBetween(1, 5),
            'tax_name' => $this->faker->words(3, true),
            'authority_name' => $this->faker->words(3, true),
            'tax_code' => $this->faker->word,
            'description' => $this->faker->optional()->sentence,
            'regulatory_code' => $this->faker->word,
            'rate_type' => $this->faker->numberBetween(1, 5),
            'percentage_rate' => $this->faker->optional()->word,
            'fixed_amount' => $this->faker->randomFloat(2, 0, 10000),
            'minimum_threshold' => $this->faker->optional()->word,
            'maximum_amount' => $this->faker->randomFloat(2, 0, 10000),
            'calculation_method' => $this->faker->optional()->word,
            'service_types' => $this->faker->numberBetween(1, 5),
            'conditions' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'is_recoverable' => $this->faker->boolean(70),
            'is_compound' => $this->faker->boolean(70),
            'priority' => $this->faker->optional()->word,
            'effective_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'source' => $this->faker->optional()->word,
            'last_updated_from_source' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'metadata' => $this->faker->optional()->word
        ];
    }
}
