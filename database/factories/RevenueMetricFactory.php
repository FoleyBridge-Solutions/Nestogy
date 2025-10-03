<?php

namespace Database\Factories;

use App\Models\RevenueMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class RevenueMetricFactory extends Factory
{
    protected $model = RevenueMetric::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
