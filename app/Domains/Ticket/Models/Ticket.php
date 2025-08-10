<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasArchive;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Asset;
use App\Models\Vendor;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

/**
 * Enhanced Ticket Model for Domain-Based Architecture
 * 
 * Represents support tickets with comprehensive functionality including
 * templates, recurring tickets, workflows, time tracking, and calendar integration.
 */
class Ticket extends Model
{
    use HasFactory, BelongsToCompany, HasArchive;

    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'source',
        'category',
        'subject',
        'details',
        'priority',
        'status',
        'billable',
        'scheduled_at',
        'onsite',
        'vendor_ticket_number',
        'feedback',
        'closed_at',
        'created_by',
        'assigned_to',
        'closed_by',
        'vendor_id',
        'client_id',
        'contact_id',
        'location_id',
        'asset_id',
        'invoice_id',
        'project_id',
        'template_id',
        'recurring_ticket_id',
        'workflow_id',
    ];

    protected $casts = [
        'number' => 'integer',
        'billable' => 'boolean',
        'onsite' => 'boolean',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_by' => 'integer',
        'assigned_to' => 'integer',
        'closed_by' => 'integer',
        'vendor_id' => 'integer',
        'client_id' => 'integer',
        'contact_id' => 'integer',
        'location_id' => 'integer',
        'asset_id' => 'integer',
        'invoice_id' => 'integer',
        'project_id' => 'integer',
        'template_id' => 'integer',
        'recurring_ticket_id' => 'integer',
        'workflow_id' => 'integer',
    ];

    /**
     * Priority constants
     */
    const PRIORITY_LOW = 'Low';
    const PRIORITY_MEDIUM = 'Medium';
    const PRIORITY_HIGH = 'High';
    const PRIORITY_CRITICAL = 'Critical';

    /**
     * Status constants
     */
    const STATUS_OPEN = 'Open';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_RESOLVED = 'Resolved';
    const STATUS_CLOSED = 'Closed';
    const STATUS_ON_HOLD = 'On Hold';
    const STATUS_WAITING = 'Waiting';

    /**
     * Source constants
     */
    const SOURCE_EMAIL = 'Email';
    const SOURCE_PHONE = 'Phone';
    const SOURCE_PORTAL = 'Portal';
    const SOURCE_WALK_IN = 'Walk-in';
    const SOURCE_INTERNAL = 'Internal';

    // ===========================================
    // EXISTING RELATIONSHIPS
    // ===========================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TicketWatcher::class);
    }

    // ===========================================
    // NEW DOMAIN RELATIONSHIPS
    // ===========================================

    public function template(): BelongsTo
    {
        return $this->belongsTo(TicketTemplate::class, 'template_id');
    }

    public function recurringTicket(): BelongsTo
    {
        return $this->belongsTo(RecurringTicket::class, 'recurring_ticket_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(TicketWorkflow::class, 'workflow_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TicketTimeEntry::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(TicketCalendarEvent::class);
    }

    public function priorityQueue(): HasOne
    {
        return $this->hasOne(TicketPriorityQueue::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Calculate priority score based on various factors
     */
    public function calculatePriorityScore(): float
    {
        $score = 0;

        // Base score from priority level
        $priorityScores = [
            self::PRIORITY_LOW => 1,
            self::PRIORITY_MEDIUM => 2,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_CRITICAL => 5,
        ];
        $score += $priorityScores[$this->priority] ?? 0;

        // Age factor (older tickets get higher priority)
        $ageInDays = $this->created_at->diffInDays(now());
        $score += min($ageInDays * 0.1, 2); // Cap at 2 points

        // Client importance factor
        if ($this->client && isset($this->client->importance_level)) {
            $score += $this->client->importance_level * 0.5;
        }

        // SLA deadline proximity
        if ($this->priorityQueue && $this->priorityQueue->sla_deadline) {
            $hoursToDeadline = now()->diffInHours($this->priorityQueue->sla_deadline, false);
            if ($hoursToDeadline < 24 && $hoursToDeadline > 0) {
                $score += 2; // Urgent - less than 24 hours
            } elseif ($hoursToDeadline < 0) {
                $score += 5; // Overdue
            }
        }

        return round($score, 2);
    }

    /**
     * Check if ticket can transition to a specific status
     */
    public function canTransitionTo(string $status): bool
    {
        // If no workflow is assigned, allow any transition
        if (!$this->workflow_id) {
            return in_array($status, self::getAvailableStatuses());
        }

        // Check workflow transitions
        $transitions = TicketStatusTransition::where('workflow_id', $this->workflow_id)
            ->where('from_status', $this->status)
            ->where('to_status', $status)
            ->where('is_active', true)
            ->first();

        if (!$transitions) {
            return false;
        }

        // Check role requirements
        if ($transitions->required_role) {
            $user = auth()->user();
            if (!$user || !$user->hasRole($transitions->required_role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get total time worked on ticket
     */
    public function getTotalTimeWorked(): float
    {
        return $this->timeEntries()->sum('hours_worked');
    }

    /**
     * Get billable time worked on ticket
     */
    public function getBillableTimeWorked(): float
    {
        return $this->timeEntries()->where('billable', true)->sum('hours_worked');
    }

    /**
     * Get next scheduled calendar event
     */
    public function getNextScheduledDate(): ?Carbon
    {
        $nextEvent = $this->calendarEvents()
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->first();

        return $nextEvent ? $nextEvent->start_time : null;
    }

    /**
     * Check if ticket is overdue based on SLA
     */
    public function isOverdue(): bool
    {
        if (!$this->priorityQueue || !$this->priorityQueue->sla_deadline) {
            return false;
        }

        return now()->gt($this->priorityQueue->sla_deadline) && !$this->isClosed();
    }

    /**
     * Check if ticket is closed
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return !$this->isClosed();
    }

    /**
     * Check if ticket is high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Get ticket age in hours
     */
    public function getAgeInHours(): int
    {
        return $this->created_at->diffInHours(now());
    }

    /**
     * Get ticket age in days
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereHas('priorityQueue', function ($q) {
            $q->where('sla_deadline', '<', now());
        })->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeScheduledToday($query)
    {
        return $query->whereHas('calendarEvents', function ($q) {
            $q->whereDate('start_time', today());
        });
    }

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeFromTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('recurring_ticket_id');
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ON_HOLD,
            self::STATUS_WAITING,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    public static function getAvailableSources(): array
    {
        return [
            self::SOURCE_EMAIL,
            self::SOURCE_PHONE,
            self::SOURCE_PORTAL,
            self::SOURCE_WALK_IN,
            self::SOURCE_INTERNAL,
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Auto-increment ticket number for new tickets
        static::creating(function ($ticket) {
            if (!$ticket->number) {
                $lastTicket = static::where('tenant_id', $ticket->tenant_id)
                    ->where('prefix', $ticket->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $ticket->number = $lastTicket ? $lastTicket->number + 1 : 1001;
            }

            // Set default values
            if (empty($ticket->priority)) {
                $ticket->priority = self::PRIORITY_MEDIUM;
            }
            
            if (empty($ticket->status)) {
                $ticket->status = self::STATUS_OPEN;
            }
        });

        // Update priority queue when ticket is updated
        static::updated(function ($ticket) {
            if ($ticket->priorityQueue) {
                $ticket->priorityQueue->update([
                    'priority_score' => $ticket->calculatePriorityScore(),
                ]);
            }
        });
    }
}