<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\RefundTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundTransactionFactory extends Factory
{
    protected $model = RefundTransaction::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'refund_request_id' => \App\Domains\Financial\Models\RefundRequest::factory(),
            'processed_by' => \App\Domains\Core\Models\User::factory(),
            'status' => 'completed',
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency_code' => 'USD',
        ];
    }
}
