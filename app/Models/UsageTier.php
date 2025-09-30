<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UsageTier Model
 *
 * Manages tiered pricing structures for usage-based billing with support for
 * progressive rates, volume discounts, and time-of-day pricing variations.
 *
 * @property int $id
 * @property int $company_id
 * @property int $pricing_rule_id
 * @property string $tier_name
 * @property string $tier_code
 * @property string $usage_type
 * @property string $service_type
 * @property float $min_usage
 * @property float|null $max_usage
 * @property string $pricing_model
 * @property float|null $base_rate
 * @property float|null $per_unit_rate
 * @property bool $is_active
 * @property \Carbon\Carbon $effective_date
 * @property \Carbon\Carbon|null $expiry_date
 */
class UsageTier extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'usage_tiers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'pricing_rule_id',
        'tier_name',
        'tier_code',
        'description',
        'tier_order',
        'is_active',
        'usage_type',
        'service_type',
        'applicable_services',
        'min_usage',
        'max_usage',
        'usage_unit',
        'is_unlimited_tier',
        'pricing_model',
        'base_rate',
        'per_unit_rate',
        'block_size',
        'block_rate',
        'setup_fee',
        'has_peak_pricing',
        'peak_rate_multiplier',
        'off_peak_rate_multiplier',
        'weekend_rate_multiplier',
        'peak_hours',
        'time_zone_rules',
        'has_geographic_pricing',
        'geographic_rates',
        'destination_rates',
        'has_volume_discounts',
        'volume_discount_rules',
        'commitment_discount',
        'loyalty_discount',
        'overage_handling',
        'overage_rate',
        'allows_rollover',
        'rollover_months',
        'rollover_percentage',
        'billing_frequency',
        'is_prorated',
        'proration_method',
        'requires_advance_payment',
        'advance_payment_days',
        'tier_conditions',
        'bundling_rules',
        'exclusion_rules',
        'is_taxable',
        'tax_category_mapping',
        'regulatory_compliance',
        'effective_date',
        'expiry_date',
        'is_promotional',
        'promotion_code',
        'reporting_categories',
        'track_detailed_usage',
        'kpi_targets',
        'external_tier_id',
        'billing_system_code',
        'integration_metadata',
        'created_by',
        'updated_by',
        'change_reason',
        'tier_history',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'pricing_rule_id' => 'integer',
        'tier_order' => 'integer',
        'is_active' => 'boolean',
        'min_usage' => 'decimal:4',
        'max_usage' => 'decimal:4',
        'is_unlimited_tier' => 'boolean',
        'base_rate' => 'decimal:6',
        'per_unit_rate' => 'decimal:6',
        'block_size' => 'decimal:2',
        'block_rate' => 'decimal:4',
        'setup_fee' => 'decimal:4',
        'has_peak_pricing' => 'boolean',
        'peak_rate_multiplier' => 'decimal:3',
        'off_peak_rate_multiplier' => 'decimal:3',
        'weekend_rate_multiplier' => 'decimal:3',
        'peak_hours' => 'array',
        'time_zone_rules' => 'array',
        'has_geographic_pricing' => 'boolean',
        'geographic_rates' => 'array',
        'destination_rates' => 'array',
        'has_volume_discounts' => 'boolean',
        'volume_discount_rules' => 'array',
        'commitment_discount' => 'decimal:3',
        'loyalty_discount' => 'decimal:3',
        'overage_rate' => 'decimal:6',
        'allows_rollover' => 'boolean',
        'rollover_months' => 'integer',
        'rollover_percentage' => 'decimal:2',
        'is_prorated' => 'boolean',
        'requires_advance_payment' => 'boolean',
        'advance_payment_days' => 'integer',
        'tier_conditions' => 'array',
        'bundling_rules' => 'array',
        'exclusion_rules' => 'array',
        'is_taxable' => 'boolean',
        'tax_category_mapping' => 'array',
        'regulatory_compliance' => 'array',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
        'is_promotional' => 'boolean',
        'reporting_categories' => 'array',
        'track_detailed_usage' => 'boolean',
        'kpi_targets' => 'array',
        'integration_metadata' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'tier_history' => 'array',
        'applicable_services' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Pricing model constants
     */
    const PRICING_MODEL_FLAT_RATE = 'flat_rate';

    const PRICING_MODEL_PER_UNIT = 'per_unit';

    const PRICING_MODEL_BLOCK_PRICING = 'block_pricing';

    const PRICING_MODEL_PROGRESSIVE = 'progressive';

    /**
     * Overage handling constants
     */
    const OVERAGE_CHARGE = 'charge';

    const OVERAGE_BLOCK = 'block';

    const OVERAGE_THROTTLE = 'throttle';

    const OVERAGE_POOL = 'pool';

    /**
     * Billing frequency constants
     */
    const BILLING_MONTHLY = 'monthly';

    const BILLING_DAILY = 'daily';

    const BILLING_USAGE_BASED = 'usage_based';

    /**
     * Proration method constants
     */
    const PRORATION_DAILY = 'daily';

    const PRORATION_HOURLY = 'hourly';

    const PRORATION_USAGE_BASED = 'usage_based';

    /**
     * Get the pricing rule this tier belongs to.
     */
    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    /**
     * Get the user who created this tier.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this tier.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the tier is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->effective_date && $now->lt($this->effective_date)) {
            return false;
        }

        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the tier will be active on a specific date.
     */
    public function isActiveOnDate(Carbon $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->effective_date && $date->lt($this->effective_date)) {
            return false;
        }

        if ($this->expiry_date && $date->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if usage amount falls within this tier.
     */
    public function containsUsage(float $usage): bool
    {
        if ($usage < $this->min_usage) {
            return false;
        }

        if ($this->max_usage && $usage > $this->max_usage) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the cost for usage within this tier.
     */
    public function calculateCost(float $usage, array $options = []): float
    {
        if (! $this->containsUsage($usage)) {
            return 0;
        }

        $tierUsage = $this->getTierUsage($usage);
        $baseCost = $this->calculateBaseCost($tierUsage, $options);

        // Apply time-based multipliers
        if ($this->has_peak_pricing) {
            $baseCost *= $this->getTimeMultiplier($options);
        }

        // Apply geographic pricing
        if ($this->has_geographic_pricing) {
            $baseCost *= $this->getGeographicMultiplier($options);
        }

        // Apply volume discounts
        if ($this->has_volume_discounts) {
            $baseCost *= $this->getVolumeDiscountMultiplier($usage);
        }

        return round($baseCost, 4);
    }

    /**
     * Get the usage amount that falls within this tier.
     */
    protected function getTierUsage(float $totalUsage): float
    {
        $tierStart = $this->min_usage;
        $tierEnd = $this->max_usage ?? $totalUsage;

        return min($totalUsage, $tierEnd) - $tierStart;
    }

    /**
     * Calculate base cost without modifiers.
     */
    protected function calculateBaseCost(float $tierUsage, array $options = []): float
    {
        switch ($this->pricing_model) {
            case self::PRICING_MODEL_FLAT_RATE:
                return $this->base_rate ?? 0;

            case self::PRICING_MODEL_PER_UNIT:
                return $tierUsage * ($this->per_unit_rate ?? 0);

            case self::PRICING_MODEL_BLOCK_PRICING:
                if ($this->block_size && $this->block_rate) {
                    $blocks = ceil($tierUsage / $this->block_size);

                    return $blocks * $this->block_rate;
                }

                return 0;

            case self::PRICING_MODEL_PROGRESSIVE:
                return $tierUsage * ($this->per_unit_rate ?? 0);

            default:
                return 0;
        }
    }

    /**
     * Get time-based multiplier.
     */
    protected function getTimeMultiplier(array $options = []): float
    {
        $timestamp = $options['timestamp'] ?? now();

        if ($timestamp->isWeekend()) {
            return $this->weekend_rate_multiplier;
        }

        if ($this->isPeakTime($timestamp)) {
            return $this->peak_rate_multiplier;
        }

        return $this->off_peak_rate_multiplier;
    }

    /**
     * Check if timestamp is during peak hours.
     */
    protected function isPeakTime(Carbon $timestamp): bool
    {
        if (! $this->peak_hours) {
            return false;
        }

        $hour = $timestamp->hour;

        foreach ($this->peak_hours as $period) {
            if ($hour >= $period['start'] && $hour < $period['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get geographic pricing multiplier.
     */
    protected function getGeographicMultiplier(array $options = []): float
    {
        $destination = $options['destination_country'] ?? null;

        if (! $destination || ! $this->geographic_rates) {
            return 1.0;
        }

        return $this->geographic_rates[$destination] ?? 1.0;
    }

    /**
     * Get volume discount multiplier.
     */
    protected function getVolumeDiscountMultiplier(float $totalUsage): float
    {
        if (! $this->volume_discount_rules) {
            return 1.0;
        }

        foreach ($this->volume_discount_rules as $rule) {
            if ($totalUsage >= $rule['min_usage']) {
                return 1.0 - ($rule['discount_percentage'] / 100);
            }
        }

        return 1.0;
    }

    /**
     * Get formatted rate display.
     */
    public function getFormattedRate(): string
    {
        switch ($this->pricing_model) {
            case self::PRICING_MODEL_FLAT_RATE:
                return '$'.number_format($this->base_rate, 2);

            case self::PRICING_MODEL_PER_UNIT:
                return '$'.number_format($this->per_unit_rate, 4).' per '.$this->usage_unit;

            case self::PRICING_MODEL_BLOCK_PRICING:
                return '$'.number_format($this->block_rate, 2).' per '.number_format($this->block_size).' '.$this->usage_unit;

            default:
                return 'Variable';
        }
    }

    /**
     * Get tier usage range display.
     */
    public function getUsageRangeDisplay(): string
    {
        $min = number_format($this->min_usage);

        if ($this->is_unlimited_tier || ! $this->max_usage) {
            return $min.'+ '.$this->usage_unit;
        }

        $max = number_format($this->max_usage);

        return $min.' - '.$max.' '.$this->usage_unit;
    }

    /**
     * Check if tier is promotional.
     */
    public function isPromotional(): bool
    {
        return $this->is_promotional;
    }

    /**
     * Scope to get active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope to get tiers by usage type.
     */
    public function scopeByUsageType($query, string $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    /**
     * Scope to get tiers by service type.
     */
    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope to get promotional tiers.
     */
    public function scopePromotional($query)
    {
        return $query->where('is_promotional', true);
    }

    /**
     * Scope to order by tier order.
     */
    public function scopeOrderByTier($query)
    {
        return $query->orderBy('tier_order');
    }

    /**
     * Get available pricing models.
     */
    public static function getPricingModels(): array
    {
        return [
            self::PRICING_MODEL_FLAT_RATE => 'Flat Rate',
            self::PRICING_MODEL_PER_UNIT => 'Per Unit',
            self::PRICING_MODEL_BLOCK_PRICING => 'Block Pricing',
            self::PRICING_MODEL_PROGRESSIVE => 'Progressive',
        ];
    }

    /**
     * Get overage handling options.
     */
    public static function getOverageHandlingOptions(): array
    {
        return [
            self::OVERAGE_CHARGE => 'Charge Overage',
            self::OVERAGE_BLOCK => 'Block Usage',
            self::OVERAGE_THROTTLE => 'Throttle Usage',
            self::OVERAGE_POOL => 'Use Pool',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tier) {
            if (! $tier->tier_code) {
                $tier->tier_code = 'TIER-'.strtoupper(uniqid());
            }

            if (! isset($tier->tier_order)) {
                $lastTier = static::where('pricing_rule_id', $tier->pricing_rule_id)
                    ->orderBy('tier_order', 'desc')
                    ->first();

                $tier->tier_order = $lastTier ? $lastTier->tier_order + 1 : 1;
            }
        });

        static::updating(function ($tier) {
            $tier->updated_by = auth()->id() ?? 1;

            // Store change history
            if ($tier->isDirty()) {
                $history = $tier->tier_history ?? [];
                $history[] = [
                    'changed_at' => now(),
                    'changed_by' => auth()->id() ?? 1,
                    'changes' => $tier->getDirty(),
                    'reason' => $tier->change_reason,
                ];
                $tier->tier_history = $history;
            }
        });
    }
}
