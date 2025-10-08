<?php

namespace App\Models\Concerns;

trait HasSubscriptionDisplay
{
    public function getDisplayName(): string
    {
        if ($this->subscriptionPlan) {
            return $this->subscriptionPlan->name;
        }

        return 'No Plan';
    }

    public function getPriceDisplay(): string
    {
        if ($this->monthly_amount == 0) {
            return 'Free';
        }

        return '$'.number_format($this->monthly_amount, 2).'/month';
    }
}
