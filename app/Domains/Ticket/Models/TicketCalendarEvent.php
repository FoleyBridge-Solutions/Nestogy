<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Ticket Calendar Event Model
 * 
 * Represents scheduled events and appointments related to tickets
 * with support for reminders, attendees, and location information.
 */
class TicketCalendarEvent extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'tenant_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'attendee_emails',
        'is_onsite',
        'is_all_day',
        'status',
        'notes',
        'reminders',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'tenant_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'attendee_emails' => 'array',
        'is_onsite' => 'boolean',
        'is_all_day' => 'boolean',
        'reminders' => 'array',
    ];

    // ===========================================
    // STATUS CONSTANTS
    // ===========================================

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESCHEDULED = 'rescheduled';

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
     * Check if the event is currently happening
     */
    public function isHappening(): bool
    {
        $now = now();
        return $now->between($this->start_time, $this->end_time);
    }

    /**
     * Check if the event is in the past
     */
    public function isPast(): bool
    {
        return $this->end_time->isPast();
    }

    /**
     * Check if the event is in the future
     */
    public function isFuture(): bool
    {
        return $this->start_time->isFuture();
    }

    /**
     * Check if the event is today
     */
    public function isToday(): bool
    {
        return $this->start_time->isToday();
    }

    /**
     * Check if the event conflicts with another event
     */
    public function conflictsWith(self $otherEvent): bool
    {
        return $this->start_time->lt($otherEvent->end_time) && 
               $this->end_time->gt($otherEvent->start_time);
    }

    /**
     * Get the duration of the event in minutes
     */
    public function getDurationMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get the duration of the event in hours
     */
    public function getDurationHours(): float
    {
        return round($this->getDurationMinutes() / 60, 2);
    }

    /**
     * Get time until event starts
     */
    public function getTimeUntilStart(): ?int
    {
        if ($this->isPast()) {
            return null;
        }

        return now()->diffInMinutes($this->start_time);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRange(): string
    {
        if ($this->is_all_day) {
            return 'All day';
        }

        $format = 'g:i A';
        if (!$this->start_time->isSameDay($this->end_time)) {
            $format = 'M j, g:i A';
        }

        return $this->start_time->format($format) . ' - ' . $this->end_time->format($format);
    }

    /**
     * Reschedule the event
     */
    public function reschedule(Carbon $newStartTime, Carbon $newEndTime, string $reason = null): self
    {
        // Create a new event for the new time
        $rescheduled = $this->replicate();
        $rescheduled->start_time = $newStartTime;
        $rescheduled->end_time = $newEndTime;
        $rescheduled->status = self::STATUS_SCHEDULED;
        
        if ($reason) {
            $rescheduled->notes = ($rescheduled->notes ?? '') . "\nRescheduled: {$reason}";
        }
        
        $rescheduled->save();

        // Mark original as rescheduled
        $this->update([
            'status' => self::STATUS_RESCHEDULED,
            'notes' => ($this->notes ?? '') . "\nRescheduled to: {$newStartTime->format('M j, Y g:i A')}"
        ]);

        return $rescheduled;
    }

    /**
     * Cancel the event
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => ($this->notes ?? '') . ($reason ? "\nCancelled: {$reason}" : "\nCancelled")
        ]);
    }

    /**
     * Mark event as completed
     */
    public function complete(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'notes' => $notes ? ($this->notes ?? '') . "\nCompleted: {$notes}" : $this->notes
        ]);
    }

    /**
     * Start the event (mark as in progress)
     */
    public function start(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Add attendee email
     */
    public function addAttendee(string $email): void
    {
        $attendees = $this->attendee_emails ?? [];
        if (!in_array($email, $attendees)) {
            $attendees[] = $email;
            $this->update(['attendee_emails' => $attendees]);
        }
    }

    /**
     * Remove attendee email
     */
    public function removeAttendee(string $email): void
    {
        $attendees = $this->attendee_emails ?? [];
        $attendees = array_filter($attendees, fn($attendee) => $attendee !== $email);
        $this->update(['attendee_emails' => array_values($attendees)]);
    }

    /**
     * Get attendee count
     */
    public function getAttendeeCount(): int
    {
        return count($this->attendee_emails ?? []);
    }

    /**
     * Set reminder times (in minutes before event)
     */
    public function setReminders(array $minutesBefore): void
    {
        $reminders = array_map(function ($minutes) {
            return [
                'minutes_before' => $minutes,
                'sent' => false,
                'reminder_time' => $this->start_time->copy()->subMinutes($minutes)
            ];
        }, $minutesBefore);

        $this->update(['reminders' => $reminders]);
    }

    /**
     * Check if reminder should be sent
     */
    public function shouldSendReminder(): array
    {
        $reminders = $this->reminders ?? [];
        $dueReminders = [];

        foreach ($reminders as $index => $reminder) {
            if (!$reminder['sent'] && now()->gte($reminder['reminder_time'])) {
                $dueReminders[] = $index;
            }
        }

        return $dueReminders;
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(int $reminderIndex): void
    {
        $reminders = $this->reminders ?? [];
        if (isset($reminders[$reminderIndex])) {
            $reminders[$reminderIndex]['sent'] = true;
            $this->update(['reminders' => $reminders]);
        }
    }

    /**
     * Create from ticket scheduling data
     */
    public static function createFromTicket(Ticket $ticket, array $data): self
    {
        return self::create(array_merge([
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'title' => $data['title'] ?? "Service: {$ticket->subject}",
            'description' => $data['description'] ?? $ticket->details,
            'status' => self::STATUS_SCHEDULED,
        ], $data));
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
            ->where('status', self::STATUS_SCHEDULED)
            ->orderBy('start_time');
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start_time', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeOnsite($query)
    {
        return $query->where('is_onsite', true);
    }

    public function scopeAllDay($query)
    {
        return $query->where('is_all_day', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_RESCHEDULED => 'Rescheduled',
        ];
    }

    /**
     * Find conflicts for a given time range
     */
    public static function findConflicts(Carbon $startTime, Carbon $endTime, int $excludeId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'attendee_emails' => 'nullable|array',
            'attendee_emails.*' => 'email',
            'is_onsite' => 'boolean',
            'is_all_day' => 'boolean',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled,rescheduled',
            'notes' => 'nullable|string',
            'reminders' => 'nullable|array',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Set default title if not provided
        static::creating(function ($event) {
            if (!$event->title && $event->ticket) {
                $event->title = "Service: {$event->ticket->subject}";
            }
        });

        // Validate end time is after start time
        static::saving(function ($event) {
            if ($event->end_time && $event->start_time && $event->end_time->lte($event->start_time)) {
                throw new \InvalidArgumentException('End time must be after start time.');
            }
        });
    }
}