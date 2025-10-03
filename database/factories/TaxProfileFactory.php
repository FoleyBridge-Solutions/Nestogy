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
            'profile_type' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'required_fields' => null,
            'tax_types' => null,
            'calculation_engine' => null,
            'field_definitions' => null,
            'validation_rules' => null,
            'default_values' => null,
            'is_active' => true,
            'priority' => null,
            'metadata' => null
        ];
    }
}
