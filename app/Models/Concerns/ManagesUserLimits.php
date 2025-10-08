<?php

namespace App\Models\Concerns;

use App\Models\User;

trait ManagesUserLimits
{
    public function canAddUser(): bool
    {
        if (! $this->subscriptionPlan || $this->max_users === null) {
            return true;
        }

        return $this->current_user_count < $this->max_users;
    }

    public function availableUserSlots(): ?int
    {
        if ($this->max_users === null) {
            return null;
        }

        return max(0, $this->max_users - $this->current_user_count);
    }

    public function updateUserCount(): void
    {
        $count = User::where('company_id', $this->company_id)
            ->whereNull('archived_at')
            ->where('status', true)
            ->count();

        $this->updateQuietly(['current_user_count' => $count]);
    }

    public function approachingUserLimit(): bool
    {
        if ($this->max_users === null) {
            return false;
        }

        $percentage = ($this->current_user_count / $this->max_users) * 100;

        return $percentage >= 80;
    }

    public function getUserLimitDisplay(): string
    {
        if ($this->max_users === null) {
            return 'Unlimited users';
        }

        return $this->current_user_count.' of '.$this->max_users.' users';
    }
}
