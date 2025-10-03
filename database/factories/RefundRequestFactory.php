<?php

namespace Database\Factories;

use App\Models\RefundRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundRequestFactory extends Factory
{
    protected $model = RefundRequest::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
