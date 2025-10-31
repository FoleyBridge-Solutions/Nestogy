<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\ComplianceCheck;
use App\Domains\Tax\Models\ComplianceRequirement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComplianceCheckSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Compliance Check Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating compliance checks for company: {$company->name}");

            $requirements = ComplianceRequirement::where('company_id', $company->id)->get();

            if ($requirements->isEmpty()) {
                $this->command->warn("No compliance requirements found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 3-5 checks per requirement (covering past 12 months)
            foreach ($requirements as $requirement) {
                $checkCount = rand(3, 5);

                for ($i = 0; $i < $checkCount; $i++) {
                    ComplianceCheck::factory()
                        ->for($company)
                        ->for($requirement)
                        ->create([
                            'check_date' => Carbon::now()->subMonths(rand(0, 12)),
                        ]);
                }
            }

            $this->command->info("Completed compliance checks for company: {$company->name}");
        }

        $this->command->info('Compliance Check Seeder completed!');
    }
}
