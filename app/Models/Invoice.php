<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Invoice Model
 * 
 * Represents client invoices with line items, payments, and status tracking.
 * Supports multi-currency and public access via URL keys.
 * 
 * @property int $id
 * @property string|null $prefix
 * @property int $number
 * @property string|null $scope
 * @property string $status
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $due
 * @property float $discount_amount
 * @property float $amount
 * @property string $currency_code
 * @property string|null $note
 * @property string|null $url_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $category_id
 * @property int $client_id
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prefix',
        'number',
        'scope',
        'status',
        'date',
        'due',
        'discount_amount',
        'amount',
        'currency_code',
        'note',
        'url_key',
        'category_id',
        'client_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'number' => 'integer',
        'date' => 'date',
        'due' => 'date',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'category_id' => 'integer',
        'client_id' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Invoice status enumeration
     */
    const STATUS_DRAFT = 'Draft';
    const STATUS_SENT = 'Sent';
    const STATUS_PAID = 'Paid';
    const STATUS_OVERDUE = 'Overdue';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Get the client this invoice belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the category this invoice belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get tickets associated with this invoice.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the invoice's full number.
     */
    public function getFullNumber(): string
    {
        if ($this->prefix) {
            return $this->prefix . '-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
        }

        return 'INV-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_PAID && 
               $this->status !== self::STATUS_CANCELLED &&
               Carbon::now()->gt($this->due);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get total paid amount.
     */
    public function getTotalPaid(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get remaining balance.
     */
    public function getBalance(): float
    {
        return $this->amount - $this->getTotalPaid();
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->getBalance() <= 0;
    }

    /**
     * Get subtotal (before discount and tax).
     */
    public function getSubtotal(): float
    {
        return $this->items()->sum('subtotal');
    }

    /**
     * Get total tax amount.
     */
    public function getTotalTax(): float
    {
        return $this->items()->sum('tax');
    }

    /**
     * Calculate and update invoice totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->getSubtotal();
        $tax = $this->getTotalTax();
        $total = $subtotal - $this->discount_amount + $tax;

        $this->update(['amount' => $total]);
    }

    /**
     * Get public URL for client access.
     */
    public function getPublicUrl(): string
    {
        if (!$this->url_key) {
            $this->generateUrlKey();
        }

        return url('/invoice/' . $this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return $this->formatCurrency($this->amount);
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalance(): string
    {
        return $this->formatCurrency($this->getBalance());
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
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED])
                    ->where('due', '<', Carbon::now());
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get invoices by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search invoices.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('number', $search)
              ->orWhere('note', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get validation rules for invoice creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'scope' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Sent,Paid,Overdue,Cancelled',
            'date' => 'required|date',
            'due' => 'required|date|after_or_equal:date',
            'discount_amount' => 'numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment invoice number
        static::creating(function ($invoice) {
            if (!$invoice->number) {
                $lastInvoice = static::where('client_id', $invoice->client_id)
                    ->where('prefix', $invoice->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $invoice->number = $lastInvoice ? $lastInvoice->number + 1 : 1;
            }

            // Generate URL key
            if (!$invoice->url_key) {
                $invoice->url_key = bin2hex(random_bytes(16));
            }
        });

        // Update status based on due date
        static::retrieved(function ($invoice) {
            if ($invoice->isOverdue() && $invoice->status === self::STATUS_SENT) {
                $invoice->update(['status' => self::STATUS_OVERDUE]);
            }
        });
    }
}