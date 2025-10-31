<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Tax\Models\TaxCalculation;
use Illuminate\Database\Seeder;

class TaxCalculationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax Calculation Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax calculations for company: {$company->name}");

            // Get invoices for this company
            $invoices = Invoice::where('company_id', $company->id)->get();

            if ($invoices->isEmpty()) {
                $this->command->warn("No invoices found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create tax calculations for 70-80% of invoices
            $invoiceCount = (int) ($invoices->count() * rand(70, 80) / 100);
            $selectedInvoices = $invoices->random(min($invoiceCount, $invoices->count()));

            foreach ($selectedInvoices as $invoice) {
                TaxCalculation::factory()
                    ->for($company)
                    ->create([
                        'calculable_type' => Invoice::class,
                        'calculable_id' => $invoice->id,
                        'base_amount' => $invoice->subtotal ?? 0,
                        'total_tax_amount' => $invoice->tax ?? 0,
                        'final_amount' => $invoice->total ?? 0,
                    ]);
            }

            $this->command->info("Completed tax calculations for company: {$company->name}");
        }

        $this->command->info('Tax Calculation Seeder completed!');
    }
}
