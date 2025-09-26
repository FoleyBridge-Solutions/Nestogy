<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MailQueue extends Model
{
    use HasFactory, BelongsToCompany;
    
    protected $table = 'mail_queue';
    
    protected $fillable = [
        'uuid',
        'company_id',
        'client_id',
        'contact_id',
        'user_id',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'cc',
        'bcc',
        'reply_to',
        'subject',
        'html_body',
        'text_body',
        'attachments',
        'headers',
        'template',
        'template_data',
        'status',
        'priority',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'next_retry_at',
        'last_error',
        'error_log',
        'failure_reason',
        'mailer',
        'message_id',
        'provider_response',
        'tracking_token',
        'opened_at',
        'open_count',
        'opens',
        'click_count',
        'clicks',
        'category',
        'related_type',
        'related_id',
        'tags',
        'metadata',
    ];
    
    protected $casts = [
        'cc' => 'array',
        'bcc' => 'array',
        'attachments' => 'array',
        'headers' => 'array',
        'template_data' => 'array',
        'error_log' => 'array',
        'provider_response' => 'array',
        'opens' => 'array',
        'clicks' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'opened_at' => 'datetime',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_COMPLAINED = 'complained';
    const STATUS_CANCELLED = 'cancelled';
    
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';
    
    const CATEGORY_INVOICE = 'invoice';
    const CATEGORY_NOTIFICATION = 'notification';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_PORTAL = 'portal';
    const CATEGORY_SUPPORT = 'support';
    const CATEGORY_REPORT = 'report';
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (!$model->tracking_token) {
                $model->tracking_token = Str::random(32);
            }
        });
    }
    
    /**
     * Get the client associated with the email.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Get the contact associated with the email.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
    
    /**
     * Get the user who initiated the email.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the related model.
     */
    public function related()
    {
        return $this->morphTo();
    }
    
    /**
     * Check if email is ready to be sent.
     */
    public function isReadyToSend(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               (!$this->scheduled_at || $this->scheduled_at->isPast());
    }
    
    /**
     * Check if email can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && 
               $this->attempts < $this->max_attempts;
    }
    
    /**
     * Mark email as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }
    
    /**
     * Mark email as sent.
     */
    public function markAsSent(?string $messageId = null, ?array $providerResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'message_id' => $messageId,
            'provider_response' => $providerResponse,
        ]);
    }
    
    /**
     * Mark email as failed.
     */
    public function markAsFailed(string $error, ?string $reason = null): void
    {
        $errorLog = $this->error_log ?? [];
        $errorLog[] = [
            'attempt' => $this->attempts + 1,
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
        ];
        
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'attempts' => $this->attempts + 1,
            'last_error' => $error,
            'error_log' => $errorLog,
            'failure_reason' => $reason,
            'next_retry_at' => $this->calculateNextRetryTime(),
        ]);
    }
    
    /**
     * Calculate next retry time based on exponential backoff.
     */
    protected function calculateNextRetryTime(): ?string
    {
        if ($this->attempts >= $this->max_attempts) {
            return null;
        }
        
        // Exponential backoff: 5 min, 15 min, 45 min, etc.
        $minutes = 5 * pow(3, $this->attempts);
        
        return now()->addMinutes($minutes);
    }
    
    /**
     * Record email open.
     */
    public function recordOpen(array $data = []): void
    {
        $opens = $this->opens ?? [];
        $opens[] = array_merge($data, [
            'timestamp' => now()->toIso8601String(),
        ]);
        
        $this->update([
            'opened_at' => $this->opened_at ?? now(),
            'open_count' => $this->open_count + 1,
            'opens' => $opens,
        ]);
    }
    
    /**
     * Record email click.
     */
    public function recordClick(string $url, array $data = []): void
    {
        $clicks = $this->clicks ?? [];
        $clicks[] = array_merge($data, [
            'url' => $url,
            'timestamp' => now()->toIso8601String(),
        ]);
        
        $this->update([
            'click_count' => $this->click_count + 1,
            'clicks' => $clicks,
        ]);
    }
    
    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_SENT => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_BOUNCED => 'orange',
            self::STATUS_COMPLAINED => 'purple',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }
    
    /**
     * Get priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_CRITICAL => 'red',
            default => 'gray'
        };
    }
}