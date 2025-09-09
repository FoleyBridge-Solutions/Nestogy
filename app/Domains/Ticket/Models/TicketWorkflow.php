<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket Workflow Model
 * 
 * Represents workflow definitions that control ticket status transitions,
 * assignment rules, and automated actions.
 */
class TicketWorkflow extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_default',
        'is_active',
        'allowed_statuses',
        'initial_status',
        'configuration',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'allowed_statuses' => 'array',
        'configuration' => 'array',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function statusTransitions(): HasMany
    {
        return $this->hasMany(TicketStatusTransition::class, 'workflow_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'workflow_id');
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Get available transitions from a given status
     */
    public function getAvailableTransitions(string $fromStatus): array
    {
        return $this->statusTransitions()
            ->where('from_status', $fromStatus)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Check if a status transition is allowed
     */
    public function canTransition(string $fromStatus, string $toStatus, $user = null): bool
    {
        $transition = $this->statusTransitions()
            ->where('from_status', $fromStatus)
            ->where('to_status', $toStatus)
            ->where('is_active', true)
            ->first();

        if (!$transition) {
            return false;
        }

        // Check role requirements
        if ($transition->required_role && $user) {
            if (!$user->hasRole($transition->required_role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute workflow transition with actions
     */
    public function executeTransition(Ticket $ticket, string $toStatus, array $context = []): bool
    {
        $transition = $this->statusTransitions()
            ->where('from_status', $ticket->status)
            ->where('to_status', $toStatus)
            ->where('is_active', true)
            ->first();

        if (!$transition) {
            return false;
        }

        // Update ticket status
        $ticket->update(['status' => $toStatus]);

        // Execute automated actions
        if ($transition->actions) {
            $this->executeActions($ticket, $transition->actions, $context);
        }

        // Handle auto-assignment
        if ($transition->auto_assign_rule) {
            $this->handleAutoAssignment($ticket, $transition->auto_assign_rule);
        }

        return true;
    }

    /**
     * Execute automated actions defined in transition
     */
    private function executeActions(Ticket $ticket, array $actions, array $context): void
    {
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'send_notification':
                    $this->sendNotificationAction($ticket, $action, $context);
                    break;
                
                case 'add_comment':
                    $this->addCommentAction($ticket, $action, $context);
                    break;
                
                case 'update_priority':
                    $this->updatePriorityAction($ticket, $action);
                    break;
                
                case 'set_due_date':
                    $this->setDueDateAction($ticket, $action);
                    break;
            }
        }
    }

    /**
     * Handle auto-assignment rules
     */
    private function handleAutoAssignment(Ticket $ticket, array $rules): void
    {
        // Simple round-robin assignment
        if ($rules['type'] === 'round_robin' && isset($rules['user_ids'])) {
            $userIds = $rules['user_ids'];
            $lastAssigned = $this->getLastAssignedUser($userIds);
            $nextIndex = (array_search($lastAssigned, $userIds) + 1) % count($userIds);
            $ticket->update(['assigned_to' => $userIds[$nextIndex]]);
        }
        
        // Load balancing assignment
        elseif ($rules['type'] === 'load_balance' && isset($rules['user_ids'])) {
            $userIds = $rules['user_ids'];
            $assignments = Ticket::whereIn('assigned_to', $userIds)
                ->open()
                ->groupBy('assigned_to')
                ->selectRaw('assigned_to, count(*) as ticket_count')
                ->pluck('ticket_count', 'assigned_to')
                ->toArray();

            // Find user with least tickets
            $minCount = PHP_INT_MAX;
            $selectedUser = $userIds[0];
            
            foreach ($userIds as $userId) {
                $count = $assignments[$userId] ?? 0;
                if ($count < $minCount) {
                    $minCount = $count;
                    $selectedUser = $userId;
                }
            }
            
            $ticket->update(['assigned_to' => $selectedUser]);
        }
    }

    /**
     * Get the last assigned user for round-robin
     */
    private function getLastAssignedUser(array $userIds): int
    {
        $lastTicket = Ticket::whereIn('assigned_to', $userIds)
            ->latest('updated_at')
            ->first();

        return $lastTicket ? $lastTicket->assigned_to : $userIds[0];
    }

    /**
     * Send notification action
     */
    private function sendNotificationAction(Ticket $ticket, array $action, array $context): void
    {
        // Implementation would integrate with notification system
        // This is a placeholder for the notification logic
    }

    /**
     * Add comment action
     */
    private function addCommentAction(Ticket $ticket, array $action, array $context): void
    {
        $message = $action['message'] ?? 'Status changed automatically by workflow';
        
        // Replace placeholders in message
        $message = str_replace([
            '{{old_status}}',
            '{{new_status}}',
            '{{user_name}}',
        ], [
            $context['old_status'] ?? '',
            $ticket->status,
            auth()->user()->name ?? 'System',
        ], $message);

        $ticket->replies()->create([
            'reply' => $message,
            'type' => 'internal',
            'replied_by' => auth()->id() ?? null,
        ]);
    }

    /**
     * Update priority action
     */
    private function updatePriorityAction(Ticket $ticket, array $action): void
    {
        if (isset($action['priority'])) {
            $ticket->update(['priority' => $action['priority']]);
        }
    }

    /**
     * Set due date action
     */
    private function setDueDateAction(Ticket $ticket, array $action): void
    {
        if (isset($action['hours_from_now'])) {
            $dueDate = now()->addHours($action['hours_from_now']);
            
            // Update or create priority queue entry with SLA deadline
            $ticket->priorityQueue()->updateOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'sla_deadline' => $dueDate,
                    'company_id' => $ticket->company_id,
                ]
            );
        }
    }

    /**
     * Create a copy of this workflow
     */
    public function duplicate(string $newName = null): self
    {
        $copy = $this->replicate();
        $copy->name = $newName ?? ($this->name . ' (Copy)');
        $copy->is_default = false;
        $copy->is_active = false;
        $copy->save();

        // Copy all transitions
        foreach ($this->statusTransitions as $transition) {
            $transitionCopy = $transition->replicate();
            $transitionCopy->workflow_id = $copy->id;
            $transitionCopy->save();
        }

        return $copy;
    }

    /**
     * Get workflow statistics
     */
    public function getStats(): array
    {
        $totalTickets = $this->tickets()->count();
        $openTickets = $this->tickets()->open()->count();
        $closedTickets = $this->tickets()->closed()->count();

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'closed_tickets' => $closedTickets,
            'completion_rate' => $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 2) : 0,
            'transitions_count' => $this->statusTransitions()->count(),
        ];
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getDefaultStatuses(): array
    {
        return [
            'Open',
            'In Progress',
            'Waiting',
            'On Hold',
            'Resolved',
            'Closed',
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'allowed_statuses' => 'nullable|array',
            'initial_status' => 'nullable|string|max:50',
            'configuration' => 'nullable|array',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Ensure only one default workflow per tenant
        static::saving(function ($workflow) {
            if ($workflow->is_default) {
                static::where('company_id', $workflow->company_id)
                    ->where('id', '!=', $workflow->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}