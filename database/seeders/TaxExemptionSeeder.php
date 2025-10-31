<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\TaxExemption;
use App\Domains\Tax\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;

class TaxExemptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax Exemption Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax exemptions for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $jurisdictions = TaxJurisdiction::where('company_id', $company->id)->pluck('id')->toArray();

            if ($clients->isEmpty() || empty($jurisdictions)) {
                $this->command->warn("Skipping company {$company->name}: no clients or jurisdictions");
                continue;
            }

            // Give 10-20% of clients at least one tax exemption
            $exemptClientCount = (int) ($clients->count() * rand(10, 20) / 100);
            $exemptClients = $clients->random(min($exemptClientCount, $clients->count()));

            foreach ($exemptClients as $client) {
                // Each exempt client gets 1-3 exemptions
                $exemptionCount = rand(1, 3);

                for ($i = 0; $i < $exemptionCount; $i++) {
                    TaxExemption::factory()
                        ->for($company)
                        ->for($client)
                        ->create([
                            'tax_jurisdiction_id' => fake()->randomElement($jurisdictions),
                        ]);
                }
            }

            $this->command->info("Completed tax exemptions for company: {$company->name}");
        }

        $this->command->info('Tax Exemption Seeder completed!');
    }
}
