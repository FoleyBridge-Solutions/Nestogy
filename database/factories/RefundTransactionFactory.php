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
            'company_id' => 1,
        ];
    }
}
