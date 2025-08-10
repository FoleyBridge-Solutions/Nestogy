<?php

namespace App\Domains\Financial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'invoice_id',
        'company_id',
        'payment_method',
        'payment_reference',
        'amount',
        'currency',
        'gateway',
        'gateway_transaction_id',
        'gateway_fee',
        'status',
        'payment_date',
        'notes',
        'metadata',
        'processed_by',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'chargeback_amount',
        'chargeback_reason',
        'chargeback_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'chargeback_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'refunded_at' => 'datetime',
        'chargeback_date' => 'datetime',
        'metadata' => 'json',
    ];

    protected $dates = [
        'payment_date',
        'refunded_at',
        'chargeback_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Payment status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIAL_REFUND = 'partial_refund';
    const STATUS_CHARGEBACK = 'chargeback';

    /**
     * Payment method constants
     */
    const METHOD_CASH = 'cash';
    const METHOD_CHECK = 'check';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_STRIPE = 'stripe';
    const METHOD_SQUARE = 'square';
    const METHOD_ACH = 'ach';
    const METHOD_WIRE_TRANSFER = 'wire_transfer';

    /**
     * Gateway constants
     */
    const GATEWAY_MANUAL = 'manual';
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_PAYPAL = 'paypal';
    const GATEWAY_SQUARE = 'square';
    const GATEWAY_AUTHORIZE_NET = 'authorize_net';

    /**
     * Get the client that owns this payment
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice this payment is for
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who processed this payment
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for refunded payments
     */
    public function scopeRefunded($query)
    {
        return $query->whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIAL_REFUND]);
    }

    /**
     * Get payment methods list
     */
    public static function getPaymentMethods()
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHECK => 'Check',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_DEBIT_CARD => 'Debit Card',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_SQUARE => 'Square',
            self::METHOD_ACH => 'ACH',
            self::METHOD_WIRE_TRANSFER => 'Wire Transfer',
        ];
    }

    /**
     * Get payment statuses list
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_PARTIAL_REFUND => 'Partial Refund',
            self::STATUS_CHARGEBACK => 'Chargeback',
        ];
    }

    /**
     * Get gateways list
     */
    public static function getGateways()
    {
        return [
            self::GATEWAY_MANUAL => 'Manual',
            self::GATEWAY_STRIPE => 'Stripe',
            self::GATEWAY_PAYPAL => 'PayPal',
            self::GATEWAY_SQUARE => 'Square',
            self::GATEWAY_AUTHORIZE_NET => 'Authorize.Net',
        ];
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIAL_REFUND]);
    }

    /**
     * Get net amount (amount minus fees and refunds)
     */
    public function getNetAmountAttribute(): float
    {
        $net = (float) $this->amount;
        
        if ($this->gateway_fee) {
            $net -= (float) $this->gateway_fee;
        }
        
        if ($this->refund_amount) {
            $net -= (float) $this->refund_amount;
        }
        
        return $net;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_COMPLETED:
                return 'bg-green-100 text-green-800';
            case self::STATUS_PENDING:
                return 'bg-yellow-100 text-yellow-800';
            case self::STATUS_PROCESSING:
                return 'bg-blue-100 text-blue-800';
            case self::STATUS_FAILED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_CANCELLED:
                return 'bg-gray-100 text-gray-800';
            case self::STATUS_REFUNDED:
            case self::STATUS_PARTIAL_REFUND:
                return 'bg-orange-100 text-orange-800';
            case self::STATUS_CHARGEBACK:
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}