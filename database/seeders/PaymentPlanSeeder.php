<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\PaymentPlan;
use Illuminate\Database\Seeder;

class PaymentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Payment Plan Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating payment plans for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create payment plans for 5-10% of clients
            $clientCount = (int) ($clients->count() * rand(5, 10) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                // Get an invoice for this client
                $invoice = Invoice::where('company_id', $company->id)
                    ->where('client_id', $client->id)
                    ->first();

                if (!$invoice) continue;

                PaymentPlan::factory()
                    ->for($company)
                    ->for($client)
                    ->create([
                        'invoice_id' => $invoice->id,
                    ]);
            }

            $this->command->info("Completed payment plans for company: {$company->name}");
        }

        $this->command->info('Payment Plan Seeder completed!');
    }
}
