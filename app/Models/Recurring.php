<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Recurring Model
 * 
 * Represents recurring invoices with automatic generation schedules.
 */
class Recurring extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recurring';
    const DELETED_AT = 'archived_at';

    protected $fillable = [
        'prefix', 'number', 'scope', 'frequency', 'last_sent', 'next_date',
        'status', 'discount_amount', 'amount', 'currency_code', 'note',
        'category_id', 'client_id'
    ];

    protected $casts = [
        'number' => 'integer', 'last_sent' => 'date', 'next_date' => 'date',
        'status' => 'boolean', 'discount_amount' => 'decimal:2', 'amount' => 'decimal:2',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'archived_at' => 'datetime'
    ];

    const FREQUENCY_MONTHLY = 'Monthly';
    const FREQUENCY_QUARTERLY = 'Quarterly';
    const FREQUENCY_YEARLY = 'Yearly';

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }

    public function getFullNumber(): string
    {
        return ($this->prefix ?: 'REC') . '-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    public function isDue(): bool
    {
        return $this->status && Carbon::now()->gte($this->next_date);
    }

    public function calculateNextDate(): Carbon
    {
        $current = $this->next_date ?: Carbon::now();
        return match($this->frequency) {
            self::FREQUENCY_MONTHLY => $current->addMonth(),
            self::FREQUENCY_QUARTERLY => $current->addMonths(3),
            self::FREQUENCY_YEARLY => $current->addYear(),
            default => $current->addMonth(),
        };
    }

    public function generateInvoice(): Invoice
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
        
        $this->update([
            'last_sent' => now(),
            'next_date' => $this->calculateNextDate(),
        ]);

        return $invoice;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $tax = $this->items()->sum('tax');
        $total = $subtotal - $this->discount_amount + $tax;
        $this->update(['amount' => $total]);
    }

    public static function getValidationRules(): array
    {
        return [
            'frequency' => 'required|in:Monthly,Quarterly,Yearly',
            'next_date' => 'required|date',
            'status' => 'boolean',
            'discount_amount' => 'numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'category_id' => 'required|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($recurring) {
            if (!$recurring->number) {
                $lastRecurring = static::where('client_id', $recurring->client_id)
                    ->where('prefix', $recurring->prefix)->orderBy('number', 'desc')->first();
                $recurring->number = $lastRecurring ? $lastRecurring->number + 1 : 1;
            }
        });
    }
}