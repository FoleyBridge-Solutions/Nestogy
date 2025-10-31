<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\RefundRequest;
use App\Domains\Financial\Models\RefundTransaction;
use Illuminate\Database\Seeder;

class RefundTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Refund Transaction Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating refund transactions for company: {$company->name}");

            $refundRequests = RefundRequest::where('company_id', $company->id)->get();

            if ($refundRequests->isEmpty()) {
                $this->command->warn("No refund requests found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create transactions for 80-90% of refund requests (approved ones)
            $requestCount = (int) ($refundRequests->count() * rand(80, 90) / 100);
            $selectedRequests = $refundRequests->random(min($requestCount, $refundRequests->count()));

            foreach ($selectedRequests as $refundRequest) {
                RefundTransaction::factory()
                    ->for($company)
                    ->for($refundRequest)
                    ->create([
                        'client_id' => $refundRequest->client_id,
                    ]);
            }

            $this->command->info("Completed refund transactions for company: {$company->name}");
        }

        $this->command->info('Refund Transaction Seeder completed!');
    }
}
