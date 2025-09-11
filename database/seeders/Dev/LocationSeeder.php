<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Client;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating locations for clients...');
        
        $clients = Client::all();
        
        foreach ($clients as $client) {
            // Each client gets 1-3 locations
            $numLocations = rand(1, 3);
            
            // First location is always primary
            Location::factory()
                ->forClient($client)
                ->primary()
                ->create();
            
            // Additional locations if needed
            if ($numLocations > 1) {
                Location::factory()
                    ->count($numLocations - 1)
                    ->forClient($client)
                    ->create();
            }
            
            $this->command->info("    ✓ Created {$numLocations} location(s) for {$client->name}");
        }
        
        $this->command->info('Locations created successfully.');
    }
}