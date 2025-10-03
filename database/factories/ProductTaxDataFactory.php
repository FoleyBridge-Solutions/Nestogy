<?php

namespace Database\Factories;

use App\Models\ProductTaxData;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTaxDataFactory extends Factory
{
    protected $model = ProductTaxData::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'tax_data' => $this->faker->optional()->word,
            'calculated_taxes' => $this->faker->optional()->word,
            'effective_tax_rate' => $this->faker->optional()->word,
            'total_tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'last_calculated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
