<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UsagePool Model
 *
 * Manages shared usage allowances and limits across multiple clients, services, or locations.
 * Supports complex pooling scenarios for enterprise VoIP deployments.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $client_id
 * @property int|null $parent_pool_id
 * @property string $pool_name
 * @property string $pool_code
 * @property string $pool_type
 * @property string $usage_type
 * @property float $total_capacity
 * @property float $allocated_capacity
 * @property float $used_capacity
 * @property string $capacity_unit
 * @property bool $is_active
 * @property string $pool_status
 */
class UsagePool extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'usage_pools';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'parent_pool_id',
        'pool_name',
        'pool_code',
        'pool_type',
        'description',
        'is_active',
        'usage_type',
        'service_types',
        'included_services',
        'excluded_services',
        'total_capacity',
        'allocated_capacity',
        'used_capacity',
        'capacity_unit',
        'warning_threshold',
        'critical_threshold',
        'allocation_method',
        'allocation_weights',
        'priority_rules',
        'allow_overallocation',
        'overallocation_limit',
        'pool_members',
        'member_allocations',
        'member_priorities',
        'max_members',
        'current_period_usage',
        'previous_period_usage',
        'lifetime_usage',
        'last_usage_update',
        'usage_history',
        'allows_rollover',
        'rollover_percentage',
        'rollover_months',
        'rollover_capacity',
        'rollover_expires_at',
        'billing_model',
        'pool_cost_per_unit',
        'overage_rate',
        'cost_allocation_method',
        'billing_preferences',
        'has_time_restrictions',
        'time_restrictions',
        'peak_hour_rules',
        'weekend_restrictions',
        'has_geographic_restrictions',
        'geographic_rules',
        'allowed_locations',
        'restricted_destinations',
        'auto_refill_enabled',
        'auto_refill_threshold',
        'auto_refill_amount',
        'auto_refill_frequency',
        'auto_suspend_on_depletion',
        'alert_settings',
        'notification_recipients',
        'email_alerts_enabled',
        'sms_alerts_enabled',
        'last_alert_sent',
        'pool_status',
        'status_reason',
        'activated_at',
        'suspended_at',
        'expires_at',
        'billing_cycle',
        'cycle_start_date',
        'cycle_end_date',
        'next_reset_date',
        'auto_reset_enabled',
        'contract_reference',
        'contract_terms',
        'committed_spend',
        'discount_rate',
        'reporting_tags',
        'detailed_reporting_enabled',
        'kpi_metrics',
        'benchmark_data',
        'external_pool_id',
        'integration_settings',
        'last_sync_at',
        'sync_status',
        'created_by',
        'updated_by',
        'change_reason',
        'audit_log',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'parent_pool_id' => 'integer',
        'is_active' => 'boolean',
        'service_types' => 'array',
        'included_services' => 'array',
        'excluded_services' => 'array',
        'total_capacity' => 'decimal:4',
        'allocated_capacity' => 'decimal:4',
        'used_capacity' => 'decimal:4',
        'warning_threshold' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
        'allocation_weights' => 'array',
        'priority_rules' => 'array',
        'allow_overallocation' => 'boolean',
        'overallocation_limit' => 'decimal:2',
        'pool_members' => 'array',
        'member_allocations' => 'array',
        'member_priorities' => 'array',
        'max_members' => 'integer',
        'current_period_usage' => 'decimal:4',
        'previous_period_usage' => 'decimal:4',
        'lifetime_usage' => 'decimal:4',
        'last_usage_update' => 'datetime',
        'usage_history' => 'array',
        'allows_rollover' => 'boolean',
        'rollover_percentage' => 'decimal:2',
        'rollover_months' => 'integer',
        'rollover_capacity' => 'decimal:4',
        'rollover_expires_at' => 'datetime',
        'pool_cost_per_unit' => 'decimal:6',
        'overage_rate' => 'decimal:6',
        'billing_preferences' => 'array',
        'has_time_restrictions' => 'boolean',
        'time_restrictions' => 'array',
        'peak_hour_rules' => 'array',
        'weekend_restrictions' => 'boolean',
        'has_geographic_restrictions' => 'boolean',
        'geographic_rules' => 'array',
        'allowed_locations' => 'array',
        'restricted_destinations' => 'array',
        'auto_refill_enabled' => 'boolean',
        'auto_refill_threshold' => 'decimal:2',
        'auto_refill_amount' => 'decimal:4',
        'auto_suspend_on_depletion' => 'boolean',
        'alert_settings' => 'array',
        'notification_recipients' => 'array',
        'email_alerts_enabled' => 'boolean',
        'sms_alerts_enabled' => 'boolean',
        'last_alert_sent' => 'datetime',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'expires_at' => 'datetime',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'next_reset_date' => 'date',
        'auto_reset_enabled' => 'boolean',
        'contract_terms' => 'array',
        'committed_spend' => 'decimal:2',
        'discount_rate' => 'decimal:3',
        'reporting_tags' => 'array',
        'detailed_reporting_enabled' => 'boolean',
        'kpi_metrics' => 'array',
        'benchmark_data' => 'array',
        'integration_settings' => 'array',
        'last_sync_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'audit_log' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Pool type constants
     */
    const POOL_TYPE_SHARED = 'shared';

    const POOL_TYPE_CLIENT_SPECIFIC = 'client_specific';

    const POOL_TYPE_LOCATION_BASED = 'location_based';

    const POOL_TYPE_SERVICE_BASED = 'service_based';

    /**
     * Pool status constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_DEPLETED = 'depleted';

    const STATUS_EXPIRED = 'expired';

    /**
     * Allocation method constants
     */
    const ALLOCATION_EQUAL_SHARE = 'equal_share';

    const ALLOCATION_WEIGHTED = 'weighted';

    const ALLOCATION_PRIORITY_BASED = 'priority_based';

    const ALLOCATION_FIRST_COME_FIRST_SERVED = 'first_come_first_served';

    /**
     * Billing model constants
     */
    const BILLING_SHARED_COST = 'shared_cost';

    const BILLING_INDIVIDUAL_BILLING = 'individual_billing';

    const BILLING_HYBRID = 'hybrid';

    /**
     * Get the client that owns this pool.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the parent pool (for hierarchical pools).
     */
    public function parentPool(): BelongsTo
    {
        return $this->belongsTo(UsagePool::class, 'parent_pool_id');
    }

    /**
     * Get child pools.
     */
    public function childPools(): HasMany
    {
        return $this->hasMany(UsagePool::class, 'parent_pool_id');
    }

    /**
     * Get usage records associated with this pool.
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Get usage buckets in this pool.
     */
    public function usageBuckets(): HasMany
    {
        return $this->hasMany(UsageBucket::class);
    }

    /**
     * Get the user who created this pool.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this pool.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the pool is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->pool_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the pool is depleted.
     */
    public function isDepleted(): bool
    {
        return $this->pool_status === self::STATUS_DEPLETED || $this->getRemainingCapacity() <= 0;
    }

    /**
     * Check if the pool is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): float
    {
        return $this->total_capacity - $this->used_capacity;
    }

    /**
     * Get capacity utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->total_capacity <= 0) {
            return 0;
        }

        return round(($this->used_capacity / $this->total_capacity) * 100, 2);
    }

    /**
     * Check if pool is at warning threshold.
     */
    public function isAtWarningThreshold(): bool
    {
        return $this->getUtilizationPercentage() >= $this->warning_threshold;
    }

    /**
     * Check if pool is at critical threshold.
     */
    public function isAtCriticalThreshold(): bool
    {
        return $this->getUtilizationPercentage() >= $this->critical_threshold;
    }

    /**
     * Allocate usage from the pool.
     */
    public function allocateUsage(float $amount, array $options = []): bool
    {
        if (! $this->canAllocate($amount)) {
            return false;
        }

        $this->increment('used_capacity', $amount);
        $this->update([
            'current_period_usage' => $this->current_period_usage + $amount,
            'lifetime_usage' => $this->lifetime_usage + $amount,
            'last_usage_update' => now(),
        ]);

        // Check for depletion
        if ($this->getRemainingCapacity() <= 0) {
            $this->update(['pool_status' => self::STATUS_DEPLETED]);
        }

        $this->recordUsageHistory($amount, $options);
        $this->checkThresholds();

        return true;
    }

    /**
     * Check if amount can be allocated from pool.
     */
    public function canAllocate(float $amount): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isDepleted()) {
            return false;
        }

        $remainingCapacity = $this->getRemainingCapacity();

        if ($amount <= $remainingCapacity) {
            return true;
        }

        // Check if overallocation is allowed
        if ($this->allow_overallocation) {
            $overallocationLimit = $this->total_capacity * ($this->overallocation_limit / 100);

            return ($this->used_capacity + $amount) <= ($this->total_capacity + $overallocationLimit);
        }

        return false;
    }

    /**
     * Deallocate usage from the pool (for adjustments or refunds).
     */
    public function deallocateUsage(float $amount): void
    {
        $this->decrement('used_capacity', $amount);
        $this->update([
            'current_period_usage' => max(0, $this->current_period_usage - $amount),
            'last_usage_update' => now(),
        ]);

        // Update status if pool was depleted
        if ($this->pool_status === self::STATUS_DEPLETED && $this->getRemainingCapacity() > 0) {
            $this->update(['pool_status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Reset pool for new billing period.
     */
    public function resetForNewPeriod(): void
    {
        $rolloverAmount = $this->calculateRollover();

        $this->update([
            'previous_period_usage' => $this->current_period_usage,
            'current_period_usage' => 0,
            'used_capacity' => 0,
            'rollover_capacity' => $rolloverAmount,
            'cycle_start_date' => now()->toDateString(),
            'cycle_end_date' => $this->calculateNextCycleEndDate(),
            'next_reset_date' => $this->calculateNextResetDate(),
            'pool_status' => self::STATUS_ACTIVE,
        ]);

        $this->recordUsageHistory(0, ['type' => 'period_reset', 'rollover' => $rolloverAmount]);
    }

    /**
     * Calculate rollover amount.
     */
    protected function calculateRollover(): float
    {
        if (! $this->allows_rollover) {
            return 0;
        }

        $unusedCapacity = $this->getRemainingCapacity();

        return $unusedCapacity * ($this->rollover_percentage / 100);
    }

    /**
     * Calculate next cycle end date.
     */
    protected function calculateNextCycleEndDate(): string
    {
        switch ($this->billing_cycle) {
            case 'monthly':
                return now()->endOfMonth()->toDateString();
            case 'quarterly':
                return now()->addMonths(3)->endOfQuarter()->toDateString();
            case 'yearly':
                return now()->endOfYear()->toDateString();
            default:
                return now()->endOfMonth()->toDateString();
        }
    }

    /**
     * Calculate next reset date.
     */
    protected function calculateNextResetDate(): string
    {
        switch ($this->billing_cycle) {
            case 'monthly':
                return now()->addMonth()->startOfMonth()->toDateString();
            case 'quarterly':
                return now()->addMonths(3)->startOfQuarter()->toDateString();
            case 'yearly':
                return now()->addYear()->startOfYear()->toDateString();
            default:
                return now()->addMonth()->startOfMonth()->toDateString();
        }
    }

    /**
     * Record usage history entry.
     */
    protected function recordUsageHistory(float $amount, array $options = []): void
    {
        $history = $this->usage_history ?? [];
        $history[] = [
            'timestamp' => now(),
            'amount' => $amount,
            'remaining_capacity' => $this->getRemainingCapacity(),
            'utilization_percentage' => $this->getUtilizationPercentage(),
            'options' => $options,
        ];

        // Keep only last 100 entries
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        $this->update(['usage_history' => $history]);
    }

    /**
     * Check thresholds and send alerts if necessary.
     */
    protected function checkThresholds(): void
    {
        if (! $this->email_alerts_enabled && ! $this->sms_alerts_enabled) {
            return;
        }

        $utilization = $this->getUtilizationPercentage();
        $shouldAlert = false;
        $alertLevel = 'info';

        if ($utilization >= $this->critical_threshold) {
            $shouldAlert = true;
            $alertLevel = 'critical';
        } elseif ($utilization >= $this->warning_threshold) {
            $shouldAlert = true;
            $alertLevel = 'warning';
        }

        if ($shouldAlert && $this->shouldSendAlert()) {
            $this->sendThresholdAlert($alertLevel, $utilization);
        }
    }

    /**
     * Check if alert should be sent (rate limiting).
     */
    protected function shouldSendAlert(): bool
    {
        if (! $this->last_alert_sent) {
            return true;
        }

        // Don't send alerts more than once per hour
        return $this->last_alert_sent->diffInHours(now()) >= 1;
    }

    /**
     * Send threshold alert.
     */
    protected function sendThresholdAlert(string $level, float $utilization): void
    {
        // Implementation would integrate with notification system
        \Log::info('Usage pool threshold alert', [
            'pool_id' => $this->id,
            'pool_name' => $this->pool_name,
            'level' => $level,
            'utilization' => $utilization,
        ]);

        $this->update(['last_alert_sent' => now()]);
    }

    /**
     * Scope to get active pools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('pool_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get pools by type.
     */
    public function scopeByType($query, string $poolType)
    {
        return $query->where('pool_type', $poolType);
    }

    /**
     * Scope to get pools by usage type.
     */
    public function scopeByUsageType($query, string $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    /**
     * Scope to get depleted pools.
     */
    public function scopeDepleted($query)
    {
        return $query->where('pool_status', self::STATUS_DEPLETED);
    }

    /**
     * Scope to get pools at warning threshold.
     */
    public function scopeAtWarningThreshold($query)
    {
        return $query->whereRaw('(used_capacity / total_capacity * 100) >= warning_threshold');
    }

    /**
     * Scope to get pools at critical threshold.
     */
    public function scopeAtCriticalThreshold($query)
    {
        return $query->whereRaw('(used_capacity / total_capacity * 100) >= critical_threshold');
    }

    /**
     * Get available pool types.
     */
    public static function getPoolTypes(): array
    {
        return [
            self::POOL_TYPE_SHARED => 'Shared Pool',
            self::POOL_TYPE_CLIENT_SPECIFIC => 'Client Specific',
            self::POOL_TYPE_LOCATION_BASED => 'Location Based',
            self::POOL_TYPE_SERVICE_BASED => 'Service Based',
        ];
    }

    /**
     * Get pool statuses.
     */
    public static function getPoolStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_DEPLETED => 'Depleted',
            self::STATUS_EXPIRED => 'Expired',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pool) {
            if (! $pool->pool_code) {
                $pool->pool_code = 'POOL-'.strtoupper(uniqid());
            }

            if (! $pool->cycle_start_date) {
                $pool->cycle_start_date = now()->startOfMonth()->toDateString();
            }

            if (! $pool->cycle_end_date) {
                $pool->cycle_end_date = now()->endOfMonth()->toDateString();
            }

            if (! $pool->next_reset_date) {
                $pool->next_reset_date = now()->addMonth()->startOfMonth()->toDateString();
            }
        });

        static::updating(function ($pool) {
            $pool->updated_by = auth()->id() ?? 1;

            // Update pool status based on capacity
            if ($pool->isDirty(['used_capacity', 'total_capacity'])) {
                if ($pool->getRemainingCapacity() <= 0 && $pool->pool_status === self::STATUS_ACTIVE) {
                    $pool->pool_status = self::STATUS_DEPLETED;
                } elseif ($pool->getRemainingCapacity() > 0 && $pool->pool_status === self::STATUS_DEPLETED) {
                    $pool->pool_status = self::STATUS_ACTIVE;
                }
            }
        });
    }
}
