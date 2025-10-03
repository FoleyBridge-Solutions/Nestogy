<?php

namespace Database\Factories;

use App\Models\CashFlowProjection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashFlowProjectionFactory extends Factory
{
    protected $model = CashFlowProjection::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
