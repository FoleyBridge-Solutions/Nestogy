<?php

namespace Database\Factories;

use App\Models\Recurring;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringFactory extends Factory
{
    protected $model = Recurring::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'prefix' => $this->faker->optional()->word,
            'number' => $this->faker->numberBetween(1, 100),
            'scope' => $this->faker->optional()->word,
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'last_sent' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'next_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->boolean(),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->sentence,
            'overage_rates' => null,
            'auto_invoice_generation' => $this->faker->boolean(),
            'invoice_terms_days' => $this->faker->numberBetween(0, 90),
            'email_invoice' => $this->faker->boolean(),
            'email_template' => $this->faker->safeEmail,
            'category_id' => \App\Models\Category::factory(),
            'client_id' => \App\Models\Client::factory()
        ];
    }
}
