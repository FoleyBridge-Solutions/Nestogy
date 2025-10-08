<?php

namespace App\Models\Concerns;

trait HasSubscriptionStatus
{
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function onGracePeriod(): bool
    {
        return $this->grace_period_ends_at &&
               $this->grace_period_ends_at->isFuture();
    }

    public function hasFeature(string $feature): bool
    {
        if (! $this->features) {
            return false;
        }

        return in_array($feature, $this->features) ||
               (isset($this->features[$feature]) && $this->features[$feature] === true);
    }

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
}
