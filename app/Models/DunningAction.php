<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dunning Action Model
 *
 * Represents individual collection actions and their outcomes with
 * comprehensive tracking of communication attempts, responses, and results.
 *
 * @property int $id
 * @property int $campaign_id
 * @property int $sequence_id
 * @property int $client_id
 * @property int $invoice_id
 * @property string $action_reference
 * @property string $action_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $attempted_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property string|null $recipient_email
 * @property string|null $recipient_phone
 * @property string|null $recipient_name
 * @property string|null $message_subject
 * @property string|null $message_content
 * @property string|null $template_used
 * @property string|null $email_message_id
 * @property string|null $sms_message_id
 * @property string|null $call_session_id
 * @property array|null $delivery_metadata
 * @property bool $opened
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property bool $clicked
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property string|null $response_type
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property array|null $response_data
 * @property float $invoice_amount
 * @property float $amount_due
 * @property float $late_fees
 * @property int $days_overdue
 * @property float|null $settlement_offer_amount
 * @property float $amount_collected
 * @property array|null $suspended_services
 * @property array|null $maintained_services
 * @property \Illuminate\Support\Carbon|null $suspension_effective_at
 * @property \Illuminate\Support\Carbon|null $restoration_scheduled_at
 * @property string|null $suspension_reason
 * @property bool $final_notice
 * @property bool $legal_action_threatened
 * @property array|null $compliance_flags
 * @property string|null $legal_disclaimer
 * @property bool $dispute_period_active
 * @property \Illuminate\Support\Carbon|null $dispute_deadline
 * @property bool $escalated
 * @property int|null $escalated_to_user_id
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property string|null $escalation_reason
 * @property string|null $escalation_level
 * @property float $cost_per_action
 * @property bool $resulted_in_payment
 * @property float $roi
 * @property int|null $client_satisfaction_score
 * @property string|null $error_message
 * @property array|null $error_details
 * @property \Illuminate\Support\Carbon|null $last_error_at
 * @property bool $requires_manual_review
 * @property bool $pause_sequence
 * @property string|null $pause_reason
 * @property \Illuminate\Support\Carbon|null $sequence_resumed_at
 * @property int|null $next_action_id
 */
class DunningAction extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'dunning_actions';

    protected $fillable = [
        'company_id', 'campaign_id', 'sequence_id', 'client_id', 'invoice_id',
        'action_reference', 'action_type', 'status', 'scheduled_at', 'attempted_at',
        'completed_at', 'expires_at', 'retry_count', 'next_retry_at',
        'recipient_email', 'recipient_phone', 'recipient_name', 'message_subject',
        'message_content', 'template_used', 'email_message_id', 'sms_message_id',
        'call_session_id', 'delivery_metadata', 'opened', 'opened_at', 'clicked',
        'clicked_at', 'response_type', 'responded_at', 'response_data',
        'invoice_amount', 'amount_due', 'late_fees', 'days_overdue',
        'settlement_offer_amount', 'amount_collected', 'suspended_services',
        'maintained_services', 'suspension_effective_at', 'restoration_scheduled_at',
        'suspension_reason', 'final_notice', 'legal_action_threatened',
        'compliance_flags', 'legal_disclaimer', 'dispute_period_active',
        'dispute_deadline', 'escalated', 'escalated_to_user_id', 'escalated_at',
        'escalation_reason', 'escalation_level', 'cost_per_action',
        'resulted_in_payment', 'roi', 'client_satisfaction_score',
        'error_message', 'error_details', 'last_error_at', 'requires_manual_review',
        'pause_sequence', 'pause_reason', 'sequence_resumed_at', 'next_action_id',
        'created_by', 'processed_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'campaign_id' => 'integer',
        'sequence_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'scheduled_at' => 'datetime',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'delivery_metadata' => 'array',
        'opened' => 'boolean',
        'opened_at' => 'datetime',
        'clicked' => 'boolean',
        'clicked_at' => 'datetime',
        'responded_at' => 'datetime',
        'response_data' => 'array',
        'invoice_amount' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'days_overdue' => 'integer',
        'settlement_offer_amount' => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'suspended_services' => 'array',
        'maintained_services' => 'array',
        'suspension_effective_at' => 'datetime',
        'restoration_scheduled_at' => 'datetime',
        'final_notice' => 'boolean',
        'legal_action_threatened' => 'boolean',
        'compliance_flags' => 'array',
        'dispute_period_active' => 'boolean',
        'dispute_deadline' => 'datetime',
        'escalated' => 'boolean',
        'escalated_to_user_id' => 'integer',
        'escalated_at' => 'datetime',
        'cost_per_action' => 'decimal:4',
        'resulted_in_payment' => 'boolean',
        'roi' => 'decimal:4',
        'client_satisfaction_score' => 'integer',
        'error_details' => 'array',
        'last_error_at' => 'datetime',
        'requires_manual_review' => 'boolean',
        'pause_sequence' => 'boolean',
        'sequence_resumed_at' => 'datetime',
        'next_action_id' => 'integer',
        'created_by' => 'integer',
        'processed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_PROCESSING = 'processing';

    const STATUS_SENT = 'sent';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_FAILED = 'failed';

    const STATUS_BOUNCED = 'bounced';

    const STATUS_OPENED = 'opened';

    const STATUS_CLICKED = 'clicked';

    const STATUS_RESPONDED = 'responded';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ESCALATED = 'escalated';

    // Action type constants (same as DunningSequence)
    const ACTION_EMAIL = 'email';

    const ACTION_SMS = 'sms';

    const ACTION_PHONE_CALL = 'phone_call';

    const ACTION_LETTER = 'letter';

    const ACTION_PORTAL_NOTIFICATION = 'portal_notification';

    const ACTION_SERVICE_SUSPENSION = 'service_suspension';

    const ACTION_SERVICE_RESTORATION = 'service_restoration';

    const ACTION_LEGAL_HANDOFF = 'legal_handoff';

    const ACTION_COLLECTION_AGENCY = 'collection_agency';

    const ACTION_PAYMENT_PLAN_OFFER = 'payment_plan_offer';

    const ACTION_SETTLEMENT_OFFER = 'settlement_offer';

    const ACTION_ACCOUNT_HOLD = 'account_hold';

    const ACTION_CREDIT_HOLD = 'credit_hold';

    const ACTION_WRITEOFF = 'writeoff';

    // Response type constants
    const RESPONSE_PAYMENT = 'payment';

    const RESPONSE_CONTACT = 'contact';

    const RESPONSE_DISPUTE = 'dispute';

    const RESPONSE_PROMISE_TO_PAY = 'promise_to_pay';

    const RESPONSE_HARDSHIP = 'hardship';

    const RESPONSE_LEGAL_THREAT = 'legal_threat';

    // Escalation level constants
    const ESCALATION_MANAGER = 'manager';

    const ESCALATION_LEGAL = 'legal';

    const ESCALATION_COLLECTION_AGENCY = 'collection_agency';

    const ESCALATION_WRITEOFF = 'writeoff';

    /**
     * Get the campaign this action belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DunningCampaign::class, 'campaign_id');
    }

    /**
     * Get the sequence this action belongs to.
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DunningSequence::class, 'sequence_id');
    }

    /**
     * Get the client for this action.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the invoice for this action.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get collection notes related to this action.
     */
    public function collectionNotes(): HasMany
    {
        return $this->hasMany(CollectionNote::class, 'dunning_action_id');
    }

    /**
     * Get payment plans created from this action.
     */
    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class, 'dunning_action_id');
    }

    /**
     * Get account holds created from this action.
     */
    public function accountHolds(): HasMany
    {
        return $this->hasMany(AccountHold::class, 'dunning_action_id');
    }

    /**
     * Get the user who created this action.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who processed this action.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user to whom this action was escalated.
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    /**
     * Check if action is ready to execute.
     */
    public function isReadyToExecute(): bool
    {
        return $this->status === self::STATUS_SCHEDULED &&
               $this->scheduled_at &&
               Carbon::now()->gte($this->scheduled_at);
    }

    /**
     * Check if action can be retried.
     */
    public function canRetry(): bool
    {
        $maxRetries = $this->sequence->max_retry_attempts ?? 3;

        return $this->status === self::STATUS_FAILED &&
               $this->retry_count < $maxRetries;
    }

    /**
     * Check if action is a communication type.
     */
    public function isCommunicationAction(): bool
    {
        return in_array($this->action_type, [
            self::ACTION_EMAIL,
            self::ACTION_SMS,
            self::ACTION_PHONE_CALL,
            self::ACTION_LETTER,
            self::ACTION_PORTAL_NOTIFICATION,
        ]);
    }

    /**
     * Check if action resulted in customer engagement.
     */
    public function hasCustomerEngagement(): bool
    {
        return $this->opened || $this->clicked || $this->responded_at;
    }

    /**
     * Mark action as sent/delivered.
     */
    public function markAsSent(array $metadata = []): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'attempted_at' => Carbon::now(),
            'delivery_metadata' => array_merge($this->delivery_metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark action as opened (for emails).
     */
    public function markAsOpened(): void
    {
        if (! $this->opened) {
            $this->update([
                'opened' => true,
                'opened_at' => Carbon::now(),
                'status' => self::STATUS_OPENED,
            ]);
        }
    }

    /**
     * Mark action as clicked.
     */
    public function markAsClicked(): void
    {
        if (! $this->clicked) {
            $this->update([
                'clicked' => true,
                'clicked_at' => Carbon::now(),
                'status' => self::STATUS_CLICKED,
            ]);
        }
    }

    /**
     * Record customer response.
     */
    public function recordResponse(string $responseType, array $responseData = []): void
    {
        $this->update([
            'response_type' => $responseType,
            'response_data' => $responseData,
            'responded_at' => Carbon::now(),
            'status' => self::STATUS_RESPONDED,
        ]);

        // Create collection note for the response
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'dunning_action_id' => $this->id,
            'note_type' => $responseType === self::RESPONSE_CONTACT ? 'client_contact' : $responseType,
            'subject' => 'Customer Response to '.ucwords(str_replace('_', ' ', $this->action_type)),
            'content' => 'Customer responded with: '.$responseType,
            'outcome' => $responseType,
            'outcome_details' => json_encode($responseData),
            'created_by' => 1, // System user
        ]);
    }

    /**
     * Record payment received as result of this action.
     */
    public function recordPayment(float $amount): void
    {
        $this->update([
            'resulted_in_payment' => true,
            'amount_collected' => $amount,
            'status' => self::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
        ]);

        // Calculate ROI
        if ($this->cost_per_action > 0) {
            $roi = (($amount - $this->cost_per_action) / $this->cost_per_action) * 100;
            $this->update(['roi' => $roi]);
        }
    }

    /**
     * Mark action as failed with error details.
     */
    public function markAsFailed(string $errorMessage, array $errorDetails = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'last_error_at' => Carbon::now(),
        ]);

        // Schedule retry if possible
        if ($this->canRetry()) {
            $retryInterval = $this->sequence->retry_interval_hours ?? 24;
            $this->update([
                'next_retry_at' => Carbon::now()->addHours($retryInterval),
                'retry_count' => $this->retry_count + 1,
            ]);
        }
    }

    /**
     * Escalate action to higher level.
     */
    public function escalate(string $reason, string $level = self::ESCALATION_MANAGER, ?int $escalatedToUserId = null): void
    {
        $this->update([
            'escalated' => true,
            'escalation_reason' => $reason,
            'escalation_level' => $level,
            'escalated_to_user_id' => $escalatedToUserId,
            'escalated_at' => Carbon::now(),
            'status' => self::STATUS_ESCALATED,
            'requires_manual_review' => true,
        ]);

        // Create collection note for escalation
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'dunning_action_id' => $this->id,
            'note_type' => 'escalation',
            'priority' => $level === self::ESCALATION_LEGAL ? 'urgent' : 'high',
            'subject' => 'Action Escalated to '.ucwords(str_replace('_', ' ', $level)),
            'content' => $reason,
            'escalation_risk' => true,
            'attorney_review_required' => $level === self::ESCALATION_LEGAL,
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Pause the sequence due to this action.
     */
    public function pauseSequence(string $reason): void
    {
        $this->update([
            'pause_sequence' => true,
            'pause_reason' => $reason,
        ]);
    }

    /**
     * Resume the sequence from this action.
     */
    public function resumeSequence(): void
    {
        $this->update([
            'pause_sequence' => false,
            'sequence_resumed_at' => Carbon::now(),
            'pause_reason' => null,
        ]);
    }

    /**
     * Calculate the effectiveness score for this action.
     */
    public function calculateEffectivenessScore(): int
    {
        $score = 0;

        // Base score for completion
        if ($this->status === self::STATUS_COMPLETED) {
            $score += 40;
        } elseif ($this->status === self::STATUS_RESPONDED) {
            $score += 30;
        } elseif ($this->hasCustomerEngagement()) {
            $score += 20;
        } elseif ($this->status === self::STATUS_DELIVERED) {
            $score += 10;
        }

        // Bonus for payment
        if ($this->resulted_in_payment) {
            $score += 30;
        }

        // Bonus for customer satisfaction
        if ($this->client_satisfaction_score) {
            $score += ($this->client_satisfaction_score / 10) * 10; // Scale 1-10 to 1-10
        }

        // Penalty for escalation
        if ($this->escalated) {
            $score -= 10;
        }

        // ROI consideration
        if ($this->roi > 100) {
            $score += 10;
        } elseif ($this->roi < 0) {
            $score -= 10;
        }

        return max(0, min(100, $score)); // Keep between 0-100
    }

    /**
     * Scope to get pending actions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get scheduled actions ready to execute.
     */
    public function scopeReadyToExecute($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', Carbon::now());
    }

    /**
     * Scope to get failed actions that can be retried.
     */
    public function scopeCanRetry($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('next_retry_at', '<=', Carbon::now())
            ->whereColumn('retry_count', '<', 'max_retry_attempts');
    }

    /**
     * Scope to get actions by action type.
     */
    public function scopeActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to get successful actions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('resulted_in_payment', true);
    }

    /**
     * Scope to get escalated actions.
     */
    public function scopeEscalated($query)
    {
        return $query->where('escalated', true);
    }

    /**
     * Generate unique action reference.
     */
    public static function generateActionReference(): string
    {
        $prefix = 'DA';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($action) {
            if (! $action->action_reference) {
                $action->action_reference = self::generateActionReference();
            }
            if (! $action->created_by) {
                $action->created_by = auth()->id() ?? 1;
            }
        });

        static::updated(function ($action) {
            // Update sequence performance metrics when action is completed
            if ($action->isDirty('status') &&
                in_array($action->status, [self::STATUS_COMPLETED, self::STATUS_RESPONDED])) {
                $action->sequence->updatePerformanceMetrics();
            }
        });
    }
}
