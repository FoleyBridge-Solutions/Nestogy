<?php

namespace Database\Factories;

use App\Models\QuoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteApprovalFactory extends Factory
{
    protected $model = QuoteApproval::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'approval_level' => $this->faker->optional()->word,
            'status' => 'active',
            'comments' => $this->faker->optional()->word,
            'approved_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'rejected_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
