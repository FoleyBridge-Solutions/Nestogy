<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\SubsidiaryPermission;
use Illuminate\Database\Seeder;

class SubsidiaryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Subsidiary Permission Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 5-10 subsidiary permissions per company
            SubsidiaryPermission::factory()
                ->count(rand(5, 10))
                ->for($company)
                ->create();
        }

        $this->command->info('Subsidiary Permission Seeder completed!');
    }
}
