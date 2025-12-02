<?php

namespace App\Listeners;

use App\Domains\Contract\Models\ContractContactAssignment;
use App\Events\TicketCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Record Ticket Creation on Contract
 * 
 * When a ticket is created, this listener updates the contract's
 * ticket usage counters for per-ticket billing.
 */
class RecordContractTicketUsage implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Skip if ticket has no contact
        if (!$ticket->contact_id) {
            return;
        }

        try {
            // Find active contract assignment for this contact
            $assignment = ContractContactAssignment::where('contact_id', $ticket->contact_id)
                ->whereHas('schedule', function ($query) {
                    $query->where('is_active', true)
                        ->whereDate('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', now());
                        });
                })
                ->first();

            if ($assignment) {
                // Record ticket creation on contract
                $assignment->recordTicketCreation();

                Log::info('Recorded ticket creation on contract', [
                    'ticket_id' => $ticket->id,
                    'contract_assignment_id' => $assignment->id,
                    'contact_id' => $ticket->contact_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record ticket usage on contract', [
                'ticket_id' => $ticket->id,
                'contact_id' => $ticket->contact_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
