<?php

namespace App\Jobs;

use App\Domains\Core\Services\Notification\NotificationDispatcher;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Notification Job
 * 
 * Handles queued notification processing through the notification dispatcher.
 */
class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $eventType;
    protected int $ticketId;
    protected array $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $eventType, int $ticketId, array $eventData = [])
    {
        $this->eventType = $eventType;
        $this->ticketId = $ticketId;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationDispatcher $dispatcher): void
    {
        try {
            $ticket = Ticket::find($this->ticketId);
            
            if (!$ticket) {
                Log::warning('Notification job failed: Ticket not found', [
                    'ticket_id' => $this->ticketId,
                    'event_type' => $this->eventType
                ]);
                return;
            }

            $result = $dispatcher->dispatch($this->eventType, $ticket, $this->eventData);
            
            Log::info('Queued notification processed', [
                'event_type' => $this->eventType,
                'ticket_id' => $this->ticketId,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Notification job failed', [
                'event_type' => $this->eventType,
                'ticket_id' => $this->ticketId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger job retry logic
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job permanently failed', [
            'event_type' => $this->eventType,
            'ticket_id' => $this->ticketId,
            'error' => $exception->getMessage()
        ]);
    }
}