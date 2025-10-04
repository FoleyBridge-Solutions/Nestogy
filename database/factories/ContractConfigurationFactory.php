<?php

namespace Database\Factories;

use App\Models\ContractConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractConfigurationFactory extends Factory
{
    protected $model = ContractConfiguration::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'configuration' => $this->faker->optional()->randomNumber(),
            'metadata' => json_encode([]),
            'is_active' => $this->faker->boolean(70),
            'version' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->optional()->sentence,
            'activated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'created_by' => \App\Models\User::factory(),
            'updated_by' => null
        ];
    }
}
