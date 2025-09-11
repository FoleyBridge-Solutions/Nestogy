<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all paid invoices
        $paidInvoices = Invoice::where('status', 'paid')->get();
        $users = User::all();
        
        foreach ($paidInvoices as $invoice) {
            // Create payment record using factory
            $paymentDate = Carbon::parse($invoice->date)->addDays(fake()->numberBetween(5, 30));
            
            Payment::factory()
                ->completed()
                ->create([
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'processed_by' => $users->where('company_id', $invoice->company_id)->random()->id,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency_code,
                    'payment_date' => $paymentDate,
                ]);
        }
        
        // Create partial payments for some overdue invoices
        $overdueInvoices = Invoice::where('status', 'overdue')->inRandomOrder()->limit(5)->get();
        
        foreach ($overdueInvoices as $invoice) {
            $partialAmount = $invoice->amount * fake()->randomFloat(2, 0.2, 0.8);
            $paymentDate = Carbon::parse($invoice->due_date)->addDays(fake()->numberBetween(10, 30));
            
            Payment::factory()
                ->completed()
                ->create([
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'processed_by' => $users->where('company_id', $invoice->company_id)->random()->id,
                    'amount' => $partialAmount,
                    'currency' => $invoice->currency_code,
                    'payment_date' => $paymentDate,
                    'notes' => 'Partial payment received',
                ]);
        }

        // Create some failed payment attempts
        $sentInvoices = Invoice::where('status', 'sent')->inRandomOrder()->limit(3)->get();
        
        foreach ($sentInvoices as $invoice) {
            Payment::factory()
                ->failed()
                ->creditCard()
                ->create([
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'processed_by' => $users->where('company_id', $invoice->company_id)->random()->id,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency_code,
                    'payment_date' => fake()->dateTimeBetween($invoice->date, 'now'),
                    'notes' => 'Payment declined - insufficient funds',
                ]);
        }

        // Create some refunded payments
        $refundCandidates = Payment::where('status', 'completed')
            ->inRandomOrder()
            ->limit(2)
            ->get();
            
        foreach ($refundCandidates as $payment) {
            $payment->update([
                'refund_amount' => $payment->amount * fake()->randomFloat(2, 0.5, 1.0),
                'refund_reason' => fake()->randomElement(['Service not delivered', 'Customer request', 'Duplicate payment']),
                'refunded_at' => fake()->dateTimeBetween($payment->payment_date, 'now'),
            ]);
        }
    }
}