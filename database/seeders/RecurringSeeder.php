<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Recurring;
use Illuminate\Database\Seeder;

class RecurringSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Recurring Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();

            if ($clients->isEmpty()) {
                continue;
            }

            // Create recurring billing for 30-50% of clients
            $clientCount = (int) ($clients->count() * rand(30, 50) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                Recurring::factory()
                    ->for($company)
                    ->for($client)
                    ->create();
            }
        }

        $this->command->info('Recurring Seeder completed!');
    }
}
