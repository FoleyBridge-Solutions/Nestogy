<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Expense Model
 *
 * Represents business expenses with categorization and receipt tracking.
 * Supports vendor association and bank integration.
 */
class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expenses';

    const DELETED_AT = 'archived_at';

    protected $fillable = [
        'company_id', 'description', 'amount', 'currency_code', 'date', 'reference',
        'payment_method', 'receipt', 'vendor_id', 'client_id', 'category_id',
        'account_id', 'plaid_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'date' => 'date',
        'vendor_id' => 'integer', 'client_id' => 'integer',
        'category_id' => 'integer', 'account_id' => 'integer',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'archived_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getFormattedAmount(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    public function hasReceipt(): bool
    {
        return ! empty($this->receipt);
    }

    public function getReceiptUrl(): ?string
    {
        return $this->receipt ? asset('storage/expenses/'.$this->receipt) : null;
    }

    public function hasPlaidIntegration(): bool
    {
        return ! empty($this->plaid_transaction_id);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public static function getValidationRules(): array
    {
        return [
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'category_id' => 'required|integer|exists:categories,id',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'plaid_transaction_id' => 'nullable|string|max:255',
        ];
    }
}
