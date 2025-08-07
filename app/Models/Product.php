<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 * 
 * Represents products/services that can be added to invoices and quotes.
 * Supports pricing, tax rates, and categorization.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property int|null $cost
 * @property string $currency_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int|null $tax_id
 * @property int $category_id
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'cost',
        'currency_code',
        'tax_id',
        'category_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'integer',
        'tax_id' => 'integer',
        'category_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the tax rate for this product.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get invoice items using this product.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Check if product is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return $this->formatCurrency($this->price);
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCost(): string
    {
        return $this->cost ? $this->formatCurrency($this->cost) : 'Not set';
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
     * Calculate price with tax.
     */
    public function getPriceWithTax(): float
    {
        if (!$this->tax) {
            return $this->price;
        }

        return $this->tax->calculateTotalWithTax($this->price);
    }

    /**
     * Get formatted price with tax.
     */
    public function getFormattedPriceWithTax(): string
    {
        return $this->formatCurrency($this->getPriceWithTax());
    }

    /**
     * Calculate tax amount.
     */
    public function getTaxAmount(): float
    {
        if (!$this->tax) {
            return 0;
        }

        return $this->tax->calculateTaxAmount($this->price);
    }

    /**
     * Get formatted tax amount.
     */
    public function getFormattedTaxAmount(): string
    {
        return $this->formatCurrency($this->getTaxAmount());
    }

    /**
     * Calculate profit margin.
     */
    public function getProfitMargin(): ?float
    {
        if (!$this->cost || $this->cost <= 0) {
            return null;
        }

        return round((($this->price - $this->cost) / $this->price) * 100, 2);
    }

    /**
     * Get formatted profit margin.
     */
    public function getFormattedProfitMargin(): string
    {
        $margin = $this->getProfitMargin();
        return $margin !== null ? number_format($margin, 2) . '%' : 'N/A';
    }

    /**
     * Calculate markup percentage.
     */
    public function getMarkupPercentage(): ?float
    {
        if (!$this->cost || $this->cost <= 0) {
            return null;
        }

        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }

    /**
     * Get formatted markup percentage.
     */
    public function getFormattedMarkupPercentage(): string
    {
        $markup = $this->getMarkupPercentage();
        return $markup !== null ? number_format($markup, 2) . '%' : 'N/A';
    }

    /**
     * Check if product has tax.
     */
    public function hasTax(): bool
    {
        return !is_null($this->tax_id) && $this->tax && $this->tax->percent > 0;
    }

    /**
     * Check if product has cost set.
     */
    public function hasCost(): bool
    {
        return !is_null($this->cost) && $this->cost > 0;
    }

    /**
     * Get usage count (how many times used in invoices).
     */
    public function getUsageCount(): int
    {
        return $this->invoiceItems()->count();
    }

    /**
     * Check if product is in use.
     */
    public function isInUse(): bool
    {
        return $this->getUsageCount() > 0;
    }

    /**
     * Get total revenue generated by this product.
     */
    public function getTotalRevenue(): float
    {
        return $this->invoiceItems()->sum('total');
    }

    /**
     * Get formatted total revenue.
     */
    public function getFormattedTotalRevenue(): string
    {
        return $this->formatCurrency($this->getTotalRevenue());
    }

    /**
     * Scope to search products.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope to get products by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to get products by price range.
     */
    public function scopeByPriceRange($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope to get products with tax.
     */
    public function scopeWithTax($query)
    {
        return $query->whereNotNull('tax_id');
    }

    /**
     * Scope to get products without tax.
     */
    public function scopeWithoutTax($query)
    {
        return $query->whereNull('tax_id');
    }

    /**
     * Scope to get products with cost.
     */
    public function scopeWithCost($query)
    {
        return $query->whereNotNull('cost')->where('cost', '>', 0);
    }

    /**
     * Scope to order by price.
     */
    public function scopeOrderByPrice($query, string $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    /**
     * Scope to order by usage.
     */
    public function scopeOrderByUsage($query, string $direction = 'desc')
    {
        return $query->withCount('invoiceItems')
                    ->orderBy('invoice_items_count', $direction);
    }

    /**
     * Get validation rules for product creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|integer|min:0',
            'currency_code' => 'required|string|size:3',
            'tax_id' => 'nullable|integer|exists:taxes,id',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }

    /**
     * Get validation rules for product update.
     */
    public static function getUpdateValidationRules(int $productId): array
    {
        return self::getValidationRules();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion of products that are in use
        static::deleting(function ($product) {
            if ($product->isInUse()) {
                throw new \Exception('Cannot delete product that is used in invoices');
            }
        });
    }
}