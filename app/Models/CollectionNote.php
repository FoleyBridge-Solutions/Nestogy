<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Collection Note Model
 *
 * Represents detailed collection activity tracking and notes with
 * comprehensive outcome tracking and follow-up management.
 *
 * @property int $id
 * @property int $client_id
 * @property int|null $invoice_id
 * @property int|null $dunning_action_id
 * @property int|null $payment_plan_id
 * @property string $note_type
 * @property string $priority
 * @property string $visibility
 * @property string $subject
 * @property string $content
 * @property array|null $metadata
 * @property array|null $tags
 * @property string|null $communication_method
 * @property string|null $contact_person
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property \Illuminate\Support\Carbon|null $contact_datetime
 * @property int|null $call_duration_seconds
 * @property string|null $outcome
 * @property string|null $outcome_details
 * @property bool $contains_promise_to_pay
 * @property float|null $promised_amount
 * @property \Illuminate\Support\Carbon|null $promised_payment_date
 * @property bool|null $promise_kept
 * @property \Illuminate\Support\Carbon|null $promise_followed_up_at
 * @property bool $contains_dispute
 * @property string|null $dispute_reason
 * @property float|null $disputed_amount
 * @property string|null $dispute_status
 * @property \Illuminate\Support\Carbon|null $dispute_deadline
 * @property bool $requires_followup
 * @property \Illuminate\Support\Carbon|null $followup_date
 * @property \Illuminate\Support\Carbon|null $followup_time
 * @property string|null $followup_type
 * @property string|null $followup_instructions
 * @property bool $followup_completed
 * @property \Illuminate\Support\Carbon|null $followup_completed_at
 * @property bool $legally_significant
 * @property bool $compliance_sensitive
 * @property array|null $compliance_flags
 * @property bool $attorney_review_required
 * @property bool $attorney_reviewed
 * @property int|null $reviewed_by_attorney_id
 * @property \Illuminate\Support\Carbon|null $attorney_reviewed_at
 * @property string|null $client_mood
 * @property int|null $satisfaction_rating
 * @property bool $escalation_risk
 * @property string|null $relationship_notes
 * @property float|null $invoice_balance_at_time
 * @property int|null $days_overdue_at_time
 * @property float|null $total_account_balance
 * @property array|null $payment_history_summary
 * @property array|null $attachments
 * @property array|null $related_documents
 * @property string|null $external_reference
 * @property bool $billable_time
 * @property int|null $time_spent_minutes
 * @property float|null $hourly_rate
 * @property float|null $billable_amount
 * @property bool $quality_reviewed
 * @property int|null $quality_reviewed_by
 * @property \Illuminate\Support\Carbon|null $quality_reviewed_at
 * @property int|null $quality_score
 * @property string|null $quality_feedback
 * @property bool $flagged_for_review
 * @property bool $archived
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class CollectionNote extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'collection_notes';

    protected $fillable = [
        'company_id', 'client_id', 'invoice_id', 'dunning_action_id', 'payment_plan_id',
        'note_type', 'priority', 'visibility', 'subject', 'content', 'metadata', 'tags',
        'communication_method', 'contact_person', 'contact_phone', 'contact_email',
        'contact_datetime', 'call_duration_seconds', 'outcome', 'outcome_details',
        'contains_promise_to_pay', 'promised_amount', 'promised_payment_date',
        'promise_kept', 'promise_followed_up_at', 'contains_dispute', 'dispute_reason',
        'disputed_amount', 'dispute_status', 'dispute_deadline', 'requires_followup',
        'followup_date', 'followup_time', 'followup_type', 'followup_instructions',
        'followup_completed', 'followup_completed_at', 'legally_significant',
        'compliance_sensitive', 'compliance_flags', 'attorney_review_required',
        'attorney_reviewed', 'reviewed_by_attorney_id', 'attorney_reviewed_at',
        'client_mood', 'satisfaction_rating', 'escalation_risk', 'relationship_notes',
        'invoice_balance_at_time', 'days_overdue_at_time', 'total_account_balance',
        'payment_history_summary', 'attachments', 'related_documents',
        'external_reference', 'billable_time', 'time_spent_minutes', 'hourly_rate',
        'billable_amount', 'quality_reviewed', 'quality_reviewed_by',
        'quality_reviewed_at', 'quality_score', 'quality_feedback',
        'flagged_for_review', 'archived', 'archived_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'dunning_action_id' => 'integer',
        'payment_plan_id' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
        'contact_datetime' => 'datetime',
        'call_duration_seconds' => 'integer',
        'contains_promise_to_pay' => 'boolean',
        'promised_amount' => 'decimal:2',
        'promised_payment_date' => 'date',
        'promise_kept' => 'boolean',
        'promise_followed_up_at' => 'datetime',
        'contains_dispute' => 'boolean',
        'disputed_amount' => 'decimal:2',
        'dispute_deadline' => 'date',
        'requires_followup' => 'boolean',
        'followup_date' => 'date',
        'followup_time' => 'datetime',
        'followup_completed' => 'boolean',
        'followup_completed_at' => 'datetime',
        'legally_significant' => 'boolean',
        'compliance_sensitive' => 'boolean',
        'compliance_flags' => 'array',
        'attorney_review_required' => 'boolean',
        'attorney_reviewed' => 'boolean',
        'reviewed_by_attorney_id' => 'integer',
        'attorney_reviewed_at' => 'datetime',
        'satisfaction_rating' => 'integer',
        'escalation_risk' => 'boolean',
        'invoice_balance_at_time' => 'decimal:2',
        'days_overdue_at_time' => 'integer',
        'total_account_balance' => 'decimal:2',
        'payment_history_summary' => 'array',
        'attachments' => 'array',
        'related_documents' => 'array',
        'billable_time' => 'boolean',
        'time_spent_minutes' => 'integer',
        'hourly_rate' => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'quality_reviewed' => 'boolean',
        'quality_reviewed_by' => 'integer',
        'quality_reviewed_at' => 'datetime',
        'quality_score' => 'integer',
        'flagged_for_review' => 'boolean',
        'archived' => 'boolean',
        'archived_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Note type constants
    const TYPE_CONTACT_ATTEMPT = 'contact_attempt';

    const TYPE_CLIENT_CONTACT = 'client_contact';

    const TYPE_PROMISE_TO_PAY = 'promise_to_pay';

    const TYPE_DISPUTE = 'dispute';

    const TYPE_HARDSHIP = 'hardship';

    const TYPE_PAYMENT_ARRANGEMENT = 'payment_arrangement';

    const TYPE_LEGAL_ACTION = 'legal_action';

    const TYPE_SETTLEMENT = 'settlement';

    const TYPE_SERVICE_SUSPENSION = 'service_suspension';

    const TYPE_ACCOUNT_REVIEW = 'account_review';

    const TYPE_ESCALATION = 'escalation';

    const TYPE_RESOLUTION = 'resolution';

    const TYPE_COMPLIANCE_ISSUE = 'compliance_issue';

    const TYPE_SYSTEM_GENERATED = 'system_generated';

    const TYPE_MANUAL_ENTRY = 'manual_entry';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    // Visibility constants
    const VISIBILITY_INTERNAL = 'internal';

    const VISIBILITY_CLIENT_VISIBLE = 'client_visible';

    const VISIBILITY_LEGAL_ONLY = 'legal_only';

    // Communication method constants
    const METHOD_PHONE = 'phone';

    const METHOD_EMAIL = 'email';

    const METHOD_SMS = 'sms';

    const METHOD_IN_PERSON = 'in_person';

    const METHOD_MAIL = 'mail';

    const METHOD_PORTAL = 'portal';

    const METHOD_SYSTEM_AUTOMATED = 'system_automated';

    const METHOD_THIRD_PARTY = 'third_party';

    // Outcome constants
    const OUTCOME_NO_ANSWER = 'no_answer';

    const OUTCOME_BUSY = 'busy';

    const OUTCOME_DISCONNECTED = 'disconnected';

    const OUTCOME_SPOKE_WITH_CLIENT = 'spoke_with_client';

    const OUTCOME_LEFT_MESSAGE = 'left_message';

    const OUTCOME_EMAIL_SENT = 'email_sent';

    const OUTCOME_EMAIL_BOUNCED = 'email_bounced';

    const OUTCOME_PAYMENT_PROMISED = 'payment_promised';

    const OUTCOME_DISPUTE_RAISED = 'dispute_raised';

    const OUTCOME_HARDSHIP_CLAIMED = 'hardship_claimed';

    const OUTCOME_PAYMENT_MADE = 'payment_made';

    const OUTCOME_PLAN_AGREED = 'plan_agreed';

    const OUTCOME_REFUSED_TO_PAY = 'refused_to_pay';

    const OUTCOME_REQUESTED_CALLBACK = 'requested_callback';

    const OUTCOME_HOSTILE_RESPONSE = 'hostile_response';

    // Client mood constants
    const MOOD_COOPERATIVE = 'cooperative';

    const MOOD_NEUTRAL = 'neutral';

    const MOOD_FRUSTRATED = 'frustrated';

    const MOOD_ANGRY = 'angry';

    const MOOD_HOSTILE = 'hostile';

    const MOOD_THREATENING = 'threatening';

    // Dispute status constants
    const DISPUTE_PENDING = 'pending';

    const DISPUTE_INVESTIGATING = 'investigating';

    const DISPUTE_RESOLVED = 'resolved';

    const DISPUTE_UPHELD = 'upheld';

    const DISPUTE_DENIED = 'denied';

    // Followup type constants
    const FOLLOWUP_CALL = 'call';

    const FOLLOWUP_EMAIL = 'email';

    const FOLLOWUP_SMS = 'sms';

    const FOLLOWUP_LETTER = 'letter';

    const FOLLOWUP_IN_PERSON = 'in_person';

    const FOLLOWUP_LEGAL_REVIEW = 'legal_review';

    /**
     * Get the client this note belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the invoice this note relates to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the dunning action this note relates to.
     */
    public function dunningAction(): BelongsTo
    {
        return $this->belongsTo(DunningAction::class, 'dunning_action_id');
    }

    /**
     * Get the payment plan this note relates to.
     */
    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }

    /**
     * Get the user who created this note.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attorney who reviewed this note.
     */
    public function reviewingAttorney(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_attorney_id');
    }

    /**
     * Get the user who quality reviewed this note.
     */
    public function qualityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_reviewed_by');
    }

    /**
     * Check if note requires follow-up.
     */
    public function requiresFollowup(): bool
    {
        return $this->requires_followup && ! $this->followup_completed;
    }

    /**
     * Check if follow-up is overdue.
     */
    public function isFollowupOverdue(): bool
    {
        return $this->requires_followup &&
               ! $this->followup_completed &&
               $this->followup_date &&
               Carbon::now()->gt($this->followup_date);
    }

    /**
     * Check if promise to pay is due.
     */
    public function isPromiseToPay(): bool
    {
        return $this->contains_promise_to_pay && $this->promised_payment_date;
    }

    /**
     * Check if promise to pay is overdue.
     */
    public function isPromiseOverdue(): bool
    {
        return $this->contains_promise_to_pay &&
               $this->promised_payment_date &&
               Carbon::now()->gt($this->promised_payment_date) &&
               $this->promise_kept !== true;
    }

    /**
     * Mark promise as kept.
     */
    public function markPromiseKept(?float $paymentAmount = null): void
    {
        $updateData = [
            'promise_kept' => true,
            'promise_followed_up_at' => Carbon::now(),
        ];

        if ($paymentAmount !== null) {
            $updateData['metadata'] = array_merge($this->metadata ?: [], [
                'actual_payment_amount' => $paymentAmount,
            ]);
        }

        $this->update($updateData);
    }

    /**
     * Mark promise as broken.
     */
    public function markPromiseBroken(?string $reason = null): void
    {
        $this->update([
            'promise_kept' => false,
            'promise_followed_up_at' => Carbon::now(),
            'metadata' => array_merge($this->metadata ?: [], [
                'promise_broken_reason' => $reason ?: 'Payment not received by promised date',
            ]),
        ]);

        // Create follow-up note
        self::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'note_type' => self::TYPE_PROMISE_TO_PAY,
            'priority' => self::PRIORITY_HIGH,
            'subject' => 'Promise to Pay Broken',
            'content' => "Client failed to honor promise to pay by {$this->promised_payment_date->format('M d, Y')}. ".($reason ?: ''),
            'escalation_risk' => true,
            'requires_followup' => true,
            'followup_date' => Carbon::now()->addDay(),
            'created_by' => $this->created_by,
        ]);
    }

    /**
     * Complete follow-up task.
     */
    public function completeFollowup(?string $outcome = null, ?string $notes = null): void
    {
        $this->update([
            'followup_completed' => true,
            'followup_completed_at' => Carbon::now(),
            'metadata' => array_merge($this->metadata ?: [], [
                'followup_outcome' => $outcome,
                'followup_notes' => $notes,
            ]),
        ]);
    }

    /**
     * Mark for attorney review.
     */
    public function requireAttorneyReview(?string $reason = null): void
    {
        $this->update([
            'attorney_review_required' => true,
            'legally_significant' => true,
            'metadata' => array_merge($this->metadata ?: [], [
                'attorney_review_reason' => $reason ?: 'Legal review required',
            ]),
        ]);
    }

    /**
     * Complete attorney review.
     */
    public function completeAttorneyReview(int $attorneyId, ?string $feedback = null, ?int $riskScore = null): void
    {
        $this->update([
            'attorney_reviewed' => true,
            'reviewed_by_attorney_id' => $attorneyId,
            'attorney_reviewed_at' => Carbon::now(),
            'metadata' => array_merge($this->metadata ?: [], [
                'attorney_feedback' => $feedback,
                'legal_risk_score' => $riskScore,
            ]),
        ]);
    }

    /**
     * Flag for quality review.
     */
    public function flagForReview(?string $reason = null): void
    {
        $this->update([
            'flagged_for_review' => true,
            'metadata' => array_merge($this->metadata ?: [], [
                'review_flag_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Complete quality review.
     */
    public function completeQualityReview(int $reviewerId, int $score, ?string $feedback = null): void
    {
        $this->update([
            'quality_reviewed' => true,
            'quality_reviewed_by' => $reviewerId,
            'quality_reviewed_at' => Carbon::now(),
            'quality_score' => $score,
            'quality_feedback' => $feedback,
            'flagged_for_review' => false,
        ]);
    }

    /**
     * Archive note.
     */
    public function archive(): void
    {
        $this->update([
            'archived' => true,
            'archived_at' => Carbon::now(),
        ]);
    }

    /**
     * Calculate billable amount based on time spent.
     */
    public function calculateBillableAmount(): float
    {
        if (! $this->billable_time || ! $this->time_spent_minutes || ! $this->hourly_rate) {
            return 0;
        }

        $hours = $this->time_spent_minutes / 60;

        return $hours * $this->hourly_rate;
    }

    /**
     * Get formatted call duration.
     */
    public function getFormattedCallDuration(): string
    {
        if (! $this->call_duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->call_duration_seconds / 60);
        $seconds = $this->call_duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Scope to get notes requiring follow-up.
     */
    public function scopeRequiresFollowup($query)
    {
        return $query->where('requires_followup', true)
            ->where('followup_completed', false);
    }

    /**
     * Scope to get overdue follow-ups.
     */
    public function scopeOverdueFollowup($query)
    {
        return $query->where('requires_followup', true)
            ->where('followup_completed', false)
            ->where('followup_date', '<', Carbon::now());
    }

    /**
     * Scope to get promises to pay.
     */
    public function scopePromisesToPay($query)
    {
        return $query->where('contains_promise_to_pay', true);
    }

    /**
     * Scope to get overdue promises.
     */
    public function scopeOverduePromises($query)
    {
        return $query->where('contains_promise_to_pay', true)
            ->where('promised_payment_date', '<', Carbon::now())
            ->whereNull('promise_kept');
    }

    /**
     * Scope to get notes requiring attorney review.
     */
    public function scopeRequiresAttorneyReview($query)
    {
        return $query->where('attorney_review_required', true)
            ->where('attorney_reviewed', false);
    }

    /**
     * Scope to get flagged notes.
     */
    public function scopeFlagged($query)
    {
        return $query->where('flagged_for_review', true);
    }

    /**
     * Scope to get notes by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('note_type', $type);
    }

    /**
     * Scope to get notes by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to search notes.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', '%'.$search.'%')
                ->orWhere('content', 'like', '%'.$search.'%')
                ->orWhere('contact_person', 'like', '%'.$search.'%');
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (! $note->created_by) {
                $note->created_by = auth()->id() ?? 1;
            }

            // Auto-populate financial context if not provided
            if ($note->invoice_id && ! $note->invoice_balance_at_time) {
                $invoice = Invoice::find($note->invoice_id);
                if ($invoice) {
                    $note->invoice_balance_at_time = $invoice->getBalance();
                    $note->days_overdue_at_time = $invoice->isOverdue() ?
                        Carbon::now()->diffInDays($invoice->due_date) : 0;
                    $note->total_account_balance = $invoice->client->getBalance();
                }
            }

            // Calculate billable amount
            if ($note->billable_time) {
                $note->billable_amount = $note->calculateBillableAmount();
            }
        });

        static::updating(function ($note) {
            // Recalculate billable amount if time or rate changed
            if ($note->isDirty(['time_spent_minutes', 'hourly_rate']) && $note->billable_time) {
                $note->billable_amount = $note->calculateBillableAmount();
            }
        });
    }
}
