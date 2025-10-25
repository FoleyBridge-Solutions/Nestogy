<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\TaxCategory;
use App\Domains\Tax\Models\TaxJurisdiction;
use App\Domains\Tax\Models\VoIPTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoIPTaxRateFactory extends Factory
{
    protected $model = VoIPTaxRate::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'tax_jurisdiction_id' => TaxJurisdiction::factory(),
            'tax_category_id' => null, // TaxCategoryFactory does not exist
            'tax_name' => $this->faker->words(3, true),
            'rate_type' => $this->faker->randomElement(['percentage', 'fixed_amount', 'per_line', 'tiered']),
            'percentage_rate' => $this->faker->randomFloat(2, 0, 15),
            'fixed_amount' => $this->faker->randomFloat(2, 0.50, 5.00),
            'effective_date' => now(),
            'service_types' => $this->faker->randomElements(['local', 'long_distance', 'international', 'voip_fixed', 'voip_nomadic'], 3),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'last_updated' => now(),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => 'percentage',
            'percentage_rate' => $this->faker->randomFloat(2, 1, 10),
            'fixed_amount' => null,
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => 'fixed_amount',
            'percentage_rate' => null,
            'fixed_amount' => $this->faker->randomFloat(2, 0.50, 5.00),
        ]);
    }

    public function federal(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_name' => 'Federal Excise Tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 3.00,
            'conditions' => ['min_amount' => 0.20],
        ]);
    }

    public function usf(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_name' => 'USF Contribution',
            'rate_type' => 'percentage',
            'percentage_rate' => 33.4,
        ]);
    }
}
