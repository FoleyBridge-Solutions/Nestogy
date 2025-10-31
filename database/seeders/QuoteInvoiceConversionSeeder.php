<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Quote;
use App\Domains\Financial\Models\QuoteInvoiceConversion;
use Illuminate\Database\Seeder;

class QuoteInvoiceConversionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Quote Invoice Conversion Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating quote-invoice conversions for company: {$company->name}");

            $quotes = Quote::where('company_id', $company->id)->get();

            if ($quotes->isEmpty()) {
                $this->command->warn("No quotes found for company: {$company->name}. Skipping.");
                continue;
            }

            // Convert 40-50% of quotes to invoices
            $quoteCount = (int) ($quotes->count() * rand(40, 50) / 100);
            $selectedQuotes = $quotes->random(min($quoteCount, $quotes->count()));

            foreach ($selectedQuotes as $quote) {
                // Get an invoice for this client
                $invoice = Invoice::where('company_id', $company->id)
                    ->where('client_id', $quote->client_id)
                    ->first();

                if (!$invoice) continue;

                QuoteInvoiceConversion::factory()
                    ->for($company)
                    ->for($quote)
                    ->create([
                        'invoice_id' => $invoice->id,
                    ]);
            }

            $this->command->info("Completed quote-invoice conversions for company: {$company->name}");
        }

        $this->command->info('Quote Invoice Conversion Seeder completed!');
    }
}
