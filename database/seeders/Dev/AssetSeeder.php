<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Client;
use App\Models\Location;

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
            
            $clients = Client::where('company_id', $company->id)->get();
            
            foreach ($clients as $client) {
                $locations = Location::where('client_id', $client->id)->pluck('id')->toArray();
                
                // Create various types of assets for each client
                $assetTypes = [
                    'desktop' => rand(5, 20),
                    'laptop' => rand(3, 15),
                    'server' => rand(1, 5),
                    'printer' => rand(2, 8),
                    'network' => rand(3, 10),
                    'mobile' => rand(2, 10),
                    'software' => rand(5, 15),
                ];
                
                foreach ($assetTypes as $type => $count) {
                    Asset::factory()
                        ->count($count)
                        ->forClient($client)
                        ->state([
                            'type' => $type,
                            'location_id' => !empty($locations) ? fake()->randomElement($locations) : null,
                        ])
                        ->create();
                }
            }
            
            $this->command->info("Completed assets for company: {$company->name}");
        }
        
        $this->command->info('Asset Seeder completed!');
    }
}