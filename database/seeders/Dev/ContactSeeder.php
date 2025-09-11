<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Location;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating contacts for clients...');
        
        $clients = Client::all();
        
        foreach ($clients as $client) {
            // Get primary location for this client
            $primaryLocation = Location::where('client_id', $client->id)
                                      ->where('primary', true)
                                      ->first();
            
            // Create primary contact
            Contact::factory()
                ->forClient($client)
                ->primary()
                ->state(['location_id' => $primaryLocation?->id])
                ->create();
            
            // Create billing contact
            Contact::factory()
                ->forClient($client)
                ->billing()
                ->state(['location_id' => $primaryLocation?->id])
                ->create();
            
            // Create technical contact
            Contact::factory()
                ->forClient($client)
                ->technical()
                ->state(['location_id' => $primaryLocation?->id])
                ->create();
            
            // Create 2-5 additional contacts
            $additionalContacts = rand(2, 5);
            Contact::factory()
                ->count($additionalContacts)
                ->forClient($client)
                ->state(['location_id' => $primaryLocation?->id])
                ->create();
            
            $totalContacts = 3 + $additionalContacts;
            $this->command->info("    ✓ Created {$totalContacts} contacts for {$client->name}");
        }
        
        $this->command->info('Contacts created successfully.');
    }
}