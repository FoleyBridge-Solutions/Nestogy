<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Lead\Models\Lead;
use App\Models\Contact;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingCampaign extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'name',
        'description',
        'type',
        'status',
        'settings',
        'target_criteria',
        'auto_enroll',
        'start_date',
        'end_date',
        'total_recipients',
        'total_sent',
        'total_delivered',
        'total_opened',
        'total_clicked',
        'total_replied',
        'total_unsubscribed',
        'total_converted',
        'total_revenue',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'created_by_user_id' => 'integer',
        'settings' => 'array',
        'target_criteria' => 'array',
        'auto_enroll' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'total_recipients' => 'integer',
        'total_sent' => 'integer',
        'total_delivered' => 'integer',
        'total_opened' => 'integer',
        'total_clicked' => 'integer',
        'total_replied' => 'integer',
        'total_unsubscribed' => 'integer',
        'total_converted' => 'integer',
        'total_revenue' => 'decimal:2',
    ];

    // Campaign type constants
    const TYPE_EMAIL = 'email';

    const TYPE_NURTURE = 'nurture';

    const TYPE_DRIP = 'drip';

    const TYPE_EVENT = 'event';

    const TYPE_WEBINAR = 'webinar';

    const TYPE_CONTENT = 'content';

    // Campaign status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_COMPLETED = 'completed';

    const STATUS_ARCHIVED = 'archived';

    /**
     * Get the user who created this campaign.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the campaign sequences.
     */
    public function sequences(): HasMany
    {
        return $this->hasMany(CampaignSequence::class, 'campaign_id')->orderBy('step_number');
    }

    /**
     * Get the campaign enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CampaignEnrollment::class, 'campaign_id');
    }

    /**
     * Get active enrollments.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', CampaignEnrollment::STATUS_ACTIVE);
    }

    /**
     * Get completed enrollments.
     */
    public function completedEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', CampaignEnrollment::STATUS_COMPLETED);
    }

    /**
     * Get available campaign types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_EMAIL => 'Email Campaign',
            self::TYPE_NURTURE => 'Lead Nurture',
            self::TYPE_DRIP => 'Drip Campaign',
            self::TYPE_EVENT => 'Event Campaign',
            self::TYPE_WEBINAR => 'Webinar Campaign',
            self::TYPE_CONTENT => 'Content Campaign',
        ];
    }

    /**
     * Get available campaign statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * Get the open rate percentage.
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->total_delivered === 0) {
            return 0;
        }

        return ($this->total_opened / $this->total_delivered) * 100;
    }

    /**
     * Get the click-through rate percentage.
     */
    public function getClickThroughRateAttribute(): float
    {
        if ($this->total_delivered === 0) {
            return 0;
        }

        return ($this->total_clicked / $this->total_delivered) * 100;
    }

    /**
     * Get the conversion rate percentage.
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return ($this->total_converted / $this->total_recipients) * 100;
    }

    /**
     * Get the unsubscribe rate percentage.
     */
    public function getUnsubscribeRateAttribute(): float
    {
        if ($this->total_delivered === 0) {
            return 0;
        }

        return ($this->total_unsubscribed / $this->total_delivered) * 100;
    }

    /**
     * Get the bounce rate percentage.
     */
    public function getBounceRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        $bounced = $this->total_sent - $this->total_delivered;

        return ($bounced / $this->total_sent) * 100;
    }

    /**
     * Get ROI percentage.
     */
    public function getRoiAttribute(): float
    {
        // This would need to be calculated based on campaign cost
        // For now, return revenue per recipient
        if ($this->total_recipients === 0) {
            return 0;
        }

        return $this->total_revenue / $this->total_recipients;
    }

    /**
     * Check if campaign is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if campaign is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if campaign can be started.
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PAUSED]);
    }

    /**
     * Check if campaign can be paused.
     */
    public function canBePaused(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if campaign can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PAUSED]);
    }

    /**
     * Scope to get active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get draft campaigns.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get scheduled campaigns.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get campaigns created by user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Start the campaign.
     */
    public function start(): void
    {
        if (! $this->canBeStarted()) {
            throw new \Exception('Campaign cannot be started in current status: '.$this->status);
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    /**
     * Pause the campaign.
     */
    public function pause(): void
    {
        if (! $this->canBePaused()) {
            throw new \Exception('Campaign cannot be paused in current status: '.$this->status);
        }

        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Complete the campaign.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'end_date' => now(),
        ]);
    }

    /**
     * Archive the campaign.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Enroll a lead in this campaign.
     */
    public function enrollLead(Lead $lead): CampaignEnrollment
    {
        // Check if lead is already enrolled
        $existingEnrollment = $this->enrollments()
            ->where('lead_id', $lead->id)
            ->first();

        if ($existingEnrollment) {
            return $existingEnrollment;
        }

        return $this->enrollments()->create([
            'lead_id' => $lead->id,
            'status' => CampaignEnrollment::STATUS_ENROLLED,
            'enrolled_at' => now(),
            'current_step' => 0,
        ]);
    }

    /**
     * Enroll a contact in this campaign.
     */
    public function enrollContact(Contact $contact): CampaignEnrollment
    {
        // Check if contact is already enrolled
        $existingEnrollment = $this->enrollments()
            ->where('contact_id', $contact->id)
            ->first();

        if ($existingEnrollment) {
            return $existingEnrollment;
        }

        return $this->enrollments()->create([
            'contact_id' => $contact->id,
            'status' => CampaignEnrollment::STATUS_ENROLLED,
            'enrolled_at' => now(),
            'current_step' => 0,
        ]);
    }

    /**
     * Update campaign metrics.
     */
    public function updateMetrics(): void
    {
        $enrollments = $this->enrollments();

        $this->update([
            'total_recipients' => $enrollments->count(),
            'total_sent' => $enrollments->sum('emails_sent'),
            'total_opened' => $enrollments->sum('emails_opened'),
            'total_clicked' => $enrollments->sum('emails_clicked'),
            'total_converted' => $enrollments->where('converted', true)->count(),
        ]);
    }

    /**
     * Get campaign performance summary.
     */
    public function getPerformanceSummary(): array
    {
        return [
            'total_recipients' => $this->total_recipients,
            'total_sent' => $this->total_sent,
            'total_delivered' => $this->total_delivered,
            'total_opened' => $this->total_opened,
            'total_clicked' => $this->total_clicked,
            'total_converted' => $this->total_converted,
            'open_rate' => $this->open_rate,
            'click_through_rate' => $this->click_through_rate,
            'conversion_rate' => $this->conversion_rate,
            'unsubscribe_rate' => $this->unsubscribe_rate,
            'bounce_rate' => $this->bounce_rate,
            'total_revenue' => $this->total_revenue,
            'roi' => $this->roi,
        ];
    }
}
