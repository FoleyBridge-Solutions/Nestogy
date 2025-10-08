<?php

namespace App\Models;

use App\Models\Concerns\HasSubscriptionActions;
use App\Models\Concerns\HasSubscriptionDisplay;
use App\Models\Concerns\HasSubscriptionStatus;
use App\Models\Concerns\ManagesUserLimits;
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
    use HasSubscriptionActions;
    use HasSubscriptionDisplay;
    use HasSubscriptionStatus;
    use ManagesUserLimits;

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
