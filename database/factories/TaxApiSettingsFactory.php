<?php

namespace Database\Factories;

use App\Models\TaxApiSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiSettingsFactory extends Factory
{
    protected $model = TaxApiSettings::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'provider' => $this->faker->randomElement(['avalara', 'taxjar', 'vertex']),
            'enabled' => $this->faker->boolean(70),
            'credentials' => $this->faker->optional()->randomNumber(),
            'configuration' => $this->faker->optional()->randomNumber(),
            'monthly_api_calls' => $this->faker->numberBetween(0, 10000),
            'monthly_limit' => $this->faker->optional()->randomNumber(),
            'last_api_call' => $this->faker->optional()->randomNumber(),
            'monthly_cost' => $this->faker->randomFloat(2, 0, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'error', 'quota_exceeded']),
            'last_error' => $this->faker->optional()->randomNumber(),
            'last_health_check' => $this->faker->optional()->randomNumber(),
            'health_data' => $this->faker->optional()->randomNumber(),
            'audit_log' => $this->faker->optional()->randomNumber()
        ];
    }
}
