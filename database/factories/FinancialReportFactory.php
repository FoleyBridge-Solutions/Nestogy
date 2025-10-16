<?php

namespace Database\Factories;

use App\Domains\Financial\Models\FinancialReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialReportFactory extends Factory
{
    protected $model = FinancialReport::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
