<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
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
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $tax_id
 * @property int $category_id
 * @property string $type
 * @property string $unit_type
 * @property string $billing_model
 * @property bool $is_active
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'sku',
        'type',
        'subcategory_id',
        'base_price',
        'cost',
        'currency_code',
        'tax_id',
        'tax_profile_id',
        'category_id',
        'vendor_id',
        'tax_inclusive',
        'tax_rate',
        'unit_type',
        'billing_model',
        'billing_cycle',
        'billing_interval',
        'track_inventory',
        'current_stock',
        'reserved_stock',
        'min_stock_level',
        'max_quantity_per_order',
        'reorder_level',
        'supplier_id',
        'supplier_sku',
        'lead_time_days',
        'weight',
        'dimensions',
        'warranty_period',
        'tags',
        'is_active',
        'active',
        'taxable',
        'recurring_type',
        'stock_quantity',
        'sort_order',
        'short_description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'tax_id' => 'integer',
        'tax_profile_id' => 'integer',
        'category_id' => 'integer',
        'subcategory_id' => 'integer',
        'tax_inclusive' => 'boolean',
        'tax_rate' => 'decimal:4',
        'billing_cycle' => 'string',
        'billing_interval' => 'integer',
        'track_inventory' => 'boolean',
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_quantity_per_order' => 'integer',
        'reorder_level' => 'integer',
        'supplier_id' => 'integer',
        'lead_time_days' => 'integer',
        'weight' => 'decimal:2',
        'tags' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


    /**
     * Get the tax rate for this product.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
    
    /**
     * Get the tax profile that determines calculation requirements.
     */
    public function taxProfile(): BelongsTo
    {
        return $this->belongsTo(TaxProfile::class);
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
        return !is_null($this->deleted_at);
    }

    /**
     * Price accessor for backward compatibility
     */
    public function getPriceAttribute()
    {
        return $this->base_price;
    }

    /**
     * Price mutator for backward compatibility
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['base_price'] = $value;
    }


    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return $this->formatCurrency($this->base_price);
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
            return $this->base_price;
        }

        return $this->tax->calculateTotalWithTax($this->base_price);
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

        return $this->tax->calculateTaxAmount($this->base_price);
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

        return round((($this->base_price - $this->cost) / $this->base_price) * 100, 2);
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

        return round((($this->base_price - $this->cost) / $this->cost) * 100, 2);
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
        return $query->whereBetween('base_price', [$min, $max]);
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
        return $query->orderBy('base_price', $direction);
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
     * Scope to get only products.
     */
    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    /**
     * Scope to get only services.
     */
    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    /**
     * Scope to get active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by billing model.
     */
    public function scopeByBillingModel($query, string $billingModel)
    {
        return $query->where('billing_model', $billingModel);
    }

    /**
     * Check if this is a service.
     */
    public function isService(): bool
    {
        return $this->type === 'service';
    }

    /**
     * Check if this is a product.
     */
    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    /**
     * Check if this is a subscription service.
     */
    public function isSubscription(): bool
    {
        return $this->billing_model === 'subscription';
    }

    /**
     * Check if this is usage-based billing.
     */
    public function isUsageBased(): bool
    {
        return $this->billing_model === 'usage_based';
    }

    /**
     * Get formatted unit type for display.
     */
    public function getFormattedUnitType(): string
    {
        return match($this->unit_type) {
            'hours' => 'per hour',
            'days' => 'per day',
            'weeks' => 'per week',
            'months' => 'per month',
            'years' => 'per year',
            'fixed' => 'fixed price',
            'subscription' => 'subscription',
            'units' => 'per unit',
            default => $this->unit_type,
        };
    }

    /**
     * Get billing cycle description.
     */
    public function getBillingCycleDescription(): string
    {
        if (!$this->isSubscription()) {
            return 'N/A';
        }

        $interval = $this->billing_interval ?: 1;
        $cycle = $this->billing_cycle ?: 'month';
        
        if ($interval === 1) {
            return "Every {$cycle}";
        }
        
        return "Every {$interval} {$cycle}s";
    }

    /**
     * Get required tax fields for invoice calculations.
     */
    public function getRequiredTaxFields(): array
    {
        if (!$this->taxProfile) {
            return [];
        }
        
        return $this->taxProfile->required_fields ?? [];
    }

    /**
     * Check if this product requires specific tax data for calculations.
     */
    public function requiresTaxData(): bool
    {
        return !empty($this->getRequiredTaxFields());
    }

    /**
     * Get tax calculation engine type for this product.
     */
    public function getTaxEngineType(): string
    {
        if (!$this->taxProfile) {
            return 'general';
        }
        
        return $this->taxProfile->profile_type ?? 'general';
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