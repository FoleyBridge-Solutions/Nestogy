<?php

namespace Database\Seeders;

use App\Domains\Financial\Models\AutoPayment;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Seeder;

class AutoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating auto payments for clients...');

        $clients = Client::where('status', 'active')->get();

        foreach ($clients as $client) {
            if (fake()->boolean(40)) {
                AutoPayment::factory()->create([
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                ]);
            }
        }

        $this->command->info('✓ Created '.AutoPayment::count().' auto payments');
    }
}
