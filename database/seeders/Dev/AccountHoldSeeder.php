<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\Account;
use App\Domains\Company\Models\AccountHold;
use Illuminate\Database\Seeder;

class AccountHoldSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating account holds...');

        $accounts = Account::all();

        foreach ($accounts as $account) {
            if (fake()->boolean(20)) {
                AccountHold::factory()->create([
                    'account_id' => $account->id,
                    'company_id' => $account->company_id,
                ]);
            }
        }

        $this->command->info('✓ Created '.AccountHold::count().' account holds');
    }
}
