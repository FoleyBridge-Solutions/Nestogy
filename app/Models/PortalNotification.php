<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Portal Notification Model
 * 
 * Manages notifications and communications for client portal users.
 * Supports multiple delivery channels and notification preferences.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property string $type
 * @property string|null $category
 * @property string $priority
 * @property string $title
 * @property string $message
 * @property string|null $description
 * @property array|null $data
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $action_url
 * @property string|null $action_text
 * @property bool $show_in_portal
 * @property bool $send_email
 * @property bool $send_sms
 * @property bool $send_push
 * @property array|null $delivery_channels
 * @property string|null $email_subject
 * @property string|null $email_body
 * @property string|null $email_template
 * @property \Illuminate\Support\Carbon|null $email_sent_at
 * @property bool|null $email_delivered
 * @property string|null $email_error
 * @property string|null $sms_message
 * @property \Illuminate\Support\Carbon|null $sms_sent_at
 * @property bool|null $sms_delivered
 * @property string|null $sms_error
 * @property string|null $push_title
 * @property string|null $push_body
 * @property array|null $push_data
 * @property \Illuminate\Support\Carbon|null $push_sent_at
 * @property bool|null $push_delivered
 * @property string|null $push_error
 * @property string $status
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property bool $is_dismissed
 * @property \Illuminate\Support\Carbon|null $dismissed_at
 * @property bool $requires_action
 * @property bool $action_completed
 * @property \Illuminate\Support\Carbon|null $action_completed_at
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_recurring
 * @property string|null $recurring_pattern
 * @property \Illuminate\Support\Carbon|null $next_occurrence
 * @property array|null $target_conditions
 * @property array|null $personalization_data
 * @property string $language
 * @property string|null $timezone
 * @property int|null $invoice_id
 * @property int|null $payment_id
 * @property int|null $ticket_id
 * @property int|null $contract_id
 * @property string|null $related_model_type
 * @property int|null $related_model_id
 * @property string|null $group_key
 * @property int|null $parent_id
 * @property int|null $thread_position
 * @property bool $is_summary
 * @property array|null $tracking_data
 * @property int $view_count
 * @property \Illuminate\Support\Carbon|null $first_viewed_at
 * @property \Illuminate\Support\Carbon|null $last_viewed_at
 * @property int $click_count
 * @property \Illuminate\Support\Carbon|null $first_clicked_at
 * @property \Illuminate\Support\Carbon|null $last_clicked_at
 * @property string|null $variant
 * @property string|null $campaign_id
 * @property array|null $experiment_data
 * @property bool $requires_acknowledgment
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property string|null $acknowledgment_method
 * @property array|null $audit_trail
 * @property bool $respects_do_not_disturb
 * @property array|null $client_preferences
 * @property bool $can_be_disabled
 * @property string|null $frequency_limit
 * @property string|null $source_system
 * @property string|null $external_id
 * @property array|null $webhook_data
 * @property bool $trigger_webhooks
 * @property array|null $metadata
 * @property array|null $custom_fields
 * @property string|null $internal_notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PortalNotification extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'portal_notifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'category',
        'priority',
        'title',
        'message',
        'description',
        'data',
        'icon',
        'color',
        'action_url',
        'action_text',
        'show_in_portal',
        'send_email',
        'send_sms',
        'send_push',
        'delivery_channels',
        'email_subject',
        'email_body',
        'email_template',
        'email_sent_at',
        'email_delivered',
        'email_error',
        'sms_message',
        'sms_sent_at',
        'sms_delivered',
        'sms_error',
        'push_title',
        'push_body',
        'push_data',
        'push_sent_at',
        'push_delivered',
        'push_error',
        'status',
        'is_read',
        'read_at',
        'is_dismissed',
        'dismissed_at',
        'requires_action',
        'action_completed',
        'action_completed_at',
        'scheduled_at',
        'expires_at',
        'is_recurring',
        'recurring_pattern',
        'next_occurrence',
        'target_conditions',
        'personalization_data',
        'language',
        'timezone',
        'invoice_id',
        'payment_id',
        'ticket_id',
        'contract_id',
        'related_model_type',
        'related_model_id',
        'group_key',
        'parent_id',
        'thread_position',
        'is_summary',
        'tracking_data',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
        'click_count',
        'first_clicked_at',
        'last_clicked_at',
        'variant',
        'campaign_id',
        'experiment_data',
        'requires_acknowledgment',
        'acknowledged_at',
        'acknowledgment_method',
        'audit_trail',
        'respects_do_not_disturb',
        'client_preferences',
        'can_be_disabled',
        'frequency_limit',
        'source_system',
        'external_id',
        'webhook_data',
        'trigger_webhooks',
        'metadata',
        'custom_fields',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'data' => 'array',
        'show_in_portal' => 'boolean',
        'send_email' => 'boolean',
        'send_sms' => 'boolean',
        'send_push' => 'boolean',
        'delivery_channels' => 'array',
        'email_sent_at' => 'datetime',
        'email_delivered' => 'boolean',
        'sms_sent_at' => 'datetime',
        'sms_delivered' => 'boolean',
        'push_data' => 'array',
        'push_sent_at' => 'datetime',
        'push_delivered' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
        'requires_action' => 'boolean',
        'action_completed' => 'boolean',
        'action_completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_recurring' => 'boolean',
        'next_occurrence' => 'datetime',
        'target_conditions' => 'array',
        'personalization_data' => 'array',
        'invoice_id' => 'integer',
        'payment_id' => 'integer',
        'ticket_id' => 'integer',
        'contract_id' => 'integer',
        'related_model_id' => 'integer',
        'parent_id' => 'integer',
        'thread_position' => 'integer',
        'is_summary' => 'boolean',
        'tracking_data' => 'array',
        'view_count' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'click_count' => 'integer',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'experiment_data' => 'array',
        'requires_acknowledgment' => 'boolean',
        'acknowledged_at' => 'datetime',
        'audit_trail' => 'array',
        'respects_do_not_disturb' => 'boolean',
        'client_preferences' => 'array',
        'can_be_disabled' => 'boolean',
        'webhook_data' => 'array',
        'trigger_webhooks' => 'boolean',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Notification type constants
     */
    const TYPE_INVOICE_DUE = 'invoice_due';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_PAYMENT_FAILED = 'payment_failed';
    const TYPE_SERVICE_OUTAGE = 'service_outage';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_ACCOUNT_UPDATE = 'account_update';
    const TYPE_SECURITY_ALERT = 'security_alert';
    const TYPE_SYSTEM_MESSAGE = 'system_message';
    const TYPE_MARKETING = 'marketing';
    const TYPE_REMINDER = 'reminder';

    /**
     * Category constants
     */
    const CATEGORY_BILLING = 'billing';
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_SYSTEM = 'system';

    /**
     * Priority constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the client that owns this notification.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns this notification.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this notification.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the related invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the related payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the related ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the parent notification (for threading).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child notifications (for threading).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('thread_position');
    }

    /**
     * Get the related model (polymorphic relation).
     */
    public function relatedModel()
    {
        return $this->morphTo('related_model', 'related_model_type', 'related_model_id');
    }

    /**
     * Check if notification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if notification is sent.
     */
    public function isSent(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ]);
    }

    /**
     * Check if notification is delivered.
     */
    public function isDelivered(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_READ]);
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->is_read === true || $this->status === self::STATUS_READ;
    }

    /**
     * Check if notification is dismissed.
     */
    public function isDismissed(): bool
    {
        return $this->is_dismissed === true;
    }

    /**
     * Check if notification is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    /**
     * Check if notification is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at && Carbon::now()->lt($this->scheduled_at);
    }

    /**
     * Check if notification requires action.
     */
    public function requiresAction(): bool
    {
        return $this->requires_action === true && !$this->action_completed;
    }

    /**
     * Check if notification requires acknowledgment.
     */
    public function requiresAcknowledgment(): bool
    {
        return $this->requires_acknowledgment === true && !$this->acknowledged_at;
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->recordView();

        return $this->update([
            'is_read' => true,
            'read_at' => Carbon::now(),
            'status' => self::STATUS_READ,
        ]);
    }

    /**
     * Mark notification as dismissed.
     */
    public function dismiss(): bool
    {
        return $this->update([
            'is_dismissed' => true,
            'dismissed_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark action as completed.
     */
    public function markActionCompleted(): bool
    {
        return $this->update([
            'action_completed' => true,
            'action_completed_at' => Carbon::now(),
        ]);
    }

    /**
     * Acknowledge the notification.
     */
    public function acknowledge(string $method = 'manual'): bool
    {
        return $this->update([
            'acknowledged_at' => Carbon::now(),
            'acknowledgment_method' => $method,
        ]);
    }

    /**
     * Record view event.
     */
    public function recordView(): bool
    {
        $updates = [
            'view_count' => $this->view_count + 1,
            'last_viewed_at' => Carbon::now(),
        ];

        if (!$this->first_viewed_at) {
            $updates['first_viewed_at'] = Carbon::now();
        }

        return $this->update($updates);
    }

    /**
     * Record click event.
     */
    public function recordClick(): bool
    {
        $updates = [
            'click_count' => $this->click_count + 1,
            'last_clicked_at' => Carbon::now(),
        ];

        if (!$this->first_clicked_at) {
            $updates['first_clicked_at'] = Carbon::now();
        }

        return $this->update($updates);
    }

    /**
     * Get priority level as number.
     */
    public function getPriorityLevel(): int
    {
        $levels = [
            self::PRIORITY_LOW => 1,
            self::PRIORITY_NORMAL => 2,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_URGENT => 4,
            self::PRIORITY_CRITICAL => 5,
        ];

        return $levels[$this->priority] ?? 2;
    }

    /**
     * Get priority color class.
     */
    public function getPriorityColor(): string
    {
        $colors = [
            self::PRIORITY_LOW => 'green',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'yellow',
            self::PRIORITY_URGENT => 'orange',
            self::PRIORITY_CRITICAL => 'red',
        ];

        return $colors[$this->priority] ?? 'blue';
    }

    /**
     * Get notification icon.
     */
    public function getIcon(): string
    {
        if ($this->icon) {
            return $this->icon;
        }

        $icons = [
            self::TYPE_INVOICE_DUE => 'invoice',
            self::TYPE_PAYMENT_RECEIVED => 'payment',
            self::TYPE_PAYMENT_FAILED => 'payment-failed',
            self::TYPE_SERVICE_OUTAGE => 'alert-triangle',
            self::TYPE_MAINTENANCE => 'settings',
            self::TYPE_ACCOUNT_UPDATE => 'user',
            self::TYPE_SECURITY_ALERT => 'shield-alert',
            self::TYPE_SYSTEM_MESSAGE => 'message-circle',
            self::TYPE_MARKETING => 'megaphone',
            self::TYPE_REMINDER => 'clock',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get truncated message for display.
     */
    public function getExcerpt(int $length = 100): string
    {
        return strlen($this->message) > $length 
            ? substr($this->message, 0, $length) . '...' 
            : $this->message;
    }

    /**
     * Check if notification should be shown to client.
     */
    public function shouldShowToClient(): bool
    {
        if (!$this->show_in_portal) {
            return false;
        }

        if ($this->isExpired() || $this->isDismissed()) {
            return false;
        }

        if ($this->isScheduled()) {
            return false;
        }

        // Check client preferences
        if ($this->respects_do_not_disturb && $this->client->hasDoNotDisturbActive()) {
            return false;
        }

        return true;
    }

    /**
     * Check if email should be sent.
     */
    public function shouldSendEmail(): bool
    {
        return $this->send_email && 
               !$this->email_sent_at && 
               $this->client->email && 
               $this->client->allowsEmailNotifications($this->type);
    }

    /**
     * Check if SMS should be sent.
     */
    public function shouldSendSMS(): bool
    {
        return $this->send_sms && 
               !$this->sms_sent_at && 
               $this->client->phone && 
               $this->client->allowsSMSNotifications($this->type);
    }

    /**
     * Check if push notification should be sent.
     */
    public function shouldSendPush(): bool
    {
        return $this->send_push && 
               !$this->push_sent_at && 
               $this->client->hasPushTokens() && 
               $this->client->allowsPushNotifications($this->type);
    }

    /**
     * Mark email as sent.
     */
    public function markEmailSent(bool $delivered = true, string $error = null): bool
    {
        return $this->update([
            'email_sent_at' => Carbon::now(),
            'email_delivered' => $delivered,
            'email_error' => $error,
        ]);
    }

    /**
     * Mark SMS as sent.
     */
    public function markSMSSent(bool $delivered = true, string $error = null): bool
    {
        return $this->update([
            'sms_sent_at' => Carbon::now(),
            'sms_delivered' => $delivered,
            'sms_error' => $error,
        ]);
    }

    /**
     * Mark push notification as sent.
     */
    public function markPushSent(bool $delivered = true, string $error = null): bool
    {
        return $this->update([
            'push_sent_at' => Carbon::now(),
            'push_delivered' => $delivered,
            'push_error' => $error,
        ]);
    }

    /**
     * Calculate next occurrence for recurring notifications.
     */
    public function calculateNextOccurrence(): ?Carbon
    {
        if (!$this->is_recurring || !$this->recurring_pattern) {
            return null;
        }

        $base = $this->next_occurrence ?? Carbon::now();

        switch ($this->recurring_pattern) {
            case 'daily':
                return $base->addDay();
            case 'weekly':
                return $base->addWeek();
            case 'monthly':
                return $base->addMonth();
            case 'quarterly':
                return $base->addMonths(3);
            case 'yearly':
                return $base->addYear();
            default:
                return null;
        }
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to get undismissed notifications.
     */
    public function scopeUndismissed($query)
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope to get notifications by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get notifications by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get notifications by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get high priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT, self::PRIORITY_CRITICAL]);
    }

    /**
     * Scope to get active notifications.
     */
    public function scopeActive($query)
    {
        return $query->where('show_in_portal', true)
                    ->where('is_dismissed', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                    });
    }

    /**
     * Scope to get due notifications for sending.
     */
    public function scopeDueForSending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', Carbon::now());
                    });
    }

    /**
     * Scope to get notifications that require action.
     */
    public function scopeRequiringAction($query)
    {
        return $query->where('requires_action', true)
                    ->where('action_completed', false);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            // Set default scheduled_at if not provided
            if (!$notification->scheduled_at) {
                $notification->scheduled_at = Carbon::now();
            }

            // Set language from client if not provided
            if (!$notification->language && $notification->client) {
                $notification->language = $notification->client->preferred_language ?? 'en';
            }

            // Set timezone from client if not provided
            if (!$notification->timezone && $notification->client) {
                $notification->timezone = $notification->client->timezone ?? 'UTC';
            }
        });

        static::updated(function ($notification) {
            // Update next occurrence for recurring notifications
            if ($notification->is_recurring && $notification->isDirty('next_occurrence')) {
                $notification->next_occurrence = $notification->calculateNextOccurrence();
                $notification->saveQuietly();
            }
        });
    }
}