<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Account Model
 * 
 * Represents financial accounts for tracking payments and expenses.
 * Supports multi-currency and bank integration via Plaid.
 * 
 * @property int $id
 * @property string $name
 * @property float $opening_balance
 * @property string $currency_code
 * @property string|null $notes
 * @property int|null $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property string|null $plaid_id
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'accounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'opening_balance',
        'currency_code',
        'notes',
        'type',
        'plaid_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Account types enumeration
     */
    const TYPE_CHECKING = 1;
    const TYPE_SAVINGS = 2;
    const TYPE_CREDIT_CARD = 3;
    const TYPE_CASH = 4;
    const TYPE_INVESTMENT = 5;
    const TYPE_LOAN = 6;
    const TYPE_OTHER = 7;

    /**
     * Type labels mapping
     */
    const TYPE_LABELS = [
        self::TYPE_CHECKING => 'Checking',
        self::TYPE_SAVINGS => 'Savings',
        self::TYPE_CREDIT_CARD => 'Credit Card',
        self::TYPE_CASH => 'Cash',
        self::TYPE_INVESTMENT => 'Investment',
        self::TYPE_LOAN => 'Loan',
        self::TYPE_OTHER => 'Other',
    ];

    /**
     * Get payments from this account.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get expenses from this account.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Unknown';
    }

    /**
     * Check if account is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Check if account has Plaid integration.
     */
    public function hasPlaidIntegration(): bool
    {
        return !empty($this->plaid_id);
    }

    /**
     * Get current balance including transactions.
     */
    public function getCurrentBalance(): float
    {
        $paymentsTotal = $this->payments()->sum('amount');
        $expensesTotal = $this->expenses()->sum('amount');
        
        return $this->opening_balance + $paymentsTotal - $expensesTotal;
    }

    /**
     * Get formatted current balance.
     */
    public function getFormattedCurrentBalance(): string
    {
        return $this->formatCurrency($this->getCurrentBalance());
    }

    /**
     * Get formatted opening balance.
     */
    public function getFormattedOpeningBalance(): string
    {
        return $this->formatCurrency($this->opening_balance);
    }

    /**
     * Format amount with currency.
     */
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($amount, 2);
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }

    /**
     * Get total payments for this account.
     */
    public function getTotalPayments(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get total expenses for this account.
     */
    public function getTotalExpenses(): float
    {
        return $this->expenses()->sum('amount');
    }

    /**
     * Get transaction count.
     */
    public function getTransactionCount(): int
    {
        return $this->payments()->count() + $this->expenses()->count();
    }

    /**
     * Scope to search accounts.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    /**
     * Scope to get accounts by type.
     */
    public function scopeByType($query, int $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get accounts by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency_code', $currency);
    }

    /**
     * Scope to get accounts with Plaid integration.
     */
    public function scopeWithPlaid($query)
    {
        return $query->whereNotNull('plaid_id');
    }

    /**
     * Get validation rules for account creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'opening_balance' => 'required|numeric',
            'currency_code' => 'required|string|size:3',
            'notes' => 'nullable|string',
            'type' => 'nullable|integer|in:1,2,3,4,5,6,7',
            'plaid_id' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get validation rules for account update.
     */
    public static function getUpdateValidationRules(int $accountId): array
    {
        return self::getValidationRules();
    }

    /**
     * Get available account types.
     */
    public static function getAvailableTypes(): array
    {
        return self::TYPE_LABELS;
    }
}