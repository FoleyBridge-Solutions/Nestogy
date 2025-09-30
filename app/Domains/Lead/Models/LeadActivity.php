<?php

namespace App\Domains\Lead\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'subject',
        'description',
        'metadata',
        'activity_date',
    ];

    protected $casts = [
        'lead_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
        'activity_date' => 'datetime',
    ];

    // Activity type constants
    const TYPE_LEAD_CREATED = 'lead_created';

    const TYPE_EMAIL_SENT = 'email_sent';

    const TYPE_EMAIL_OPENED = 'email_opened';

    const TYPE_EMAIL_CLICKED = 'email_clicked';

    const TYPE_EMAIL_REPLIED = 'email_replied';

    const TYPE_CALL_MADE = 'call_made';

    const TYPE_CALL_RECEIVED = 'call_received';

    const TYPE_MEETING_SCHEDULED = 'meeting_scheduled';

    const TYPE_MEETING_COMPLETED = 'meeting_completed';

    const TYPE_NOTE_ADDED = 'note_added';

    const TYPE_STATUS_CHANGED = 'status_changed';

    const TYPE_SCORE_UPDATED = 'score_updated';

    const TYPE_CAMPAIGN_ENROLLED = 'campaign_enrolled';

    const TYPE_CAMPAIGN_COMPLETED = 'campaign_completed';

    const TYPE_FORM_SUBMITTED = 'form_submitted';

    const TYPE_WEBSITE_VISIT = 'website_visit';

    const TYPE_DOCUMENT_DOWNLOADED = 'document_downloaded';

    const TYPE_QUALIFIED = 'qualified';

    const TYPE_CONVERTED = 'converted';

    const TYPE_LOST = 'lost';

    /**
     * Get the lead this activity belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who performed this activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get available activity types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_LEAD_CREATED => 'Lead Created',
            self::TYPE_EMAIL_SENT => 'Email Sent',
            self::TYPE_EMAIL_OPENED => 'Email Opened',
            self::TYPE_EMAIL_CLICKED => 'Email Clicked',
            self::TYPE_EMAIL_REPLIED => 'Email Replied',
            self::TYPE_CALL_MADE => 'Call Made',
            self::TYPE_CALL_RECEIVED => 'Call Received',
            self::TYPE_MEETING_SCHEDULED => 'Meeting Scheduled',
            self::TYPE_MEETING_COMPLETED => 'Meeting Completed',
            self::TYPE_NOTE_ADDED => 'Note Added',
            self::TYPE_STATUS_CHANGED => 'Status Changed',
            self::TYPE_SCORE_UPDATED => 'Score Updated',
            self::TYPE_CAMPAIGN_ENROLLED => 'Campaign Enrolled',
            self::TYPE_CAMPAIGN_COMPLETED => 'Campaign Completed',
            self::TYPE_FORM_SUBMITTED => 'Form Submitted',
            self::TYPE_WEBSITE_VISIT => 'Website Visit',
            self::TYPE_DOCUMENT_DOWNLOADED => 'Document Downloaded',
            self::TYPE_QUALIFIED => 'Qualified',
            self::TYPE_CONVERTED => 'Converted',
            self::TYPE_LOST => 'Lost',
        ];
    }

    /**
     * Get activity icon.
     */
    public function getIconAttribute(): string
    {
        $icons = [
            self::TYPE_LEAD_CREATED => 'user-plus',
            self::TYPE_EMAIL_SENT => 'mail',
            self::TYPE_EMAIL_OPENED => 'mail-open',
            self::TYPE_EMAIL_CLICKED => 'mouse-pointer',
            self::TYPE_EMAIL_REPLIED => 'reply',
            self::TYPE_CALL_MADE => 'phone-outgoing',
            self::TYPE_CALL_RECEIVED => 'phone-incoming',
            self::TYPE_MEETING_SCHEDULED => 'calendar',
            self::TYPE_MEETING_COMPLETED => 'calendar-check',
            self::TYPE_NOTE_ADDED => 'file-text',
            self::TYPE_STATUS_CHANGED => 'refresh-cw',
            self::TYPE_SCORE_UPDATED => 'trending-up',
            self::TYPE_CAMPAIGN_ENROLLED => 'send',
            self::TYPE_CAMPAIGN_COMPLETED => 'check-circle',
            self::TYPE_FORM_SUBMITTED => 'edit',
            self::TYPE_WEBSITE_VISIT => 'globe',
            self::TYPE_DOCUMENT_DOWNLOADED => 'download',
            self::TYPE_QUALIFIED => 'check',
            self::TYPE_CONVERTED => 'star',
            self::TYPE_LOST => 'x',
        ];

        return $icons[$this->type] ?? 'chart-bar';
    }

    /**
     * Get activity color.
     */
    public function getColorAttribute(): string
    {
        $colors = [
            self::TYPE_LEAD_CREATED => 'blue',
            self::TYPE_EMAIL_SENT => 'blue',
            self::TYPE_EMAIL_OPENED => 'green',
            self::TYPE_EMAIL_CLICKED => 'purple',
            self::TYPE_EMAIL_REPLIED => 'green',
            self::TYPE_CALL_MADE => 'blue',
            self::TYPE_CALL_RECEIVED => 'green',
            self::TYPE_MEETING_SCHEDULED => 'yellow',
            self::TYPE_MEETING_COMPLETED => 'green',
            self::TYPE_NOTE_ADDED => 'gray',
            self::TYPE_STATUS_CHANGED => 'orange',
            self::TYPE_SCORE_UPDATED => 'purple',
            self::TYPE_CAMPAIGN_ENROLLED => 'blue',
            self::TYPE_CAMPAIGN_COMPLETED => 'green',
            self::TYPE_FORM_SUBMITTED => 'blue',
            self::TYPE_WEBSITE_VISIT => 'blue',
            self::TYPE_DOCUMENT_DOWNLOADED => 'purple',
            self::TYPE_QUALIFIED => 'green',
            self::TYPE_CONVERTED => 'green',
            self::TYPE_LOST => 'red',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    /**
     * Get formatted activity date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->activity_date->format('M j, Y g:i A');
    }

    /**
     * Get time ago.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->activity_date->diffForHumans();
    }

    /**
     * Check if activity is email-related.
     */
    public function isEmailActivity(): bool
    {
        return in_array($this->type, [
            self::TYPE_EMAIL_SENT,
            self::TYPE_EMAIL_OPENED,
            self::TYPE_EMAIL_CLICKED,
            self::TYPE_EMAIL_REPLIED,
        ]);
    }

    /**
     * Check if activity is call-related.
     */
    public function isCallActivity(): bool
    {
        return in_array($this->type, [
            self::TYPE_CALL_MADE,
            self::TYPE_CALL_RECEIVED,
        ]);
    }

    /**
     * Check if activity is meeting-related.
     */
    public function isMeetingActivity(): bool
    {
        return in_array($this->type, [
            self::TYPE_MEETING_SCHEDULED,
            self::TYPE_MEETING_COMPLETED,
        ]);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('activity_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to get email activities.
     */
    public function scopeEmailActivities($query)
    {
        return $query->whereIn('type', [
            self::TYPE_EMAIL_SENT,
            self::TYPE_EMAIL_OPENED,
            self::TYPE_EMAIL_CLICKED,
            self::TYPE_EMAIL_REPLIED,
        ]);
    }

    /**
     * Scope to get engagement activities.
     */
    public function scopeEngagementActivities($query)
    {
        return $query->whereIn('type', [
            self::TYPE_EMAIL_OPENED,
            self::TYPE_EMAIL_CLICKED,
            self::TYPE_EMAIL_REPLIED,
            self::TYPE_CALL_RECEIVED,
            self::TYPE_WEBSITE_VISIT,
            self::TYPE_FORM_SUBMITTED,
            self::TYPE_DOCUMENT_DOWNLOADED,
        ]);
    }
}
