<?php

namespace Database\Seeders;

use App\Domains\Financial\Models\Category;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating expense categories...');

        $companies = Company::where('id', '>', 1)->get();

        $expenseCategories = [
            'Office Supplies',
            'Utilities',
            'Rent',
            'Insurance',
            'Marketing',
            'Travel',
            'Equipment',
            'Software Subscriptions',
            'Professional Services',
            'Payroll',
        ];

        foreach ($companies as $company) {
            foreach ($expenseCategories as $categoryName) {
                Category::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $categoryName,
                ], [
                    'type' => ['expense'],
                    'color' => fake()->hexColor(),
                    'description' => fake()->optional(0.5)->sentence(),
                ]);
            }
        }

        $this->command->info('Expense categories created successfully.');
    }
}
