<?php

namespace Database\Factories;

use App\Domains\Financial\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentPlanFactory extends Factory
{
    protected $model = PaymentPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
