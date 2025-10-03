<?php

namespace Database\Factories;

use App\Models\FinancialReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialReportFactory extends Factory
{
    protected $model = FinancialReport::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
