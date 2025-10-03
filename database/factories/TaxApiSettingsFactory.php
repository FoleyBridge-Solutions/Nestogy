<?php

namespace Database\Factories;

use App\Models\TaxApiSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiSettingsFactory extends Factory
{
    protected $model = TaxApiSettings::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'provider' => $this->faker->optional()->word,
            'enabled' => $this->faker->boolean(70),
            'credentials' => $this->faker->optional()->word,
            'configuration' => $this->faker->optional()->word,
            'monthly_api_calls' => $this->faker->optional()->word,
            'monthly_limit' => $this->faker->optional()->word,
            'last_api_call' => $this->faker->optional()->word,
            'monthly_cost' => $this->faker->optional()->word,
            'status' => 'active',
            'last_error' => $this->faker->optional()->word,
            'last_health_check' => $this->faker->optional()->word,
            'health_data' => $this->faker->optional()->word,
            'audit_log' => $this->faker->optional()->word
        ];
    }
}
