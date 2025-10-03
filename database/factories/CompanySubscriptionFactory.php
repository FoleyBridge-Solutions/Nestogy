<?php

namespace Database\Factories;

use App\Models\CompanySubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanySubscriptionFactory extends Factory
{
    protected $model = CompanySubscription::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'status' => 'active',
            'max_users' => null,
            'current_user_count' => null,
            'monthly_amount' => null,
            'trial_ends_at' => null,
            'current_period_start' => null,
            'current_period_end' => null,
            'canceled_at' => null,
            'suspended_at' => null,
            'grace_period_ends_at' => null,
            'features' => null,
            'metadata' => null
        ];
    }
}
