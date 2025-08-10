<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Priority Queue Model
 * 
 * Manages ticket prioritization with automated escalation rules,
 * SLA tracking, and team-based assignment queues.
 */
class TicketPriorityQueue extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'ticket_id',
        'tenant_id',
        'queue_position',
        'priority_score',
        'escalation_level',
        'assigned_team',
        'sla_deadline',
        'escalated_at',
        'escalation_rules',
        'escalation_reason',
        'is_active',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'tenant_id' => 'integer',
        'queue_position' => 'integer',
        'priority_score' => 'decimal:2',
        'escalation_level' => 'integer',
        'sla_deadline' => 'datetime',
        'escalated_at' => 'datetime',
        'escalation_rules' => 'array',
        'is_active' => 'boolean',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if this ticket is overdue based on SLA
     */
    public function isOverdue(): bool
    {
        return $this->sla_deadline && now()->gt($this->sla_deadline);
    }

    /**
     * Get time remaining until SLA deadline
     */
    public function getTimeRemainingMinutes(): ?int
    {
        if (!$this->sla_deadline) {
            return null;
        }

        return now()->diffInMinutes($this->sla_deadline, false);
    }

    /**
     * Get hours remaining until SLA deadline
     */
    public function getTimeRemainingHours(): ?float
    {
        $minutes = $this->getTimeRemainingMinutes();
        return $minutes ? round($minutes / 60, 1) : null;
    }

    /**
     * Get SLA status string
     */
    public function getSlaStatus(): string
    {
        if (!$this->sla_deadline) {
            return 'No SLA';
        }

        $hoursRemaining = $this->getTimeRemainingHours();
        
        if ($hoursRemaining < 0) {
            return 'Overdue';
        } elseif ($hoursRemaining < 2) {
            return 'Critical';
        } elseif ($hoursRemaining < 8) {
            return 'Warning';
        } else {
            return 'On Track';
        }
    }

    /**
     * Get SLA status color for UI
     */
    public function getSlaStatusColor(): string
    {
        return match ($this->getSlaStatus()) {
            'Overdue' => '#dc3545',     // Red
            'Critical' => '#fd7e14',    // Orange
            'Warning' => '#ffc107',     // Yellow
            'On Track' => '#28a745',    // Green
            default => '#6c757d',       // Gray
        };
    }

    /**
     * Check if ticket should be escalated
     */
    public function shouldEscalate(): bool
    {
        if (!$this->escalation_rules || !$this->is_active) {
            return false;
        }

        foreach ($this->escalation_rules as $rule) {
            if ($this->evaluateEscalationRule($rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate a single escalation rule
     */
    private function evaluateEscalationRule(array $rule): bool
    {
        switch ($rule['type']) {
            case 'sla_breach':
                return $this->isOverdue();

            case 'time_since_update':
                if (!isset($rule['hours'])) return false;
                $lastUpdate = $this->ticket->updated_at;
                return now()->diffInHours($lastUpdate) >= $rule['hours'];

            case 'priority_age':
                if (!isset($rule['hours']) || !isset($rule['priority'])) return false;
                $isHighPriority = in_array($this->ticket->priority, (array) $rule['priority']);
                $ageInHours = $this->ticket->getAgeInHours();
                return $isHighPriority && $ageInHours >= $rule['hours'];

            case 'no_assignment':
                if (!isset($rule['hours'])) return false;
                $hasAssignment = !is_null($this->ticket->assigned_to);
                $ageInHours = $this->ticket->getAgeInHours();
                return !$hasAssignment && $ageInHours >= $rule['hours'];

            default:
                return false;
        }
    }

    /**
     * Escalate this ticket
     */
    public function escalate(string $reason = null): void
    {
        $this->update([
            'escalation_level' => $this->escalation_level + 1,
            'escalated_at' => now(),
            'escalation_reason' => $reason,
            'priority_score' => $this->priority_score * 1.5, // Boost priority
        ]);

        // Reorder queue positions
        $this->reorderQueue();
    }

    /**
     * Move this ticket up in the queue
     */
    public function moveUp(int $positions = 1): void
    {
        $newPosition = max(1, $this->queue_position - $positions);
        $this->moveToPosition($newPosition);
    }

    /**
     * Move this ticket down in the queue
     */
    public function moveDown(int $positions = 1): void
    {
        $maxPosition = self::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->max('queue_position') ?? 1;
        
        $newPosition = min($maxPosition, $this->queue_position + $positions);
        $this->moveToPosition($newPosition);
    }

    /**
     * Move to specific queue position
     */
    public function moveToPosition(int $position): void
    {
        $oldPosition = $this->queue_position;
        
        // Temporarily set to a high number to avoid conflicts
        $this->update(['queue_position' => 999999]);
        
        if ($position < $oldPosition) {
            // Moving up - shift others down
            self::where('tenant_id', $this->tenant_id)
                ->where('is_active', true)
                ->whereBetween('queue_position', [$position, $oldPosition - 1])
                ->increment('queue_position');
        } elseif ($position > $oldPosition) {
            // Moving down - shift others up
            self::where('tenant_id', $this->tenant_id)
                ->where('is_active', true)
                ->whereBetween('queue_position', [$oldPosition + 1, $position])
                ->decrement('queue_position');
        }
        
        // Set final position
        $this->update(['queue_position' => $position]);
    }

    /**
     * Reorder entire queue based on priority scores
     */
    public function reorderQueue(): void
    {
        $queueItems = self::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($queueItems as $index => $item) {
            $item->update(['queue_position' => $index + 1]);
        }
    }

    /**
     * Remove from queue
     */
    public function removeFromQueue(): void
    {
        $this->update(['is_active' => false]);
        
        // Shift remaining items up
        self::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->where('queue_position', '>', $this->queue_position)
            ->decrement('queue_position');
    }

    /**
     * Get queue statistics for tenant
     */
    public static function getQueueStats(int $tenantId): array
    {
        $queue = self::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('ticket')
            ->get();

        return [
            'total_tickets' => $queue->count(),
            'overdue_tickets' => $queue->filter(fn($item) => $item->isOverdue())->count(),
            'high_priority_tickets' => $queue->filter(fn($item) => $item->ticket->isHighPriority())->count(),
            'escalated_tickets' => $queue->filter(fn($item) => $item->escalation_level > 0)->count(),
            'average_priority_score' => $queue->avg('priority_score'),
            'oldest_ticket_age' => $queue->min(fn($item) => $item->ticket->getAgeInHours()),
        ];
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now());
    }

    public function scopeByTeam($query, string $team)
    {
        return $query->where('assigned_team', $team);
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalation_level', '>', 0);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority_score', '>', 3);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('queue_position');
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    /**
     * Add ticket to priority queue
     */
    public static function addTicket(Ticket $ticket, array $options = []): self
    {
        // Get next queue position
        $nextPosition = self::where('tenant_id', $ticket->tenant_id)
            ->where('is_active', true)
            ->max('queue_position') + 1;

        return self::create(array_merge([
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'queue_position' => $nextPosition,
            'priority_score' => $ticket->calculatePriorityScore(),
            'is_active' => true,
        ], $options));
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'queue_position' => 'required|integer|min:1',
            'priority_score' => 'required|numeric|min:0',
            'escalation_level' => 'integer|min:0',
            'assigned_team' => 'nullable|string|max:100',
            'sla_deadline' => 'nullable|date',
            'escalation_rules' => 'nullable|array',
            'escalation_reason' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Auto-assign queue position for new entries
        static::creating(function ($queueItem) {
            if (!$queueItem->queue_position) {
                $queueItem->queue_position = self::where('tenant_id', $queueItem->tenant_id)
                    ->where('is_active', true)
                    ->max('queue_position') + 1;
            }
        });
    }
}