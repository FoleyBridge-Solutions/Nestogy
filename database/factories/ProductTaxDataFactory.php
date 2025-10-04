<?php

namespace Database\Factories;

use App\Models\ProductTaxData;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTaxDataFactory extends Factory
{
    protected $model = ProductTaxData::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'product_id' => \App\Models\Product::factory(),
            'tax_data' => json_encode([]),
            'calculated_taxes' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'effective_tax_rate' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'total_tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'last_calculated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
