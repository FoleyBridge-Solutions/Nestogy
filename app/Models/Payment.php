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
    const DELETED_AT = 'archived_at';

    protected $fillable = [
        'date', 'amount', 'currency_code', 'method', 'reference',
        'account_id', 'invoice_id', 'plaid_transaction_id'
    ];

    protected $casts = [
        'date' => 'date', 'amount' => 'decimal:2',
        'account_id' => 'integer', 'invoice_id' => 'integer',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'archived_at' => 'datetime'
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