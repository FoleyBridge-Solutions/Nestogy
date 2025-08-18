<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Lead\Models\Lead;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'contact_id',
        'status',
        'current_step',
        'enrolled_at',
        'last_activity_at',
        'next_send_at',
        'completed_at',
        'emails_sent',
        'emails_opened',
        'emails_clicked',
        'converted',
        'converted_at',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'lead_id' => 'integer',
        'contact_id' => 'integer',
        'current_step' => 'integer',
        'enrolled_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'next_send_at' => 'datetime',
        'completed_at' => 'datetime',
        'emails_sent' => 'integer',
        'emails_opened' => 'integer',
        'emails_clicked' => 'integer',
        'converted' => 'boolean',
        'converted_at' => 'datetime',
    ];

    // Enrollment status constants
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PAUSED = 'paused';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_BOUNCED = 'bounced';

    /**
     * Get the campaign.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    /**
     * Get the lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the contact.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the recipient (lead or contact).
     */
    public function getRecipientAttribute()
    {
        return $this->lead ?: $this->contact;
    }

    /**
     * Get recipient email.
     */
    public function getRecipientEmailAttribute(): ?string
    {
        $recipient = $this->recipient;
        return $recipient ? $recipient->email : null;
    }

    /**
     * Get recipient name.
     */
    public function getRecipientNameAttribute(): ?string
    {
        if ($this->lead) {
            return $this->lead->full_name;
        }
        
        if ($this->contact) {
            return $this->contact->name;
        }

        return null;
    }

    /**
     * Get available enrollment statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ENROLLED => 'Enrolled',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            self::STATUS_BOUNCED => 'Bounced',
        ];
    }

    /**
     * Get days enrolled.
     */
    public function getDaysEnrolledAttribute(): int
    {
        return $this->enrolled_at->diffInDays(now());
    }

    /**
     * Get engagement rate.
     */
    public function getEngagementRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }

        return (($this->emails_opened + $this->emails_clicked) / $this->emails_sent) * 100;
    }

    /**
     * Get open rate.
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }

        return ($this->emails_opened / $this->emails_sent) * 100;
    }

    /**
     * Get click rate.
     */
    public function getClickRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }

        return ($this->emails_clicked / $this->emails_sent) * 100;
    }

    /**
     * Check if enrollment is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if enrollment is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Check if recipient unsubscribed.
     */
    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    /**
     * Check if ready for next email.
     */
    public function isReadyForNextEmail(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->next_send_at) {
            return true;
        }

        return $this->next_send_at <= now();
    }

    /**
     * Scope to get active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get enrollments ready for next email.
     */
    public function scopeReadyForEmail($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function($q) {
                        $q->whereNull('next_send_at')
                          ->orWhere('next_send_at', '<=', now());
                    });
    }

    /**
     * Scope to get converted enrollments.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    /**
     * Start the enrollment.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'current_step' => 1,
        ]);
    }

    /**
     * Pause the enrollment.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume the enrollment.
     */
    public function resume(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Complete the enrollment.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as unsubscribed.
     */
    public function unsubscribe(): void
    {
        $this->update(['status' => self::STATUS_UNSUBSCRIBED]);
    }

    /**
     * Mark as bounced.
     */
    public function markAsBounced(): void
    {
        $this->update(['status' => self::STATUS_BOUNCED]);
    }

    /**
     * Advance to next step.
     */
    public function advanceToNextStep(): void
    {
        $campaign = $this->campaign;
        $nextStep = $this->current_step + 1;
        
        // Check if there are more steps
        $hasNextStep = $campaign->sequences()
            ->where('step_number', $nextStep)
            ->where('is_active', true)
            ->exists();

        if ($hasNextStep) {
            $nextSequence = $campaign->sequences()
                ->where('step_number', $nextStep)
                ->where('is_active', true)
                ->first();

            $nextSendAt = now()
                ->addDays($nextSequence->delay_days)
                ->addHours($nextSequence->delay_hours);

            $this->update([
                'current_step' => $nextStep,
                'next_send_at' => $nextSendAt,
                'last_activity_at' => now(),
            ]);
        } else {
            // No more steps, complete the enrollment
            $this->complete();
        }
    }

    /**
     * Record email sent.
     */
    public function recordEmailSent(): void
    {
        $this->increment('emails_sent');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Record email opened.
     */
    public function recordEmailOpened(): void
    {
        $this->increment('emails_opened');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Record email clicked.
     */
    public function recordEmailClicked(): void
    {
        $this->increment('emails_clicked');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Mark as converted.
     */
    public function markAsConverted(): void
    {
        $this->update([
            'converted' => true,
            'converted_at' => now(),
        ]);

        // Also update the campaign metrics
        $this->campaign->increment('total_converted');
    }

    /**
     * Get current sequence step.
     */
    public function getCurrentSequence(): ?CampaignSequence
    {
        return $this->campaign->sequences()
            ->where('step_number', $this->current_step)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate next send time based on sequence settings.
     */
    public function calculateNextSendTime(CampaignSequence $sequence): \Carbon\Carbon
    {
        $nextSend = now()
            ->addDays($sequence->delay_days)
            ->addHours($sequence->delay_hours);

        // Adjust for preferred send time
        if ($sequence->send_time) {
            $sendTime = $sequence->send_time;
            $nextSend->setTime($sendTime->hour, $sendTime->minute);
        }

        // Adjust for preferred send days
        if ($sequence->send_days && !empty($sequence->send_days)) {
            while (!$sequence->canSendOnDay($nextSend->dayOfWeek)) {
                $nextSend->addDay();
            }
        }

        return $nextSend;
    }
}