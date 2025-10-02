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
        $company = \App\Models\Company::factory()->create();
        $category = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'General Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = \App\Models\User::factory()->create(['company_id' => $company->id]);

        return [
            'company_id' => $company->id,
            'category_id' => $category,
            'user_id' => $user->id,
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
