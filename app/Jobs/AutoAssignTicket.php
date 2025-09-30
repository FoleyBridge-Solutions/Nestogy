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
 * Auto Assign Ticket Job
 *
 * Automatically assigns tickets to technicians based on workload,
 * client assignments, and availability.
 */
class AutoAssignTicket implements ShouldQueue
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
        $this->queue = 'ticket-assignment';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing auto-assignment for ticket', [
                'ticket_id' => $this->ticket->id,
                'client_id' => $this->ticket->client_id,
                'priority' => $this->ticket->priority,
            ]);

            // Skip if ticket is already assigned
            if ($this->ticket->assigned_to) {
                Log::info('Ticket already assigned, skipping auto-assignment', [
                    'ticket_id' => $this->ticket->id,
                    'assigned_to' => $this->ticket->assigned_to,
                ]);

                return;
            }

            // Find best technician for assignment
            $technician = $this->findBestTechnician();

            if ($technician) {
                $this->ticket->update([
                    'assigned_to' => $technician->id,
                    'assigned_at' => now(),
                ]);

                Log::info('Ticket auto-assigned successfully', [
                    'ticket_id' => $this->ticket->id,
                    'assigned_to' => $technician->id,
                    'technician_name' => $technician->name,
                ]);
            } else {
                Log::warning('No suitable technician found for auto-assignment', [
                    'ticket_id' => $this->ticket->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Ticket auto-assignment failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find the best technician for ticket assignment.
     */
    protected function findBestTechnician()
    {
        // Get all active technicians for the company
        $technicians = \App\Models\User::forCompany($this->ticket->company_id)
            ->where('role', 'technician')
            ->where('is_active', true)
            ->get();

        if ($technicians->isEmpty()) {
            return null;
        }

        // Score technicians based on various factors
        $scored = $technicians->map(function ($technician) {
            return [
                'technician' => $technician,
                'score' => $this->calculateTechnicianScore($technician),
            ];
        });

        // Sort by score (highest first) and return best match
        $best = $scored->sortByDesc('score')->first();

        return $best['technician'];
    }

    /**
     * Calculate assignment score for a technician.
     */
    protected function calculateTechnicianScore($technician): int
    {
        $score = 0;

        // Factor 1: Current workload (fewer open tickets = higher score)
        $openTickets = Ticket::where('assigned_to', $technician->id)
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();

        $score += max(0, 20 - $openTickets); // Max 20 points, reduced by open tickets

        // Factor 2: Client familiarity (has worked on tickets for this client)
        $clientTickets = Ticket::where('assigned_to', $technician->id)
            ->where('client_id', $this->ticket->client_id)
            ->count();

        if ($clientTickets > 0) {
            $score += 15; // Bonus for client familiarity
        }

        // Factor 3: Specialization match
        $score += $this->getSpecializationScore($technician);

        // Factor 4: Availability (business hours, time zones)
        $score += $this->getAvailabilityScore($technician);

        // Factor 5: Priority handling preference
        if ($this->ticket->priority === 'Urgent' && $this->canHandleUrgent($technician)) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Get specialization score for technician.
     */
    protected function getSpecializationScore($technician): int
    {
        // This would check technician skills/specializations
        // For now, return a base score
        return 5;
    }

    /**
     * Get availability score for technician.
     */
    protected function getAvailabilityScore($technician): int
    {
        // Check if technician is in business hours, not on vacation, etc.
        // For now, return a base score
        return 5;
    }

    /**
     * Check if technician can handle urgent tickets.
     */
    protected function canHandleUrgent($technician): bool
    {
        // Check technician permissions/role for handling urgent tickets
        return true; // Simplified for now
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Auto-assignment job failed permanently', [
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
            'auto-assignment',
            'ticket:'.$this->ticket->id,
            'priority:'.$this->ticket->priority,
        ];
    }
}
