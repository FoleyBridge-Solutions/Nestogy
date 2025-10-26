<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\TaxApiSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiSettingsFactory extends Factory
{
    protected $model = TaxApiSettings::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'provider' => $this->faker->randomElement(['taxcloud', 'vat_comply', 'fcc', 'nominatim', 'census']),
            'enabled' => $this->faker->boolean(70),
            'credentials' => ['api_key' => $this->faker->uuid],
            'configuration' => [],
            'monthly_api_calls' => $this->faker->numberBetween(0, 10000),
            'monthly_limit' => $this->faker->optional()->numberBetween(10000, 100000),
            'last_api_call' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'monthly_cost' => $this->faker->randomFloat(2, 0, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'error', 'quota_exceeded']),
            'last_error' => $this->faker->optional()->sentence,
            'last_health_check' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'health_data' => [],
            'audit_log' => []
        ];
    }
}
