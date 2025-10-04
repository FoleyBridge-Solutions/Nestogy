<?php

namespace Database\Factories;

use App\Models\RefundTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundTransactionFactory extends Factory
{
    protected $model = RefundTransaction::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'transaction_id' => $this->faker->unique()->regexify('[A-Z0-9]{20}'),
            'initiated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'max_retries' => 3,
            'processed_by' => null,
        ];
    }
}
