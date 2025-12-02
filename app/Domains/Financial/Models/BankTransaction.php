<?php

namespace App\Domains\Financial\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Account;
use App\Domains\Core\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BankTransaction Model
 *
 * Represents transactions synced from bank accounts via Plaid.
 * Can be reconciled with payments or expenses.
 */
class BankTransaction extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'plaid_item_id',
        'plaid_transaction_id',
        'plaid_account_id',
        'amount',
        'date',
        'authorized_date',
        'name',
        'merchant_name',
        'category',
        'category_id',
        'pending',
        'payment_channel',
        'transaction_type',
        'location',
        'payment_meta',
        'is_reconciled',
        'reconciled_payment_id',
        'reconciled_expense_id',
        'reconciled_at',
        'reconciled_by',
        'reconciliation_notes',
        'is_ignored',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'authorized_date' => 'date',
        'category' => 'array',
        'location' => 'array',
        'payment_meta' => 'array',
        'metadata' => 'array',
        'pending' => 'boolean',
        'is_reconciled' => 'boolean',
        'is_ignored' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Get the account this transaction belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the Plaid item.
     */
    public function plaidItem(): BelongsTo
    {
        return $this->belongsTo(PlaidItem::class);
    }

    /**
     * Get the reconciled payment.
     */
    public function reconciledPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reconciled_payment_id');
    }

    /**
     * Get the reconciled expense.
     */
    public function reconciledExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'reconciled_expense_id');
    }

    /**
     * Get the category.
     */
    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the user who reconciled.
     */
    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Scope: Get unreconciled transactions.
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false)
            ->where('is_ignored', false);
    }

    /**
     * Scope: Get pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('pending', true);
    }

    /**
     * Scope: Get transactions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get income transactions (positive amounts).
     */
    public function scopeIncome($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope: Get expense transactions (negative amounts).
     */
    public function scopeExpense($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Check if transaction is reconciled.
     */
    public function isReconciled(): bool
    {
        return $this->is_reconciled;
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return '$' . number_format(abs($this->amount), 2);
    }

    /**
     * Get display name (merchant name or transaction name).
     */
    public function getDisplayName(): string
    {
        return $this->merchant_name ?? $this->name;
    }

    /**
     * Reconcile with payment.
     */
    public function reconcileWithPayment(Payment $payment, ?int $userId = null): bool
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_payment_id' => $payment->id,
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Reconcile with expense.
     */
    public function reconcileWithExpense(Expense $expense, ?int $userId = null): bool
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_expense_id' => $expense->id,
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Unreconcile transaction.
     */
    public function unreconcile(): bool
    {
        $this->update([
            'is_reconciled' => false,
            'reconciled_payment_id' => null,
            'reconciled_expense_id' => null,
            'reconciled_at' => null,
            'reconciled_by' => null,
            'reconciliation_notes' => null,
        ]);

        return true;
    }

    /**
     * Mark as ignored.
     */
    public function ignore(): bool
    {
        $this->update(['is_ignored' => true]);
        return true;
    }

    /**
     * Unignore transaction.
     */
    public function unignore(): bool
    {
        $this->update(['is_ignored' => false]);
        return true;
    }

    /**
     * Get primary category from Plaid categories.
     */
    public function getPrimaryCategory(): ?string
    {
        if (empty($this->category) || !is_array($this->category)) {
            return null;
        }

        return $this->category[0] ?? null;
    }

    /**
     * Get all Plaid categories as comma-separated string.
     */
    public function getCategoriesString(): string
    {
        if (empty($this->category) || !is_array($this->category)) {
            return 'Uncategorized';
        }

        return implode(', ', $this->category);
    }
}
