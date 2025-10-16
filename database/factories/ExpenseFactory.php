<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Financial\Models\Expense>
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
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'category_id' => \App\Domains\Financial\Models\Category::factory()->expenseCategory(),
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'expense_date' => fake()->dateTimeThisYear(),
            'receipt_path' => fake()->optional()->filePath(),
            'notes' => fake()->optional()->sentence(),
            'is_billable' => fake()->boolean(30),
            'client_id' => null,
            'status' => 'pending',
        ];
    }
}
