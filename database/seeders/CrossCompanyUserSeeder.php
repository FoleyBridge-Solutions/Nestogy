<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CrossCompanyUser;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class CrossCompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Cross Company User Seeder...');

        $companies = Company::where('id', '>', 1)->get();
        $users = User::all();

        if ($users->isEmpty() || $companies->count() < 2) {
            $this->command->warn('Not enough companies or users for cross-company access');
            return;
        }

        // Give 10-20% of users access to multiple companies
        $userCount = (int) ($users->count() * rand(10, 20) / 100);
        $selectedUsers = $users->random(min($userCount, $users->count()));

        foreach ($selectedUsers as $user) {
            // Give access to 1-2 additional companies
            $additionalCompanies = $companies->where('id', '!=', $user->company_id)
                ->random(min(rand(1, 2), $companies->count() - 1));

            $primaryCompany = Company::find($user->company_id);

            foreach ($additionalCompanies as $company) {
                CrossCompanyUser::factory()
                    ->for($user)
                    ->for($company, 'company')
                    ->for($primaryCompany, 'primaryCompany')
                    ->create();
            }
        }

        $this->command->info('Cross Company User Seeder completed!');
    }
}
}
