<?php

namespace Database\Factories;

use App\Models\TaxCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxCategoryFactory extends Factory
{
    protected $model = TaxCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
