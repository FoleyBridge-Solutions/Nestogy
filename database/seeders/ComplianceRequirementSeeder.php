<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\ComplianceRequirement;
use App\Domains\Tax\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;

class ComplianceRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Compliance Requirement Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating compliance requirements for company: {$company->name}");

            $jurisdictions = TaxJurisdiction::where('company_id', $company->id)->pluck('id')->toArray();

            if (empty($jurisdictions)) {
                $this->command->warn("No jurisdictions found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 10-20 compliance requirements per company
            ComplianceRequirement::factory()
                ->count(rand(10, 20))
                ->for($company)
                ->create([
                    'tax_jurisdiction_id' => fake()->randomElement($jurisdictions),
                ]);

            $this->command->info("Completed compliance requirements for company: {$company->name}");
        }

        $this->command->info('Compliance Requirement Seeder completed!');
    }
}
