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
            'configuration' => null,
            'metadata' => null,
            'is_active' => true,
            'version' => null,
            'description' => $this->faker->sentence,
            'activated_at' => null,
            'created_by' => null,
            'updated_by' => null
        ];
    }
}
