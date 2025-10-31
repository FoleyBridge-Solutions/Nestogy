<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\CustomQuickAction;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class CustomQuickActionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Custom Quick Action Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();

            if ($users->isEmpty()) {
                continue;
            }

            // Create 5-15 custom quick actions per company
            CustomQuickAction::factory()
                ->count(rand(5, 15))
                ->for($company)
                ->create(['created_by' => $users->random()->id]);
        }

        $this->command->info('Custom Quick Action Seeder completed!');
    }
}
