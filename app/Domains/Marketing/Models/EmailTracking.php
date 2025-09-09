<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Lead\Models\Lead;
use App\Models\Contact;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTracking extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tracking_id',
        'lead_id',
        'contact_id',
        'recipient_email',
        'campaign_id',
        'campaign_sequence_id',
        'email_type',
        'subject_line',
        'status',
        'sent_at',
        'delivered_at',
        'bounced_at',
        'bounce_reason',
        'first_opened_at',
        'last_opened_at',
        'open_count',
        'first_clicked_at',
        'last_clicked_at',
        'click_count',
        'replied_at',
        'unsubscribed_at',
        'user_agent',
        'ip_address',
        'location',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'lead_id' => 'integer',
        'contact_id' => 'integer',
        'campaign_id' => 'integer',
        'campaign_sequence_id' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'open_count' => 'integer',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'click_count' => 'integer',
        'replied_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    // Email status constants
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_FAILED = 'failed';

    // Email type constants
    const TYPE_CAMPAIGN = 'campaign';
    const TYPE_TRANSACTIONAL = 'transactional';
    const TYPE_MANUAL = 'manual';

    /**
     * Get the campaign.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    /**
     * Get the campaign sequence.
     */
    public function campaignSequence(): BelongsTo
    {
        return $this->belongsTo(CampaignSequence::class, 'campaign_sequence_id');
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
     * Check if email was opened.
     */
    public function wasOpened(): bool
    {
        return !is_null($this->first_opened_at);
    }

    /**
     * Check if email was clicked.
     */
    public function wasClicked(): bool
    {
        return !is_null($this->first_clicked_at);
    }

    /**
     * Check if email was replied to.
     */
    public function wasReplied(): bool
    {
        return !is_null($this->replied_at);
    }

    /**
     * Check if recipient unsubscribed.
     */
    public function wasUnsubscribed(): bool
    {
        return !is_null($this->unsubscribed_at);
    }

    /**
     * Check if email bounced.
     */
    public function bounced(): bool
    {
        return $this->status === self::STATUS_BOUNCED;
    }

    /**
     * Check if email was delivered.
     */
    public function wasDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Get engagement score (0-100).
     */
    public function getEngagementScoreAttribute(): int
    {
        $score = 0;
        
        if ($this->wasOpened()) {
            $score += 30;
        }
        
        if ($this->wasClicked()) {
            $score += 50;
        }
        
        if ($this->wasReplied()) {
            $score += 100;
        }
        
        // Bonus for multiple opens/clicks
        if ($this->open_count > 1) {
            $score += min(($this->open_count - 1) * 5, 20);
        }
        
        if ($this->click_count > 1) {
            $score += min(($this->click_count - 1) * 10, 30);
        }

        return min($score, 100);
    }

    /**
     * Get time to first open in minutes.
     */
    public function getTimeToOpenAttribute(): ?int
    {
        if (!$this->first_opened_at || !$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->first_opened_at);
    }

    /**
     * Get time to first click in minutes.
     */
    public function getTimeToClickAttribute(): ?int
    {
        if (!$this->first_clicked_at || !$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->first_clicked_at);
    }

    /**
     * Get available email statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_BOUNCED => 'Bounced',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    /**
     * Get available email types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CAMPAIGN => 'Campaign',
            self::TYPE_TRANSACTIONAL => 'Transactional',
            self::TYPE_MANUAL => 'Manual',
        ];
    }

    /**
     * Scope to filter by campaign.
     */
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to filter opened emails.
     */
    public function scopeOpened($query)
    {
        return $query->whereNotNull('first_opened_at');
    }

    /**
     * Scope to filter clicked emails.
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('first_clicked_at');
    }

    /**
     * Scope to filter replied emails.
     */
    public function scopeReplied($query)
    {
        return $query->whereNotNull('replied_at');
    }

    /**
     * Scope to filter unsubscribed emails.
     */
    public function scopeUnsubscribed($query)
    {
        return $query->whereNotNull('unsubscribed_at');
    }

    /**
     * Scope to filter bounced emails.
     */
    public function scopeBounced($query)
    {
        return $query->where('status', self::STATUS_BOUNCED);
    }

    /**
     * Scope to filter delivered emails.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope to filter by email type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('email_type', $type);
    }

    /**
     * Scope to get recent tracking data.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get high engagement emails.
     */
    public function scopeHighEngagement($query, int $minScore = 50)
    {
        return $query->whereRaw('
            (CASE 
                WHEN first_opened_at IS NOT NULL THEN 30 ELSE 0 END) +
            (CASE 
                WHEN first_clicked_at IS NOT NULL THEN 50 ELSE 0 END) +
            (CASE 
                WHEN replied_at IS NOT NULL THEN 100 ELSE 0 END) +
            (LEAST((open_count - 1) * 5, 20)) +
            (LEAST((click_count - 1) * 10, 30))
            >= ?
        ', [$minScore]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as bounced.
     */
    public function markAsBounced(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'bounce_reason' => $reason,
        ]);
    }

    /**
     * Record email open.
     */
    public function recordOpen(string $userAgent = null, string $ipAddress = null): void
    {
        $updateData = [
            'open_count' => $this->open_count + 1,
            'last_opened_at' => now(),
        ];

        if ($userAgent) {
            $updateData['user_agent'] = $userAgent;
        }

        if ($ipAddress) {
            $updateData['ip_address'] = $ipAddress;
        }

        if (!$this->first_opened_at) {
            $updateData['first_opened_at'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Record email click.
     */
    public function recordClick(string $userAgent = null, string $ipAddress = null): void
    {
        $updateData = [
            'click_count' => $this->click_count + 1,
            'last_clicked_at' => now(),
        ];

        if ($userAgent) {
            $updateData['user_agent'] = $userAgent;
        }

        if ($ipAddress) {
            $updateData['ip_address'] = $ipAddress;
        }

        if (!$this->first_clicked_at) {
            $updateData['first_clicked_at'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Record email reply.
     */
    public function recordReply(): void
    {
        $this->update(['replied_at' => now()]);
    }

    /**
     * Record unsubscribe.
     */
    public function recordUnsubscribe(): void
    {
        $this->update(['unsubscribed_at' => now()]);
    }
}