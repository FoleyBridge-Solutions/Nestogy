<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * UsageBucket Model
 *
 * Manages usage categorization and allocation for complex billing scenarios.
 * Supports bucket-based usage allocation with overflows and priority handling.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int|null $usage_pool_id
 * @property int|null $parent_bucket_id
 * @property string $bucket_name
 * @property string $bucket_code
 * @property string $bucket_type
 * @property string $usage_type
 * @property float $bucket_capacity
 * @property float $allocated_amount
 * @property float $used_amount
 * @property string $capacity_unit
 * @property bool $is_active
 * @property string $bucket_status
 */
class UsageBucket extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'usage_buckets';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'usage_pool_id',
        'parent_bucket_id',
        'bucket_name',
        'bucket_code',
        'bucket_type',
        'description',
        'is_active',
        'usage_type',
        'service_types',
        'included_categories',
        'excluded_categories',
        'bucket_capacity',
        'allocated_amount',
        'used_amount',
        'reserved_amount',
        'capacity_unit',
        'usage_priority',
        'billing_priority',
        'is_primary_bucket',
        'allocation_order',
        'current_period_usage',
        'daily_usage',
        'weekly_usage',
        'monthly_usage',
        'lifetime_usage',
        'last_usage_at',
        'first_usage_at',
        'daily_limit',
        'weekly_limit',
        'monthly_limit',
        'warning_threshold',
        'critical_threshold',
        'allows_overflow',
        'overflow_bucket_id',
        'overflow_behavior',
        'overflow_rate',
        'overflow_rules',
        'has_time_restrictions',
        'allowed_time_periods',
        'blackout_periods',
        'peak_hour_only',
        'off_peak_only',
        'has_location_restrictions',
        'allowed_locations',
        'restricted_destinations',
        'roaming_behavior',
        'allows_rollover',
        'rollover_percentage',
        'rollover_months',
        'rollover_balance',
        'rollover_expires_at',
        'bucket_expires_at',
        'included_rate',
        'overage_rate',
        'is_billable',
        'is_taxable',
        'billing_frequency',
        'pricing_rules',
        'bucket_status',
        'status_reason',
        'activated_at',
        'suspended_at',
        'depleted_at',
        'reset_frequency',
        'last_reset_date',
        'next_reset_date',
        'auto_reset_enabled',
        'reset_rules',
        'is_shared_bucket',
        'sharing_rules',
        'distribution_weights',
        'sharing_percentage',
        'is_promotional',
        'promotion_code',
        'promotion_expires_at',
        'promotion_rules',
        'is_bonus_bucket',
        'bonus_type',
        'quality_restrictions',
        'service_restrictions',
        'emergency_services_only',
        'feature_restrictions',
        'usage_analytics',
        'average_daily_usage',
        'peak_usage_rate',
        'usage_trends',
        'efficiency_metrics',
        'external_bucket_id',
        'integration_metadata',
        'last_sync_at',
        'sync_status',
        'alert_preferences',
        'email_alerts_enabled',
        'sms_alerts_enabled',
        'notification_recipients',
        'last_alert_sent',
        'compliance_rules',
        'requires_audit_trail',
        'audit_settings',
        'compliance_notes',
        'created_by',
        'updated_by',
        'change_reason',
        'configuration_history',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'usage_pool_id' => 'integer',
        'parent_bucket_id' => 'integer',
        'is_active' => 'boolean',
        'service_types' => 'array',
        'included_categories' => 'array',
        'excluded_categories' => 'array',
        'bucket_capacity' => 'decimal:4',
        'allocated_amount' => 'decimal:4',
        'used_amount' => 'decimal:4',
        'reserved_amount' => 'decimal:4',
        'usage_priority' => 'integer',
        'billing_priority' => 'integer',
        'is_primary_bucket' => 'boolean',
        'current_period_usage' => 'decimal:4',
        'daily_usage' => 'decimal:4',
        'weekly_usage' => 'decimal:4',
        'monthly_usage' => 'decimal:4',
        'lifetime_usage' => 'decimal:4',
        'last_usage_at' => 'datetime',
        'first_usage_at' => 'datetime',
        'daily_limit' => 'decimal:4',
        'weekly_limit' => 'decimal:4',
        'monthly_limit' => 'decimal:4',
        'warning_threshold' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
        'allows_overflow' => 'boolean',
        'overflow_bucket_id' => 'integer',
        'overflow_rate' => 'decimal:6',
        'overflow_rules' => 'array',
        'has_time_restrictions' => 'boolean',
        'allowed_time_periods' => 'array',
        'blackout_periods' => 'array',
        'peak_hour_only' => 'boolean',
        'off_peak_only' => 'boolean',
        'has_location_restrictions' => 'boolean',
        'allowed_locations' => 'array',
        'restricted_destinations' => 'array',
        'allows_rollover' => 'boolean',
        'rollover_percentage' => 'decimal:2',
        'rollover_months' => 'integer',
        'rollover_balance' => 'decimal:4',
        'rollover_expires_at' => 'datetime',
        'bucket_expires_at' => 'datetime',
        'included_rate' => 'decimal:6',
        'overage_rate' => 'decimal:6',
        'is_billable' => 'boolean',
        'is_taxable' => 'boolean',
        'pricing_rules' => 'array',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'depleted_at' => 'datetime',
        'last_reset_date' => 'date',
        'next_reset_date' => 'date',
        'auto_reset_enabled' => 'boolean',
        'reset_rules' => 'array',
        'is_shared_bucket' => 'boolean',
        'sharing_rules' => 'array',
        'distribution_weights' => 'array',
        'sharing_percentage' => 'decimal:2',
        'is_promotional' => 'boolean',
        'promotion_expires_at' => 'datetime',
        'promotion_rules' => 'array',
        'is_bonus_bucket' => 'boolean',
        'quality_restrictions' => 'array',
        'service_restrictions' => 'array',
        'emergency_services_only' => 'boolean',
        'feature_restrictions' => 'array',
        'usage_analytics' => 'array',
        'average_daily_usage' => 'decimal:4',
        'peak_usage_rate' => 'decimal:4',
        'usage_trends' => 'array',
        'efficiency_metrics' => 'array',
        'integration_metadata' => 'array',
        'last_sync_at' => 'datetime',
        'alert_preferences' => 'array',
        'email_alerts_enabled' => 'boolean',
        'sms_alerts_enabled' => 'boolean',
        'notification_recipients' => 'array',
        'last_alert_sent' => 'datetime',
        'compliance_rules' => 'array',
        'requires_audit_trail' => 'boolean',
        'audit_settings' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'configuration_history' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Bucket type constants
     */
    const BUCKET_TYPE_INCLUDED = 'included';

    const BUCKET_TYPE_BONUS = 'bonus';

    const BUCKET_TYPE_PROMOTIONAL = 'promotional';

    const BUCKET_TYPE_OVERAGE = 'overage';

    const BUCKET_TYPE_ROLLOVER = 'rollover';

    /**
     * Bucket status constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_DEPLETED = 'depleted';

    const STATUS_EXPIRED = 'expired';

    /**
     * Allocation order constants
     */
    const ALLOCATION_FIFO = 'fifo';

    const ALLOCATION_LIFO = 'lifo';

    const ALLOCATION_PRIORITY = 'priority';

    const ALLOCATION_WEIGHTED = 'weighted';

    /**
     * Overflow behavior constants
     */
    const OVERFLOW_SPILLOVER = 'spillover';

    const OVERFLOW_BLOCK = 'block';

    const OVERFLOW_CHARGE_OVERAGE = 'charge_overage';

    /**
     * Reset frequency constants
     */
    const RESET_DAILY = 'daily';

    const RESET_WEEKLY = 'weekly';

    const RESET_MONTHLY = 'monthly';

    const RESET_BILLING_CYCLE = 'billing_cycle';

    /**
     * Roaming behavior constants
     */
    const ROAMING_ALLOWED = 'allowed';

    const ROAMING_BLOCKED = 'blocked';

    const ROAMING_CHARGED = 'charged';

    /**
     * Get the client that owns this bucket.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the usage pool this bucket belongs to.
     */
    public function usagePool(): BelongsTo
    {
        return $this->belongsTo(UsagePool::class);
    }

    /**
     * Get the parent bucket (for nested hierarchies).
     */
    public function parentBucket(): BelongsTo
    {
        return $this->belongsTo(UsageBucket::class, 'parent_bucket_id');
    }

    /**
     * Get child buckets.
     */
    public function childBuckets(): HasMany
    {
        return $this->hasMany(UsageBucket::class, 'parent_bucket_id');
    }

    /**
     * Get the overflow bucket.
     */
    public function overflowBucket(): BelongsTo
    {
        return $this->belongsTo(UsageBucket::class, 'overflow_bucket_id');
    }

    /**
     * Get usage records allocated to this bucket.
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Get the user who created this bucket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this bucket.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the bucket is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->bucket_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the bucket is depleted.
     */
    public function isDepleted(): bool
    {
        return $this->bucket_status === self::STATUS_DEPLETED || $this->getRemainingCapacity() <= 0;
    }

    /**
     * Check if the bucket is expired.
     */
    public function isExpired(): bool
    {
        return $this->bucket_expires_at && now()->gt($this->bucket_expires_at);
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): float
    {
        return max(0, $this->bucket_capacity - $this->used_amount);
    }

    /**
     * Get utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->bucket_capacity <= 0) {
            return 0;
        }

        return round(($this->used_amount / $this->bucket_capacity) * 100, 2);
    }

    /**
     * Check if bucket is at warning threshold.
     */
    public function isAtWarningThreshold(): bool
    {
        return $this->getUtilizationPercentage() >= $this->warning_threshold;
    }

    /**
     * Check if bucket is at critical threshold.
     */
    public function isAtCriticalThreshold(): bool
    {
        return $this->getUtilizationPercentage() >= $this->critical_threshold;
    }

    /**
     * Check if usage is allowed at current time.
     */
    public function isUsageAllowedNow(): bool
    {
        if (! $this->has_time_restrictions) {
            return true;
        }

        $now = now();

        // Check blackout periods
        if ($this->blackout_periods) {
            foreach ($this->blackout_periods as $period) {
                $start = Carbon::parse($period['start']);
                $end = Carbon::parse($period['end']);
                if ($now->between($start, $end)) {
                    return false;
                }
            }
        }

        // Check allowed time periods
        if ($this->allowed_time_periods) {
            $allowed = false;
            foreach ($this->allowed_time_periods as $period) {
                $start = $period['start_hour'] ?? 0;
                $end = $period['end_hour'] ?? 23;
                if ($now->hour >= $start && $now->hour <= $end) {
                    $allowed = true;
                    break;
                }
            }
            if (! $allowed) {
                return false;
            }
        }

        // Check peak/off-peak restrictions
        if ($this->peak_hour_only && ! $this->isPeakTime($now)) {
            return false;
        }

        if ($this->off_peak_only && $this->isPeakTime($now)) {
            return false;
        }

        return true;
    }

    /**
     * Check if current time is peak time.
     */
    protected function isPeakTime(Carbon $timestamp): bool
    {
        $hour = $timestamp->hour;

        return $hour >= 8 && $hour < 18; // Default peak hours 8 AM to 6 PM
    }

    /**
     * Allocate usage from the bucket.
     */
    public function allocateUsage(float $amount, array $options = []): float
    {
        if (! $this->canAllocate($amount)) {
            return 0;
        }

        if (! $this->isUsageAllowedNow()) {
            return 0;
        }

        $remainingCapacity = $this->getRemainingCapacity();
        $allocatedAmount = min($amount, $remainingCapacity);
        $overflowAmount = $amount - $allocatedAmount;

        // Update bucket usage
        $this->increment('used_amount', $allocatedAmount);
        $this->updateUsageCounters($allocatedAmount);

        // Handle overflow if configured
        if ($overflowAmount > 0 && $this->allows_overflow && $this->overflowBucket) {
            $overflowAllocated = $this->overflowBucket->allocateUsage($overflowAmount, $options);
            $allocatedAmount += $overflowAllocated;
        }

        // Check for depletion
        if ($this->getRemainingCapacity() <= 0) {
            $this->update([
                'bucket_status' => self::STATUS_DEPLETED,
                'depleted_at' => now(),
            ]);
        }

        $this->checkThresholds();

        return $allocatedAmount;
    }

    /**
     * Check if amount can be allocated from bucket.
     */
    public function canAllocate(float $amount): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isDepleted()) {
            return false;
        }

        if (! $this->isUsageAllowedNow()) {
            return false;
        }

        // Check daily/weekly/monthly limits
        if ($this->daily_limit && $this->daily_usage + $amount > $this->daily_limit) {
            return false;
        }

        if ($this->weekly_limit && $this->weekly_usage + $amount > $this->weekly_limit) {
            return false;
        }

        if ($this->monthly_limit && $this->monthly_usage + $amount > $this->monthly_limit) {
            return false;
        }

        return true;
    }

    /**
     * Update usage counters.
     */
    protected function updateUsageCounters(float $amount): void
    {
        $now = now();
        $updates = [
            'current_period_usage' => $this->current_period_usage + $amount,
            'lifetime_usage' => $this->lifetime_usage + $amount,
            'last_usage_at' => $now,
        ];

        // Update daily usage if it's the same day
        if (! $this->last_usage_at || $this->last_usage_at->isSameDay($now)) {
            $updates['daily_usage'] = $this->daily_usage + $amount;
        } else {
            $updates['daily_usage'] = $amount;
        }

        // Update weekly usage if it's the same week
        if (! $this->last_usage_at || $this->last_usage_at->isSameWeek($now)) {
            $updates['weekly_usage'] = $this->weekly_usage + $amount;
        } else {
            $updates['weekly_usage'] = $amount;
        }

        // Update monthly usage if it's the same month
        if (! $this->last_usage_at || $this->last_usage_at->isSameMonth($now)) {
            $updates['monthly_usage'] = $this->monthly_usage + $amount;
        } else {
            $updates['monthly_usage'] = $amount;
        }

        // Set first usage timestamp if not set
        if (! $this->first_usage_at) {
            $updates['first_usage_at'] = $now;
        }

        $this->update($updates);
    }

    /**
     * Reset bucket for new period.
     */
    public function resetForNewPeriod(?string $resetType = null): void
    {
        $resetType = $resetType ?? $this->reset_frequency;
        $rolloverAmount = $this->calculateRollover();

        $updates = [
            'last_reset_date' => now()->toDateString(),
            'next_reset_date' => $this->calculateNextResetDate($resetType),
            'bucket_status' => self::STATUS_ACTIVE,
            'depleted_at' => null,
        ];

        switch ($resetType) {
            case self::RESET_DAILY:
                $updates['daily_usage'] = 0;
                break;
            case self::RESET_WEEKLY:
                $updates['weekly_usage'] = 0;
                $updates['daily_usage'] = 0;
                break;
            case self::RESET_MONTHLY:
            case self::RESET_BILLING_CYCLE:
                $updates['monthly_usage'] = 0;
                $updates['weekly_usage'] = 0;
                $updates['daily_usage'] = 0;
                $updates['current_period_usage'] = 0;
                $updates['used_amount'] = 0;
                $updates['rollover_balance'] = $rolloverAmount;
                break;
        }

        $this->update($updates);
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
     * Calculate next reset date.
     */
    protected function calculateNextResetDate(string $resetType): string
    {
        switch ($resetType) {
            case self::RESET_DAILY:
                return now()->addDay()->toDateString();
            case self::RESET_WEEKLY:
                return now()->addWeek()->toDateString();
            case self::RESET_MONTHLY:
                return now()->addMonth()->toDateString();
            case self::RESET_BILLING_CYCLE:
                return now()->addMonth()->startOfMonth()->toDateString();
            default:
                return now()->addMonth()->toDateString();
        }
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
        Log::info('Usage bucket threshold alert', [
            'bucket_id' => $this->id,
            'bucket_name' => $this->bucket_name,
            'level' => $level,
            'utilization' => $utilization,
        ]);

        $this->update(['last_alert_sent' => now()]);
    }

    /**
     * Scope to get active buckets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('bucket_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get buckets by type.
     */
    public function scopeByType($query, string $bucketType)
    {
        return $query->where('bucket_type', $bucketType);
    }

    /**
     * Scope to get buckets by usage type.
     */
    public function scopeByUsageType($query, string $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    /**
     * Scope to get primary buckets.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary_bucket', true);
    }

    /**
     * Scope to get promotional buckets.
     */
    public function scopePromotional($query)
    {
        return $query->where('is_promotional', true);
    }

    /**
     * Scope to order by usage priority.
     */
    public function scopeOrderByUsagePriority($query)
    {
        return $query->orderBy('usage_priority');
    }

    /**
     * Scope to order by billing priority.
     */
    public function scopeOrderByBillingPriority($query)
    {
        return $query->orderBy('billing_priority');
    }

    /**
     * Get available bucket types.
     */
    public static function getBucketTypes(): array
    {
        return [
            self::BUCKET_TYPE_INCLUDED => 'Included Allowance',
            self::BUCKET_TYPE_BONUS => 'Bonus',
            self::BUCKET_TYPE_PROMOTIONAL => 'Promotional',
            self::BUCKET_TYPE_OVERAGE => 'Overage',
            self::BUCKET_TYPE_ROLLOVER => 'Rollover',
        ];
    }

    /**
     * Get bucket statuses.
     */
    public static function getBucketStatuses(): array
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

        static::creating(function ($bucket) {
            if (! $bucket->bucket_code) {
                $bucket->bucket_code = 'BUCKET-'.strtoupper(uniqid());
            }

            if (! $bucket->next_reset_date) {
                $bucket->next_reset_date = $bucket->calculateNextResetDate($bucket->reset_frequency ?? self::RESET_MONTHLY);
            }
        });

        static::updating(function ($bucket) {
            $bucket->updated_by = auth()->id() ?? 1;

            // Update bucket status based on capacity
            if ($bucket->isDirty(['used_amount', 'bucket_capacity'])) {
                if ($bucket->getRemainingCapacity() <= 0 && $bucket->bucket_status === self::STATUS_ACTIVE) {
                    $bucket->bucket_status = self::STATUS_DEPLETED;
                    $bucket->depleted_at = now();
                } elseif ($bucket->getRemainingCapacity() > 0 && $bucket->bucket_status === self::STATUS_DEPLETED) {
                    $bucket->bucket_status = self::STATUS_ACTIVE;
                    $bucket->depleted_at = null;
                }
            }
        });
    }
}
