<?php

namespace Database\Seeders\Dev;

use App\Models\Network;
use App\Models\Client;
use Illuminate\Database\Seeder;

class NetworkSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Network records...');
$clients = Client::all();
        $count = 0;
        
        foreach ($clients as $client) {
            if (fake()->boolean(70)) {
                $networkCount = rand(1, 5);
                for ($i = 0; $i < $networkCount; $i++) {
                    Network::factory()->create([
                        'client_id' => $client->id,
                        'company_id' => $client->company_id,
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} networks");
    }
}
