<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Services\TicketNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolution Service
 * 
 * Handles ticket resolution and reopening logic with proper validation,
 * notifications, and audit trail.
 */
class ResolutionService
{
    protected TicketNotificationService $notificationService;

    public function __construct(TicketNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Resolve a ticket
     * 
     * @param Ticket $ticket
     * @param User $resolver
     * @param string|null $summary
     * @param bool $allowClientReopen
     * @param array $options
     * @return bool
     */
    public function resolveTicket(
        Ticket $ticket,
        User $resolver,
        ?string $summary = null,
        bool $allowClientReopen = true,
        array $options = []
    ): bool {
        try {
            DB::beginTransaction();

            // Check if already resolved
            if ($ticket->is_resolved) {
                throw new \Exception('Ticket is already resolved');
            }

            // Check permissions
            if (!$resolver->can('resolve', $ticket)) {
                throw new \Exception('User does not have permission to resolve this ticket');
            }

            // Generate default summary if not provided
            if (empty($summary)) {
                $summary = $this->generateResolutionSummary($ticket, $options);
            }

            // Resolve the ticket
            $ticket->resolve($resolver, $summary, $allowClientReopen);

            // Update SLA metrics if applicable
            if ($ticket->priorityQueue) {
                $ticket->priorityQueue->update([
                    'resolution_met_sla' => now() <= $ticket->priorityQueue->sla_deadline,
                    'actual_resolution_at' => now(),
                ]);
            }

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy($resolver)
                ->withProperties([
                    'action' => 'resolved',
                    'summary' => $summary,
                    'allow_client_reopen' => $allowClientReopen,
                ])
                ->log('Ticket resolved');

            // Send notifications
            $this->notificationService->notifyTicketResolved($ticket, $resolver, $summary);

            DB::commit();

            Log::info('Ticket resolved', [
                'ticket_id' => $ticket->id,
                'resolver_id' => $resolver->id,
                'summary' => $summary,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to resolve ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Reopen a resolved ticket
     * 
     * @param Ticket $ticket
     * @param User $user
     * @param string|null $reason
     * @param array $options
     * @return bool
     */
    public function reopenTicket(
        Ticket $ticket,
        User $user,
        ?string $reason = null,
        array $options = []
    ): bool {
        try {
            DB::beginTransaction();

            // Validate ticket can be reopened
            if (!$ticket->canBeReopenedBy($user)) {
                throw new \Exception('You do not have permission to reopen this ticket');
            }

            // Generate default reason if not provided
            if (empty($reason)) {
                $reason = $this->generateReopenReason($user, $options);
            }

            // Reopen the ticket
            $ticket->reopen($user, $reason);

            // Re-assign to previous technician if available and not closed
            if ($options['reassign'] ?? true) {
                if ($ticket->assigned_to && $ticket->assignee->is_active) {
                    // Keep existing assignment
                } elseif ($ticket->resolved_by) {
                    // Assign to resolver
                    $ticket->update(['assigned_to' => $ticket->resolved_by]);
                }
            }

            // Update priority if needed
            if ($options['escalate'] ?? false) {
                $this->escalateReopenedTicket($ticket);
            }

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'reopened',
                    'reason' => $reason,
                    'previous_resolution' => $ticket->resolution_summary,
                ])
                ->log('Ticket reopened');

            // Send notifications
            $this->notificationService->notifyTicketReopened($ticket, $user, $reason);

            DB::commit();

            Log::info('Ticket reopened', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reopen ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Close a resolved ticket permanently
     * 
     * @param Ticket $ticket
     * @param User $user
     * @param string|null $note
     * @return bool
     */
    public function closeResolvedTicket(
        Ticket $ticket,
        User $user,
        ?string $note = null
    ): bool {
        try {
            DB::beginTransaction();

            // Check if ticket is resolved
            if (!$ticket->is_resolved) {
                throw new \Exception('Only resolved tickets can be closed');
            }

            // Check permissions
            if (!$user->can('close', $ticket)) {
                throw new \Exception('User does not have permission to close this ticket');
            }

            // Close the ticket
            $ticket->update([
                'status' => Ticket::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $user->id,
                'client_can_reopen' => false, // Closed tickets cannot be reopened
            ]);

            // Add closing comment
            $ticket->comments()->create([
                'company_id' => $ticket->company_id,
                'content' => $note ?: 'Ticket has been closed permanently.',
                'visibility' => 'internal',
                'source' => 'system',
                'author_id' => $user->id,
                'author_type' => 'user',
                'metadata' => ['action' => 'closed'],
            ]);

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'closed',
                    'note' => $note,
                ])
                ->log('Ticket closed');

            DB::commit();

            Log::info('Ticket closed', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to close ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Prevent client from reopening a ticket
     * 
     * @param Ticket $ticket
     * @param User $user
     * @param string|null $reason
     * @return bool
     */
    public function lockTicketFromReopening(
        Ticket $ticket,
        User $user,
        ?string $reason = null
    ): bool {
        try {
            // Check permissions
            if (!$user->can('manage', $ticket)) {
                throw new \Exception('User does not have permission to manage this ticket');
            }

            $ticket->update(['client_can_reopen' => false]);

            // Add internal note
            $ticket->comments()->create([
                'company_id' => $ticket->company_id,
                'content' => $reason ?: 'Client reopening has been disabled for this ticket.',
                'visibility' => 'internal',
                'source' => 'system',
                'author_id' => $user->id,
                'author_type' => 'user',
                'metadata' => ['action' => 'reopen_locked'],
            ]);

            Log::info('Ticket locked from reopening', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to lock ticket from reopening', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate default resolution summary
     * 
     * @param Ticket $ticket
     * @param array $options
     * @return string
     */
    protected function generateResolutionSummary(Ticket $ticket, array $options): string
    {
        $parts = ['Ticket has been resolved'];

        // Add time worked if available
        $totalHours = $ticket->timeEntries()->sum('hours_worked');
        if ($totalHours > 0) {
            $parts[] = sprintf('Total time: %.2f hours', $totalHours);
        }

        // Add resolution type if specified
        if (isset($options['resolution_type'])) {
            $parts[] = 'Resolution: ' . $options['resolution_type'];
        }

        return implode('. ', $parts) . '.';
    }

    /**
     * Generate default reopen reason
     * 
     * @param User $user
     * @param array $options
     * @return string
     */
    protected function generateReopenReason(User $user, array $options): string
    {
        if ($user->hasRole(['admin', 'manager', 'technician'])) {
            return 'Ticket reopened by support staff for further investigation.';
        }

        return 'Issue has reoccurred or was not fully resolved. Additional assistance needed.';
    }

    /**
     * Escalate a reopened ticket
     * 
     * @param Ticket $ticket
     * @return void
     */
    protected function escalateReopenedTicket(Ticket $ticket): void
    {
        // Increase priority if not already critical
        if ($ticket->priority !== Ticket::PRIORITY_CRITICAL) {
            $newPriority = match($ticket->priority) {
                Ticket::PRIORITY_LOW => Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_MEDIUM => Ticket::PRIORITY_HIGH,
                Ticket::PRIORITY_HIGH => Ticket::PRIORITY_CRITICAL,
                default => Ticket::PRIORITY_HIGH,
            };

            $ticket->update(['priority' => $newPriority]);

            // Add escalation note
            $ticket->comments()->create([
                'company_id' => $ticket->company_id,
                'content' => "Priority escalated to {$newPriority} due to ticket reopening.",
                'visibility' => 'internal',
                'source' => 'system',
                'author_type' => 'system',
                'metadata' => ['action' => 'priority_escalated', 'new_priority' => $newPriority],
            ]);
        }
    }

    /**
     * Get resolution statistics for a period
     * 
     * @param int $companyId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    public function getResolutionStats(int $companyId, $startDate, $endDate): array
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $resolved = $tickets->where('is_resolved', true);
        $reopened = $tickets->where('reopened_at', '!=', null);

        return [
            'total_tickets' => $tickets->count(),
            'resolved_count' => $resolved->count(),
            'resolution_rate' => $tickets->count() > 0 
                ? round(($resolved->count() / $tickets->count()) * 100, 2) 
                : 0,
            'reopened_count' => $reopened->count(),
            'reopen_rate' => $resolved->count() > 0
                ? round(($reopened->count() / $resolved->count()) * 100, 2)
                : 0,
            'avg_resolution_time_hours' => $resolved->avg(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->resolved_at);
            }),
        ];
    }
}