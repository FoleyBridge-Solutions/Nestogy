<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Auto Payment Model
 * 
 * Manages automated payment configurations for clients including
 * recurring payments, auto-pay for invoices, and scheduled payments.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int $payment_method_id
 * @property string|null $name
 * @property string $type
 * @property string|null $frequency
 * @property bool $is_active
 * @property bool $is_paused
 * @property \Illuminate\Support\Carbon|null $paused_until
 * @property string $trigger_type
 * @property int $trigger_days_offset
 * @property string $trigger_time
 * @property array|null $trigger_conditions
 * @property float|null $minimum_amount
 * @property float|null $maximum_amount
 * @property array|null $invoice_types
 * @property array|null $excluded_invoice_types
 * @property bool $partial_payment_allowed
 * @property float|null $partial_payment_percentage
 * @property float|null $partial_payment_fixed_amount
 * @property bool $retry_on_failure
 * @property int $max_retry_attempts
 * @property array|null $retry_schedule
 * @property string|null $retry_escalation
 * @property bool $send_success_notifications
 * @property bool $send_failure_notifications
 * @property bool $send_retry_notifications
 * @property bool $send_pause_notifications
 * @property array|null $notification_methods
 * @property array|null $notification_recipients
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $next_processing_date
 * @property \Illuminate\Support\Carbon|null $last_processed_at
 * @property \Illuminate\Support\Carbon|null $last_successful_payment_at
 * @property \Illuminate\Support\Carbon|null $last_failed_payment_at
 * @property int $total_payments_processed
 * @property int $successful_payments_count
 * @property int $failed_payments_count
 * @property float $total_amount_processed
 * @property float $total_fees_paid
 * @property int $consecutive_failures
 * @property array|null $failure_reasons
 * @property bool $requires_confirmation
 * @property float|null $daily_limit
 * @property float|null $monthly_limit
 * @property array|null $risk_rules
 * @property array|null $velocity_checks
 * @property bool $client_can_pause
 * @property bool $client_can_modify
 * @property bool $client_can_cancel
 * @property bool $requires_client_approval
 * @property array|null $client_notifications
 * @property array|null $business_rules
 * @property array|null $exception_handling
 * @property string $currency_code
 * @property array|null $allowed_currencies
 * @property bool $currency_conversion_allowed
 * @property array|null $webhook_urls
 * @property array|null $integration_settings
 * @property string|null $external_reference_id
 * @property array|null $metadata
 * @property array|null $custom_fields
 * @property string|null $notes
 * @property string|null $client_notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AutoPayment extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'auto_payments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'payment_method_id',
        'name',
        'type',
        'frequency',
        'is_active',
        'is_paused',
        'paused_until',
        'trigger_type',
        'trigger_days_offset',
        'trigger_time',
        'trigger_conditions',
        'minimum_amount',
        'maximum_amount',
        'invoice_types',
        'excluded_invoice_types',
        'partial_payment_allowed',
        'partial_payment_percentage',
        'partial_payment_fixed_amount',
        'retry_on_failure',
        'max_retry_attempts',
        'retry_schedule',
        'retry_escalation',
        'send_success_notifications',
        'send_failure_notifications',
        'send_retry_notifications',
        'send_pause_notifications',
        'notification_methods',
        'notification_recipients',
        'start_date',
        'end_date',
        'next_processing_date',
        'last_processed_at',
        'last_successful_payment_at',
        'last_failed_payment_at',
        'total_payments_processed',
        'successful_payments_count',
        'failed_payments_count',
        'total_amount_processed',
        'total_fees_paid',
        'consecutive_failures',
        'failure_reasons',
        'requires_confirmation',
        'daily_limit',
        'monthly_limit',
        'risk_rules',
        'velocity_checks',
        'client_can_pause',
        'client_can_modify',
        'client_can_cancel',
        'requires_client_approval',
        'client_notifications',
        'business_rules',
        'exception_handling',
        'currency_code',
        'allowed_currencies',
        'currency_conversion_allowed',
        'webhook_urls',
        'integration_settings',
        'external_reference_id',
        'metadata',
        'custom_fields',
        'notes',
        'client_notes',
        'status',
        'activated_at',
        'deactivated_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'payment_method_id' => 'integer',
        'is_active' => 'boolean',
        'is_paused' => 'boolean',
        'paused_until' => 'datetime',
        'trigger_days_offset' => 'integer',
        'trigger_conditions' => 'array',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'invoice_types' => 'array',
        'excluded_invoice_types' => 'array',
        'partial_payment_allowed' => 'boolean',
        'partial_payment_percentage' => 'decimal:2',
        'partial_payment_fixed_amount' => 'decimal:2',
        'retry_on_failure' => 'boolean',
        'max_retry_attempts' => 'integer',
        'retry_schedule' => 'array',
        'send_success_notifications' => 'boolean',
        'send_failure_notifications' => 'boolean',
        'send_retry_notifications' => 'boolean',
        'send_pause_notifications' => 'boolean',
        'notification_methods' => 'array',
        'notification_recipients' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_processing_date' => 'datetime',
        'last_processed_at' => 'datetime',
        'last_successful_payment_at' => 'datetime',
        'last_failed_payment_at' => 'datetime',
        'total_payments_processed' => 'integer',
        'successful_payments_count' => 'integer',
        'failed_payments_count' => 'integer',
        'total_amount_processed' => 'decimal:2',
        'total_fees_paid' => 'decimal:2',
        'consecutive_failures' => 'integer',
        'failure_reasons' => 'array',
        'requires_confirmation' => 'boolean',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'risk_rules' => 'array',
        'velocity_checks' => 'array',
        'client_can_pause' => 'boolean',
        'client_can_modify' => 'boolean',
        'client_can_cancel' => 'boolean',
        'requires_client_approval' => 'boolean',
        'client_notifications' => 'array',
        'business_rules' => 'array',
        'exception_handling' => 'array',
        'allowed_currencies' => 'array',
        'currency_conversion_allowed' => 'boolean',
        'webhook_urls' => 'array',
        'integration_settings' => 'array',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancelled_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Auto payment type constants
     */
    const TYPE_INVOICE_AUTO_PAY = 'invoice_auto_pay';
    const TYPE_RECURRING_PAYMENT = 'recurring_payment';
    const TYPE_SCHEDULED_PAYMENT = 'scheduled_payment';

    /**
     * Frequency constants
     */
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_ANNUALLY = 'annually';
    const FREQUENCY_SEMI_ANNUALLY = 'semi_annually';

    /**
     * Trigger type constants
     */
    const TRIGGER_INVOICE_DUE = 'invoice_due';
    const TRIGGER_INVOICE_SENT = 'invoice_sent';
    const TRIGGER_FIXED_SCHEDULE = 'fixed_schedule';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the client that owns this auto payment.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns this auto payment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the payment method for this auto payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the user who created this auto payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this auto payment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who cancelled this auto payment.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the payments processed by this auto payment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'auto_payment_id');
    }

    /**
     * Check if auto payment is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->is_active === true && 
               !$this->isPaused() && 
               !$this->isExpired();
    }

    /**
     * Check if auto payment is paused.
     */
    public function isPaused(): bool
    {
        if ($this->is_paused) {
            return true;
        }

        if ($this->paused_until && Carbon::now()->lt($this->paused_until)) {
            return true;
        }

        return false;
    }

    /**
     * Check if auto payment is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date && Carbon::now()->gt($this->end_date);
    }

    /**
     * Check if auto payment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED || $this->cancelled_at !== null;
    }

    /**
     * Check if auto payment is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if auto payment is due for processing.
     */
    public function isDueForProcessing(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->next_processing_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_processing_date);
    }

    /**
     * Check if payment method is valid for processing.
     */
    public function hasValidPaymentMethod(): bool
    {
        return $this->paymentMethod && 
               $this->paymentMethod->isActive() && 
               !$this->paymentMethod->isExpired();
    }

    /**
     * Get display name for auto payment.
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        switch ($this->type) {
            case self::TYPE_INVOICE_AUTO_PAY:
                return 'Auto-Pay for Invoices';
            case self::TYPE_RECURRING_PAYMENT:
                return 'Recurring Payment - ' . ucfirst($this->frequency);
            case self::TYPE_SCHEDULED_PAYMENT:
                return 'Scheduled Payment';
            default:
                return 'Auto Payment';
        }
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->total_payments_processed === 0) {
            return 100.0;
        }

        return ($this->successful_payments_count / $this->total_payments_processed) * 100;
    }

    /**
     * Check if auto payment has good health.
     */
    public function hasGoodHealth(): bool
    {
        // Check success rate
        if ($this->getSuccessRate() < 85) {
            return false;
        }

        // Check consecutive failures
        if ($this->consecutive_failures >= 3) {
            return false;
        }

        // Check payment method health
        if (!$this->hasValidPaymentMethod() || !$this->paymentMethod->hasGoodHealth()) {
            return false;
        }

        return true;
    }

    /**
     * Activate the auto payment.
     */
    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'status' => self::STATUS_ACTIVE,
            'activated_at' => Carbon::now(),
            'deactivated_at' => null,
        ]);
    }

    /**
     * Deactivate the auto payment.
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'status' => self::STATUS_PAUSED,
            'deactivated_at' => Carbon::now(),
        ]);
    }

    /**
     * Pause the auto payment.
     */
    public function pause(Carbon $until = null): bool
    {
        return $this->update([
            'is_paused' => true,
            'paused_until' => $until,
            'status' => self::STATUS_PAUSED,
        ]);
    }

    /**
     * Resume the auto payment.
     */
    public function resume(): bool
    {
        return $this->update([
            'is_paused' => false,
            'paused_until' => null,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Cancel the auto payment.
     */
    public function cancel(string $reason = null, int $cancelledBy = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'is_active' => false,
        ]);
    }

    /**
     * Suspend the auto payment.
     */
    public function suspend(): bool
    {
        return $this->update([
            'status' => self::STATUS_SUSPENDED,
            'is_active' => false,
        ]);
    }

    /**
     * Record successful payment.
     */
    public function recordSuccessfulPayment(float $amount, float $fee = 0): bool
    {
        return $this->update([
            'total_payments_processed' => $this->total_payments_processed + 1,
            'successful_payments_count' => $this->successful_payments_count + 1,
            'total_amount_processed' => $this->total_amount_processed + $amount,
            'total_fees_paid' => $this->total_fees_paid + $fee,
            'last_processed_at' => Carbon::now(),
            'last_successful_payment_at' => Carbon::now(),
            'consecutive_failures' => 0,
            'next_processing_date' => $this->calculateNextProcessingDate(),
        ]);
    }

    /**
     * Record failed payment.
     */
    public function recordFailedPayment(string $reason = null): bool
    {
        $failureReasons = $this->failure_reasons ?? [];
        if ($reason) {
            $failureReasons[] = [
                'reason' => $reason,
                'timestamp' => Carbon::now()->toISOString(),
            ];
        }

        return $this->update([
            'total_payments_processed' => $this->total_payments_processed + 1,
            'failed_payments_count' => $this->failed_payments_count + 1,
            'consecutive_failures' => $this->consecutive_failures + 1,
            'last_processed_at' => Carbon::now(),
            'last_failed_payment_at' => Carbon::now(),
            'failure_reasons' => $failureReasons,
            'next_processing_date' => $this->calculateRetryProcessingDate(),
        ]);
    }

    /**
     * Calculate next processing date based on frequency.
     */
    public function calculateNextProcessingDate(): ?Carbon
    {
        if ($this->type === self::TYPE_INVOICE_AUTO_PAY) {
            // Invoice auto-pay doesn't have fixed schedule
            return null;
        }

        $base = $this->next_processing_date ?? Carbon::now();

        switch ($this->frequency) {
            case self::FREQUENCY_MONTHLY:
                return $base->addMonth();
            case self::FREQUENCY_QUARTERLY:
                return $base->addMonths(3);
            case self::FREQUENCY_SEMI_ANNUALLY:
                return $base->addMonths(6);
            case self::FREQUENCY_ANNUALLY:
                return $base->addYear();
            default:
                return null;
        }
    }

    /**
     * Calculate retry processing date.
     */
    public function calculateRetryProcessingDate(): ?Carbon
    {
        if (!$this->retry_on_failure) {
            return null;
        }

        if ($this->consecutive_failures >= $this->max_retry_attempts) {
            // Max retries reached, suspend auto payment
            $this->suspend();
            return null;
        }

        $retrySchedule = $this->retry_schedule ?? [1, 3, 7]; // Default: retry after 1, 3, 7 days
        $retryIndex = min($this->consecutive_failures - 1, count($retrySchedule) - 1);
        $retryDays = $retrySchedule[$retryIndex] ?? 7;

        return Carbon::now()->addDays($retryDays);
    }

    /**
     * Check if amount is within limits.
     */
    public function isAmountWithinLimits(float $amount): bool
    {
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }

        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            return false;
        }

        if ($this->daily_limit && $this->getDailyUsage() + $amount > $this->daily_limit) {
            return false;
        }

        if ($this->monthly_limit && $this->getMonthlyUsage() + $amount > $this->monthly_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get daily usage amount.
     */
    public function getDailyUsage(): float
    {
        return $this->payments()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get monthly usage amount.
     */
    public function getMonthlyUsage(): float
    {
        return $this->payments()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Check if invoice type is allowed.
     */
    public function isInvoiceTypeAllowed(string $invoiceType): bool
    {
        // Check exclusions first
        if ($this->excluded_invoice_types && in_array($invoiceType, $this->excluded_invoice_types)) {
            return false;
        }

        // If inclusions are specified, type must be in the list
        if ($this->invoice_types && !in_array($invoiceType, $this->invoice_types)) {
            return false;
        }

        return true;
    }

    /**
     * Get payment amount for invoice.
     */
    public function getPaymentAmount(float $invoiceAmount): float
    {
        if (!$this->partial_payment_allowed) {
            return $invoiceAmount;
        }

        if ($this->partial_payment_fixed_amount) {
            return min($this->partial_payment_fixed_amount, $invoiceAmount);
        }

        if ($this->partial_payment_percentage) {
            return $invoiceAmount * ($this->partial_payment_percentage / 100);
        }

        return $invoiceAmount;
    }

    /**
     * Scope to get active auto payments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('status', self::STATUS_ACTIVE)
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>', Carbon::now());
                    });
    }

    /**
     * Scope to get auto payments due for processing.
     */
    public function scopeDueForProcessing($query)
    {
        return $query->active()
                    ->where('is_paused', false)
                    ->where(function ($q) {
                        $q->whereNull('paused_until')
                          ->orWhere('paused_until', '<=', Carbon::now());
                    })
                    ->where('next_processing_date', '<=', Carbon::now());
    }

    /**
     * Scope to get auto payments by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get auto payments with failed payments.
     */
    public function scopeWithFailures($query)
    {
        return $query->where('consecutive_failures', '>', 0);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($autoPayment) {
            if (!$autoPayment->activated_at && $autoPayment->is_active) {
                $autoPayment->activated_at = Carbon::now();
            }

            if (!$autoPayment->next_processing_date && $autoPayment->type !== self::TYPE_INVOICE_AUTO_PAY) {
                $autoPayment->next_processing_date = $autoPayment->calculateNextProcessingDate();
            }
        });

        static::updating(function ($autoPayment) {
            if ($autoPayment->isDirty('is_active')) {
                if ($autoPayment->is_active) {
                    $autoPayment->activated_at = Carbon::now();
                    $autoPayment->deactivated_at = null;
                } else {
                    $autoPayment->deactivated_at = Carbon::now();
                }
            }
        });
    }
}