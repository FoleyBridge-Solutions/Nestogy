<?php

namespace Database\Factories;

use App\Models\QuoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteApprovalFactory extends Factory
{
    protected $model = QuoteApproval::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'quote_id' => \App\Models\Quote::factory(),
            'user_id' => \App\Models\User::factory(),
            'approval_level' => $this->faker->randomElement(['manager', 'executive', 'finance']),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'comments' => $this->faker->optional()->randomNumber(),
            'approved_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'rejected_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
