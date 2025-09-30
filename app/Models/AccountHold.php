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
 * Account Hold Model
 *
 * Represents service suspension and restoration management with
 * comprehensive VoIP-specific controls and compliance tracking.
 *
 * @property int $id
 * @property int $client_id
 * @property int|null $invoice_id
 * @property int|null $dunning_action_id
 * @property int|null $payment_plan_id
 * @property string $hold_reference
 * @property string $hold_type
 * @property string $status
 * @property string $severity
 * @property string $title
 * @property string $description
 * @property string $reason
 * @property float|null $amount_threshold
 * @property int|null $days_overdue
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $effective_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $lifted_at
 * @property int $grace_period_hours
 * @property \Illuminate\Support\Carbon|null $grace_period_expires_at
 * @property array|null $services_affected
 * @property array|null $essential_services_maintained
 * @property bool $graceful_suspension
 * @property int $suspension_notice_hours
 * @property bool $partial_suspension
 * @property bool $restrict_outbound_calls
 * @property bool $restrict_long_distance
 * @property bool $restrict_international
 * @property bool $maintain_e911
 * @property bool $maintain_inbound_calls
 * @property bool $prevent_number_porting
 * @property array|null $allowed_numbers
 * @property bool $restrict_equipment_changes
 * @property bool $prevent_new_orders
 * @property bool $require_equipment_return
 * @property array|null $equipment_to_recover
 * @property \Illuminate\Support\Carbon|null $equipment_return_deadline
 * @property bool $credit_hold
 * @property float|null $credit_limit_override
 * @property bool $require_prepayment
 * @property bool $stop_recurring_billing
 * @property bool $prevent_service_changes
 * @property array|null $regulatory_requirements
 * @property bool $customer_notification_sent
 * @property \Illuminate\Support\Carbon|null $customer_notified_at
 * @property array|null $notification_methods
 * @property bool $regulatory_filing_required
 * @property bool $regulatory_filing_completed
 * @property bool $can_be_overridden
 * @property array|null $override_permissions
 * @property bool $requires_approval
 * @property string $approval_status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property array|null $lift_conditions
 * @property float|null $payment_required_amount
 * @property bool $full_payment_required
 * @property bool $payment_plan_acceptable
 * @property bool $manager_approval_required
 * @property float $revenue_impact
 * @property int $affected_services_count
 * @property int $affected_users_count
 * @property array|null $business_impact_assessment
 * @property array|null $communication_log
 * @property bool $customer_contacted
 * @property \Illuminate\Support\Carbon|null $last_customer_contact
 * @property string|null $customer_response
 * @property string|null $customer_feedback
 * @property string $restoration_method
 * @property int|null $restoration_time_minutes
 * @property array|null $restoration_steps
 * @property bool $restoration_verification_required
 * @property bool $restoration_completed
 * @property \Illuminate\Support\Carbon|null $restoration_verified_at
 * @property bool $legal_action_pending
 * @property bool $collection_agency_involved
 * @property string|null $collection_agency
 * @property \Illuminate\Support\Carbon|null $legal_action_date
 * @property string|null $legal_notes
 * @property int|null $effectiveness_score
 * @property bool $resulted_in_payment
 * @property float $payment_amount_received
 * @property int|null $days_to_resolution
 * @property string|null $resolution_type
 */
class AccountHold extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'account_holds';

    protected $fillable = [
        'company_id', 'client_id', 'invoice_id', 'dunning_action_id', 'payment_plan_id',
        'hold_reference', 'hold_type', 'status', 'severity', 'title', 'description',
        'reason', 'amount_threshold', 'days_overdue', 'scheduled_at', 'effective_at',
        'expires_at', 'lifted_at', 'grace_period_hours', 'grace_period_expires_at',
        'services_affected', 'essential_services_maintained', 'graceful_suspension',
        'suspension_notice_hours', 'partial_suspension', 'restrict_outbound_calls',
        'restrict_long_distance', 'restrict_international', 'maintain_e911',
        'maintain_inbound_calls', 'prevent_number_porting', 'allowed_numbers',
        'restrict_equipment_changes', 'prevent_new_orders', 'require_equipment_return',
        'equipment_to_recover', 'equipment_return_deadline', 'credit_hold',
        'credit_limit_override', 'require_prepayment', 'stop_recurring_billing',
        'prevent_service_changes', 'regulatory_requirements', 'customer_notification_sent',
        'customer_notified_at', 'notification_methods', 'regulatory_filing_required',
        'regulatory_filing_completed', 'can_be_overridden', 'override_permissions',
        'requires_approval', 'approval_status', 'approved_by', 'approved_at',
        'approval_notes', 'lift_conditions', 'payment_required_amount',
        'full_payment_required', 'payment_plan_acceptable', 'manager_approval_required',
        'revenue_impact', 'affected_services_count', 'affected_users_count',
        'business_impact_assessment', 'communication_log', 'customer_contacted',
        'last_customer_contact', 'customer_response', 'customer_feedback',
        'restoration_method', 'restoration_time_minutes', 'restoration_steps',
        'restoration_verification_required', 'restoration_completed',
        'restoration_verified_at', 'legal_action_pending', 'collection_agency_involved',
        'collection_agency', 'legal_action_date', 'legal_notes', 'effectiveness_score',
        'resulted_in_payment', 'payment_amount_received', 'days_to_resolution',
        'resolution_type', 'created_by', 'updated_by', 'lifted_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'dunning_action_id' => 'integer',
        'payment_plan_id' => 'integer',
        'amount_threshold' => 'decimal:2',
        'days_overdue' => 'integer',
        'scheduled_at' => 'datetime',
        'effective_at' => 'datetime',
        'expires_at' => 'datetime',
        'lifted_at' => 'datetime',
        'grace_period_hours' => 'integer',
        'grace_period_expires_at' => 'datetime',
        'services_affected' => 'array',
        'essential_services_maintained' => 'array',
        'graceful_suspension' => 'boolean',
        'suspension_notice_hours' => 'integer',
        'partial_suspension' => 'boolean',
        'restrict_outbound_calls' => 'boolean',
        'restrict_long_distance' => 'boolean',
        'restrict_international' => 'boolean',
        'maintain_e911' => 'boolean',
        'maintain_inbound_calls' => 'boolean',
        'prevent_number_porting' => 'boolean',
        'allowed_numbers' => 'array',
        'restrict_equipment_changes' => 'boolean',
        'prevent_new_orders' => 'boolean',
        'require_equipment_return' => 'boolean',
        'equipment_to_recover' => 'array',
        'equipment_return_deadline' => 'date',
        'credit_hold' => 'boolean',
        'credit_limit_override' => 'decimal:2',
        'require_prepayment' => 'boolean',
        'stop_recurring_billing' => 'boolean',
        'prevent_service_changes' => 'boolean',
        'regulatory_requirements' => 'array',
        'customer_notification_sent' => 'boolean',
        'customer_notified_at' => 'datetime',
        'notification_methods' => 'array',
        'regulatory_filing_required' => 'boolean',
        'regulatory_filing_completed' => 'boolean',
        'can_be_overridden' => 'boolean',
        'override_permissions' => 'array',
        'requires_approval' => 'boolean',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'lift_conditions' => 'array',
        'payment_required_amount' => 'decimal:2',
        'full_payment_required' => 'boolean',
        'payment_plan_acceptable' => 'boolean',
        'manager_approval_required' => 'boolean',
        'revenue_impact' => 'decimal:2',
        'affected_services_count' => 'integer',
        'affected_users_count' => 'integer',
        'business_impact_assessment' => 'array',
        'communication_log' => 'array',
        'customer_contacted' => 'boolean',
        'last_customer_contact' => 'datetime',
        'restoration_time_minutes' => 'integer',
        'restoration_steps' => 'array',
        'restoration_verification_required' => 'boolean',
        'restoration_completed' => 'boolean',
        'restoration_verified_at' => 'datetime',
        'legal_action_pending' => 'boolean',
        'collection_agency_involved' => 'boolean',
        'legal_action_date' => 'date',
        'effectiveness_score' => 'integer',
        'resulted_in_payment' => 'boolean',
        'payment_amount_received' => 'decimal:2',
        'days_to_resolution' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'lifted_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Hold type constants
    const TYPE_SERVICE_SUSPENSION = 'service_suspension';

    const TYPE_CREDIT_HOLD = 'credit_hold';

    const TYPE_BILLING_HOLD = 'billing_hold';

    const TYPE_COMPLIANCE_HOLD = 'compliance_hold';

    const TYPE_LEGAL_HOLD = 'legal_hold';

    const TYPE_FRAUD_HOLD = 'fraud_hold';

    const TYPE_PAYMENT_PLAN_VIOLATION = 'payment_plan_violation';

    const TYPE_DISPUTE_HOLD = 'dispute_hold';

    const TYPE_REGULATORY_HOLD = 'regulatory_hold';

    const TYPE_EQUIPMENT_RECOVERY = 'equipment_recovery';

    const TYPE_PORTING_RESTRICTION = 'porting_restriction';

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_PARTIAL = 'partial';

    const STATUS_LIFTED = 'lifted';

    const STATUS_EXPIRED = 'expired';

    const STATUS_OVERRIDDEN = 'overridden';

    // Severity constants
    const SEVERITY_LOW = 'low';

    const SEVERITY_MEDIUM = 'medium';

    const SEVERITY_HIGH = 'high';

    const SEVERITY_CRITICAL = 'critical';

    // Approval status constants
    const APPROVAL_NOT_REQUIRED = 'not_required';

    const APPROVAL_PENDING = 'pending';

    const APPROVAL_APPROVED = 'approved';

    const APPROVAL_REJECTED = 'rejected';

    // Customer response constants
    const RESPONSE_NO_RESPONSE = 'no_response';

    const RESPONSE_ACKNOWLEDGED = 'acknowledged';

    const RESPONSE_DISPUTED = 'disputed';

    const RESPONSE_PAYMENT_PROMISED = 'payment_promised';

    const RESPONSE_HARDSHIP_CLAIMED = 'hardship_claimed';

    const RESPONSE_LEGAL_THREAT = 'legal_threat';

    const RESPONSE_ESCALATED = 'escalated';

    // Restoration method constants
    const RESTORATION_AUTOMATIC = 'automatic';

    const RESTORATION_MANUAL = 'manual';

    const RESTORATION_STAGED = 'staged';

    const RESTORATION_GRADUAL = 'gradual';

    // Resolution type constants
    const RESOLUTION_PAYMENT = 'payment';

    const RESOLUTION_PAYMENT_PLAN = 'payment_plan';

    const RESOLUTION_WRITEOFF = 'writeoff';

    const RESOLUTION_LEGAL_ACTION = 'legal_action';

    const RESOLUTION_SETTLEMENT = 'settlement';

    /**
     * Get the client this hold belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the invoice this hold relates to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the dunning action that created this hold.
     */
    public function dunningAction(): BelongsTo
    {
        return $this->belongsTo(DunningAction::class, 'dunning_action_id');
    }

    /**
     * Get the payment plan this hold relates to.
     */
    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }

    /**
     * Get collection notes for this hold.
     */
    public function collectionNotes(): HasMany
    {
        return $this->hasMany(CollectionNote::class, 'account_hold_id');
    }

    /**
     * Get the user who created this hold.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this hold.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who lifted this hold.
     */
    public function lifter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    /**
     * Check if hold is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if hold is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if hold is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->grace_period_expires_at &&
               Carbon::now()->lt($this->grace_period_expires_at);
    }

    /**
     * Check if hold has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    /**
     * Check if hold is a VoIP service suspension.
     */
    public function isVoIPSuspension(): bool
    {
        return $this->hold_type === self::TYPE_SERVICE_SUSPENSION;
    }

    /**
     * Check if hold requires customer notification.
     */
    public function requiresCustomerNotification(): bool
    {
        return ! $this->customer_notification_sent &&
               $this->suspension_notice_hours > 0;
    }

    /**
     * Check if hold can be lifted based on conditions.
     */
    public function canBeLiftedBasedOnConditions(): bool
    {
        if (! $this->lift_conditions) {
            return true; // No conditions, can be lifted
        }

        foreach ($this->lift_conditions as $condition => $value) {
            switch ($condition) {
                case 'payment_required':
                    if ($value && ! $this->resulted_in_payment) {
                        return false;
                    }
                    break;
                case 'minimum_payment':
                    if ($this->payment_amount_received < $value) {
                        return false;
                    }
                    break;
                case 'full_payment':
                    if ($value && $this->invoice && $this->invoice->getBalance() > 0) {
                        return false;
                    }
                    break;
                case 'payment_plan':
                    if ($value && ! $this->paymentPlan) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Activate the hold.
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'effective_at' => Carbon::now(),
        ]);

        // Send customer notification if required
        if ($this->requiresCustomerNotification()) {
            $this->sendCustomerNotification();
        }

        // Create collection note
        $this->createCollectionNote('Account Hold Activated', 'Account hold has been activated');

        // Stop recurring billing if specified
        if ($this->stop_recurring_billing) {
            // This would integrate with billing system
            $this->stopRecurringBilling();
        }
    }

    /**
     * Lift the hold.
     */
    public function lift(?int $liftedBy = null, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_LIFTED,
            'lifted_at' => Carbon::now(),
            'lifted_by' => $liftedBy,
        ]);

        // Restore services based on restoration method
        $this->restoreServices();

        // Create collection note
        $this->createCollectionNote('Account Hold Lifted', $reason ?: 'Hold conditions met');

        // Resume recurring billing if it was stopped
        if ($this->stop_recurring_billing) {
            $this->resumeRecurringBilling();
        }
    }

    /**
     * Override the hold.
     */
    public function override(int $overriddenBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_OVERRIDDEN,
            'lifted_at' => Carbon::now(),
            'lifted_by' => $overriddenBy,
        ]);

        $this->createCollectionNote('Account Hold Overridden', "Hold overridden: {$reason}");
    }

    /**
     * Record payment received.
     */
    public function recordPayment(float $amount): void
    {
        $this->update([
            'resulted_in_payment' => true,
            'payment_amount_received' => $this->payment_amount_received + $amount,
        ]);

        // Check if hold can be lifted based on payment
        if ($this->canBeLiftedBasedOnConditions()) {
            $this->lift(1, 'Payment received - hold conditions met');
        }
    }

    /**
     * Send customer notification about the hold.
     */
    public function sendCustomerNotification(): void
    {
        $methods = $this->notification_methods ?: ['email'];
        $communicationLog = $this->communication_log ?: [];

        foreach ($methods as $method) {
            // This would integrate with communication services
            $communicationLog[] = [
                'method' => $method,
                'sent_at' => Carbon::now()->toISOString(),
                'type' => 'hold_notification',
                'status' => 'sent',
            ];
        }

        $this->update([
            'customer_notification_sent' => true,
            'customer_notified_at' => Carbon::now(),
            'communication_log' => $communicationLog,
        ]);
    }

    /**
     * Record customer response.
     */
    public function recordCustomerResponse(string $responseType, ?string $feedback = null): void
    {
        $this->update([
            'customer_contacted' => true,
            'last_customer_contact' => Carbon::now(),
            'customer_response' => $responseType,
            'customer_feedback' => $feedback,
        ]);

        $this->createCollectionNote('Customer Response to Hold', "Customer response: {$responseType}");
    }

    /**
     * Calculate effectiveness score.
     */
    public function calculateEffectivenessScore(): int
    {
        $score = 0;

        // Base score for completion
        if ($this->status === self::STATUS_LIFTED && $this->resulted_in_payment) {
            $score += 50;
        } elseif ($this->resulted_in_payment) {
            $score += 30;
        } elseif ($this->customer_contacted) {
            $score += 20;
        }

        // Time to resolution bonus
        if ($this->days_to_resolution) {
            if ($this->days_to_resolution <= 7) {
                $score += 20;
            } elseif ($this->days_to_resolution <= 14) {
                $score += 10;
            }
        }

        // Revenue recovery bonus
        if ($this->payment_amount_received > 0 && $this->amount_threshold > 0) {
            $recoveryRate = ($this->payment_amount_received / $this->amount_threshold) * 100;
            $score += min(20, $recoveryRate / 5);
        }

        // Customer satisfaction consideration
        if ($this->customer_response === self::RESPONSE_ACKNOWLEDGED) {
            $score += 10;
        } elseif ($this->customer_response === self::RESPONSE_LEGAL_THREAT) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }

    /**
     * Restore services based on restoration method.
     */
    protected function restoreServices(): void
    {
        switch ($this->restoration_method) {
            case self::RESTORATION_AUTOMATIC:
                $this->performAutomaticRestoration();
                break;
            case self::RESTORATION_STAGED:
                $this->performStagedRestoration();
                break;
            case self::RESTORATION_GRADUAL:
                $this->performGradualRestoration();
                break;
            case self::RESTORATION_MANUAL:
            default:
                // Manual restoration requires intervention
                break;
        }
    }

    /**
     * Perform automatic service restoration.
     */
    protected function performAutomaticRestoration(): void
    {
        // This would integrate with VoIP service management
        $this->update(['restoration_completed' => true]);

        if ($this->restoration_verification_required) {
            // Schedule verification
            $this->scheduleRestorationVerification();
        }
    }

    /**
     * Perform staged service restoration.
     */
    protected function performStagedRestoration(): void
    {
        if (! $this->restoration_steps) {
            return;
        }

        // Process each restoration step
        foreach ($this->restoration_steps as $step) {
            // Execute restoration step
            // This would integrate with service management systems
        }
    }

    /**
     * Perform gradual service restoration.
     */
    protected function performGradualRestoration(): void
    {
        // Gradually restore services over time
        // This would be handled by a background job
    }

    /**
     * Schedule restoration verification.
     */
    protected function scheduleRestorationVerification(): void
    {
        // This would schedule a job to verify service restoration
        $verificationTime = Carbon::now()->addMinutes($this->restoration_time_minutes ?: 30);

        // Schedule verification job here
    }

    /**
     * Stop recurring billing.
     */
    protected function stopRecurringBilling(): void
    {
        // This would integrate with billing system to pause recurring charges
    }

    /**
     * Resume recurring billing.
     */
    protected function resumeRecurringBilling(): void
    {
        // This would integrate with billing system to resume recurring charges
    }

    /**
     * Create collection note for this hold.
     */
    protected function createCollectionNote(string $subject, string $content): void
    {
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'note_type' => CollectionNote::TYPE_SERVICE_SUSPENSION,
            'subject' => $subject,
            'content' => $content,
            'metadata' => [
                'hold_reference' => $this->hold_reference,
                'hold_type' => $this->hold_type,
            ],
            'created_by' => $this->created_by ?: 1,
        ]);
    }

    /**
     * Scope to get active holds.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get pending holds.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get holds by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('hold_type', $type);
    }

    /**
     * Scope to get holds requiring approval.
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true)
            ->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope to get expired holds.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', Carbon::now())
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING]);
    }

    /**
     * Generate unique hold reference.
     */
    public static function generateHoldReference(): string
    {
        $prefix = 'AH';
        $year = now()->format('y');
        $sequence = self::whereYear('created_at', now()->year)->count() + 1;

        return "{$prefix}-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hold) {
            if (! $hold->hold_reference) {
                $hold->hold_reference = self::generateHoldReference();
            }
            if (! $hold->created_by) {
                $hold->created_by = auth()->id() ?? 1;
            }

            // Set grace period expiration
            if ($hold->grace_period_hours > 0) {
                $hold->grace_period_expires_at = Carbon::now()->addHours($hold->grace_period_hours);
            }
        });

        static::updated(function ($hold) {
            // Update effectiveness score when relevant fields change
            if ($hold->isDirty(['resulted_in_payment', 'payment_amount_received', 'status'])) {
                $hold->effectiveness_score = $hold->calculateEffectivenessScore();

                // Calculate days to resolution if hold was lifted
                if ($hold->isDirty('status') && $hold->status === self::STATUS_LIFTED) {
                    $hold->days_to_resolution = Carbon::parse($hold->created_at)->diffInDays($hold->lifted_at);
                }

                $hold->saveQuietly(); // Prevent infinite loop
            }
        });
    }
}
