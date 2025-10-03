<?php

namespace Database\Factories;

use App\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentPlanFactory extends Factory
{
    protected $model = PaymentPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
