<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\ContractConfiguration;
use Illuminate\Database\Seeder;

class ContractConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Contract Configuration Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 3-7 contract configurations per company
            ContractConfiguration::factory()
                ->count(rand(3, 7))
                ->for($company)
                ->create();
        }

        $this->command->info('Contract Configuration Seeder completed!');
    }
}
