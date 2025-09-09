<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendRecurringInvoiceEmail Job
 * 
 * Handles sending emails for automatically generated recurring invoices.
 */
class SendRecurringInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $maxExceptions = 2;

    protected $invoiceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            $invoice = Invoice::with(['client', 'items', 'recurring'])->findOrFail($this->invoiceId);

            Log::info('Sending recurring invoice email', [
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'amount' => $invoice->amount
            ]);

            // Send the invoice email using the email service
            $emailService->sendInvoiceEmail($invoice, [
                'template' => 'recurring_invoice',
                'subject_prefix' => '[Recurring]',
                'additional_context' => [
                    'is_recurring' => true,
                    'recurring_service' => $invoice->recurring->name ?? 'Recurring Service',
                    'billing_cycle' => $invoice->recurring->billing_cycle ?? 'monthly'
                ]
            ]);

            Log::info('Recurring invoice email sent successfully', [
                'invoice_id' => $invoice->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send recurring invoice email', [
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Recurring invoice email job failed permanently', [
            'invoice_id' => $this->invoiceId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email',
            'recurring-invoice',
            'invoice:' . $this->invoiceId
        ];
    }
}