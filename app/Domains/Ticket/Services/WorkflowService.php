<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketStatusTransition;
use App\Domains\Ticket\Models\TicketWorkflow;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Workflow Service
 *
 * Handles workflow automation, status transitions, condition evaluation,
 * and automated actions for comprehensive ticket workflow management.
 */
class WorkflowService
{
    /**
     * Execute a workflow transition for a ticket
     */
    public function executeTransition(
        Ticket $ticket,
        TicketStatusTransition $transition,
        array $context = []
    ): bool {
        // Verify the transition is valid for current ticket state
        if (! $this->canExecuteTransition($ticket, $transition)) {
            Log::warning('Invalid transition attempted', [
                'ticket_id' => $ticket->id,
                'transition_id' => $transition->id,
                'current_status' => $ticket->status,
                'from_status' => $transition->from_status,
            ]);

            return false;
        }

        // Evaluate conditions
        if (! $this->evaluateConditions($transition->conditions ?? [], $ticket, $context)) {
            Log::info('Transition conditions not met', [
                'ticket_id' => $ticket->id,
                'transition_id' => $transition->id,
            ]);

            return false;
        }

        try {
            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->update(['status' => $transition->to_status]);

            // Execute transition actions
            $this->executeActions($transition->actions ?? [], $ticket, $context);

            // Log the transition
            $this->logTransition($ticket, $transition, $oldStatus, $context);

            // Add note to ticket
            $ticket->addNote(
                "Workflow transition: {$transition->name} (from {$oldStatus} to {$transition->to_status})",
                'workflow'
            );

            Log::info('Workflow transition executed successfully', [
                'ticket_id' => $ticket->id,
                'transition_id' => $transition->id,
                'from_status' => $oldStatus,
                'to_status' => $transition->to_status,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute workflow transition', [
                'ticket_id' => $ticket->id,
                'transition_id' => $transition->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get available transitions for a ticket
     */
    public function getAvailableTransitions(Ticket $ticket): Collection
    {
        if (! $ticket->workflow) {
            return collect();
        }

        return $ticket->workflow->transitions()
            ->where('from_status', $ticket->status)
            ->get()
            ->filter(function ($transition) use ($ticket) {
                return $this->canExecuteTransition($ticket, $transition);
            });
    }

    /**
     * Auto-execute automatic transitions for tickets
     */
    public function processAutomaticTransitions(): array
    {
        $results = [
            'processed' => 0,
            'executed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get tickets with workflows that have automatic transitions
        $tickets = Ticket::whereNotNull('workflow_id')
            ->whereIn('status', function ($query) {
                $query->select('from_status')
                    ->from('ticket_status_transitions')
                    ->where('is_automatic', true)
                    ->distinct();
            })
            ->with(['workflow.transitions'])
            ->get();

        foreach ($tickets as $ticket) {
            $results['processed']++;

            try {
                $automaticTransitions = $ticket->workflow->transitions()
                    ->where('from_status', $ticket->status)
                    ->where('is_automatic', true)
                    ->get();

                foreach ($automaticTransitions as $transition) {
                    if ($this->executeTransition($ticket, $transition)) {
                        $results['executed']++;
                        break; // Only execute one automatic transition per cycle
                    }
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Evaluate workflow conditions
     */
    public function evaluateConditions(array $conditions, Ticket $ticket, array $context = []): bool
    {
        if (empty($conditions)) {
            return true; // No conditions means always pass
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $ticket, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Preview workflow actions without executing them
     */
    public function previewActions(array $actions, Ticket $ticket, array $context = []): array
    {
        $preview = [];

        foreach ($actions as $action) {
            $preview[] = $this->previewAction($action, $ticket, $context);
        }

        return $preview;
    }

    /**
     * Duplicate a workflow with all its transitions
     */
    public function duplicateWorkflow(TicketWorkflow $workflow, string $newName): TicketWorkflow
    {
        $duplicate = $workflow->replicate([
            'created_at',
            'updated_at',
        ]);

        $duplicate->name = $newName;
        $duplicate->is_active = false; // Start as inactive
        $duplicate->created_by = auth()->id();
        $duplicate->save();

        // Duplicate transitions
        foreach ($workflow->transitions as $transition) {
            $duplicateTransition = $transition->replicate([
                'id',
                'workflow_id',
                'created_at',
                'updated_at',
            ]);

            $duplicateTransition->workflow_id = $duplicate->id;
            $duplicateTransition->save();
        }

        return $duplicate;
    }

    /**
     * Validate workflow configuration
     */
    public function validateWorkflow(TicketWorkflow $workflow): array
    {
        $issues = [];

        // Check for orphaned statuses
        $allStatuses = $workflow->transitions->pluck('from_status')
            ->merge($workflow->transitions->pluck('to_status'))
            ->unique();

        if (! $allStatuses->contains($workflow->initial_status)) {
            $issues[] = "Initial status '{$workflow->initial_status}' is not referenced in any transitions";
        }

        // Check for unreachable final statuses
        $reachableStatuses = $workflow->transitions->pluck('to_status')->unique();
        foreach ($workflow->final_statuses as $finalStatus) {
            if (! $reachableStatuses->contains($finalStatus)) {
                $issues[] = "Final status '{$finalStatus}' is not reachable from any transition";
            }
        }

        // Check for circular dependencies
        if ($this->hasCircularDependencies($workflow)) {
            $issues[] = 'Workflow contains circular dependencies that may cause infinite loops';
        }

        // Validate transition actions and conditions
        foreach ($workflow->transitions as $transition) {
            $actionIssues = $this->validateActions($transition->actions ?? []);
            $conditionIssues = $this->validateConditions($transition->conditions ?? []);

            $issues = array_merge($issues, $actionIssues, $conditionIssues);
        }

        return $issues;
    }

    /**
     * Check if a transition can be executed for a ticket
     */
    private function canExecuteTransition(Ticket $ticket, TicketStatusTransition $transition): bool
    {
        // Check basic status match
        if ($ticket->status !== $transition->from_status) {
            return false;
        }

        // Check required role if specified
        if ($transition->required_role && ! auth()->user()->hasRole($transition->required_role)) {
            return false;
        }

        // Check if user can update ticket
        if (! auth()->user()->can('update', $ticket)) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, Ticket $ticket, array $context = []): bool
    {
        $type = $condition['type'] ?? '';
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        switch ($type) {
            case 'field_equals':
                return $this->getTicketFieldValue($ticket, $field) == $value;

            case 'field_not_equals':
                return $this->getTicketFieldValue($ticket, $field) != $value;

            case 'field_contains':
                $fieldValue = $this->getTicketFieldValue($ticket, $field);

                return is_string($fieldValue) && str_contains($fieldValue, $value);

            case 'field_greater_than':
                return $this->getTicketFieldValue($ticket, $field) > $value;

            case 'field_less_than':
                return $this->getTicketFieldValue($ticket, $field) < $value;

            case 'has_tag':
                return in_array($value, $ticket->tags ?? []);

            case 'missing_tag':
                return ! in_array($value, $ticket->tags ?? []);

            case 'assigned_to':
                return $ticket->assigned_to == $value;

            case 'created_by':
                return $ticket->created_by == $value;

            case 'age_greater_than':
                return $ticket->created_at->diffInHours(now()) > $value;

            case 'age_less_than':
                return $ticket->created_at->diffInHours(now()) < $value;

            case 'priority_equals':
                return $ticket->priority === $value;

            case 'client_type':
                return $ticket->client->type === $value;

            case 'business_hours':
                return $this->isBusinessHours();

            default:
                Log::warning('Unknown condition type', ['type' => $type]);

                return false;
        }
    }

    /**
     * Execute workflow actions
     */
    private function executeActions(array $actions, Ticket $ticket, array $context = []): void
    {
        foreach ($actions as $action) {
            $this->executeAction($action, $ticket, $context);
        }
    }

    /**
     * Execute a single action
     */
    private function executeAction(array $action, Ticket $ticket, array $context = []): void
    {
        $type = $action['type'] ?? '';
        $value = $action['value'] ?? '';

        try {
            switch ($type) {
                case 'assign_to_user':
                    if (is_numeric($value)) {
                        $ticket->update(['assigned_to' => $value]);
                        $ticket->addNote("Auto-assigned to user ID {$value}", 'workflow');
                    }
                    break;

                case 'set_priority':
                    $ticket->update(['priority' => $value]);
                    $ticket->addNote("Priority auto-changed to {$value}", 'workflow');
                    break;

                case 'add_tag':
                    $tags = $ticket->tags ?? [];
                    if (! in_array($value, $tags)) {
                        $tags[] = $value;
                        $ticket->update(['tags' => $tags]);
                        $ticket->addNote("Tag '{$value}' auto-added", 'workflow');
                    }
                    break;

                case 'remove_tag':
                    $tags = $ticket->tags ?? [];
                    $tags = array_filter($tags, fn ($tag) => $tag !== $value);
                    $ticket->update(['tags' => array_values($tags)]);
                    $ticket->addNote("Tag '{$value}' auto-removed", 'workflow');
                    break;

                case 'update_field':
                    $field = $action['field'] ?? '';
                    if ($field && $this->isUpdatableField($field)) {
                        $ticket->update([$field => $value]);
                        $ticket->addNote("Field '{$field}' auto-updated to '{$value}'", 'workflow');
                    }
                    break;

                case 'add_note':
                    $ticket->addNote($value, 'workflow');
                    break;

                case 'send_email':
                    // TODO: Implement email sending
                    $ticket->addNote("Email notification sent: {$value}", 'workflow');
                    break;

                case 'escalate':
                    // TODO: Implement escalation logic
                    $ticket->addNote("Ticket escalated: {$value}", 'workflow');
                    break;

                default:
                    Log::warning('Unknown action type', ['type' => $type]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to execute workflow action', [
                'ticket_id' => $ticket->id,
                'action_type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Preview a single action
     */
    private function previewAction(array $action, Ticket $ticket, array $context = []): array
    {
        $type = $action['type'] ?? '';
        $value = $action['value'] ?? '';

        switch ($type) {
            case 'assign_to_user':
                $user = User::find($value);

                return [
                    'type' => $type,
                    'description' => 'Assign to user: '.($user ? $user->name : "User ID {$value}"),
                    'current_value' => $ticket->assignee?->name ?? 'Unassigned',
                    'new_value' => $user?->name ?? "User ID {$value}",
                ];

            case 'set_priority':
                return [
                    'type' => $type,
                    'description' => "Set priority to: {$value}",
                    'current_value' => $ticket->priority,
                    'new_value' => $value,
                ];

            case 'add_tag':
                return [
                    'type' => $type,
                    'description' => "Add tag: {$value}",
                    'current_value' => implode(', ', $ticket->tags ?? []),
                    'new_value' => implode(', ', array_unique(array_merge($ticket->tags ?? [], [$value]))),
                ];

            default:
                return [
                    'type' => $type,
                    'description' => "Execute action: {$type}",
                    'current_value' => '',
                    'new_value' => $value,
                ];
        }
    }

    /**
     * Get ticket field value for condition evaluation
     */
    private function getTicketFieldValue(Ticket $ticket, string $field)
    {
        return match ($field) {
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'assigned_to' => $ticket->assigned_to,
            'client_id' => $ticket->client_id,
            'estimated_hours' => $ticket->estimated_hours,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
            default => $ticket->$field ?? null
        };
    }

    /**
     * Check if it's currently business hours
     */
    private function isBusinessHours(): bool
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $hour = $now->hour;

        // Business hours: Monday to Friday, 9 AM to 5 PM
        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 17;
    }

    /**
     * Check if a field can be updated by workflow actions
     */
    private function isUpdatableField(string $field): bool
    {
        $allowedFields = [
            'priority', 'status', 'assigned_to', 'due_date',
            'estimated_hours', 'tags', 'custom_fields',
        ];

        return in_array($field, $allowedFields);
    }

    /**
     * Check for circular dependencies in workflow
     */
    private function hasCircularDependencies(TicketWorkflow $workflow): bool
    {
        // Build adjacency list
        $graph = [];
        foreach ($workflow->transitions as $transition) {
            $graph[$transition->from_status][] = $transition->to_status;
        }

        // Check for cycles using DFS
        $visited = [];
        $recursionStack = [];

        foreach ($graph as $status => $transitions) {
            if ($this->hasCycleDFS($status, $graph, $visited, $recursionStack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * DFS helper for cycle detection
     */
    private function hasCycleDFS(string $status, array $graph, array &$visited, array &$recursionStack): bool
    {
        $visited[$status] = true;
        $recursionStack[$status] = true;

        if (isset($graph[$status])) {
            foreach ($graph[$status] as $nextStatus) {
                if (! isset($visited[$nextStatus])) {
                    if ($this->hasCycleDFS($nextStatus, $graph, $visited, $recursionStack)) {
                        return true;
                    }
                } elseif (isset($recursionStack[$nextStatus])) {
                    return true;
                }
            }
        }

        unset($recursionStack[$status]);

        return false;
    }

    /**
     * Validate workflow actions
     */
    private function validateActions(array $actions): array
    {
        $issues = [];

        foreach ($actions as $index => $action) {
            $type = $action['type'] ?? '';
            if (empty($type)) {
                $issues[] = "Action {$index} is missing type";

                continue;
            }

            switch ($type) {
                case 'assign_to_user':
                    if (empty($action['value']) || ! is_numeric($action['value'])) {
                        $issues[] = "Action {$index} requires a valid user ID";
                    }
                    break;

                case 'set_priority':
                    $validPriorities = ['Low', 'Medium', 'High', 'Critical'];
                    if (! in_array($action['value'] ?? '', $validPriorities)) {
                        $issues[] = "Action {$index} has invalid priority value";
                    }
                    break;
            }
        }

        return $issues;
    }

    /**
     * Validate workflow conditions
     */
    private function validateConditions(array $conditions): array
    {
        $issues = [];

        foreach ($conditions as $index => $condition) {
            $type = $condition['type'] ?? '';
            if (empty($type)) {
                $issues[] = "Condition {$index} is missing type";
            }
        }

        return $issues;
    }

    /**
     * Log workflow transition
     */
    private function logTransition(
        Ticket $ticket,
        TicketStatusTransition $transition,
        string $oldStatus,
        array $context
    ): void {
        Log::info('Workflow transition logged', [
            'ticket_id' => $ticket->id,
            'workflow_id' => $ticket->workflow_id,
            'transition_id' => $transition->id,
            'transition_name' => $transition->name,
            'from_status' => $oldStatus,
            'to_status' => $transition->to_status,
            'executed_by' => auth()->id(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
