<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Models\RefundRequest;
use Illuminate\Database\Seeder;

class RefundRequestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Refund Request Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating refund requests for company: {$company->name}");

            $payments = Payment::where('company_id', $company->id)->get();

            if ($payments->isEmpty()) {
                $this->command->warn("No payments found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create refund requests for 3-5% of payments
            $paymentCount = (int) ($payments->count() * rand(3, 5) / 100);
            $selectedPayments = $payments->random(min($paymentCount, $payments->count()));

            foreach ($selectedPayments as $payment) {
                RefundRequest::factory()
                    ->for($company)
                    ->for($payment->client)
                    ->create([
                        'payment_id' => $payment->id,
                    ]);
            }

            $this->command->info("Completed refund requests for company: {$company->name}");
        }

        $this->command->info('Refund Request Seeder completed!');
    }
}
