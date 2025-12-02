<?php

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Events\TicketResolved;
use App\Jobs\ProcessTicketBilling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Queue Ticket Billing Job
 * 
 * When a ticket is closed or resolved, this listener queues a job
 * to process billing for the ticket asynchronously.
 */
class QueueTicketBillingJob implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle TicketClosed event
     */
    public function handleClosed(TicketClosed $event): void
    {
        if (!config('billing.ticket.auto_bill_on_close', false)) {
            return;
        }

        $this->queueBilling($event->ticket, 'closed');
    }

    /**
     * Handle TicketResolved event
     */
    public function handleResolved(TicketResolved $event): void
    {
        if (!config('billing.ticket.auto_bill_on_resolve', false)) {
            return;
        }

        $this->queueBilling($event->ticket, 'resolved');
    }

    /**
     * Queue billing job for ticket
     */
    protected function queueBilling($ticket, string $trigger): void
    {
        try {
            // Don't queue if already invoiced
            if ($ticket->invoice_id) {
                Log::info('Skipping billing queue - ticket already invoiced', [
                    'ticket_id' => $ticket->id,
                    'invoice_id' => $ticket->invoice_id,
                ]);
                return;
            }

            // Don't queue if not billable
            if (!$ticket->billable) {
                Log::info('Skipping billing queue - ticket not billable', [
                    'ticket_id' => $ticket->id,
                ]);
                return;
            }

            // Queue the billing job
            $queue = config('billing.ticket.queue', 'billing');
            
            ProcessTicketBilling::dispatch($ticket->id)
                ->onQueue($queue)
                ->delay(now()->addSeconds(30)); // Small delay to ensure ticket is fully updated

            Log::info('Queued ticket billing job', [
                'ticket_id' => $ticket->id,
                'trigger' => $trigger,
                'queue' => $queue,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue ticket billing job', [
                'ticket_id' => $ticket->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
