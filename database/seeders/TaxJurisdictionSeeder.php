<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;

class TaxJurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax Jurisdiction Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax jurisdictions for company: {$company->name}");

            // Create federal jurisdiction
            $federal = TaxJurisdiction::factory()
                ->for($company)
                ->federal()
                ->create([
                    'priority' => 1,
                ]);

            // Create 5-10 state jurisdictions
            $states = [];
            $stateCount = rand(5, 10);
            
            for ($i = 0; $i < $stateCount; $i++) {
                $states[] = TaxJurisdiction::factory()
                    ->for($company)
                    ->stateLevel()
                    ->create([
                        'parent_jurisdiction_id' => $federal->id,
                        'priority' => 10 + $i,
                    ]);
            }

            // Create 10-20 county jurisdictions
            $countyCount = rand(10, 20);
            
            foreach ($states as $state) {
                $countiesPerState = rand(1, 3);
                
                for ($i = 0; $i < $countiesPerState; $i++) {
                    TaxJurisdiction::factory()
                        ->for($company)
                        ->county()
                        ->create([
                            'parent_jurisdiction_id' => $state->id,
                            'state_code' => $state->code ?? 'CA',
                            'priority' => 20 + rand(0, 50),
                        ]);
                    
                    $countyCount--;
                    if ($countyCount <= 0) break;
                }
                
                if ($countyCount <= 0) break;
            }

            // Create 10-15 city/municipality jurisdictions
            $cityCount = rand(10, 15);
            
            TaxJurisdiction::factory()
                ->count($cityCount)
                ->for($company)
                ->create([
                    'jurisdiction_type' => fake()->randomElement([
                        TaxJurisdiction::TYPE_CITY,
                        TaxJurisdiction::TYPE_MUNICIPALITY,
                    ]),
                    'priority' => rand(30, 60),
                ]);

            $this->command->info("Completed tax jurisdictions for company: {$company->name}");
        }

        $this->command->info('Tax Jurisdiction Seeder completed!');
    }
}
