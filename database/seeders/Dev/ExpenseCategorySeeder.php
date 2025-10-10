<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating ExpenseCategory records...');
$companies = Company::where('id', '>', 1)->get();
        
        $categories = [
            'Office Supplies', 'Software & Licenses', 'Hardware', 'Travel',
            'Marketing', 'Utilities', 'Rent', 'Salaries', 'Professional Services',
            'Insurance', 'Training', 'Equipment', 'Telecommunications'
        ];
        
        foreach ($companies as $company) {
            foreach ($categories as $categoryName) {
                ExpenseCategory::factory()->create([
                    'company_id' => $company->id,
                    'name' => $categoryName,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".ExpenseCategory::count()." expense categories");
    }
}
