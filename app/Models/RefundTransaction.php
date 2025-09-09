<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Refund Transaction Model
 * 
 * Manages actual payment gateway transactions for refund processing,
 * supporting multiple gateways, retry logic, risk management,
 * and comprehensive transaction tracking.
 */
class RefundTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'refund_transactions';

    protected $fillable = [
        'company_id', 'refund_request_id', 'original_payment_id', 'processed_by',
        'transaction_id', 'external_transaction_id', 'batch_id', 'transaction_type',
        'status', 'amount', 'currency_code', 'processing_fee', 'gateway_fee',
        'net_amount', 'exchange_rate', 'gateway', 'gateway_transaction_id',
        'gateway_reference_number', 'gateway_request', 'gateway_response',
        'gateway_status_code', 'gateway_message', 'gateway_metadata',
        'card_last_four', 'card_brand', 'card_type', 'authorization_code',
        'bank_name', 'account_last_four', 'routing_number_masked', 'account_type',
        'account_holder_name', 'ach_trace_number', 'check_number', 'check_amount',
        'check_date', 'payee_address', 'check_memo', 'check_printed', 'check_mailed',
        'tracking_number', 'paypal_transaction_id', 'paypal_payer_id',
        'paypal_correlation_id', 'stripe_charge_id', 'stripe_refund_id',
        'stripe_payment_intent_id', 'retry_count', 'max_retries', 'next_retry_at',
        'error_log', 'failure_reason', 'risk_score', 'risk_factors',
        'requires_manual_review', 'flagged_as_suspicious', 'risk_notes',
        'chargeback_eligible', 'chargeback_deadline', 'chargeback_reason_code',
        'chargeback_liability_amount', 'settled', 'settlement_date',
        'settlement_batch_id', 'settlement_amount', 'reconciled', 'reconciliation_date',
        'pci_compliant', 'compliance_data', 'security_token', 'tokenized',
        'customer_notified', 'notification_sent_at', 'notification_details',
        'initiated_at', 'processed_at', 'completed_at', 'failed_at', 'cancelled_at',
        'processing_time_seconds', 'sla_met', 'sla_deadline', 'audit_trail',
        'correlation_id', 'metadata'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'refund_request_id' => 'integer',
        'original_payment_id' => 'integer',
        'processed_by' => 'integer',
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'check_amount' => 'decimal:2',
        'chargeback_liability_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'processing_time_seconds' => 'integer',
        'risk_score' => 'decimal:2',
        'check_printed' => 'boolean',
        'check_mailed' => 'boolean',
        'requires_manual_review' => 'boolean',
        'flagged_as_suspicious' => 'boolean',
        'chargeback_eligible' => 'boolean',
        'settled' => 'boolean',
        'reconciled' => 'boolean',
        'pci_compliant' => 'boolean',
        'tokenized' => 'boolean',
        'customer_notified' => 'boolean',
        'sla_met' => 'boolean',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'gateway_metadata' => 'array',
        'error_log' => 'array',
        'risk_factors' => 'array',
        'compliance_data' => 'array',
        'notification_details' => 'array',
        'audit_trail' => 'array',
        'metadata' => 'array',
        'check_date' => 'date',
        'chargeback_deadline' => 'date',
        'settlement_date' => 'date',
        'reconciliation_date' => 'date',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Transaction Types
    const TYPE_CREDIT_CARD_REFUND = 'credit_card_refund';
    const TYPE_ACH_REFUND = 'ach_refund';
    const TYPE_WIRE_TRANSFER_REFUND = 'wire_transfer_refund';
    const TYPE_PAYPAL_REFUND = 'paypal_refund';
    const TYPE_STRIPE_REFUND = 'stripe_refund';
    const TYPE_CHECK_REFUND = 'check_refund';
    const TYPE_ACCOUNT_CREDIT = 'account_credit';
    const TYPE_MANUAL_REFUND = 'manual_refund';
    const TYPE_CHARGEBACK_REFUND = 'chargeback_refund';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REVERSED = 'reversed';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_SETTLED = 'settled';

    // Gateways
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_PAYPAL = 'paypal';
    const GATEWAY_AUTHORIZE_NET = 'authorize_net';
    const GATEWAY_SQUARE = 'square';
    const GATEWAY_BRAINTREE = 'braintree';
    const GATEWAY_MANUAL = 'manual';

    // Card Types
    const CARD_TYPE_CREDIT = 'Credit';
    const CARD_TYPE_DEBIT = 'Debit';

    // Account Types
    const ACCOUNT_TYPE_CHECKING = 'checking';
    const ACCOUNT_TYPE_SAVINGS = 'savings';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class);
    }

    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'original_payment_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeRequiringReview($query)
    {
        return $query->where('requires_manual_review', true);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('flagged_as_suspicious', true);
    }

    public function scopeSettled($query)
    {
        return $query->where('settled', true);
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciled', true);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
                    ->where('retry_count', '<', DB::raw('max_retries'))
                    ->where(function($q) {
                        $q->whereNull('next_retry_at')
                          ->orWhere('next_retry_at', '<=', now());
                    });
    }

    /**
     * Business Logic Methods
     */

    /**
     * Generate transaction ID
     */
    public static function generateTransactionId(): string
    {
        $companyId = Auth::user()?->company_id;
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return "RT-{$companyId}-{$timestamp}-{$random}";
    }

    /**
     * Check if transaction can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED &&
               $this->retry_count < $this->max_retries &&
               (!$this->next_retry_at || $this->next_retry_at <= now());
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Mark transaction as processing
     */
    public function markAsProcessing(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_PROCESSING;
        $this->processed_at = now();
        $this->save();

        return true;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $gatewayResponse = []): bool
    {
        if ($this->status !== self::STATUS_PROCESSING) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->processing_time_seconds = $this->calculateProcessingTime();
        
        if (!empty($gatewayResponse)) {
            $this->gateway_response = $gatewayResponse;
            $this->gateway_transaction_id = $gatewayResponse['transaction_id'] ?? null;
            $this->gateway_status_code = $gatewayResponse['status_code'] ?? null;
        }

        $this->save();

        // Update refund request
        $this->refundRequest?->complete($this->net_amount);

        return true;
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason, array $errorData = []): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        $this->failure_reason = $reason;
        $this->processing_time_seconds = $this->calculateProcessingTime();
        
        if (!empty($errorData)) {
            $this->error_log = array_merge($this->error_log ?? [], [$errorData]);
        }

        // Schedule retry if within limits
        if ($this->retry_count < $this->max_retries) {
            $this->scheduleRetry();
        }

        $this->save();

        return true;
    }

    /**
     * Schedule retry
     */
    public function scheduleRetry(): void
    {
        // Exponential backoff: 2^retry_count minutes
        $retryMinutes = pow(2, $this->retry_count) * 5; // 5, 10, 20, 40 minutes
        $this->next_retry_at = now()->addMinutes($retryMinutes);
        $this->retry_count += 1;
    }

    /**
     * Calculate processing time in seconds
     */
    public function calculateProcessingTime(): ?int
    {
        if (!$this->processed_at) {
            return null;
        }

        return $this->processed_at->diffInSeconds(now());
    }

    /**
     * Check if SLA is met
     */
    public function checkSla(): bool
    {
        if (!$this->sla_deadline) {
            return true;
        }

        $this->sla_met = now() <= $this->sla_deadline;
        $this->save();

        return $this->sla_met;
    }

    /**
     * Flag as suspicious
     */
    public function flagAsSuspicious(array $riskFactors, float $riskScore = null): void
    {
        $this->flagged_as_suspicious = true;
        $this->requires_manual_review = true;
        $this->risk_factors = $riskFactors;
        
        if ($riskScore !== null) {
            $this->risk_score = $riskScore;
        }

        $this->save();
    }

    /**
     * Mark as settled
     */
    public function markAsSettled(string $batchId = null, float $settlementAmount = null): bool
    {
        if (!$this->isCompleted()) {
            return false;
        }

        $this->settled = true;
        $this->settlement_date = now()->toDateString();
        $this->settlement_batch_id = $batchId;
        $this->settlement_amount = $settlementAmount ?? $this->net_amount;
        
        $this->save();

        return true;
    }

    /**
     * Mark as reconciled
     */
    public function markAsReconciled(): bool
    {
        if (!$this->settled) {
            return false;
        }

        $this->reconciled = true;
        $this->reconciliation_date = now()->toDateString();
        $this->save();

        return true;
    }

    /**
     * Get available transaction types
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_CREDIT_CARD_REFUND => 'Credit Card Refund',
            self::TYPE_ACH_REFUND => 'ACH Refund',
            self::TYPE_WIRE_TRANSFER_REFUND => 'Wire Transfer Refund',
            self::TYPE_PAYPAL_REFUND => 'PayPal Refund',
            self::TYPE_STRIPE_REFUND => 'Stripe Refund',
            self::TYPE_CHECK_REFUND => 'Check Refund',
            self::TYPE_ACCOUNT_CREDIT => 'Account Credit',
            self::TYPE_MANUAL_REFUND => 'Manual Refund',
            self::TYPE_CHARGEBACK_REFUND => 'Chargeback Refund'
        ];
    }

    /**
     * Get available gateways
     */
    public static function getGateways(): array
    {
        return [
            self::GATEWAY_STRIPE => 'Stripe',
            self::GATEWAY_PAYPAL => 'PayPal',
            self::GATEWAY_AUTHORIZE_NET => 'Authorize.Net',
            self::GATEWAY_SQUARE => 'Square',
            self::GATEWAY_BRAINTREE => 'Braintree',
            self::GATEWAY_MANUAL => 'Manual'
        ];
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REVERSED => 'orange',
            self::STATUS_DISPUTED => 'purple',
            self::STATUS_SETTLED => 'green',
            default => 'gray'
        };
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency_code;
    }

    /**
     * Get formatted net amount
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2) . ' ' . $this->currency_code;
    }

    /**
     * Get masked card number for display
     */
    public function getMaskedCardAttribute(): ?string
    {
        if (!$this->card_last_four) {
            return null;
        }

        return '**** **** **** ' . $this->card_last_four;
    }

    /**
     * Get masked bank account for display
     */
    public function getMaskedAccountAttribute(): ?string
    {
        if (!$this->account_last_four) {
            return null;
        }

        return '****' . $this->account_last_four;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (!$transaction->company_id) {
                $transaction->company_id = Auth::user()?->company_id;
            }
            
            if (!$transaction->processed_by) {
                $transaction->processed_by = Auth::id();
            }
            
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = self::generateTransactionId();
            }
            
            if (!$transaction->initiated_at) {
                $transaction->initiated_at = now();
            }
            
            // Set default max retries
            if (!$transaction->max_retries) {
                $transaction->max_retries = 3;
            }
            
            // Calculate net amount
            if (!$transaction->net_amount) {
                $transaction->net_amount = $transaction->amount - 
                    ($transaction->processing_fee ?? 0) - 
                    ($transaction->gateway_fee ?? 0);
            }
            
            // Set default SLA deadline (24 hours for most transactions)
            if (!$transaction->sla_deadline) {
                $transaction->sla_deadline = now()->addHours(24);
            }
        });
        
        static::updated(function ($transaction) {
            // Check SLA when status changes
            if ($transaction->isDirty('status')) {
                $transaction->checkSla();
            }
            
            // Update correlation ID for tracking
            if (!$transaction->correlation_id) {
                $transaction->correlation_id = $transaction->refundRequest?->request_number ?? 
                    $transaction->transaction_id;
            }
        });
    }
}