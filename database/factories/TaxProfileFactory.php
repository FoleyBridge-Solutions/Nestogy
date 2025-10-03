<?php

namespace Database\Factories;

use App\Models\TaxProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxProfileFactory extends Factory
{
    protected $model = TaxProfile::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'profile_type' => $this->faker->numberBetween(1, 5),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'required_fields' => $this->faker->optional()->word,
            'tax_types' => $this->faker->numberBetween(1, 5),
            'calculation_engine' => $this->faker->optional()->word,
            'field_definitions' => $this->faker->optional()->word,
            'validation_rules' => $this->faker->optional()->word,
            'default_values' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'priority' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word
        ];
    }
}
