<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SubscriptionPlan Model
 * 
 * Manages subscription plans for the SaaS platform including pricing,
 * user limits, and feature flags for different plan tiers.
 * 
 * @property int $id
 * @property string $name
 * @property string $stripe_price_id
 * @property float $price_monthly
 * @property int|null $user_limit
 * @property array|null $features
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'subscription_plans';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'stripe_price_id_yearly',
        'price_monthly',
        'price_yearly',
        'price_per_user_monthly',
        'pricing_model',
        'minimum_users',
        'base_price',
        'user_limit',
        'max_users',
        'max_clients',
        'features',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_per_user_monthly' => 'decimal:2',
        'base_price' => 'decimal:2',
        'minimum_users' => 'integer',
        'user_limit' => 'integer',
        'max_users' => 'integer',
        'max_clients' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default plan constants
     */
    const PLAN_STARTER = 'starter';
    const PLAN_PROFESSIONAL = 'professional';
    const PLAN_ENTERPRISE = 'enterprise';
    
    /**
     * Pricing model constants
     */
    const PRICING_FIXED = 'fixed';
    const PRICING_PER_USER = 'per_user';
    const PRICING_HYBRID = 'hybrid';

    /**
     * Get the clients subscribed to this plan.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'subscription_plan_id');
    }

    /**
     * Check if this plan has unlimited users.
     */
    public function hasUnlimitedUsers(): bool
    {
        // Check both user_limit and max_users fields for compatibility
        return $this->user_limit === null && $this->max_users === null;
    }

    /**
     * Check if this plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->features) {
            return false;
        }

        return in_array($feature, $this->features) || 
               (isset($this->features[$feature]) && $this->features[$feature] === true);
    }

    /**
     * Get the feature value for this plan.
     */
    public function getFeature(string $feature, $default = null)
    {
        if (!$this->features) {
            return $default;
        }

        return $this->features[$feature] ?? $default;
    }

    /**
     * Get all available features for this plan.
     */
    public function getAvailableFeatures(): array
    {
        return $this->features ?? [];
    }

    /**
     * Calculate monthly cost for a given user count.
     */
    public function calculateMonthlyCost(int $userCount = 1): float
    {
        $userCount = max($userCount, $this->minimum_users);
        
        switch ($this->pricing_model) {
            case self::PRICING_PER_USER:
                return $this->price_per_user_monthly * $userCount;
                
            case self::PRICING_HYBRID:
                return $this->base_price + ($this->price_per_user_monthly * $userCount);
                
            case self::PRICING_FIXED:
            default:
                return $this->price_monthly;
        }
    }

    /**
     * Check if a user count is within plan limits.
     */
    public function canAccommodateUsers(int $userCount): bool
    {
        if ($this->hasUnlimitedUsers()) {
            return true;
        }

        return $userCount <= $this->user_limit;
    }

    /**
     * Get formatted price display.
     */
    public function getFormattedPrice(int $userCount = 1): string
    {
        $totalCost = $this->calculateMonthlyCost($userCount);
        
        if ($totalCost == 0) {
            return 'Free';
        }
        
        switch ($this->pricing_model) {
            case self::PRICING_PER_USER:
                return '$' . number_format($this->price_per_user_monthly, 0) . '/user/month';
                
            case self::PRICING_HYBRID:
                return '$' . number_format($this->base_price, 0) . ' + $' . number_format($this->price_per_user_monthly, 0) . '/user/month';
                
            case self::PRICING_FIXED:
            default:
                return '$' . number_format($this->price_monthly, 0) . '/month';
        }
    }

    /**
     * Get user limit display text.
     */
    public function getUserLimitText(): string
    {
        // Check if per-user pricing with minimum
        if ($this->pricing_model === self::PRICING_PER_USER && $this->minimum_users) {
            if ($this->hasUnlimitedUsers()) {
                return 'Unlimited users (minimum ' . $this->minimum_users . ')';
            }
            return 'Pay per user (minimum ' . $this->minimum_users . ')';
        }

        if ($this->hasUnlimitedUsers()) {
            return 'Unlimited users';
        }

        // Use max_users field (which is what we're storing)
        $limit = $this->max_users ?? $this->user_limit ?? 0;

        if ($limit > 0) {
            return $limit . ' users included';
        }

        return 'Up to ' . $limit . ' users';
    }

    /**
     * Scope to get active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get plans ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }

    /**
     * Scope to get a plan by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Get validation rules for subscription plan.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'stripe_price_id' => 'required|string|unique:subscription_plans,stripe_price_id',
            'price_monthly' => 'required|numeric|min:0',
            'user_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get validation rules for updating.
     */
    public static function getUpdateValidationRules(int $planId): array
    {
        $rules = self::getValidationRules();
        $rules['stripe_price_id'] = 'required|string|unique:subscription_plans,stripe_price_id,' . $planId;
        return $rules;
    }
    
    /**
     * Check if this plan uses per-user pricing.
     */
    public function isPerUserPricing(): bool
    {
        return $this->pricing_model === self::PRICING_PER_USER;
    }
    
    /**
     * Check if this plan uses hybrid pricing.
     */
    public function isHybridPricing(): bool
    {
        return $this->pricing_model === self::PRICING_HYBRID;
    }
    
    /**
     * Check if this plan uses fixed pricing.
     */
    public function isFixedPricing(): bool
    {
        return $this->pricing_model === self::PRICING_FIXED;
    }
    
    /**
     * Get starting price display for marketing.
     */
    public function getStartingPrice(): string
    {
        // Check pricing model
        if ($this->pricing_model === self::PRICING_PER_USER) {
            $price = $this->price_per_user_monthly ?? 0;
            return '$' . number_format($price, 0);
        }

        // Fixed pricing plans
        $price = $this->price_monthly ?? 0;

        if ($price == 0) {
            return 'Free';
        }

        return '$' . number_format($price, 0);
    }
    
    /**
     * Get price explanation for marketing.
     */
    public function getPriceExplanation(): string
    {
        // Check pricing model
        if ($this->pricing_model === self::PRICING_PER_USER) {
            return 'user/month';
        }

        // For fixed pricing plans
        if ($this->price_monthly == 0) {
            return '';
        }

        return 'month';
    }

}