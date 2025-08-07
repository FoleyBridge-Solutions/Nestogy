<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Quote Model
 * 
 * Represents client quotes that can be converted to invoices.
 * Similar structure to invoices with expiration dates.
 */
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotes';
    const DELETED_AT = 'archived_at';

    protected $fillable = [
        'prefix', 'number', 'scope', 'status', 'discount_amount', 'date', 'expire',
        'amount', 'currency_code', 'note', 'url_key', 'category_id', 'client_id'
    ];

    protected $casts = [
        'number' => 'integer', 'date' => 'date', 'expire' => 'date',
        'discount_amount' => 'decimal:2', 'amount' => 'decimal:2',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'archived_at' => 'datetime'
    ];

    const STATUS_DRAFT = 'Draft';
    const STATUS_SENT = 'Sent';
    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_DECLINED = 'Declined';
    const STATUS_EXPIRED = 'Expired';

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }

    public function getFullNumber(): string
    {
        return ($this->prefix ?: 'QTE') . '-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        return $this->expire && Carbon::now()->gt($this->expire);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $tax = $this->items()->sum('tax');
        $total = $subtotal - $this->discount_amount + $tax;
        $this->update(['amount' => $total]);
    }

    public function convertToInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $this->client_id,
            'category_id' => $this->category_id,
            'date' => now(),
            'due' => now()->addDays(30),
            'currency_code' => $this->currency_code,
            'discount_amount' => $this->discount_amount,
            'note' => $this->note,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        foreach ($this->items as $item) {
            $invoice->items()->create($item->toArray());
        }

        $invoice->calculateTotals();
        return $invoice;
    }

    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10', 'number' => 'required|integer|min:1',
            'status' => 'required|in:Draft,Sent,Accepted,Declined,Expired',
            'date' => 'required|date', 'expire' => 'nullable|date|after:date',
            'discount_amount' => 'numeric|min:0', 'currency_code' => 'required|string|size:3',
            'category_id' => 'required|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($quote) {
            if (!$quote->number) {
                $lastQuote = static::where('client_id', $quote->client_id)
                    ->where('prefix', $quote->prefix)->orderBy('number', 'desc')->first();
                $quote->number = $lastQuote ? $lastQuote->number + 1 : 1;
            }
            if (!$quote->url_key) $quote->url_key = bin2hex(random_bytes(16));
        });
    }
}