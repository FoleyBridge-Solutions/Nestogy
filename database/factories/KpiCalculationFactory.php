<?php

namespace Database\Factories;

use App\Domains\Core\Models\KpiCalculation;
use Illuminate\Database\Eloquent\Factories\Factory;

class KpiCalculationFactory extends Factory
{
    protected $model = KpiCalculation::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
