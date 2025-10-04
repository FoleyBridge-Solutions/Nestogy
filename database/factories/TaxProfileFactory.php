<?php

namespace Database\Factories;

use App\Models\TaxProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxProfileFactory extends Factory
{
    protected $model = TaxProfile::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'profile_type' => $this->faker->numberBetween(1, 5),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'required_fields' => $this->faker->boolean(),
            'tax_types' => $this->faker->numberBetween(1, 5),
            'calculation_engine' => 'simple',
            'field_definitions' => $this->faker->optional()->randomNumber(),
            'validation_rules' => $this->faker->optional()->numberBetween(1, 100),
            'default_values' => $this->faker->optional()->randomNumber(),
            'is_active' => $this->faker->boolean(70),
            'priority' => $this->faker->numberBetween(1, 100),
            'metadata' => json_encode([])
        ];
    }
}
