<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\Account;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating accounts for companies...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $accountCount = rand(2, 5);
            
            for ($i = 0; $i < $accountCount; $i++) {
                Account::factory()->create([
                    'company_id' => $company->id,
                    'type' => fake()->randomElement([
                        Account::TYPE_CHECKING,
                        Account::TYPE_CHECKING,
                        Account::TYPE_SAVINGS,
                        Account::TYPE_CREDIT_CARD,
                        Account::TYPE_CASH,
                    ]),
                ]);
            }
        }

        $this->command->info('✓ Created '.Account::count().' accounts');
    }
}
