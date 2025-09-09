<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'Business Checking Account',
                'opening_balance' => 10000.00,
                'currency_code' => 'USD',
                'notes' => 'Primary business checking account for daily operations',
                'type' => 1, // Checking account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'Business Savings Account',
                'opening_balance' => 25000.00,
                'currency_code' => 'USD',
                'notes' => 'Business savings account for emergency funds',
                'type' => 2, // Savings account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'name' => 'Petty Cash',
                'opening_balance' => 500.00,
                'currency_code' => 'USD',
                'notes' => 'Cash on hand for small expenses',
                'type' => 3, // Cash account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
            [
                'id' => 4,
                'company_id' => 1,
                'name' => 'Business Credit Card',
                'opening_balance' => -2500.00,
                'currency_code' => 'USD',
                'notes' => 'Business credit card for expenses',
                'type' => 4, // Credit card account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
            [
                'id' => 5,
                'company_id' => 1,
                'name' => 'Accounts Receivable',
                'opening_balance' => 15000.00,
                'currency_code' => 'USD',
                'notes' => 'Outstanding invoices from clients',
                'type' => 5, // Receivable account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
            [
                'id' => 6,
                'company_id' => 1,
                'name' => 'Accounts Payable',
                'opening_balance' => -5000.00,
                'currency_code' => 'USD',
                'notes' => 'Outstanding bills to vendors',
                'type' => 6, // Payable account type
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
                'plaid_id' => null,
            ],
        ]);
    }
}