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
use App\Models\TicketReply;
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
        // Resolution fields
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_summary',
        'client_can_reopen',
        'reopened_at',
        'reopened_by',
        'resolution_count',
        // Sentiment analysis fields
        'sentiment_score',
        'sentiment_label',
        'sentiment_analyzed_at',
        'sentiment_confidence',
    ];

    protected $casts = [
        'number' => 'integer',
        'billable' => 'boolean',
        'onsite' => 'boolean',
        'scheduled_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'closed_at' => 'datetime',
        'sentiment_analyzed_at' => 'datetime',
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
        // Resolution fields
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'resolved_by' => 'integer',
        'reopened_at' => 'datetime',
        'reopened_by' => 'integer',
        'client_can_reopen' => 'boolean',
        'resolution_count' => 'integer',
        'sentiment_score' => 'decimal:2',
        'sentiment_confidence' => 'decimal:2',
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

    /**
     * Sentiment constants
     */
    const SENTIMENT_POSITIVE = 'POSITIVE';
    const SENTIMENT_WEAK_POSITIVE = 'WEAK_POSITIVE';
    const SENTIMENT_NEUTRAL = 'NEUTRAL';
    const SENTIMENT_WEAK_NEGATIVE = 'WEAK_NEGATIVE';
    const SENTIMENT_NEGATIVE = 'NEGATIVE';

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

    public function assignedTo(): BelongsTo
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
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
        // Legacy relationship - deprecated, use comments() instead
        return $this->hasMany(TicketReply::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
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

    public function timeLogs(): HasMany
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
    // RESOLUTION METHODS
    // ===========================================

    /**
     * Resolve the ticket
     */
    public function resolve(User $user, ?string $summary = null, bool $allowClientReopen = true): void
    {
        if ($this->is_resolved) {
            throw new \Exception('Ticket is already resolved');
        }

        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_summary' => $summary,
            'client_can_reopen' => $allowClientReopen,
            'resolution_count' => $this->resolution_count + 1,
        ]);

        // Create resolution comment
        $this->comments()->create([
            'company_id' => $this->company_id,
            'content' => $summary ?: 'Ticket has been resolved.',
            'visibility' => 'public',
            'source' => 'system',
            'author_id' => $user->id,
            'author_type' => 'user',
            'is_resolution' => true,
        ]);
    }

    /**
     * Check if ticket can be reopened by user
     */
    public function canBeReopenedBy(User $user): bool
    {
        if (!$this->is_resolved) {
            return false;
        }

        // Closed tickets cannot be reopened
        if ($this->status === self::STATUS_CLOSED) {
            return false;
        }

        // Staff can always reopen
        if ($user->hasRole(['admin', 'manager', 'technician'])) {
            return true;
        }

        // Client can reopen if allowed
        if ($user->id === $this->created_by && $this->client_can_reopen) {
            return true;
        }

        // Client company users can reopen if allowed
        if ($this->client && $user->client_id === $this->client_id && $this->client_can_reopen) {
            return true;
        }

        return false;
    }

    /**
     * Reopen the ticket
     */
    public function reopen(User $user, ?string $reason = null): void
    {
        if (!$this->canBeReopenedBy($user)) {
            throw new \Exception('You do not have permission to reopen this ticket');
        }

        $this->update([
            'is_resolved' => false,
            'status' => self::STATUS_OPEN,
            'reopened_at' => now(),
            'reopened_by' => $user->id,
        ]);

        // Create reopen comment
        $this->comments()->create([
            'company_id' => $this->company_id,
            'content' => $reason ?: 'Ticket has been reopened.',
            'visibility' => 'public',
            'source' => 'system',
            'author_id' => $user->id,
            'author_type' => 'user',
            'metadata' => ['action' => 'reopened'],
        ]);
    }

    /**
     * Check if ticket is resolved
     */
    public function isResolved(): bool
    {
        return (bool) $this->is_resolved;
    }

    /**
     * Get resolver user
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get reopener user
     */
    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
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
     * Get text content for sentiment analysis (subject + details)
     */
    public function getSentimentAnalysisText(): string
    {
        $text = '';
        
        if (!empty($this->subject)) {
            $text .= $this->subject;
        }
        
        if (!empty($this->details)) {
            if (!empty($text)) {
                $text .= '. ';
            }
            $text .= $this->details;
        }
        
        return trim($text);
    }

    /**
     * Check if ticket has sentiment analysis
     */
    public function hasSentimentAnalysis(): bool
    {
        return !is_null($this->sentiment_score) && !is_null($this->sentiment_analyzed_at);
    }

    /**
     * Get sentiment interpretation
     */
    public function getSentimentInterpretation(): array
    {
        if (!$this->hasSentimentAnalysis()) {
            return [
                'interpretation' => 'Not Analyzed',
                'color' => '#94a3b8', // slate-400
                'confidence_level' => 'N/A'
            ];
        }

        return \App\Services\TaxEngine\SentimentAnalysisService::interpretSentimentScore($this->sentiment_score);
    }

    /**
     * Check if ticket sentiment is negative
     */
    public function hasNegativeSentiment(): bool
    {
        return in_array($this->sentiment_label, [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE]);
    }

    /**
     * Check if ticket sentiment is positive  
     */
    public function hasPositiveSentiment(): bool
    {
        return in_array($this->sentiment_label, [self::SENTIMENT_POSITIVE, self::SENTIMENT_WEAK_POSITIVE]);
    }

    /**
     * Check if ticket sentiment needs attention (negative with high confidence)
     */
    public function sentimentNeedsAttention(): bool
    {
        return $this->hasNegativeSentiment() && ($this->sentiment_confidence ?? 0) > 0.6;
    }

    /**
     * Get sentiment color for UI display
     */
    public function getSentimentColor(): string
    {
        if (!$this->hasSentimentAnalysis()) {
            return '#94a3b8'; // slate-400
        }

        return match($this->sentiment_label) {
            self::SENTIMENT_POSITIVE => '#10b981', // emerald-500
            self::SENTIMENT_WEAK_POSITIVE => '#84cc16', // lime-500  
            self::SENTIMENT_NEUTRAL => '#64748b', // slate-500
            self::SENTIMENT_WEAK_NEGATIVE => '#f97316', // orange-500
            self::SENTIMENT_NEGATIVE => '#ef4444', // red-500
            default => '#94a3b8' // slate-400
        };
    }

    /**
     * Get sentiment icon for UI display
     */
    public function getSentimentIcon(): string
    {
        if (!$this->hasSentimentAnalysis()) {
            return 'fas fa-question-circle';
        }

        return match($this->sentiment_label) {
            self::SENTIMENT_POSITIVE => 'fas fa-smile',
            self::SENTIMENT_WEAK_POSITIVE => 'fas fa-smile-wink',
            self::SENTIMENT_NEUTRAL => 'fas fa-meh',
            self::SENTIMENT_WEAK_NEGATIVE => 'fas fa-frown',
            self::SENTIMENT_NEGATIVE => 'fas fa-angry',
            default => 'fas fa-question-circle'
        };
    }

    /**
     * Update priority score including sentiment factor
     */
    public function calculatePriorityScoreWithSentiment(): float
    {
        $score = $this->calculatePriorityScore();
        
        // Add sentiment factor to priority calculation
        if ($this->hasSentimentAnalysis()) {
            $sentimentFactor = 0;
            
            // Negative sentiment increases priority
            if ($this->sentiment_label === self::SENTIMENT_NEGATIVE) {
                $sentimentFactor = 2 * ($this->sentiment_confidence ?? 0.5);
            } elseif ($this->sentiment_label === self::SENTIMENT_WEAK_NEGATIVE) {
                $sentimentFactor = 1 * ($this->sentiment_confidence ?? 0.5);
            }
            
            $score += $sentimentFactor;
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
        return $this->status === self::STATUS_CLOSED;
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

    /**
     * Get recent activity for the ticket
     */
    public function getRecentActivity(int $limit = 20): \Illuminate\Support\Collection
    {
        $activities = collect();

        // Add replies
        $this->replies()->with('user')->orderBy('created_at', 'desc')->limit($limit)->get()->each(function ($reply) use ($activities) {
            $activities->push([
                'type' => 'reply',
                'description' => 'Reply added',
                'user' => $reply->user,
                'created_at' => $reply->created_at,
                'data' => $reply,
            ]);
        });

        // Add time entries
        $this->timeEntries()->with('user')->orderBy('created_at', 'desc')->limit($limit)->get()->each(function ($entry) use ($activities) {
            $activities->push([
                'type' => 'time_entry',
                'description' => 'Time logged: ' . $entry->hours_worked . ' hours',
                'user' => $entry->user,
                'created_at' => $entry->created_at,
                'data' => $entry,
            ]);
        });

        // Add assignments
        $this->assignments()->with(['assignedTo', 'assignedBy'])->orderBy('assigned_at', 'desc')->limit($limit)->get()->each(function ($assignment) use ($activities) {
            $description = $assignment->assigned_to 
                ? 'Assigned to ' . $assignment->assignedTo->name
                : 'Unassigned';
            
            $activities->push([
                'type' => 'assignment',
                'description' => $description,
                'user' => $assignment->assignedBy,
                'created_at' => $assignment->assigned_at,
                'data' => $assignment,
            ]);
        });

        // Add calendar events
        $this->calendarEvents()->orderBy('created_at', 'desc')->limit($limit)->get()->each(function ($event) use ($activities) {
            $activities->push([
                'type' => 'calendar_event',
                'description' => 'Event scheduled: ' . $event->title,
                'user' => null, // Events don't have a specific user
                'created_at' => $event->created_at,
                'data' => $event,
            ]);
        });

        // Sort by created_at and limit
        return $activities->sortByDesc('created_at')->take($limit)->values();
    }

    /**
     * Get available workflow transitions for the ticket
     */
    public function getAvailableTransitions(): \Illuminate\Support\Collection
    {
        if (!$this->workflow) {
            return collect();
        }

        // This would typically query transition rules based on current status
        // For now, return basic status transitions
        return collect([
            ['from' => 'open', 'to' => 'in_progress', 'name' => 'Start Work'],
            ['from' => 'in_progress', 'to' => 'pending', 'name' => 'Set Pending'],
            ['from' => 'pending', 'to' => 'in_progress', 'name' => 'Resume Work'],
            ['from' => 'in_progress', 'to' => 'resolved', 'name' => 'Resolve'],
            ['from' => 'resolved', 'to' => 'closed', 'name' => 'Close'],
        ])->filter(function ($transition) {
            return strtolower($transition['from']) === strtolower($this->status);
        });
    }

    /**
     * Get the color class for the ticket priority
     */
    public function getPriorityColor(): string
    {
        return match(strtolower($this->priority)) {
            'low' => 'success',
            'medium' => 'warning', 
            'high' => 'danger',
            'critical' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get the color class for the ticket status
     */
    public function getStatusColor(): string
    {
        return match(strtolower($this->status)) {
            'new' => 'primary',
            'open' => 'info',
            'in_progress', 'in progress' => 'warning',
            'pending' => 'secondary',
            'resolved' => 'success',
            'closed' => 'dark',
            default => 'light',
        };
    }

    /**
     * Get the icon class for the ticket priority
     */
    public function getPriorityIcon(): string
    {
        return match(strtolower($this->priority)) {
            'low' => 'fas fa-arrow-down',
            'medium' => 'fas fa-minus',
            'high' => 'fas fa-arrow-up',
            'critical' => 'fas fa-exclamation-triangle',
            default => 'fas fa-circle',
        };
    }

    /**
     * Get the icon class for the ticket status
     */
    public function getStatusIcon(): string
    {
        return match(strtolower($this->status)) {
            'new' => 'fas fa-plus-circle',
            'open' => 'fas fa-folder-open',
            'in_progress', 'in progress' => 'fas fa-spinner',
            'pending' => 'fas fa-pause-circle',
            'resolved' => 'fas fa-check-circle',
            'closed' => 'fas fa-times-circle',
            default => 'fas fa-circle',
        };
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED)
                     ->where('is_resolved', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_resolved', false)
                     ->where('status', '!=', self::STATUS_CLOSED);
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

    // Sentiment-related scopes
    public function scopeWithSentimentAnalysis($query)
    {
        return $query->whereNotNull('sentiment_analyzed_at');
    }

    public function scopeWithoutSentimentAnalysis($query)
    {
        return $query->whereNull('sentiment_analyzed_at');
    }

    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('sentiment_label', $sentiment);
    }

    public function scopePositiveSentiment($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_POSITIVE, self::SENTIMENT_WEAK_POSITIVE]);
    }

    public function scopeNegativeSentiment($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE]);
    }

    public function scopeNeutralSentiment($query)
    {
        return $query->where('sentiment_label', self::SENTIMENT_NEUTRAL);
    }

    public function scopeSentimentNeedsAttention($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE])
                    ->where('sentiment_confidence', '>', 0.6);
    }

    public function scopeSentimentScoreBetween($query, float $min, float $max)
    {
        return $query->whereBetween('sentiment_score', [$min, $max]);
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

    public static function getAvailableSentiments(): array
    {
        return [
            self::SENTIMENT_POSITIVE,
            self::SENTIMENT_WEAK_POSITIVE,
            self::SENTIMENT_NEUTRAL,
            self::SENTIMENT_WEAK_NEGATIVE,
            self::SENTIMENT_NEGATIVE,
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
                $lastTicket = static::where('company_id', $ticket->company_id)
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
        
        // Create priority queue entry with SLA deadline after ticket is created
        static::created(function ($ticket) {
            // Calculate SLA deadlines based on priority
            $priority = strtolower($ticket->priority);
            
            // Default SLA times in hours
            $slaHours = [
                'critical' => 4,
                'high' => 8,
                'medium' => 24,
                'low' => 48,
            ];
            
            $deadlineHours = $slaHours[$priority] ?? 24;
            $slaDeadline = $ticket->created_at->addHours($deadlineHours);
            
            // Create priority queue entry
            \App\Domains\Ticket\Models\TicketPriorityQueue::create([
                'ticket_id' => $ticket->id,
                'company_id' => $ticket->company_id,
                'priority_score' => $ticket->calculatePriorityScore(),
                'sla_deadline' => $slaDeadline,
                'queue_position' => 999, // Will be recalculated
                'escalation_level' => 0,
                'is_active' => true,
            ]);
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