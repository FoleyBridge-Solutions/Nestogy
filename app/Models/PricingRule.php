<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * PricingRule Model
 * 
 * Complex pricing rule engine for dynamic usage-based billing with conditional logic,
 * time-based variations, and contract-specific pricing overrides.
 * 
 * @property int $id
 * @property int $company_id
 * @property int|null $client_id
 * @property int|null $contract_id
 * @property string $rule_name
 * @property string $rule_code
 * @property string $rule_type
 * @property string $usage_type
 * @property string $pricing_model
 * @property float|null $base_rate
 * @property bool $is_active
 * @property bool $is_global_rule
 * @property int $rule_priority
 * @property \Carbon\Carbon $effective_date
 * @property \Carbon\Carbon|null $expiry_date
 */
class PricingRule extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'pricing_rules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'contract_id',
        'rule_name',
        'rule_code',
        'rule_type',
        'description',
        'is_active',
        'rule_priority',
        'is_global_rule',
        'is_default_rule',
        'rule_scope',
        'usage_type',
        'service_types',
        'included_services',
        'excluded_services',
        'pricing_model',
        'billing_frequency',
        'is_prepaid',
        'requires_commitment',
        'base_rate',
        'setup_fee',
        'monthly_fee',
        'minimum_charge',
        'rate_unit',
        'has_time_based_pricing',
        'time_based_rates',
        'peak_hour_definitions',
        'holiday_rates',
        'weekend_rates',
        'has_geographic_pricing',
        'geographic_rates',
        'international_rates',
        'roaming_rates',
        'default_geographic_zone',
        'has_volume_discounts',
        'volume_discount_tiers',
        'loyalty_discount',
        'contract_discount',
        'bulk_pricing_rules',
        'minimum_monthly_commitment',
        'minimum_usage_commitment',
        'commitment_penalty_rate',
        'commitment_terms',
        'overage_handling',
        'overage_rate',
        'overage_threshold',
        'overage_rules',
        'is_promotional',
        'promotion_code',
        'promotion_start_date',
        'promotion_end_date',
        'promotion_conditions',
        'promotional_discount',
        'rule_conditions',
        'client_criteria',
        'usage_criteria',
        'time_criteria',
        'conditional_logic',
        'is_ab_test_rule',
        'ab_test_group',
        'test_allocation_percentage',
        'test_parameters',
        'test_start_date',
        'test_end_date',
        'performance_metrics',
        'average_revenue_per_user',
        'total_applications',
        'total_revenue_generated',
        'last_applied_at',
        'is_taxable',
        'tax_category_mapping',
        'regulatory_requirements',
        'requires_regulatory_approval',
        'regulatory_status',
        'billing_integration_settings',
        'billing_system_code',
        'auto_invoice_generation',
        'invoice_line_item_mapping',
        'primary_currency',
        'supported_currencies',
        'currency_conversion_rules',
        'dynamic_currency_rates',
        'effective_date',
        'expiry_date',
        'rule_version',
        'superseded_by_rule_id',
        'is_current_version',
        'external_rule_id',
        'integration_metadata',
        'last_sync_at',
        'sync_status',
        'prerequisite_rules',
        'conflicting_rules',
        'related_rules',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'approval_workflow',
        'created_by',
        'updated_by',
        'change_reason',
        'change_history',
        'last_reviewed_at',
        'last_reviewed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'contract_id' => 'integer',
        'is_active' => 'boolean',
        'rule_priority' => 'integer',
        'is_global_rule' => 'boolean',
        'is_default_rule' => 'boolean',
        'service_types' => 'array',
        'included_services' => 'array',
        'excluded_services' => 'array',
        'is_prepaid' => 'boolean',
        'requires_commitment' => 'boolean',
        'base_rate' => 'decimal:6',
        'setup_fee' => 'decimal:4',
        'monthly_fee' => 'decimal:4',
        'minimum_charge' => 'decimal:4',
        'has_time_based_pricing' => 'boolean',
        'time_based_rates' => 'array',
        'peak_hour_definitions' => 'array',
        'holiday_rates' => 'array',
        'weekend_rates' => 'array',
        'has_geographic_pricing' => 'boolean',
        'geographic_rates' => 'array',
        'international_rates' => 'array',
        'roaming_rates' => 'array',
        'has_volume_discounts' => 'boolean',
        'volume_discount_tiers' => 'array',
        'loyalty_discount' => 'decimal:3',
        'contract_discount' => 'decimal:3',
        'bulk_pricing_rules' => 'array',
        'minimum_monthly_commitment' => 'decimal:2',
        'minimum_usage_commitment' => 'decimal:4',
        'commitment_penalty_rate' => 'decimal:6',
        'commitment_terms' => 'array',
        'overage_rate' => 'decimal:6',
        'overage_threshold' => 'decimal:4',
        'overage_rules' => 'array',
        'is_promotional' => 'boolean',
        'promotion_start_date' => 'datetime',
        'promotion_end_date' => 'datetime',
        'promotion_conditions' => 'array',
        'promotional_discount' => 'decimal:3',
        'rule_conditions' => 'array',
        'client_criteria' => 'array',
        'usage_criteria' => 'array',
        'time_criteria' => 'array',
        'is_ab_test_rule' => 'boolean',
        'test_allocation_percentage' => 'decimal:2',
        'test_parameters' => 'array',
        'test_start_date' => 'datetime',
        'test_end_date' => 'datetime',
        'performance_metrics' => 'array',
        'average_revenue_per_user' => 'decimal:2',
        'total_applications' => 'integer',
        'total_revenue_generated' => 'decimal:2',
        'last_applied_at' => 'datetime',
        'is_taxable' => 'boolean',
        'tax_category_mapping' => 'array',
        'regulatory_requirements' => 'array',
        'requires_regulatory_approval' => 'boolean',
        'billing_integration_settings' => 'array',
        'auto_invoice_generation' => 'boolean',
        'invoice_line_item_mapping' => 'array',
        'supported_currencies' => 'array',
        'currency_conversion_rules' => 'array',
        'dynamic_currency_rates' => 'boolean',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
        'superseded_by_rule_id' => 'integer',
        'is_current_version' => 'boolean',
        'integration_metadata' => 'array',
        'last_sync_at' => 'datetime',
        'prerequisite_rules' => 'array',
        'conflicting_rules' => 'array',
        'related_rules' => 'array',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'approval_workflow' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'change_history' => 'array',
        'last_reviewed_at' => 'datetime',
        'last_reviewed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Rule type constants
     */
    const RULE_TYPE_STANDARD = 'standard';
    const RULE_TYPE_PROMOTIONAL = 'promotional';
    const RULE_TYPE_CONTRACT = 'contract';
    const RULE_TYPE_OVERRIDE = 'override';
    const RULE_TYPE_EMERGENCY = 'emergency';

    /**
     * Rule scope constants
     */
    const SCOPE_CLIENT = 'client';
    const SCOPE_GROUP = 'group';
    const SCOPE_GLOBAL = 'global';
    const SCOPE_CONTRACT = 'contract';

    /**
     * Pricing model constants
     */
    const PRICING_MODEL_TIERED = 'tiered';
    const PRICING_MODEL_FLAT_RATE = 'flat_rate';
    const PRICING_MODEL_USAGE_BASED = 'usage_based';
    const PRICING_MODEL_BLOCK = 'block';
    const PRICING_MODEL_HYBRID = 'hybrid';

    /**
     * Billing frequency constants
     */
    const BILLING_MONTHLY = 'monthly';
    const BILLING_DAILY = 'daily';
    const BILLING_USAGE_BASED = 'usage_based';
    const BILLING_REAL_TIME = 'real_time';

    /**
     * Overage handling constants
     */
    const OVERAGE_CHARGE = 'charge';
    const OVERAGE_BLOCK = 'block';
    const OVERAGE_THROTTLE = 'throttle';
    const OVERAGE_POOL = 'pool';

    /**
     * Approval status constants
     */
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    /**
     * Regulatory status constants
     */
    const REGULATORY_APPROVED = 'approved';
    const REGULATORY_PENDING = 'pending';
    const REGULATORY_REJECTED = 'rejected';

    /**
     * Get the client this rule belongs to (if client-specific).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the rule that superseded this one.
     */
    public function supersededByRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class, 'superseded_by_rule_id');
    }

    /**
     * Get the usage tiers for this pricing rule.
     */
    public function usageTiers(): HasMany
    {
        return $this->hasMany(UsageTier::class)->orderBy('tier_order');
    }

    /**
     * Get the user who approved this rule.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this rule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this rule.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who last reviewed this rule.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reviewed_by');
    }

    /**
     * Check if the rule is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
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
     * Check if the rule will be active on a specific date.
     */
    public function isActiveOnDate(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
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
     * Check if the rule applies to a specific client.
     */
    public function appliesToClient(int $clientId): bool
    {
        // Global rules apply to all clients
        if ($this->is_global_rule) {
            return true;
        }

        // Client-specific rules
        if ($this->client_id) {
            return $this->client_id === $clientId;
        }

        // Check client criteria
        if ($this->client_criteria) {
            return $this->evaluateClientCriteria($clientId);
        }

        return false;
    }

    /**
     * Check if the rule applies to a specific service type.
     */
    public function appliesToServiceType(string $serviceType): bool
    {
        // If no service types specified, applies to all
        if (empty($this->service_types)) {
            return true;
        }

        // Check if service type is included
        if (in_array($serviceType, $this->service_types)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate cost based on this pricing rule.
     */
    public function calculateCost(float $usage, array $options = []): array
    {
        if (!$this->isCurrentlyActive()) {
            return [
                'base_cost' => 0,
                'total_cost' => 0,
                'tiers_applied' => [],
                'rule_applied' => false,
            ];
        }

        $result = [
            'base_cost' => 0,
            'setup_fee' => $this->setup_fee ?? 0,
            'monthly_fee' => $this->monthly_fee ?? 0,
            'total_cost' => 0,
            'tiers_applied' => [],
            'discounts_applied' => [],
            'rule_applied' => true,
            'rule_id' => $this->id,
        ];

        switch ($this->pricing_model) {
            case self::PRICING_MODEL_FLAT_RATE:
                $result['base_cost'] = $this->base_rate ?? 0;
                break;

            case self::PRICING_MODEL_USAGE_BASED:
                $result['base_cost'] = $usage * ($this->base_rate ?? 0);
                break;

            case self::PRICING_MODEL_TIERED:
                $result = $this->calculateTieredCost($usage, $options, $result);
                break;

            case self::PRICING_MODEL_BLOCK:
                $result = $this->calculateBlockCost($usage, $options, $result);
                break;
        }

        // Apply time-based pricing
        if ($this->has_time_based_pricing) {
            $result = $this->applyTimeBasedPricing($result, $options);
        }

        // Apply geographic pricing
        if ($this->has_geographic_pricing) {
            $result = $this->applyGeographicPricing($result, $options);
        }

        // Apply volume discounts
        if ($this->has_volume_discounts) {
            $result = $this->applyVolumeDiscounts($result, $usage, $options);
        }

        // Apply minimum charge
        if ($this->minimum_charge && $result['base_cost'] < $this->minimum_charge) {
            $result['base_cost'] = $this->minimum_charge;
        }

        // Calculate total
        $result['total_cost'] = $result['base_cost'] + $result['setup_fee'] + $result['monthly_fee'];

        return $result;
    }

    /**
     * Calculate tiered pricing cost.
     */
    protected function calculateTieredCost(float $usage, array $options, array $result): array
    {
        $remainingUsage = $usage;
        $totalCost = 0;
        $tiersApplied = [];

        foreach ($this->usageTiers as $tier) {
            if ($remainingUsage <= 0) {
                break;
            }

            if ($tier->isCurrentlyActive() && $tier->containsUsage($usage)) {
                $tierCost = $tier->calculateCost($remainingUsage, $options);
                $totalCost += $tierCost;
                $tiersApplied[] = [
                    'tier_id' => $tier->id,
                    'tier_name' => $tier->tier_name,
                    'usage_applied' => min($remainingUsage, $tier->max_usage - $tier->min_usage),
                    'cost' => $tierCost,
                ];
                
                if ($tier->max_usage) {
                    $remainingUsage -= ($tier->max_usage - $tier->min_usage);
                } else {
                    $remainingUsage = 0; // Unlimited tier
                }
            }
        }

        $result['base_cost'] = $totalCost;
        $result['tiers_applied'] = $tiersApplied;
        
        return $result;
    }

    /**
     * Calculate block pricing cost.
     */
    protected function calculateBlockCost(float $usage, array $options, array $result): array
    {
        $blockSize = $this->bulk_pricing_rules['block_size'] ?? 1;
        $blockRate = $this->bulk_pricing_rules['block_rate'] ?? $this->base_rate ?? 0;
        
        $blocks = ceil($usage / $blockSize);
        $result['base_cost'] = $blocks * $blockRate;
        $result['blocks_applied'] = $blocks;
        
        return $result;
    }

    /**
     * Apply time-based pricing adjustments.
     */
    protected function applyTimeBasedPricing(array $result, array $options): array
    {
        $timestamp = $options['timestamp'] ?? now();
        $multiplier = 1.0;

        if ($timestamp->isWeekend() && isset($this->weekend_rates['multiplier'])) {
            $multiplier = $this->weekend_rates['multiplier'];
        } elseif ($this->isPeakTime($timestamp) && isset($this->time_based_rates['peak_multiplier'])) {
            $multiplier = $this->time_based_rates['peak_multiplier'];
        } elseif (isset($this->time_based_rates['off_peak_multiplier'])) {
            $multiplier = $this->time_based_rates['off_peak_multiplier'];
        }

        if ($multiplier !== 1.0) {
            $result['base_cost'] *= $multiplier;
            $result['time_multiplier'] = $multiplier;
        }

        return $result;
    }

    /**
     * Apply geographic pricing adjustments.
     */
    protected function applyGeographicPricing(array $result, array $options): array
    {
        $destination = $options['destination_country'] ?? null;
        
        if ($destination && isset($this->geographic_rates[$destination])) {
            $multiplier = $this->geographic_rates[$destination];
            $result['base_cost'] *= $multiplier;
            $result['geographic_multiplier'] = $multiplier;
        }

        return $result;
    }

    /**
     * Apply volume discount adjustments.
     */
    protected function applyVolumeDiscounts(array $result, float $usage, array $options): array
    {
        if (!$this->volume_discount_tiers) {
            return $result;
        }

        foreach ($this->volume_discount_tiers as $tier) {
            if ($usage >= $tier['min_usage']) {
                $discountAmount = $result['base_cost'] * ($tier['discount_percentage'] / 100);
                $result['base_cost'] -= $discountAmount;
                $result['discounts_applied'][] = [
                    'type' => 'volume_discount',
                    'tier' => $tier,
                    'amount' => $discountAmount,
                ];
                break; // Apply only the first matching tier
            }
        }

        return $result;
    }

    /**
     * Check if timestamp is during peak hours.
     */
    protected function isPeakTime(Carbon $timestamp): bool
    {
        if (!$this->peak_hour_definitions) {
            return false;
        }

        $hour = $timestamp->hour;
        
        foreach ($this->peak_hour_definitions as $period) {
            if ($hour >= $period['start'] && $hour < $period['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate client criteria.
     */
    protected function evaluateClientCriteria(int $clientId): bool
    {
        // This would contain complex logic to evaluate client criteria
        // For now, return true - implement based on specific requirements
        return true;
    }

    /**
     * Record rule application for analytics.
     */
    public function recordApplication(float $revenue = 0): void
    {
        $this->increment('total_applications');
        
        if ($revenue > 0) {
            $this->increment('total_revenue_generated', $revenue);
            
            // Update average revenue per user
            $this->average_revenue_per_user = $this->total_revenue_generated / $this->total_applications;
        }
        
        $this->update(['last_applied_at' => now()]);
    }

    /**
     * Check if rule is promotional.
     */
    public function isPromotional(): bool
    {
        return $this->is_promotional;
    }

    /**
     * Check if promotion is currently valid.
     */
    public function isPromotionValid(): bool
    {
        if (!$this->is_promotional) {
            return false;
        }

        $now = now();
        
        if ($this->promotion_start_date && $now->lt($this->promotion_start_date)) {
            return false;
        }

        if ($this->promotion_end_date && $now->gt($this->promotion_end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('approval_status', self::APPROVAL_APPROVED)
                    ->where('effective_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', now());
                    });
    }

    /**
     * Scope to get global rules.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global_rule', true);
    }

    /**
     * Scope to get client-specific rules.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->where('is_global_rule', true)
              ->orWhere('client_id', $clientId);
        });
    }

    /**
     * Scope to get rules by usage type.
     */
    public function scopeByUsageType($query, string $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    /**
     * Scope to get promotional rules.
     */
    public function scopePromotional($query)
    {
        return $query->where('is_promotional', true);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('rule_priority');
    }

    /**
     * Get available rule types.
     */
    public static function getRuleTypes(): array
    {
        return [
            self::RULE_TYPE_STANDARD => 'Standard',
            self::RULE_TYPE_PROMOTIONAL => 'Promotional',
            self::RULE_TYPE_CONTRACT => 'Contract',
            self::RULE_TYPE_OVERRIDE => 'Override',
            self::RULE_TYPE_EMERGENCY => 'Emergency',
        ];
    }

    /**
     * Get pricing models.
     */
    public static function getPricingModels(): array
    {
        return [
            self::PRICING_MODEL_TIERED => 'Tiered',
            self::PRICING_MODEL_FLAT_RATE => 'Flat Rate',
            self::PRICING_MODEL_USAGE_BASED => 'Usage Based',
            self::PRICING_MODEL_BLOCK => 'Block',
            self::PRICING_MODEL_HYBRID => 'Hybrid',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rule) {
            if (!$rule->rule_code) {
                $rule->rule_code = 'RULE-' . strtoupper(uniqid());
            }
            
            if (!isset($rule->rule_priority)) {
                $rule->rule_priority = 100; // Default priority
            }
            
            if (!$rule->effective_date) {
                $rule->effective_date = now();
            }
        });

        static::updating(function ($rule) {
            $rule->updated_by = auth()->id() ?? 1;
            
            // Store change history
            if ($rule->isDirty()) {
                $history = $rule->change_history ?? [];
                $history[] = [
                    'changed_at' => now(),
                    'changed_by' => auth()->id() ?? 1,
                    'changes' => $rule->getDirty(),
                    'reason' => $rule->change_reason,
                ];
                $rule->change_history = $history;
            }
        });
    }
}