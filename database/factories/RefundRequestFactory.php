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
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'requested_by' => \App\Models\User::factory(),
            'requested_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
