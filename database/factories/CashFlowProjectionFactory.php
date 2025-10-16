<?php

namespace Database\Factories;

use App\Domains\Financial\Models\CashFlowProjection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashFlowProjectionFactory extends Factory
{
    protected $model = CashFlowProjection::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
