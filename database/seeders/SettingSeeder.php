<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Setting Seeder...');

        // Create company-specific settings (only if they don't already exist)
        $companies = Company::all();

        foreach ($companies as $company) {
            // Skip if this company already has settings
            if (Setting::where('company_id', $company->id)->exists()) {
                continue;
            }
            
            Setting::factory()
                ->create(['company_id' => $company->id]);
        }

        $this->command->info('Setting Seeder completed!');
    }
}
