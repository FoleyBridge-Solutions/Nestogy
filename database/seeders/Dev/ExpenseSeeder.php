<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Company;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\User;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 2 years of expense history...');
        
        $companies = Company::where('id', '>', 1)->get();
        $totalExpenses = 0;
        
        foreach ($companies as $company) {
            $this->command->info("  Creating expenses for {$company->name}...");
            
            // Get or create expense categories
            $categories = Category::where('company_id', $company->id)
                ->where('type', 'expense')
                ->get();
            
            if ($categories->isEmpty()) {
                $expenseCategories = [
                    'Office Supplies',
                    'Software Subscriptions',
                    'Hardware Purchases',
                    'Internet & Phone',
                    'Rent & Utilities',
                    'Travel & Transportation',
                    'Professional Services',
                    'Marketing & Advertising',
                    'Training & Education',
                    'Insurance',
                ];
                
                foreach ($expenseCategories as $catName) {
                    $categories->push(Category::create([
                        'company_id' => $company->id,
                        'name' => $catName,
                        'type' => 'expense',
                        'color' => fake()->hexColor()
                    ]));
                }
            }
            
            $vendors = Vendor::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();
            
            if ($users->isEmpty()) {
                continue;
            }
            
            // Define recurring monthly expenses
            $recurringExpenses = [
                ['name' => 'Office Rent', 'amount_range' => [2000, 5000], 'category' => 'Rent & Utilities'],
                ['name' => 'Internet Service', 'amount_range' => [100, 500], 'category' => 'Internet & Phone'],
                ['name' => 'Phone Service', 'amount_range' => [200, 800], 'category' => 'Internet & Phone'],
                ['name' => 'Electricity', 'amount_range' => [200, 1000], 'category' => 'Rent & Utilities'],
                ['name' => 'Microsoft 365 Subscription', 'amount_range' => [300, 1500], 'category' => 'Software Subscriptions'],
                ['name' => 'Adobe Creative Cloud', 'amount_range' => [200, 1000], 'category' => 'Software Subscriptions'],
                ['name' => 'Professional Liability Insurance', 'amount_range' => [500, 2000], 'category' => 'Insurance'],
            ];
            
            // Generate recurring expenses for past 24 months
            foreach ($recurringExpenses as $recurringExpense) {
                $category = $categories->firstWhere('name', $recurringExpense['category']);
                if (!$category) {
                    $category = $categories->first();
                }
                
                $baseAmount = fake()->numberBetween($recurringExpense['amount_range'][0], $recurringExpense['amount_range'][1]);
                
                for ($month = 0; $month < 24; $month++) {
                    $expenseDate = Carbon::now()->subMonths($month)->startOfMonth()->addDays(rand(1, 28));
                    
                    // Add some variation to monthly amounts
                    $amount = $baseAmount * fake()->randomFloat(2, 0.95, 1.05);
                    
                    Expense::create([
                        'company_id' => $company->id,
                        'category_id' => $category->id,
                        'vendor_id' => $vendors->isNotEmpty() ? $vendors->random()->id : null,
                        'user_id' => $users->random()->id,
                        'name' => $recurringExpense['name'],
                        'description' => "Monthly {$recurringExpense['name']} - " . $expenseDate->format('F Y'),
                        'amount' => $amount,
                        'date' => $expenseDate,
                        'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'check', 'cash']),
                        'reference_number' => fake()->optional(0.7)->bothify('REF-####-????'),
                        'is_recurring' => true,
                        'status' => $expenseDate->isFuture() ? 'pending' : 'paid',
                        'created_at' => $expenseDate,
                        'updated_at' => fake()->dateTimeBetween($expenseDate, 'now'),
                    ]);
                    
                    $totalExpenses++;
                }
            }
            
            // Generate random one-time expenses
            $oneTimeExpenseTemplates = [
                ['name' => 'Laptop Purchase', 'amount_range' => [800, 2500], 'category' => 'Hardware Purchases'],
                ['name' => 'Server Hardware', 'amount_range' => [2000, 10000], 'category' => 'Hardware Purchases'],
                ['name' => 'Network Equipment', 'amount_range' => [500, 3000], 'category' => 'Hardware Purchases'],
                ['name' => 'Office Furniture', 'amount_range' => [200, 2000], 'category' => 'Office Supplies'],
                ['name' => 'Printer & Supplies', 'amount_range' => [100, 500], 'category' => 'Office Supplies'],
                ['name' => 'Conference Travel', 'amount_range' => [500, 3000], 'category' => 'Travel & Transportation'],
                ['name' => 'Client Meeting Expenses', 'amount_range' => [50, 500], 'category' => 'Travel & Transportation'],
                ['name' => 'Training Course', 'amount_range' => [500, 5000], 'category' => 'Training & Education'],
                ['name' => 'Certification Exam', 'amount_range' => [200, 1000], 'category' => 'Training & Education'],
                ['name' => 'Google Ads Campaign', 'amount_range' => [200, 2000], 'category' => 'Marketing & Advertising'],
                ['name' => 'Trade Show Booth', 'amount_range' => [1000, 5000], 'category' => 'Marketing & Advertising'],
                ['name' => 'Legal Consultation', 'amount_range' => [500, 3000], 'category' => 'Professional Services'],
                ['name' => 'Accounting Services', 'amount_range' => [300, 2000], 'category' => 'Professional Services'],
            ];
            
            // Generate 50-100 random one-time expenses over 2 years
            $numOneTimeExpenses = rand(50, 100);
            
            for ($i = 0; $i < $numOneTimeExpenses; $i++) {
                $template = fake()->randomElement($oneTimeExpenseTemplates);
                $category = $categories->firstWhere('name', $template['category']);
                if (!$category) {
                    $category = $categories->first();
                }
                
                $expenseDate = fake()->dateTimeBetween('-2 years', 'now');
                
                Expense::create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'vendor_id' => $vendors->isNotEmpty() ? fake()->optional(0.7)->randomElement($vendors)->id : null,
                    'user_id' => $users->random()->id,
                    'name' => $template['name'],
                    'description' => fake()->optional(0.6)->sentence(),
                    'amount' => fake()->numberBetween($template['amount_range'][0], $template['amount_range'][1]),
                    'date' => $expenseDate,
                    'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'check', 'cash', 'paypal']),
                    'reference_number' => fake()->optional(0.7)->bothify('EXP-####-????'),
                    'receipt_path' => fake()->optional(0.3)->filePath(),
                    'is_recurring' => false,
                    'is_billable' => fake()->boolean(30),
                    'status' => Carbon::parse($expenseDate)->isFuture() ? 'pending' : 
                              fake()->randomElement(['paid', 'paid', 'paid', 'approved', 'reimbursed']),
                    'created_at' => $expenseDate,
                    'updated_at' => fake()->dateTimeBetween($expenseDate, 'now'),
                ]);
                
                $totalExpenses++;
            }
            
            $this->command->info("    ✓ Created expenses for {$company->name}");
        }
        
        $this->command->info("Created {$totalExpenses} expenses with 2 years of history.");
    }
}