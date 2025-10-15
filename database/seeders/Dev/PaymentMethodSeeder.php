<?php

namespace Database\Seeders\Dev;

use App\Models\PaymentMethod;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating PaymentMethod records...');
$clients = Client::where('status', 'active')->get();
        $count = 0;
        
        foreach ($clients as $client) {
            if (fake()->boolean(60)) {
                PaymentMethod::factory()->create([
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} payment methods");
    }
}
