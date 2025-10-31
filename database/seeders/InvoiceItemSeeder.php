<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use Illuminate\Database\Seeder;

class InvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Invoice Item Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating invoice items for company: {$company->name}");

            $invoices = Invoice::where('company_id', $company->id)->get();

            if ($invoices->isEmpty()) {
                $this->command->warn("No invoices found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 1-10 items per invoice
            foreach ($invoices as $invoice) {
                $itemCount = rand(1, 10);

                InvoiceItem::factory()
                    ->count($itemCount)
                    ->for($company)
                    ->for($invoice)
                    ->create();
            }

            $this->command->info("Completed invoice items for company: {$company->name}");
        }

        $this->command->info('Invoice Item Seeder completed!');
    }
}
