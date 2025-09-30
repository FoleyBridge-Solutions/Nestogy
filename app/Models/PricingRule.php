<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'client_id',
        'name',
        'pricing_model',
        'discount_type',
        'discount_value',
        'price_override',
        'min_quantity',
        'max_quantity',
        'quantity_increment',
        'valid_from',
        'valid_until',
        'applicable_days',
        'applicable_hours',
        'is_promotional',
        'promo_code',
        'conditions',
        'priority',
        'is_active',
        'is_combinable',
        'max_uses',
        'uses_count',
        'max_uses_per_client',
        'requires_approval',
        'approval_threshold',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'price_override' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_days' => 'array',
        'applicable_hours' => 'array',
        'conditions' => 'array',
        'is_promotional' => 'boolean',
        'is_active' => 'boolean',
        'is_combinable' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the product this rule applies to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the client this rule applies to (if client-specific)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Check if rule is currently valid
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now < $this->valid_from) {
            return false;
        }

        if ($this->valid_until && $now > $this->valid_until) {
            return false;
        }

        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if rule applies to a specific quantity
     */
    public function appliesToQuantity($quantity): bool
    {
        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * Check if rule applies on a specific date/time
     */
    public function appliesToDateTime($dateTime = null): bool
    {
        $dt = $dateTime ? new \DateTime($dateTime) : new \DateTime;

        // Check day of week
        if (! empty($this->applicable_days)) {
            $dayOfWeek = strtolower($dt->format('l'));
            if (! in_array($dayOfWeek, $this->applicable_days)) {
                return false;
            }
        }

        // Check hour of day
        if (! empty($this->applicable_hours)) {
            $hour = (int) $dt->format('G');
            if (! in_array($hour, $this->applicable_hours)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discounted price
     */
    public function calculatePrice($basePrice, $quantity = 1): float
    {
        // Price override takes precedence
        if ($this->price_override !== null) {
            return $this->price_override;
        }

        $discountAmount = 0;

        if ($this->discount_type === 'percentage') {
            $discountAmount = $basePrice * ($this->discount_value / 100);
        } elseif ($this->discount_type === 'fixed') {
            $discountAmount = $this->discount_value;
        }

        $finalPrice = max(0, $basePrice - $discountAmount);

        // Apply tiered pricing if applicable
        if ($this->pricing_model === 'tiered' && ! empty($this->conditions['tiers'])) {
            $finalPrice = $this->calculateTieredPrice($basePrice, $quantity);
        } elseif ($this->pricing_model === 'volume' && ! empty($this->conditions['volumes'])) {
            $finalPrice = $this->calculateVolumePrice($basePrice, $quantity);
        }

        return $finalPrice;
    }

    /**
     * Calculate tiered pricing (different prices at different quantity levels)
     */
    protected function calculateTieredPrice($basePrice, $quantity): float
    {
        $tiers = $this->conditions['tiers'] ?? [];
        $total = 0;
        $remaining = $quantity;

        foreach ($tiers as $tier) {
            $tierMin = $tier['min'] ?? 0;
            $tierMax = $tier['max'] ?? PHP_INT_MAX;
            $tierPrice = $tier['price'] ?? $basePrice;

            if ($remaining <= 0) {
                break;
            }

            $tierQuantity = min($remaining, $tierMax - $tierMin + 1);
            $total += $tierQuantity * $tierPrice;
            $remaining -= $tierQuantity;
        }

        return $total / $quantity; // Return average price per unit
    }

    /**
     * Calculate volume pricing (all units at the tier price)
     */
    protected function calculateVolumePrice($basePrice, $quantity): float
    {
        $volumes = $this->conditions['volumes'] ?? [];
        $applicablePrice = $basePrice;

        foreach ($volumes as $volume) {
            $volumeMin = $volume['min'] ?? 0;
            $volumeMax = $volume['max'] ?? PHP_INT_MAX;

            if ($quantity >= $volumeMin && $quantity <= $volumeMax) {
                $applicablePrice = $volume['price'] ?? $basePrice;
                break;
            }
        }

        return $applicablePrice;
    }

    /**
     * Check if promo code matches
     */
    public function validatePromoCode($code): bool
    {
        if (! $this->is_promotional || ! $this->promo_code) {
            return true;
        }

        return strcasecmp($this->promo_code, $code) === 0;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Check if rule can be combined with others
     */
    public function canCombineWith(PricingRule $otherRule): bool
    {
        return $this->is_combinable && $otherRule->is_combinable;
    }

    /**
     * Get discount amount for display
     */
    public function getDiscountDisplay(): string
    {
        if ($this->price_override !== null) {
            return '$'.number_format($this->price_override, 2);
        }

        if ($this->discount_type === 'percentage') {
            return $this->discount_value.'% off';
        } elseif ($this->discount_type === 'fixed') {
            return '$'.number_format($this->discount_value, 2).' off';
        }

        return 'Custom pricing';
    }

    /**
     * Scope to get active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope to get rules for a specific client
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->whereNull('client_id')
                ->orWhere('client_id', $clientId);
        });
    }

    /**
     * Scope to order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');
    }
}
