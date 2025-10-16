<?php

namespace Database\Seeders\Dev;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientPortalUser;
use App\Domains\Client\Models\Contact;
use Illuminate\Database\Seeder;

class ClientPortalUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating ClientPortalUser records...');
$clients = Client::all();
        $count = 0;
        
        foreach ($clients as $client) {
            $contacts = Contact::where('client_id', $client->id)->get();
            
            foreach ($contacts as $contact) {
                if (fake()->boolean(60)) {
                    ClientPortalUser::factory()->create([
                        'client_id' => $client->id,
                        'contact_id' => $contact->id,
                        'company_id' => $client->company_id,
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} client portal users");
    }
}
