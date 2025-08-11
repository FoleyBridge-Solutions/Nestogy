<?php

namespace App\Jobs;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Check Ticket Escalation Job
 * 
 * Monitors urgent tickets for escalation needs based on response time
 * and resolution SLAs. Triggers escalation actions when thresholds are met.
 */
class CheckTicketEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ticket $ticket;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->queue = 'escalations';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Checking ticket escalation', [
                'ticket_id' => $this->ticket->id,
                'priority' => $this->ticket->priority,
                'status' => $this->ticket->status,
                'created_at' => $this->ticket->created_at->toISOString(),
            ]);

            // Skip if ticket is already resolved
            if (in_array($this->ticket->status, ['Closed', 'Resolved', 'Cancelled'])) {
                Log::info('Ticket already resolved, skipping escalation check', [
                    'ticket_id' => $this->ticket->id,
                    'status' => $this->ticket->status,
                ]);
                return;
            }

            // Check for escalation triggers
            $escalationNeeded = $this->checkEscalationTriggers();

            if ($escalationNeeded) {
                $this->escalateTicket($escalationNeeded);
            } else {
                // Schedule next check
                $this->scheduleNextCheck();
            }

        } catch (\Exception $e) {
            Log::error('Ticket escalation check failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if ticket needs escalation.
     */
    protected function checkEscalationTriggers(): ?string
    {
        $slaThresholds = $this->getSLAThresholds();
        $ticketAge = now()->diffInMinutes($this->ticket->created_at);

        // Check response time SLA
        if (!$this->hasFirstResponse() && $ticketAge > $slaThresholds['response_time']) {
            return 'response_overdue';
        }

        // Check resolution time SLA
        if ($ticketAge > $slaThresholds['resolution_time']) {
            return 'resolution_overdue';
        }

        // Check for lack of activity
        $lastActivity = $this->getLastActivityTime();
        if ($lastActivity && now()->diffInHours($lastActivity) > $slaThresholds['activity_timeout']) {
            return 'activity_timeout';
        }

        return null;
    }

    /**
     * Get SLA thresholds based on ticket priority.
     */
    protected function getSLAThresholds(): array
    {
        $thresholds = [
            'Urgent' => [
                'response_time' => 15, // 15 minutes
                'resolution_time' => 240, // 4 hours
                'activity_timeout' => 2, // 2 hours
            ],
            'High' => [
                'response_time' => 60, // 1 hour
                'resolution_time' => 480, // 8 hours
                'activity_timeout' => 4, // 4 hours
            ],
            'Normal' => [
                'response_time' => 240, // 4 hours
                'resolution_time' => 1440, // 24 hours
                'activity_timeout' => 8, // 8 hours
            ],
            'Low' => [
                'response_time' => 480, // 8 hours
                'resolution_time' => 2880, // 48 hours
                'activity_timeout' => 24, // 24 hours
            ],
        ];

        return $thresholds[$this->ticket->priority] ?? $thresholds['Normal'];
    }

    /**
     * Check if ticket has received first response.
     */
    protected function hasFirstResponse(): bool
    {
        // Check if there are any replies from technicians
        return $this->ticket->replies()
            ->where('user_type', 'technician')
            ->exists();
    }

    /**
     * Get last activity time for the ticket.
     */
    protected function getLastActivityTime(): ?\Carbon\Carbon
    {
        $lastReply = $this->ticket->replies()->latest()->first();
        $lastUpdate = $this->ticket->updated_at;

        if ($lastReply && $lastReply->created_at->gt($lastUpdate)) {
            return $lastReply->created_at;
        }

        return $lastUpdate;
    }

    /**
     * Escalate the ticket.
     */
    protected function escalateTicket(string $reason): void
    {
        Log::warning('Escalating ticket', [
            'ticket_id' => $this->ticket->id,
            'reason' => $reason,
            'priority' => $this->ticket->priority,
        ]);

        // Increase priority if not already at maximum
        $this->escalatePriority();

        // Notify management
        $this->notifyManagement($reason);

        // Reassign to senior technician if needed
        $this->reassignToSenior();

        // Log escalation
        $this->logEscalation($reason);
    }

    /**
     * Escalate ticket priority.
     */
    protected function escalatePriority(): void
    {
        $priorityEscalation = [
            'Low' => 'Normal',
            'Normal' => 'High',
            'High' => 'Urgent',
            'Urgent' => 'Urgent', // Already at maximum
        ];

        $newPriority = $priorityEscalation[$this->ticket->priority] ?? $this->ticket->priority;

        if ($newPriority !== $this->ticket->priority) {
            $this->ticket->update(['priority' => $newPriority]);
            
            Log::info('Ticket priority escalated', [
                'ticket_id' => $this->ticket->id,
                'old_priority' => $this->ticket->priority,
                'new_priority' => $newPriority,
            ]);
        }
    }

    /**
     * Notify management of escalation.
     */
    protected function notifyManagement(string $reason): void
    {
        // Get managers for the company
        $managers = \App\Models\User::forCompany($this->ticket->company_id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->get();

        foreach ($managers as $manager) {
            // Send escalation notification
            Log::info('Would notify manager of escalation', [
                'ticket_id' => $this->ticket->id,
                'manager_id' => $manager->id,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Reassign ticket to senior technician.
     */
    protected function reassignToSenior(): void
    {
        // Find senior technicians
        $seniorTechs = \App\Models\User::forCompany($this->ticket->company_id)
            ->where('role', 'senior_technician')
            ->where('is_active', true)
            ->get();

        if ($seniorTechs->isNotEmpty()) {
            $assignee = $seniorTechs->random();
            
            $this->ticket->update([
                'assigned_to' => $assignee->id,
                'assigned_at' => now(),
            ]);

            Log::info('Ticket reassigned to senior technician', [
                'ticket_id' => $this->ticket->id,
                'assigned_to' => $assignee->id,
            ]);
        }
    }

    /**
     * Log escalation event.
     */
    protected function logEscalation(string $reason): void
    {
        // This would create an escalation log entry
        Log::info('Ticket escalation logged', [
            'ticket_id' => $this->ticket->id,
            'reason' => $reason,
            'escalated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Schedule next escalation check.
     */
    protected function scheduleNextCheck(): void
    {
        $slaThresholds = $this->getSLAThresholds();
        $nextCheckDelay = min(30, $slaThresholds['activity_timeout'] * 60 / 4); // Check every 1/4 of activity timeout, max 30 min

        self::dispatch($this->ticket)
            ->delay(now()->addMinutes($nextCheckDelay));

        Log::debug('Next escalation check scheduled', [
            'ticket_id' => $this->ticket->id,
            'next_check_in_minutes' => $nextCheckDelay,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Escalation check job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'escalation-check',
            'ticket:' . $this->ticket->id,
            'priority:' . $this->ticket->priority,
        ];
    }
}