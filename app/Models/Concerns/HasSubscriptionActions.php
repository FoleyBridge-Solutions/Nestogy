<?php

namespace App\Models\Concerns;

use App\Models\SubscriptionPlan;

trait HasSubscriptionActions
{
    public function cancel(bool $immediately = false): void
    {
        if ($immediately) {
            $this->update([
                'status' => self::STATUS_CANCELED,
                'canceled_at' => now(),
            ]);
        } else {
            $this->update([
                'canceled_at' => now(),
                'grace_period_ends_at' => $this->current_period_end,
            ]);
        }
    }

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

    public function changePlan(SubscriptionPlan $newPlan): void
    {
        $this->update([
            'subscription_plan_id' => $newPlan->id,
            'max_users' => $newPlan->max_users,
            'monthly_amount' => $newPlan->price_monthly,
            'features' => $newPlan->features,
        ]);

        if ($newPlan->max_users && $this->current_user_count > $newPlan->max_users) {
            $metadata = $this->metadata ?? [];
            $metadata['over_user_limit'] = true;
            $metadata['previous_user_count'] = $this->current_user_count;
            $this->update(['metadata' => $metadata]);
        }
    }
}
