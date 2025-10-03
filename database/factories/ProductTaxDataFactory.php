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
            'tax_data' => null,
            'calculated_taxes' => null,
            'effective_tax_rate' => null,
            'total_tax_amount' => null,
            'last_calculated_at' => null
        ];
    }
}
