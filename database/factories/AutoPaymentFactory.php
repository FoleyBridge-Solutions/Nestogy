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
            'company_id' => 1,
        ];
    }
}
