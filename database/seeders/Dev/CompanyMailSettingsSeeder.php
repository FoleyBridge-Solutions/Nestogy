<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanyMailSettings;
use Illuminate\Database\Seeder;

class CompanyMailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating CompanyMailSettings records...');
$companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            if (!CompanyMailSettings::where('company_id', $company->id)->exists()) {
                CompanyMailSettings::factory()->create([
                    'company_id' => $company->id,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".CompanyMailSettings::count()." company mail settings");
    }
}
