<?php

namespace Database\Seeders\Dev;

use App\Domains\Client\Models\Address;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating addresses for clients...');

        $clients = Client::all();

        foreach ($clients as $client) {
            $addressCount = rand(1, 3);
            
            for ($i = 0; $i < $addressCount; $i++) {
                Address::factory()->create([
                    'addressable_type' => Client::class,
                    'addressable_id' => $client->id,
                    'company_id' => $client->company_id,
                ]);
            }
        }

        $this->command->info('✓ Created '.Address::count().' addresses');
    }
}
