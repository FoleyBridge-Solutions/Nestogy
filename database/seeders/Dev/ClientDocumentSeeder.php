<?php

namespace Database\Seeders\Dev;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Database\Seeder;

class ClientDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating ClientDocument records...');
$clients = Client::all();
        $count = 0;
        
        foreach ($clients as $client) {
            $docCount = rand(2, 10);
            for ($i = 0; $i < $docCount; $i++) {
                ClientDocument::factory()->create([
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                    'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} client documents");
    }
}
