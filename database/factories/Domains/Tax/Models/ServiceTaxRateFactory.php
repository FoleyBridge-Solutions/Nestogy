<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\ServiceTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTaxRateFactory extends Factory
{
    protected $model = ServiceTaxRate::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'tax_jurisdiction_id' => 1,
            'tax_category_id' => 1,
            'service_type' => $this->faker->randomElement(['voip', 'telecom', 'cloud', 'saas', 'hosting', 'managed_services', 'professional', 'equipment']),
            'tax_type' => $this->faker->randomElement(['federal', 'state', 'local', 'regulatory', 'sales']),
            'tax_name' => $this->faker->words(3, true),
            'authority_name' => $this->faker->words(3, true),
            'tax_code' => $this->faker->optional()->bothify('TAX-####'),
            'description' => $this->faker->optional()->sentence,
            'regulatory_code' => $this->faker->optional()->randomElement(['e911', 'usf', 'access_recovery']),
            'rate_type' => $this->faker->randomElement(['percentage', 'fixed', 'tiered', 'per_line', 'per_minute', 'per_unit']),
            'percentage_rate' => $this->faker->optional()->randomFloat(4, 0, 25),
            'fixed_amount' => $this->faker->optional()->randomFloat(2, 0, 999.99),
            'minimum_threshold' => $this->faker->optional()->randomFloat(2, 0, 999.99),
            'maximum_amount' => $this->faker->optional()->randomFloat(2, 0, 999.99),
            'calculation_method' => $this->faker->randomElement(['standard', 'compound', 'additive', 'inclusive', 'exclusive']),
            'service_types' => json_encode([]),
            'conditions' => json_encode([]),
            'is_active' => $this->faker->boolean(70),
            'is_recoverable' => $this->faker->boolean(30),
            'is_compound' => $this->faker->boolean(30),
            'priority' => $this->faker->numberBetween(0, 100),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
            'external_id' => $this->faker->optional()->uuid,
            'source' => $this->faker->optional()->randomElement(['manual', 'api', 'import']),
            'last_updated_from_source' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'metadata' => json_encode([])
        ];
    }
}
