<?php

namespace Database\Seeders;

use App\Domains\Asset\Models\Asset;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Client\Models\Location;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Asset Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating assets for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->where('status', 'active')->get();

            foreach ($clients as $client) {
                $locations = Location::where('client_id', $client->id)->pluck('id')->toArray();

                // Create realistic asset counts based on client size
                $employeeCount = $client->employee_count ?? 20;
                
                // Small clients (1-50 employees): fewer assets
                // Medium clients (51-500): moderate assets  
                // Large clients (500+): more assets
                $assetMultiplier = match(true) {
                    $employeeCount > 500 => 1.5,
                    $employeeCount > 50 => 1.0,
                    default => 0.4,
                };

                $assetTypes = [
                    'desktop' => (int) (rand(1, 3) * $assetMultiplier),
                    'laptop' => (int) (rand(1, 2) * $assetMultiplier),
                    'server' => (int) (rand(0, 2) * $assetMultiplier),
                    'printer' => (int) (rand(0, 1) * $assetMultiplier),
                    'network' => (int) (rand(1, 2) * $assetMultiplier),
                    'mobile' => (int) (rand(0, 2) * $assetMultiplier),
                    'software' => (int) (rand(1, 2) * $assetMultiplier),
                ];

                foreach ($assetTypes as $type => $count) {
                    if ($count > 0) {
                        Asset::factory()
                            ->count($count)
                            ->forClient($client)
                            ->state([
                                'type' => $type,
                                'location_id' => ! empty($locations) ? fake()->randomElement($locations) : null,
                            ])
                            ->create();
                    }
                }
            }

            $this->command->info("Completed assets for company: {$company->name}");
        }

        $this->command->info('Asset Seeder completed!');
    }
}
