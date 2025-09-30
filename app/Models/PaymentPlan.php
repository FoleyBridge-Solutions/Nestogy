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
 * Payment Plan Model
 *
 * Represents flexible payment arrangement management with comprehensive
 * tracking of terms, performance, and compliance.
 *
 * @property int $id
 * @property string $plan_number
 * @property int $client_id
 * @property int|null $invoice_id
 * @property int|null $dunning_action_id
 * @property string $plan_type
 * @property string $status
 * @property string|null $description
 * @property float $original_amount
 * @property float $plan_amount
 * @property float $down_payment
 * @property float $monthly_payment
 * @property int $number_of_payments
 * @property float $interest_rate
 * @property float $setup_fee
 * @property float $late_fee
 * @property bool $compound_interest
 * @property bool $is_settlement
 * @property float|null $settlement_percentage
 * @property float|null $settlement_amount
 * @property string|null $settlement_terms
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $first_payment_date
 * @property string $payment_frequency
 * @property int $payment_day_of_month
 * @property \Illuminate\Support\Carbon $final_payment_date
 * @property int $grace_period_days
 * @property string $payment_method
 * @property string|null $payment_token
 * @property array|null $payment_method_details
 * @property bool $auto_retry_failed_payments
 * @property int $max_retry_attempts
 * @property int $payments_made
 * @property int $payments_missed
 * @property int $consecutive_missed
 * @property float $total_paid
 * @property float $remaining_balance
 * @property \Illuminate\Support\Carbon|null $last_payment_date
 * @property float|null $last_payment_amount
 * @property bool $in_default
 * @property \Illuminate\Support\Carbon|null $default_date
 * @property string|null $default_reason
 * @property int $cure_period_days
 * @property \Illuminate\Support\Carbon|null $cure_deadline
 * @property bool $cured
 * @property \Illuminate\Support\Carbon|null $cured_date
 * @property int $modification_count
 * @property array|null $modification_history
 * @property string|null $modification_reason
 * @property int|null $modified_by
 * @property \Illuminate\Support\Carbon|null $modified_at
 * @property bool $report_to_credit_bureau
 * @property array|null $credit_reporting_details
 * @property bool $positive_payment_history
 * @property bool $negative_payment_history
 * @property array|null $terms_and_conditions
 * @property bool $client_acknowledged_terms
 * @property \Illuminate\Support\Carbon|null $terms_acknowledged_at
 * @property string|null $electronic_signature
 * @property array|null $state_law_compliance
 * @property bool $right_to_cancel_disclosed
 * @property bool $send_payment_reminders
 * @property int $reminder_days_before
 * @property bool $send_confirmation_receipts
 * @property bool $send_default_notices
 * @property array|null $communication_preferences
 * @property float $success_probability
 * @property array|null $risk_factors
 * @property float|null $expected_recovery_amount
 * @property int|null $estimated_completion_days
 * @property string $approval_status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property int|null $rejected_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 */
class PaymentPlan extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'payment_plans';

    protected $fillable = [
        'company_id', 'plan_number', 'client_id', 'invoice_id', 'dunning_action_id',
        'plan_type', 'status', 'description', 'original_amount', 'plan_amount',
        'down_payment', 'monthly_payment', 'number_of_payments', 'interest_rate',
        'setup_fee', 'late_fee', 'compound_interest', 'is_settlement',
        'settlement_percentage', 'settlement_amount', 'settlement_terms',
        'start_date', 'first_payment_date', 'payment_frequency', 'payment_day_of_month',
        'final_payment_date', 'grace_period_days', 'payment_method', 'payment_token',
        'payment_method_details', 'auto_retry_failed_payments', 'max_retry_attempts',
        'payments_made', 'payments_missed', 'consecutive_missed', 'total_paid',
        'remaining_balance', 'last_payment_date', 'last_payment_amount',
        'in_default', 'default_date', 'default_reason', 'cure_period_days',
        'cure_deadline', 'cured', 'cured_date', 'modification_count',
        'modification_history', 'modification_reason', 'modified_by', 'modified_at',
        'report_to_credit_bureau', 'credit_reporting_details', 'positive_payment_history',
        'negative_payment_history', 'terms_and_conditions', 'client_acknowledged_terms',
        'terms_acknowledged_at', 'electronic_signature', 'state_law_compliance',
        'right_to_cancel_disclosed', 'send_payment_reminders', 'reminder_days_before',
        'send_confirmation_receipts', 'send_default_notices', 'communication_preferences',
        'success_probability', 'risk_factors', 'expected_recovery_amount',
        'estimated_completion_days', 'approval_status', 'approved_by', 'approved_at',
        'approval_notes', 'rejected_by', 'rejected_at', 'rejection_reason',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'dunning_action_id' => 'integer',
        'original_amount' => 'decimal:2',
        'plan_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'number_of_payments' => 'integer',
        'interest_rate' => 'decimal:4',
        'setup_fee' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'compound_interest' => 'boolean',
        'is_settlement' => 'boolean',
        'settlement_percentage' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'start_date' => 'date',
        'first_payment_date' => 'date',
        'payment_day_of_month' => 'integer',
        'final_payment_date' => 'date',
        'grace_period_days' => 'integer',
        'payment_method_details' => 'array',
        'auto_retry_failed_payments' => 'boolean',
        'max_retry_attempts' => 'integer',
        'payments_made' => 'integer',
        'payments_missed' => 'integer',
        'consecutive_missed' => 'integer',
        'total_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'last_payment_date' => 'date',
        'last_payment_amount' => 'decimal:2',
        'in_default' => 'boolean',
        'default_date' => 'date',
        'cure_period_days' => 'integer',
        'cure_deadline' => 'date',
        'cured' => 'boolean',
        'cured_date' => 'date',
        'modification_count' => 'integer',
        'modification_history' => 'array',
        'modified_by' => 'integer',
        'modified_at' => 'datetime',
        'report_to_credit_bureau' => 'boolean',
        'credit_reporting_details' => 'array',
        'positive_payment_history' => 'boolean',
        'negative_payment_history' => 'boolean',
        'terms_and_conditions' => 'array',
        'client_acknowledged_terms' => 'boolean',
        'terms_acknowledged_at' => 'datetime',
        'state_law_compliance' => 'array',
        'right_to_cancel_disclosed' => 'boolean',
        'send_payment_reminders' => 'boolean',
        'reminder_days_before' => 'integer',
        'send_confirmation_receipts' => 'boolean',
        'send_default_notices' => 'boolean',
        'communication_preferences' => 'array',
        'success_probability' => 'decimal:2',
        'risk_factors' => 'array',
        'expected_recovery_amount' => 'decimal:2',
        'estimated_completion_days' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DEFAULTED = 'defaulted';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_RENEGOTIATED = 'renegotiated';

    const STATUS_SETTLED = 'settled';

    // Plan type constants
    const TYPE_STANDARD = 'standard';

    const TYPE_HARDSHIP = 'hardship';

    const TYPE_SETTLEMENT = 'settlement';

    const TYPE_CUSTOM = 'custom';

    // Payment frequency constants
    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_BIWEEKLY = 'biweekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    // Payment method constants
    const METHOD_AUTO_ACH = 'auto_ach';

    const METHOD_AUTO_CREDIT_CARD = 'auto_credit_card';

    const METHOD_MANUAL_PAYMENT = 'manual_payment';

    const METHOD_CHECK = 'check';

    const METHOD_WIRE_TRANSFER = 'wire_transfer';

    // Approval status constants
    const APPROVAL_NOT_REQUIRED = 'not_required';

    const APPROVAL_PENDING = 'pending';

    const APPROVAL_APPROVED = 'approved';

    const APPROVAL_REJECTED = 'rejected';

    const APPROVAL_EXPIRED = 'expired';

    /**
     * Get the client this payment plan belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the invoice this payment plan is for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the dunning action that created this plan.
     */
    public function dunningAction(): BelongsTo
    {
        return $this->belongsTo(DunningAction::class, 'dunning_action_id');
    }

    /**
     * Get collection notes for this payment plan.
     */
    public function collectionNotes(): HasMany
    {
        return $this->hasMany(CollectionNote::class, 'payment_plan_id');
    }

    /**
     * Get the user who created this plan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this plan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who modified this plan.
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Check if payment plan is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if payment plan is in default.
     */
    public function isInDefault(): bool
    {
        return $this->in_default;
    }

    /**
     * Check if payment plan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED ||
               $this->remaining_balance <= 0;
    }

    /**
     * Check if payment plan requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Calculate next payment date.
     */
    public function getNextPaymentDate(): Carbon
    {
        $lastPayment = $this->last_payment_date ?: $this->first_payment_date;

        switch ($this->payment_frequency) {
            case self::FREQUENCY_WEEKLY:
                return $lastPayment->copy()->addWeek();
            case self::FREQUENCY_BIWEEKLY:
                return $lastPayment->copy()->addWeeks(2);
            case self::FREQUENCY_QUARTERLY:
                return $lastPayment->copy()->addMonths(3);
            case self::FREQUENCY_MONTHLY:
            default:
                return $lastPayment->copy()->addMonth()->day($this->payment_day_of_month);
        }
    }

    /**
     * Check if payment is overdue.
     */
    public function isPaymentOverdue(): bool
    {
        $nextPayment = $this->getNextPaymentDate();
        $gracePeriod = Carbon::parse($nextPayment)->addDays($this->grace_period_days);

        return Carbon::now()->gt($gracePeriod) && ! $this->isCompleted();
    }

    /**
     * Calculate late fees for missed payment.
     */
    public function calculateLateFees(): float
    {
        if (! $this->isPaymentOverdue() || $this->late_fee <= 0) {
            return 0;
        }

        $daysLate = Carbon::now()->diffInDays($this->getNextPaymentDate()) - $this->grace_period_days;

        if ($daysLate <= 0) {
            return 0;
        }

        // Calculate late fees based on plan terms
        return $this->late_fee;
    }

    /**
     * Record a payment made against this plan.
     */
    public function recordPayment(float $amount, ?Carbon $paymentDate = null, array $metadata = []): void
    {
        $paymentDate = $paymentDate ?: Carbon::now();

        $this->update([
            'payments_made' => $this->payments_made + 1,
            'total_paid' => $this->total_paid + $amount,
            'remaining_balance' => $this->remaining_balance - $amount,
            'last_payment_date' => $paymentDate,
            'last_payment_amount' => $amount,
            'consecutive_missed' => 0, // Reset consecutive missed payments
        ]);

        // Check if plan is completed
        if ($this->remaining_balance <= 0) {
            $this->update(['status' => self::STATUS_COMPLETED]);
        }

        // If plan was in default and payment brings it current, cure the default
        if ($this->in_default && $this->getRemainingPayments() <= $this->number_of_payments) {
            $this->cureDefault();
        }

        // Create collection note
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'payment_plan_id' => $this->id,
            'note_type' => 'payment_arrangement',
            'subject' => 'Payment Plan Payment Received',
            'content' => 'Payment of $'.number_format($amount, 2)." received for payment plan {$this->plan_number}",
            'created_by' => 1,
        ]);
    }

    /**
     * Record a missed payment.
     */
    public function recordMissedPayment(?string $reason = null): void
    {
        $this->update([
            'payments_missed' => $this->payments_missed + 1,
            'consecutive_missed' => $this->consecutive_missed + 1,
        ]);

        // Check if this triggers default
        if ($this->consecutive_missed >= 2 && ! $this->in_default) {
            $this->markAsDefault('Consecutive missed payments');
        }

        // Create collection note
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'payment_plan_id' => $this->id,
            'note_type' => 'payment_arrangement',
            'subject' => 'Payment Plan Payment Missed',
            'content' => "Payment missed for payment plan {$this->plan_number}. Reason: ".($reason ?: 'Not specified'),
            'requires_followup' => true,
            'followup_date' => Carbon::now()->addDays(3),
            'created_by' => 1,
        ]);
    }

    /**
     * Mark payment plan as in default.
     */
    public function markAsDefault(string $reason): void
    {
        if ($this->in_default) {
            return; // Already in default
        }

        $this->update([
            'in_default' => true,
            'default_date' => Carbon::now(),
            'default_reason' => $reason,
            'cure_deadline' => Carbon::now()->addDays($this->cure_period_days),
        ]);

        // Create collection note
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'payment_plan_id' => $this->id,
            'note_type' => 'payment_arrangement',
            'priority' => 'high',
            'subject' => 'Payment Plan Default',
            'content' => "Payment plan {$this->plan_number} has gone into default. Reason: {$reason}",
            'requires_followup' => true,
            'followup_date' => Carbon::now()->addDay(),
            'escalation_risk' => true,
            'created_by' => 1,
        ]);
    }

    /**
     * Cure the default status.
     */
    public function cureDefault(): void
    {
        if (! $this->in_default) {
            return;
        }

        $this->update([
            'in_default' => false,
            'cured' => true,
            'cured_date' => Carbon::now(),
            'consecutive_missed' => 0,
        ]);

        // Create collection note
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'payment_plan_id' => $this->id,
            'note_type' => 'payment_arrangement',
            'subject' => 'Payment Plan Default Cured',
            'content' => "Payment plan {$this->plan_number} default has been cured",
            'created_by' => 1,
        ]);
    }

    /**
     * Calculate remaining payments.
     */
    public function getRemainingPayments(): int
    {
        return max(0, $this->number_of_payments - $this->payments_made);
    }

    /**
     * Calculate success probability based on payment history.
     */
    public function calculateSuccessProbability(): float
    {
        $totalPayments = $this->payments_made + $this->payments_missed;

        if ($totalPayments === 0) {
            return 50.0; // No history, neutral probability
        }

        $paymentRate = ($this->payments_made / $totalPayments) * 100;

        // Adjust for consecutive missed payments
        if ($this->consecutive_missed >= 2) {
            $paymentRate -= 20;
        }

        // Adjust for default status
        if ($this->in_default) {
            $paymentRate -= 30;
        }

        // Adjust for plan modifications
        if ($this->modification_count > 2) {
            $paymentRate -= 10;
        }

        return max(0, min(100, $paymentRate));
    }

    /**
     * Modify payment plan terms.
     */
    public function modifyPlan(array $newTerms, string $reason, int $modifiedBy): void
    {
        $oldTerms = [
            'monthly_payment' => $this->monthly_payment,
            'number_of_payments' => $this->number_of_payments,
            'interest_rate' => $this->interest_rate,
            'final_payment_date' => $this->final_payment_date,
        ];

        $history = $this->modification_history ?: [];
        $history[] = [
            'date' => Carbon::now()->toISOString(),
            'reason' => $reason,
            'old_terms' => $oldTerms,
            'new_terms' => $newTerms,
            'modified_by' => $modifiedBy,
        ];

        $updateData = array_merge($newTerms, [
            'modification_count' => $this->modification_count + 1,
            'modification_history' => $history,
            'modification_reason' => $reason,
            'modified_by' => $modifiedBy,
            'modified_at' => Carbon::now(),
        ]);

        $this->update($updateData);

        // Create collection note
        CollectionNote::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'payment_plan_id' => $this->id,
            'note_type' => 'payment_arrangement',
            'subject' => 'Payment Plan Modified',
            'content' => "Payment plan {$this->plan_number} has been modified. Reason: {$reason}",
            'created_by' => $modifiedBy,
        ]);
    }

    /**
     * Approve payment plan.
     */
    public function approve(int $approvedBy, ?string $notes = null): void
    {
        $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
            'approval_notes' => $notes,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Reject payment plan.
     */
    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Scope to get active payment plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get defaulted payment plans.
     */
    public function scopeInDefault($query)
    {
        return $query->where('in_default', true);
    }

    /**
     * Scope to get payment plans requiring approval.
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope to get overdue payment plans.
     */
    public function scopeOverdue($query)
    {
        // This would need to be implemented with a more complex query
        // For now, return all active plans and filter in application
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Generate unique plan number.
     */
    public static function generatePlanNumber(): string
    {
        $prefix = 'PP';
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

        static::creating(function ($plan) {
            if (! $plan->plan_number) {
                $plan->plan_number = self::generatePlanNumber();
            }
            if (! $plan->created_by) {
                $plan->created_by = auth()->id() ?? 1;
            }
        });

        static::updating(function ($plan) {
            // Update success probability when relevant fields change
            if ($plan->isDirty(['payments_made', 'payments_missed', 'consecutive_missed', 'in_default'])) {
                $plan->success_probability = $plan->calculateSuccessProbability();
            }
        });
    }
}
