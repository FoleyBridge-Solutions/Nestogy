<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Watcher Model
 *
 * Represents users who are watching a ticket for updates and notifications.
 * Supports email notifications for ticket changes and replies.
 */
class TicketWatcher extends Model
{
    use BelongsToCompany, HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\TicketWatcherFactory::new();
    }

    protected $fillable = [
        'ticket_id',
        'company_id',
        'email',
        'user_id',
        'notification_preferences',
        'is_active',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'company_id' => 'integer',
        'user_id' => 'integer',
        'notification_preferences' => 'array',
        'is_active' => 'boolean',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if watcher should be notified for a specific event type
     */
    public function shouldNotifyFor(string $eventType): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $preferences = $this->notification_preferences ?? [];

        // Default to true if no preferences set
        if (empty($preferences)) {
            return true;
        }

        return $preferences[$eventType] ?? false;
    }

    /**
     * Enable notification for specific event types
     */
    public function enableNotifications(array $eventTypes): void
    {
        $preferences = $this->notification_preferences ?? [];

        foreach ($eventTypes as $type) {
            $preferences[$type] = true;
        }

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Disable notification for specific event types
     */
    public function disableNotifications(array $eventTypes): void
    {
        $preferences = $this->notification_preferences ?? [];

        foreach ($eventTypes as $type) {
            $preferences[$type] = false;
        }

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get display name for this watcher
     */
    public function getDisplayName(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->email;
    }

    /**
     * Check if this is an internal user watcher
     */
    public function isInternalUser(): bool
    {
        return ! is_null($this->user_id);
    }

    /**
     * Check if this is an external email watcher
     */
    public function isExternalEmail(): bool
    {
        return is_null($this->user_id) && ! is_null($this->email);
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInternalUsers($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeExternalEmails($query)
    {
        return $query->whereNull('user_id')->whereNotNull('email');
    }

    public function scopeForTicket($query, int $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    /**
     * Add watcher to ticket by email
     */
    public static function addByEmail(int $ticketId, string $email): self
    {
        // Check if user exists with this email
        $user = \App\Models\User::where('email', $email)->first();

        return self::updateOrCreate([
            'ticket_id' => $ticketId,
            'email' => $email,
        ], [
            'company_id' => auth()->user()->company_id,
            'user_id' => $user?->id,
            'is_active' => true,
        ]);
    }

    /**
     * Add watcher to ticket by user ID
     */
    public static function addByUser(int $ticketId, int $userId): self
    {
        $user = \App\Models\User::findOrFail($userId);

        return self::updateOrCreate([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ], [
            'company_id' => auth()->user()->company_id,
            'email' => $user->email,
            'is_active' => true,
        ]);
    }

    /**
     * Get default notification preferences
     */
    public static function getDefaultNotificationPreferences(): array
    {
        return [
            'status_change' => true,
            'new_reply' => true,
            'assignment_change' => true,
            'priority_change' => false,
            'deadline_reminder' => true,
        ];
    }

    /**
     * Get available notification event types
     */
    public static function getAvailableEventTypes(): array
    {
        return [
            'status_change' => 'Status Changes',
            'new_reply' => 'New Replies',
            'assignment_change' => 'Assignment Changes',
            'priority_change' => 'Priority Changes',
            'deadline_reminder' => 'Deadline Reminders',
            'escalation' => 'Escalations',
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'email' => 'required|email|max:255',
            'user_id' => 'nullable|exists:users,id',
            'notification_preferences' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Set default notification preferences
        static::creating(function ($watcher) {
            if (! $watcher->notification_preferences) {
                $watcher->notification_preferences = self::getDefaultNotificationPreferences();
            }
        });

        // Prevent duplicate watchers
        static::creating(function ($watcher) {
            $existing = self::where('ticket_id', $watcher->ticket_id)
                ->where('email', $watcher->email)
                ->first();

            if ($existing) {
                // Update existing instead of creating duplicate
                $existing->update([
                    'user_id' => $watcher->user_id,
                    'is_active' => true,
                ]);

                return false; // Cancel creation
            }
        });
    }
}
