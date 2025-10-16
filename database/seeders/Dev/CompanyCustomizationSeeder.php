<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanyCustomization;
use Illuminate\Database\Seeder;

class CompanyCustomizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating CompanyCustomization records...');
$companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            CompanyCustomization::factory()->create([
                'company_id' => $company->id,
            ]);
        }
        
        $this->command->info("✓ Created ".CompanyCustomization::count()." company customizations");
    }
}
