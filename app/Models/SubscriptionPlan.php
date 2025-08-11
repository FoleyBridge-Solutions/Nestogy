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
        'stripe_price_id',
        'price_monthly',
        'user_limit',
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
        'user_limit' => 'integer',
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
        return $this->user_limit === null;
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
        // For now, all plans are per-company pricing, not per-user
        // But Starter plan might have user limits
        if ($this->name === self::PLAN_STARTER && $userCount > ($this->user_limit ?? 10)) {
            // Could implement overage pricing here
            return $this->price_monthly;
        }

        return $this->price_monthly;
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
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price_monthly, 2) . '/month';
    }

    /**
     * Get user limit display text.
     */
    public function getUserLimitText(): string
    {
        if ($this->hasUnlimitedUsers()) {
            return 'Unlimited users';
        }

        return 'Up to ' . $this->user_limit . ' users';
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

}