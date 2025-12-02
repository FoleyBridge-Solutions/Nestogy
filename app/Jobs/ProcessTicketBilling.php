<?php

namespace App\Jobs;

use App\Domains\Financial\Services\TicketBillingService;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Ticket Billing Job
 * 
 * Asynchronously processes billing for a ticket using the
 * TicketBillingService. Runs on the billing queue with retries.
 */
class ProcessTicketBilling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout;

    /**
     * The ID of the ticket to bill
     */
    protected int $ticketId;

    /**
     * Optional billing options
     */
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ticketId, array $options = [])
    {
        $this->ticketId = $ticketId;
        $this->options = $options;
        $this->tries = config('billing.ticket.job_retries', 3);
        $this->timeout = config('billing.ticket.job_timeout', 120);
    }

    /**
     * Execute the job.
     */
    public function handle(TicketBillingService $billingService): void
    {
        Log::info('Processing ticket billing job', [
            'ticket_id' => $this->ticketId,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Load the ticket
            $ticket = Ticket::with(['client', 'contact', 'timeEntries'])
                ->find($this->ticketId);

            if (!$ticket) {
                Log::warning('Ticket not found for billing', [
                    'ticket_id' => $this->ticketId,
                ]);
                return;
            }

            // Check if we can bill this ticket
            if (!$billingService->canBillTicket($ticket)) {
                Log::info('Ticket cannot be billed', [
                    'ticket_id' => $this->ticketId,
                    'has_invoice' => !is_null($ticket->invoice_id),
                    'is_billable' => $ticket->billable,
                    'has_client' => !is_null($ticket->client_id),
                ]);
                return;
            }

            // Process billing
            $invoice = $billingService->billTicket($ticket, $this->options);

            if ($invoice) {
                Log::info('Ticket billing completed successfully', [
                    'ticket_id' => $this->ticketId,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount,
                ]);

                // Optionally send invoice if configured
                if (config('billing.ticket.auto_send', false) && 
                    !config('billing.ticket.require_approval', true)) {
                    // TODO: Implement auto-send logic
                    // Mail::to($invoice->client->email)->send(new InvoiceCreated($invoice));
                }
            } else {
                Log::info('No invoice generated for ticket', [
                    'ticket_id' => $this->ticketId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process ticket billing', [
                'ticket_id' => $this->ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ticket billing job failed permanently', [
            'ticket_id' => $this->ticketId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Send notification to admins about failed billing
    }
}
