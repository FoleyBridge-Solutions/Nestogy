<?php

namespace Database\Factories;

use App\Models\ContractConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractConfigurationFactory extends Factory
{
    protected $model = ContractConfiguration::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'configuration' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'version' => $this->faker->optional()->word,
            'description' => $this->faker->optional()->sentence,
            'activated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'created_by' => $this->faker->optional()->word,
            'updated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
