<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\RefundRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundRequestFactory extends Factory
{
    protected $model = RefundRequest::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'requested_by' => \App\Domains\Core\Models\User::factory(),
            'request_number' => $this->faker->unique()->numerify('RR-######'),
            'refund_type' => 'credit_refund',
            'refund_method' => 'bank_transfer',
            'status' => 'pending',
            'requested_amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
