<?php

namespace Database\Factories;

use App\Models\AutoPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutoPaymentFactory extends Factory
{
    protected $model = AutoPayment::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'payment_method_id' => \App\Models\PaymentMethod::factory(),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
        ];
    }
}
