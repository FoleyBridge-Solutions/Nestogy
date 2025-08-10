<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Model
 * 
 * Represents payments received for invoices.
 * Supports multiple payment methods and bank integration.
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';
    // Using standard soft delete column to match database schema

    protected $fillable = [
        'company_id', 'client_id', 'invoice_id', 'processed_by',
        'payment_method', 'payment_reference', 'amount', 'currency',
        'gateway', 'gateway_transaction_id', 'gateway_fee', 'status',
        'payment_date', 'notes', 'metadata', 'refund_amount',
        'refund_reason', 'refunded_at', 'chargeback_amount',
        'chargeback_reason', 'chargeback_date'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'processed_by' => 'integer',
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'chargeback_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'refunded_at' => 'datetime',
        'chargeback_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array'
    ];

    const METHOD_CASH = 'Cash';
    const METHOD_CHECK = 'Check';
    const METHOD_CREDIT_CARD = 'Credit Card';
    const METHOD_BANK_TRANSFER = 'Bank Transfer';
    const METHOD_PAYPAL = 'PayPal';
    const METHOD_OTHER = 'Other';

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function hasPlaidIntegration(): bool
    {
        return !empty($this->plaid_transaction_id);
    }

    public static function getValidationRules(): array
    {
        return [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'account_id' => 'required|integer|exists:accounts,id',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            'plaid_transaction_id' => 'nullable|string|max:255',
        ];
    }

    public static function getAvailableMethods(): array
    {
        return [
            self::METHOD_CASH, self::METHOD_CHECK, self::METHOD_CREDIT_CARD,
            self::METHOD_BANK_TRANSFER, self::METHOD_PAYPAL, self::METHOD_OTHER
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($payment) {
            if ($payment->invoice && $payment->invoice->getBalance() <= 0) {
                $payment->invoice->markAsPaid();
            }
        });
    }
}