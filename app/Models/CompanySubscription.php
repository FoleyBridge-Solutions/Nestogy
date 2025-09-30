<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanySubscription Model
 *
 * Tracks subscription details for each company including plan, user limits,
 * and billing status. User counts exclude client portal users.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $subscription_plan_id
 * @property string $status
 * @property int $max_users
 * @property int $current_user_count
 * @property float $monthly_amount
 * @property string|null $stripe_subscription_id
 * @property string|null $stripe_customer_id
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $current_period_start
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $suspended_at
 * @property \Illuminate\Support\Carbon|null $grace_period_ends_at
 * @property array|null $features
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompanySubscription extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_subscriptions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'max_users',
        'current_user_count',
        'monthly_amount',
        'stripe_subscription_id',
        'stripe_customer_id',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'suspended_at',
        'grace_period_ends_at',
        'features',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'max_users' => 'integer',
        'current_user_count' => 'integer',
        'monthly_amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'features' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_TRIALING = 'trialing';

    const STATUS_PAST_DUE = 'past_due';

    const STATUS_CANCELED = 'canceled';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_EXPIRED = 'expired';

    /**
     * Get the company that owns this subscription.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if the subscription is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if the subscription is within grace period.
     */
    public function onGracePeriod(): bool
    {
        return $this->grace_period_ends_at &&
               $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if the company can add more users.
     * This excludes client portal users from the count.
     */
    public function canAddUser(): bool
    {
        // If no plan or unlimited users
        if (! $this->subscriptionPlan || $this->max_users === null) {
            return true;
        }

        // Check if under limit
        return $this->current_user_count < $this->max_users;
    }

    /**
     * Get the number of available user slots.
     */
    public function availableUserSlots(): ?int
    {
        if ($this->max_users === null) {
            return null; // Unlimited
        }

        return max(0, $this->max_users - $this->current_user_count);
    }

    /**
     * Update the current user count.
     * This should only count non-portal users.
     */
    public function updateUserCount(): void
    {
        $count = User::where('company_id', $this->company_id)
            ->whereNull('archived_at')
            ->where('status', true)
            ->count();

        // Use updateQuietly to prevent triggering model events and avoid infinite loop
        $this->updateQuietly(['current_user_count' => $count]);
    }

    /**
     * Check if a feature is available in the subscription.
     */
    public function hasFeature(string $feature): bool
    {
        if (! $this->features) {
            return false;
        }

        return in_array($feature, $this->features) ||
               (isset($this->features[$feature]) && $this->features[$feature] === true);
    }

    /**
     * Get the subscription display name.
     */
    public function getDisplayName(): string
    {
        if ($this->subscriptionPlan) {
            return $this->subscriptionPlan->name;
        }

        return 'No Plan';
    }

    /**
     * Get the subscription price display.
     */
    public function getPriceDisplay(): string
    {
        if ($this->monthly_amount == 0) {
            return 'Free';
        }

        return '$'.number_format($this->monthly_amount, 2).'/month';
    }

    /**
     * Get user limit display text.
     */
    public function getUserLimitDisplay(): string
    {
        if ($this->max_users === null) {
            return 'Unlimited users';
        }

        return $this->current_user_count.' of '.$this->max_users.' users';
    }

    /**
     * Check if approaching user limit (80% or more).
     */
    public function approachingUserLimit(): bool
    {
        if ($this->max_users === null) {
            return false;
        }

        $percentage = ($this->current_user_count / $this->max_users) * 100;

        return $percentage >= 80;
    }

    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Scope to get subscriptions needing renewal.
     */
    public function scopeNeedsRenewal($query)
    {
        return $query->where('current_period_end', '<=', now()->addDays(7))
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get subscriptions ending trial soon.
     */
    public function scopeTrialEndingSoon($query, $days = 3)
    {
        return $query->where('status', self::STATUS_TRIALING)
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(bool $immediately = false): void
    {
        if ($immediately) {
            $this->update([
                'status' => self::STATUS_CANCELED,
                'canceled_at' => now(),
            ]);
        } else {
            // Cancel at end of period
            $this->update([
                'canceled_at' => now(),
                'grace_period_ends_at' => $this->current_period_end,
            ]);
        }
    }

    /**
     * Suspend the subscription.
     */
    public function suspend(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['suspension_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Resume a suspended subscription.
     */
    public function resume(): void
    {
        $metadata = $this->metadata ?? [];
        unset($metadata['suspension_reason']);

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(SubscriptionPlan $newPlan): void
    {
        $this->update([
            'subscription_plan_id' => $newPlan->id,
            'max_users' => $newPlan->max_users,
            'monthly_amount' => $newPlan->price_monthly,
            'features' => $newPlan->features,
        ]);

        // If downgrading and over new limit, mark for attention
        if ($newPlan->max_users && $this->current_user_count > $newPlan->max_users) {
            $metadata = $this->metadata ?? [];
            $metadata['over_user_limit'] = true;
            $metadata['previous_user_count'] = $this->current_user_count;
            $this->update(['metadata' => $metadata]);
        }
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_TRIALING => 'blue',
            self::STATUS_PAST_DUE => 'orange',
            self::STATUS_CANCELED => 'gray',
            self::STATUS_SUSPENDED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($subscription) {
            if (! $subscription->status) {
                $subscription->status = self::STATUS_TRIALING;
            }
            if (! $subscription->trial_ends_at) {
                $subscription->trial_ends_at = now()->addDays(14);
            }
            if (! $subscription->current_period_start) {
                $subscription->current_period_start = now();
            }
            if (! $subscription->current_period_end) {
                $subscription->current_period_end = now()->addMonth();
            }
        });

        // Update user count after creating (but not on every update to prevent loops)
        static::created(function ($subscription) {
            $subscription->updateUserCount();
        });
    }
}
