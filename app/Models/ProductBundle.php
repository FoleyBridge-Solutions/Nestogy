<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundle extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'sku',
        'bundle_type',
        'pricing_type',
        'fixed_price',
        'discount_percentage',
        'min_value',
        'is_active',
        'available_from',
        'available_until',
        'max_quantity',
        'image_url',
        'show_items_separately',
        'sort_order',
    ];

    protected $casts = [
        'fixed_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'min_value' => 'decimal:2',
        'is_active' => 'boolean',
        'show_items_separately' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    /**
     * Get the products in this bundle
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundle_items', 'bundle_id', 'product_id')
            ->withPivot([
                'quantity',
                'is_required',
                'is_default',
                'discount_type',
                'discount_value',
                'price_override',
                'min_quantity',
                'max_quantity',
                'allowed_variants',
                'sort_order',
            ])
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
    }

    /**
     * Get bundle items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id');
    }

    /**
     * Check if bundle is currently available
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now < $this->available_from) {
            return false;
        }

        if ($this->available_until && $now > $this->available_until) {
            return false;
        }

        return true;
    }

    /**
     * Check if bundle is configurable
     */
    public function isConfigurable(): bool
    {
        return $this->bundle_type === 'configurable';
    }

    /**
     * Check if bundle is dynamic
     */
    public function isDynamic(): bool
    {
        return $this->bundle_type === 'dynamic';
    }

    /**
     * Get required products
     */
    public function getRequiredProducts()
    {
        return $this->products()->wherePivot('is_required', true)->get();
    }

    /**
     * Get optional products
     */
    public function getOptionalProducts()
    {
        return $this->products()->wherePivot('is_required', false)->get();
    }

    /**
     * Calculate bundle price
     */
    public function calculatePrice($selectedProductIds = null): float
    {
        // Fixed price bundles
        if ($this->pricing_type === 'fixed' && $this->fixed_price !== null) {
            return $this->fixed_price;
        }

        $products = $this->products;
        $total = 0;

        foreach ($products as $product) {
            // Skip optional products not selected
            if ($selectedProductIds !== null &&
                ! $product->pivot->is_required &&
                ! in_array($product->id, $selectedProductIds)) {
                continue;
            }

            $quantity = $product->pivot->quantity;

            // Check for price override
            if ($product->pivot->price_override !== null) {
                $itemPrice = $product->pivot->price_override;
            } else {
                $itemPrice = $product->base_price;

                // Apply item-specific discount
                if ($product->pivot->discount_type === 'percentage') {
                    $itemPrice *= (1 - $product->pivot->discount_value / 100);
                } elseif ($product->pivot->discount_type === 'fixed') {
                    $itemPrice -= $product->pivot->discount_value;
                }
            }

            $total += $itemPrice * $quantity;
        }

        // Apply bundle discount
        if ($this->pricing_type === 'percentage_discount' && $this->discount_percentage) {
            $total *= (1 - $this->discount_percentage / 100);
        }

        // Ensure minimum value
        if ($this->min_value && $total < $this->min_value) {
            return $this->min_value;
        }

        return $total;
    }

    /**
     * Get bundle savings amount
     */
    public function getSavingsAmount($selectedProductIds = null): float
    {
        $originalTotal = 0;
        $products = $this->products;

        foreach ($products as $product) {
            // Skip optional products not selected
            if ($selectedProductIds !== null &&
                ! $product->pivot->is_required &&
                ! in_array($product->id, $selectedProductIds)) {
                continue;
            }

            $originalTotal += $product->base_price * $product->pivot->quantity;
        }

        $bundlePrice = $this->calculatePrice($selectedProductIds);

        return max(0, $originalTotal - $bundlePrice);
    }

    /**
     * Get bundle savings percentage
     */
    public function getSavingsPercentage($selectedProductIds = null): float
    {
        $originalTotal = 0;
        $products = $this->products;

        foreach ($products as $product) {
            // Skip optional products not selected
            if ($selectedProductIds !== null &&
                ! $product->pivot->is_required &&
                ! in_array($product->id, $selectedProductIds)) {
                continue;
            }

            $originalTotal += $product->base_price * $product->pivot->quantity;
        }

        if ($originalTotal == 0) {
            return 0;
        }

        $bundlePrice = $this->calculatePrice($selectedProductIds);
        $savings = $originalTotal - $bundlePrice;

        return ($savings / $originalTotal) * 100;
    }

    /**
     * Validate product selection for configurable bundle
     */
    public function validateSelection($selectedProductIds): array
    {
        $errors = [];

        if (! $this->isConfigurable()) {
            return $errors;
        }

        foreach ($this->products as $product) {
            $isSelected = in_array($product->id, $selectedProductIds);

            // Check required products
            if ($product->pivot->is_required && ! $isSelected) {
                $errors[] = "Product '{$product->name}' is required in this bundle";
            }

            // Check quantity constraints
            if ($isSelected) {
                $selectedQuantity = $this->getSelectedQuantity($product->id, $selectedProductIds);

                if ($product->pivot->min_quantity && $selectedQuantity < $product->pivot->min_quantity) {
                    $errors[] = "Minimum quantity for '{$product->name}' is {$product->pivot->min_quantity}";
                }

                if ($product->pivot->max_quantity && $selectedQuantity > $product->pivot->max_quantity) {
                    $errors[] = "Maximum quantity for '{$product->name}' is {$product->pivot->max_quantity}";
                }
            }
        }

        return $errors;
    }

    /**
     * Get selected quantity for a product (helper method)
     */
    protected function getSelectedQuantity($productId, $selection): int
    {
        // This would depend on how selection is structured
        // For now, return the pivot quantity
        $product = $this->products()->find($productId);

        return $product ? $product->pivot->quantity : 0;
    }

    /**
     * Create a copy of this bundle
     */
    public function duplicate(): ProductBundle
    {
        $newBundle = $this->replicate();
        $newBundle->name = $this->name.' (Copy)';
        $newBundle->sku = $this->sku ? $this->sku.'-COPY' : null;
        $newBundle->save();

        // Copy bundle items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->bundle_id = $newBundle->id;
            $newItem->save();
        }

        return $newBundle;
    }

    /**
     * Scope to get active bundles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
