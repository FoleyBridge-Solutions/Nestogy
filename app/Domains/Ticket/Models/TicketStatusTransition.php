<?php

namespace App\Domains\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Status Transition Model
 * 
 * Represents individual transition rules within a workflow,
 * defining how tickets can move from one status to another.
 */
class TicketStatusTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'from_status',
        'to_status',
        'required_role',
        'requires_comment',
        'auto_assign_rule',
        'conditions',
        'actions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'workflow_id' => 'integer',
        'requires_comment' => 'boolean',
        'auto_assign_rule' => 'array',
        'conditions' => 'array',
        'actions' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(TicketWorkflow::class, 'workflow_id');
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if the conditions for this transition are met
     */
    public function conditionsAreMet(Ticket $ticket, $user = null): bool
    {
        if (!$this->conditions) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $ticket, $user)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, Ticket $ticket, $user): bool
    {
        switch ($condition['type']) {
            case 'ticket_age_hours':
                $ageInHours = $ticket->getAgeInHours();
                return $this->evaluateComparison($ageInHours, $condition['operator'], $condition['value']);

            case 'ticket_priority':
                return $this->evaluateComparison($ticket->priority, $condition['operator'], $condition['value']);

            case 'time_worked':
                $timeWorked = $ticket->getTotalTimeWorked();
                return $this->evaluateComparison($timeWorked, $condition['operator'], $condition['value']);

            case 'user_role':
                return $user && $user->hasRole($condition['value']);

            case 'business_hours':
                return $this->isBusinessHours($condition['timezone'] ?? 'UTC');

            default:
                return true;
        }
    }

    /**
     * Evaluate comparison operators
     */
    private function evaluateComparison($actual, string $operator, $expected): bool
    {
        switch ($operator) {
            case 'eq':
            case '=':
                return $actual == $expected;
            case 'ne':
            case '!=':
                return $actual != $expected;
            case 'gt':
            case '>':
                return $actual > $expected;
            case 'gte':
            case '>=':
                return $actual >= $expected;
            case 'lt':
            case '<':
                return $actual < $expected;
            case 'lte':
            case '<=':
                return $actual <= $expected;
            case 'in':
                return in_array($actual, (array) $expected);
            case 'not_in':
                return !in_array($actual, (array) $expected);
            default:
                return false;
        }
    }

    /**
     * Check if current time is within business hours
     */
    private function isBusinessHours(string $timezone): bool
    {
        $now = now($timezone);
        $hour = $now->hour;
        $dayOfWeek = $now->dayOfWeek;

        // Default business hours: Monday-Friday, 9 AM - 5 PM
        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 17;
    }

    /**
     * Get human-readable description of this transition
     */
    public function getDescription(): string
    {
        $description = "Move from '{$this->from_status}' to '{$this->to_status}'";

        if ($this->required_role) {
            $description .= " (requires {$this->required_role} role)";
        }

        if ($this->requires_comment) {
            $description .= " (comment required)";
        }

        return $description;
    }

    /**
     * Get action summary for display
     */
    public function getActionsSummary(): string
    {
        if (!$this->actions) {
            return 'No actions';
        }

        $actionTypes = array_map(function($action) {
            return $action['type'] ?? 'unknown';
        }, $this->actions);

        return implode(', ', $actionTypes);
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromStatus($query, string $status)
    {
        return $query->where('from_status', $status);
    }

    public function scopeToStatus($query, string $status)
    {
        return $query->where('to_status', $status);
    }

    public function scopeRequiringRole($query, string $role)
    {
        return $query->where('required_role', $role);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getAvailableRoles(): array
    {
        return [
            'user',
            'technician',
            'manager',
            'admin',
        ];
    }

    public static function getAvailableActions(): array
    {
        return [
            'send_notification' => 'Send Notification',
            'add_comment' => 'Add Comment',
            'update_priority' => 'Update Priority',
            'set_due_date' => 'Set Due Date',
            'assign_user' => 'Assign User',
            'add_to_queue' => 'Add to Priority Queue',
        ];
    }

    public static function getAvailableOperators(): array
    {
        return [
            'eq' => 'Equal to',
            'ne' => 'Not equal to',
            'gt' => 'Greater than',
            'gte' => 'Greater than or equal to',
            'lt' => 'Less than',
            'lte' => 'Less than or equal to',
            'in' => 'In list',
            'not_in' => 'Not in list',
        ];
    }

    public static function getAvailableConditionTypes(): array
    {
        return [
            'ticket_age_hours' => 'Ticket age (hours)',
            'ticket_priority' => 'Ticket priority',
            'time_worked' => 'Time worked (hours)',
            'user_role' => 'User role',
            'business_hours' => 'Business hours',
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'workflow_id' => 'required|exists:ticket_workflows,id',
            'from_status' => 'required|string|max:50',
            'to_status' => 'required|string|max:50',
            'required_role' => 'nullable|string|max:50',
            'requires_comment' => 'boolean',
            'auto_assign_rule' => 'nullable|array',
            'conditions' => 'nullable|array',
            'actions' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}