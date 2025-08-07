<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InvoiceItem Model
 * 
 * Represents line items on invoices, quotes, and recurring invoices.
 * Supports quantity, pricing, discounts, and tax calculations.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $quantity
 * @property float $price
 * @property float $discount
 * @property float $subtotal
 * @property float $tax
 * @property float $total
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int|null $tax_id
 * @property int|null $quote_id
 * @property int|null $recurring_id
 * @property int|null $invoice_id
 * @property int|null $category_id
 * @property int|null $product_id
 */
class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'invoice_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'price',
        'discount',
        'subtotal',
        'tax',
        'total',
        'order',
        'tax_id',
        'quote_id',
        'recurring_id',
        'invoice_id',
        'category_id',
        'product_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'order' => 'integer',
        'tax_id' => 'integer',
        'quote_id' => 'integer',
        'recurring_id' => 'integer',
        'invoice_id' => 'integer',
        'category_id' => 'integer',
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the tax rate for this item.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * Get the quote this item belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the recurring invoice this item belongs to.
     */
    public function recurring(): BelongsTo
    {
        return $this->belongsTo(Recurring::class);
    }

    /**
     * Get the invoice this item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the category this item belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the product this item is based on.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate and update item totals.
     */
    public function calculateTotals(): void
    {
        // Calculate subtotal (quantity * price)
        $subtotal = $this->quantity * $this->price;
        
        // Apply discount
        $discountedSubtotal = $subtotal - $this->discount;
        
        // Calculate tax
        $taxAmount = 0;
        if ($this->taxRate) {
            $taxAmount = $this->taxRate->calculateTaxAmount($discountedSubtotal);
        }
        
        // Calculate total
        $total = $discountedSubtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'total' => $total,
        ]);
    }

    /**
     * Get formatted quantity.
     */
    public function getFormattedQuantity(): string
    {
        return number_format($this->quantity, 2);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted discount.
     */
    public function getFormattedDiscount(): string
    {
        return '$' . number_format($this->discount, 2);
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotal(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax.
     */
    public function getFormattedTax(): string
    {
        return '$' . number_format($this->tax, 2);
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotal(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Check if item has discount.
     */
    public function hasDiscount(): bool
    {
        return $this->discount > 0;
    }

    /**
     * Check if item has tax.
     */
    public function hasTax(): bool
    {
        return $this->tax > 0;
    }

    /**
     * Get discount percentage.
     */
    public function getDiscountPercentage(): float
    {
        if ($this->subtotal <= 0) {
            return 0;
        }

        return round(($this->discount / $this->subtotal) * 100, 2);
    }

    /**
     * Get formatted discount percentage.
     */
    public function getFormattedDiscountPercentage(): string
    {
        return number_format($this->getDiscountPercentage(), 2) . '%';
    }

    /**
     * Create item from product.
     */
    public static function createFromProduct(Product $product, float $quantity = 1): array
    {
        return [
            'name' => $product->name,
            'description' => $product->description,
            'quantity' => $quantity,
            'price' => $product->price,
            'discount' => 0,
            'tax_id' => $product->tax_id,
            'category_id' => $product->category_id,
            'product_id' => $product->id,
        ];
    }

    /**
     * Scope to get items by parent type.
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope to get items by parent type.
     */
    public function scopeForQuote($query, int $quoteId)
    {
        return $query->where('quote_id', $quoteId);
    }

    /**
     * Scope to get items by parent type.
     */
    public function scopeForRecurring($query, int $recurringId)
    {
        return $query->where('recurring_id', $recurringId);
    }

    /**
     * Scope to order by item order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get validation rules for invoice item creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'discount' => 'numeric|min:0',
            'order' => 'integer|min:0',
            'tax_id' => 'nullable|integer|exists:taxes,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Calculate totals when creating or updating
        static::saving(function ($item) {
            // Calculate subtotal
            $subtotal = $item->quantity * $item->price;
            
            // Apply discount
            $discountedSubtotal = $subtotal - $item->discount;
            
            // Calculate tax
            $taxAmount = 0;
            if ($item->taxRate) {
                $taxAmount = $item->taxRate->calculateTaxAmount($discountedSubtotal);
            }
            
            // Calculate total
            $total = $discountedSubtotal + $taxAmount;

            $item->subtotal = $subtotal;
            $item->tax = $taxAmount;
            $item->total = $total;
        });

        // Update parent totals when item changes
        static::saved(function ($item) {
            if ($item->invoice) {
                $item->invoice->calculateTotals();
            }
            if ($item->quote) {
                $item->quote->calculateTotals();
            }
            if ($item->recurring) {
                $item->recurring->calculateTotals();
            }
        });

        // Update parent totals when item is deleted
        static::deleted(function ($item) {
            if ($item->invoice) {
                $item->invoice->calculateTotals();
            }
            if ($item->quote) {
                $item->quote->calculateTotals();
            }
            if ($item->recurring) {
                $item->recurring->calculateTotals();
            }
        });
    }
}