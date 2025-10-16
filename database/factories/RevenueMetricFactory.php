<?php

namespace Database\Factories;

use App\Domains\Financial\Models\RevenueMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class RevenueMetricFactory extends Factory
{
    protected $model = RevenueMetric::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
