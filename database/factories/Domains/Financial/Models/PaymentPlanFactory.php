<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentPlanFactory extends Factory
{
    protected $model = PaymentPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'plan_number' => $this->faker->unique()->numerify('PP-######'),
            'plan_type' => 'custom',
            'status' => 'active',
            'original_amount' => $this->faker->randomFloat(2, 100, 10000),
            'plan_amount' => $this->faker->randomFloat(2, 100, 10000),
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
