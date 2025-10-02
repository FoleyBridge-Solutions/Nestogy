<?php

namespace App\Domains\Ticket\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket Time Entry Model
 *
 * Represents time tracking entries for tickets with billable hours,
 * rates, and work descriptions for accurate project billing.
 */
class TicketTimeEntry extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'company_id',
        'description',
        'work_performed',
        'hours_worked',
        'minutes_worked',
        'hours_billed',
        'billable',
        'hourly_rate',
        'amount',
        'work_date',
        'started_at',
        'ended_at',
        'entry_type',
        'work_type',
        'rate_type',
        'status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'approval_notes',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'invoice_id',
        'invoiced_at',
        'metadata',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'hours_worked' => 'decimal:2',
        'minutes_worked' => 'integer',
        'hours_billed' => 'decimal:2',
        'billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'work_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'submitted_at' => 'datetime',
        'submitted_by' => 'integer',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'rejected_at' => 'datetime',
        'rejected_by' => 'integer',
        'invoice_id' => 'integer',
        'invoiced_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ===========================================
    // ENTRY TYPE CONSTANTS
    // ===========================================

    const TYPE_MANUAL = 'manual';

    const TYPE_TIMER = 'timer';

    const TYPE_IMPORT = 'import';

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Calculate the total cost for this time entry
     */
    public function getTotalCost(): float
    {
        if (! $this->billable || ! $this->hourly_rate) {
            return 0.00;
        }

        return round($this->hours_worked * $this->hourly_rate, 2);
    }

    /**
     * Calculate hours worked from started_at/ended_at times if available
     */
    public function calculateHoursFromStartedTimes(): ?float
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }

        $diff = $this->ended_at->diffInMinutes($this->started_at);

        return round($diff / 60, 2);
    }

    /**
     * Start a timer for this entry
     */
    public function startTimer(): void
    {
        if ($this->entry_type !== self::TYPE_TIMER) {
            $this->entry_type = self::TYPE_TIMER;
        }

        $this->started_at = now();
        $this->save();
    }

    /**
     * Stop the timer and calculate hours
     */
    public function stopTimer(): float
    {
        if (! $this->started_at) {
            throw new \Exception('Timer was not started for this entry.');
        }

        $this->ended_at = now();
        $this->hours_worked = $this->calculateHoursFromStartedTimes();
        $this->status = 'completed'; // Update status when timer stops
        $this->save();

        return $this->hours_worked;
    }

    /**
     * Get elapsed time in minutes for active timer
     */
    public function getElapsedTime(): int
    {
        if (! $this->started_at || $this->ended_at) {
            return 0;
        }

        $start = Carbon::parse($this->started_at);
        $now = now();
        $pausedMinutes = $this->paused_duration ?? 0;

        return (int) max(0, $start->diffInMinutes($now) - $pausedMinutes);
    }

    /**
     * Check if timer is currently running
     */
    public function isTimerRunning(): bool
    {
        return $this->entry_type === self::TYPE_TIMER
            && $this->started_at
            && ! $this->ended_at;
    }

    /**
     * Duplicate this time entry to another date
     */
    public function duplicateToDate(Carbon $date): self
    {
        $copy = $this->replicate();
        $copy->work_date = $date;
        $copy->start_time = null;
        $copy->end_time = null;
        $copy->entry_type = self::TYPE_MANUAL;
        $copy->save();

        return $copy;
    }

    /**
     * Get formatted time duration
     */
    public function getFormattedDuration(): string
    {
        $hours = floor($this->hours_worked);
        $minutes = ($this->hours_worked - $hours) * 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Get formatted duration attribute
     */
    public function getFormattedDurationAttribute(): string
    {
        return $this->getFormattedDuration();
    }

    /**
     * Get date attribute (maps to work_date with fallbacks)
     */
    public function getDateAttribute()
    {
        if ($this->work_date) {
            return $this->work_date;
        }

        if ($this->started_at) {
            return \Carbon\Carbon::parse($this->started_at->toDateString());
        }

        return $this->created_at ? \Carbon\Carbon::parse($this->created_at->toDateString()) : \Carbon\Carbon::now();
    }

    /**
     * Create time entry from timer session
     */
    public static function createFromTimer(int $ticketId, int $userId, Carbon $startTime, Carbon $endTime, array $data = []): self
    {
        $hoursWorked = $endTime->diffInMinutes($startTime) / 60;

        return self::create(array_merge([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'company_id' => auth()->user()->company_id,
            'work_date' => $startTime->toDateString(),
            'started_at' => $startTime,
            'ended_at' => $endTime,
            'hours_worked' => round($hoursWorked, 2),
            'entry_type' => self::TYPE_TIMER,
        ], $data));
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    public function scopeNonBillable($query)
    {
        return $query->where('billable', false);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTicket($query, int $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('work_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('work_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeRunningTimers($query)
    {
        return $query->where('entry_type', self::TYPE_TIMER)
            ->whereNotNull('started_at')
            ->whereNull('ended_at');
    }

    public function scopeByEntryType($query, string $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeInvoiced($query)
    {
        return $query->whereNotNull('invoice_id');
    }

    public function scopeUninvoiced($query)
    {
        return $query->whereNull('invoice_id');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeUnapproved($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->orWhereNull('status');
    }

    // ===========================================
    // ACCESSORS & MUTATORS
    // ===========================================

    /**
     * Get hourly rate with fallback to user's default rate
     */
    public function getHourlyRateAttribute($value): ?float
    {
        if ($value) {
            return $value;
        }

        // Fallback to user's default hourly rate
        if ($this->user && isset($this->user->profile->hourly_rate)) {
            return $this->user->profile->hourly_rate;
        }

        return null;
    }

    /**
     * Auto-format hours to 2 decimal places
     */
    public function setHoursWorkedAttribute($value): void
    {
        $this->attributes['hours_worked'] = round((float) $value, 2);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getAvailableEntryTypes(): array
    {
        return [
            self::TYPE_MANUAL => 'Manual Entry',
            self::TYPE_TIMER => 'Timer',
            self::TYPE_IMPORT => 'Imported',
        ];
    }

    /**
     * Get time summary for a user within a date range
     */
    public static function getTimeSummary(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $entries = self::where('user_id', $userId)
            ->byDateRange($startDate, $endDate)
            ->get();

        return [
            'total_hours' => $entries->sum('hours_worked'),
            'billable_hours' => $entries->where('billable', true)->sum('hours_worked'),
            'non_billable_hours' => $entries->where('billable', false)->sum('hours_worked'),
            'total_cost' => $entries->sum(fn ($entry) => $entry->getTotalCost()),
            'entries_count' => $entries->count(),
            'tickets_worked' => $entries->pluck('ticket_id')->unique()->count(),
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'user_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:1000',
            'hours_worked' => 'required|numeric|min:0.01|max:24',
            'billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'work_date' => 'required|date|before_or_equal:today',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after:started_at',
            'entry_type' => 'required|in:manual,timer,import',
            'metadata' => 'nullable|array',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate hours from times if not provided
        static::saving(function ($entry) {
            // Calculate hours from started_at/ended_at if not provided
            if (! $entry->hours_worked && $entry->started_at && $entry->ended_at) {
                $entry->hours_worked = $entry->calculateHoursFromStartedTimes();
            }

            // Set work_date from started_at if not provided
            if (! $entry->work_date && $entry->started_at) {
                $entry->work_date = $entry->started_at->toDateString();
            }
        });
    }
}
