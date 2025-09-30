<?php

namespace Database\Factories;

use App\Models\TaxJurisdiction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxJurisdictionFactory extends Factory
{
    protected $model = TaxJurisdiction::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->city,
            'jurisdiction_type' => $this->faker->randomElement(['federal', 'state', 'county', 'city']),
            'authority_name' => $this->faker->company,
            'tax_id_required' => $this->faker->boolean,
            'status' => 'active',
            'boundary_data' => [
                'zip_codes' => $this->faker->randomElements(['90210', '90211', '90212'], 2),
                'fips_codes' => [$this->faker->numerify('####')],
            ],
        ];
    }

    public function federal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Federal',
            'jurisdiction_type' => 'federal',
            'authority_name' => 'Internal Revenue Service',
        ]);
    }

    public function state(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->state,
            'jurisdiction_type' => 'state',
            'authority_name' => $attributes['name'].' Department of Revenue',
        ]);
    }

    public function county(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->city.' County',
            'jurisdiction_type' => 'county',
            'authority_name' => $attributes['name'].' Tax Assessor',
        ]);
    }
}
