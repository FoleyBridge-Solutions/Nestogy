<?php

namespace Database\Factories;

use App\Models\KpiCalculation;
use Illuminate\Database\Eloquent\Factories\Factory;

class KpiCalculationFactory extends Factory
{
    protected $model = KpiCalculation::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
