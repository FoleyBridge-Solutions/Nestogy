<?php

namespace App\Domains\Financial\Models;

use App\Domains\Client\Models\Client;
use App\Traits\BelongsToCompany;
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
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'payments';
    // Using standard soft delete column to match database schema

    protected $fillable = [
        'company_id', 'client_id', 'processed_by',
        'payment_method', 'payment_reference', 'amount', 'currency',
        'applied_amount', 'available_amount', 'application_status', 'auto_apply',
        'gateway', 'gateway_transaction_id', 'gateway_fee', 'status',
        'payment_date', 'notes', 'metadata', 'refund_amount',
        'refund_reason', 'refunded_at', 'chargeback_amount',
        'chargeback_reason', 'chargeback_date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'processed_by' => 'integer',
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'auto_apply' => 'boolean',
        'gateway_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'chargeback_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'refunded_at' => 'datetime',
        'chargeback_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array',
    ];

    const METHOD_CASH = 'Cash';

    const METHOD_CHECK = 'Check';

    const METHOD_CREDIT_CARD = 'Credit Card';

    const METHOD_BANK_TRANSFER = 'Bank Transfer';

    const METHOD_PAYPAL = 'PayPal';

    const METHOD_OTHER = 'Other';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function applications()
    {
        return $this->hasMany(PaymentApplication::class);
    }

    public function activeApplications()
    {
        return $this->applications()->where('is_active', true);
    }

    public function appliedInvoices()
    {
        return $this->belongsToMany(Invoice::class, 'payment_applications', 'payment_id', 'applicable_id')
            ->wherePivot('applicable_type', Invoice::class)
            ->wherePivot('is_active', true)
            ->withPivot(['amount', 'applied_date', 'notes']);
    }

    public function getFormattedAmount(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    public function hasPlaidIntegration(): bool
    {
        return ! empty($this->plaid_transaction_id);
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
            self::METHOD_BANK_TRANSFER, self::METHOD_PAYPAL, self::METHOD_OTHER,
        ];
    }

    public function getAvailableAmount(): float
    {
        return round($this->amount - $this->activeApplications()->sum('amount'), 2);
    }

    public function isFullyApplied(): bool
    {
        return $this->getAvailableAmount() <= 0;
    }

    public function isPartiallyApplied(): bool
    {
        $availableAmount = $this->getAvailableAmount();
        return $availableAmount > 0 && $availableAmount < $this->amount;
    }

    public function isUnapplied(): bool
    {
        return $this->activeApplications()->count() === 0;
    }

    public function canApply(float $amount): bool
    {
        return $this->getAvailableAmount() >= $amount && $this->status === 'completed';
    }

    public function recalculateApplicationAmounts(): void
    {
        $appliedAmount = $this->activeApplications()->sum('amount');
        $availableAmount = $this->amount - $appliedAmount;

        $status = 'unapplied';
        if ($appliedAmount > 0) {
            $status = $availableAmount <= 0 ? 'fully_applied' : 'partially_applied';
        }

        $this->update([
            'applied_amount' => $appliedAmount,
            'available_amount' => $availableAmount,
            'application_status' => $status,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (! isset($payment->available_amount)) {
                $payment->available_amount = $payment->amount;
            }
            if (! isset($payment->applied_amount)) {
                $payment->applied_amount = 0;
            }
            if (! isset($payment->application_status)) {
                $payment->application_status = 'unapplied';
            }
        });

        static::deleting(function ($payment) {
            $payment->applications->each->delete();
        });
    }
}
