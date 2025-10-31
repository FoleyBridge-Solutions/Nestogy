<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\CreditNote;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Database\Seeder;

class CreditNoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Credit Note Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating credit notes for company: {$company->name}");

            $invoices = Invoice::where('company_id', $company->id)->get();

            if ($invoices->isEmpty()) {
                $this->command->warn("No invoices found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create credit notes for 5-10% of invoices
            $invoiceCount = (int) ($invoices->count() * rand(5, 10) / 100);
            $selectedInvoices = $invoices->random(min($invoiceCount, $invoices->count()));

            foreach ($selectedInvoices as $invoice) {
                CreditNote::factory()
                    ->for($company)
                    ->for($invoice->client)
                    ->create([
                        'invoice_id' => $invoice->id,
                    ]);
            }

            $this->command->info("Completed credit notes for company: {$company->name}");
        }

        $this->command->info('Credit Note Seeder completed!');
    }
}
