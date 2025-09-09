<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Invoice;
use Carbon\Carbon;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Payment Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            // Get all paid invoices
            $paidInvoices = Invoice::where('status', 'paid')->get();
            
            foreach ($paidInvoices as $invoice) {
                // Create payment record
                $paymentDate = Carbon::parse($invoice->invoice_date)->addDays($faker->numberBetween(5, 30));
                
                Payment::create([
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'payment_date' => $paymentDate,
                    'payment_method' => $faker->randomElement(['credit_card', 'ach', 'check', 'wire_transfer']),
                    'reference_number' => strtoupper($faker->bothify('PAY-########')),
                    'notes' => $faker->optional(0.2)->sentence(),
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);
            }
            
            // Create partial payments for some overdue invoices
            $overdueInvoices = Invoice::where('status', 'overdue')->inRandomOrder()->limit(5)->get();
            
            foreach ($overdueInvoices as $invoice) {
                $partialAmount = $invoice->total * $faker->randomFloat(2, 0.2, 0.8);
                $paymentDate = Carbon::parse($invoice->due_date)->addDays($faker->numberBetween(10, 30));
                
                Payment::create([
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $partialAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $faker->randomElement(['credit_card', 'check']),
                    'reference_number' => strtoupper($faker->bothify('PAY-########')),
                    'notes' => 'Partial payment received',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);
                
                // Update invoice balance
                $invoice->update([
                    'amount_paid' => $partialAmount,
                    'balance_due' => $invoice->total - $partialAmount,
                ]);
            }
        });

        $this->command->info('Payment Seeder completed!');
    }
}