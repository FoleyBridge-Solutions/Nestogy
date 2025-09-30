<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency_code' => 'USD',
            'date' => fake()->dateTimeThisYear(),
            'reference' => fake()->optional()->bothify('EXP-####'),
            'payment_method' => fake()->randomElement(['cash', 'check', 'credit_card', 'bank_transfer']),
        ];
    }
}
