<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Exceptions\UserLimitExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionService
 *
 * Manages subscription-related operations including user limits,
 * plan changes, and subscription status. User counts exclude client portal users.
 */
class SubscriptionService
{
    /**
     * Check if a company can add more users based on their subscription.
     * This excludes client portal users from the count.
     */
    public function canAddUser(Company $company): bool
    {
        $subscription = $this->getOrCreateSubscription($company);

        // Check if subscription is active
        if (!$subscription->isActive()) {
            Log::warning('Subscription not active for company', [
                'company_id' => $company->id,
                'status' => $subscription->status
            ]);
            return false;
        }

        return $subscription->canAddUser();
    }

    /**
     * Get the current user count for a company.
     * Only counts non-portal users (regular system users).
     */
    public function getCurrentUserCount(Company $company): int
    {
        return User::where('company_id', $company->id)
            ->whereNull('archived_at')
            ->where('status', true)
            ->count();
    }

    /**
     * Enforce user limits when creating a new user.
     *
     * @throws UserLimitExceededException
     */
    public function enforceUserLimits(Company $company): void
    {
        if (!$this->canAddUser($company)) {
            $subscription = $company->subscription;
            $message = "User limit reached. Your {$subscription->getDisplayName()} plan allows {$subscription->max_users} users.";

            Log::warning('User limit exceeded', [
                'company_id' => $company->id,
                'plan' => $subscription->getDisplayName(),
                'limit' => $subscription->max_users,
                'current' => $subscription->current_user_count
            ]);

            throw new UserLimitExceededException($message);
        }
    }

    /**
     * Get or create a subscription for a company.
     * If no subscription exists, creates a free plan subscription.
     */
    public function getOrCreateSubscription(Company $company): CompanySubscription
    {
        if ($company->subscription) {
            return $company->subscription;
        }

        // Create default free subscription
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();

        if (!$freePlan) {
            // If no free plan exists, create a basic one
            $freePlan = SubscriptionPlan::create([
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Basic free plan',
                'price_monthly' => 0,
                'user_limit' => 2,
                'max_clients' => 25,
                'features' => ['basic_features'],
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        return $this->createSubscription($company, $freePlan);
    }

    /**
     * Create a new subscription for a company.
     */
    public function createSubscription(Company $company, SubscriptionPlan $plan, array $data = []): CompanySubscription
    {
        $subscriptionData = array_merge([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => $plan->price_monthly > 0 ? CompanySubscription::STATUS_TRIALING : CompanySubscription::STATUS_ACTIVE,
            'max_users' => $plan->user_limit,
            'monthly_amount' => $plan->price_monthly,
            'features' => $plan->features,
            'trial_ends_at' => $plan->price_monthly > 0 ? now()->addDays(14) : null,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ], $data);

        $subscription = CompanySubscription::create($subscriptionData);

        // Update user count
        $subscription->updateUserCount();

        Log::info('Subscription created', [
            'company_id' => $company->id,
            'plan' => $plan->name,
            'status' => $subscription->status
        ]);

        return $subscription;
    }

    /**
     * Change a company's subscription plan.
     */
    public function changePlan(Company $company, SubscriptionPlan $newPlan): CompanySubscription
    {
        $subscription = $this->getOrCreateSubscription($company);
        $oldPlan = $subscription->subscriptionPlan;

        DB::transaction(function () use ($subscription, $newPlan, $oldPlan, $company) {
            $subscription->changePlan($newPlan);

            // Check if downgrading and over limit
            if ($newPlan->user_limit && $subscription->current_user_count > $newPlan->user_limit) {
                Log::warning('Company over user limit after plan change', [
                    'company_id' => $company->id,
                    'old_plan' => $oldPlan?->name,
                    'new_plan' => $newPlan->name,
                    'user_count' => $subscription->current_user_count,
                    'new_limit' => $newPlan->user_limit
                ]);
            }
        });

        Log::info('Subscription plan changed', [
            'company_id' => $company->id,
            'old_plan' => $oldPlan?->name,
            'new_plan' => $newPlan->name
        ]);

        return $subscription;
    }

    /**
     * Update user count for a company's subscription.
     */
    public function updateUserCount(Company $company): void
    {
        $subscription = $this->getOrCreateSubscription($company);
        $subscription->updateUserCount();
    }

    /**
     * Get available user slots for a company.
     */
    public function getAvailableUserSlots(Company $company): ?int
    {
        $subscription = $this->getOrCreateSubscription($company);
        return $subscription->availableUserSlots();
    }

    /**
     * Check if a company is approaching their user limit.
     */
    public function isApproachingUserLimit(Company $company): bool
    {
        $subscription = $this->getOrCreateSubscription($company);
        return $subscription->approachingUserLimit();
    }

    /**
     * Get subscription usage statistics.
     */
    public function getUsageStats(Company $company): array
    {
        $subscription = $this->getOrCreateSubscription($company);

        return [
            'plan_name' => $subscription->getDisplayName(),
            'status' => $subscription->status,
            'user_count' => $subscription->current_user_count,
            'user_limit' => $subscription->max_users,
            'available_slots' => $subscription->availableUserSlots(),
            'percentage_used' => $subscription->max_users ?
                round(($subscription->current_user_count / $subscription->max_users) * 100, 1) : 0,
            'approaching_limit' => $subscription->approachingUserLimit(),
            'can_add_users' => $subscription->canAddUser(),
            'price' => $subscription->getPriceDisplay(),
            'trial_ends_at' => $subscription->trial_ends_at,
            'on_trial' => $subscription->onTrial(),
        ];
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Company $company, bool $immediately = false): void
    {
        $subscription = $company->subscription;

        if (!$subscription) {
            return;
        }

        $subscription->cancel($immediately);

        Log::info('Subscription canceled', [
            'company_id' => $company->id,
            'immediately' => $immediately
        ]);
    }

    /**
     * Suspend a subscription.
     */
    public function suspendSubscription(Company $company, ?string $reason = null): void
    {
        $subscription = $company->subscription;

        if (!$subscription) {
            return;
        }

        $subscription->suspend($reason);

        Log::warning('Subscription suspended', [
            'company_id' => $company->id,
            'reason' => $reason
        ]);
    }

    /**
     * Resume a suspended subscription.
     */
    public function resumeSubscription(Company $company): void
    {
        $subscription = $company->subscription;

        if (!$subscription || !$subscription->isSuspended()) {
            return;
        }

        $subscription->resume();

        Log::info('Subscription resumed', [
            'company_id' => $company->id
        ]);
    }

    /**
     * Get all available plans.
     */
    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::active()
            ->ordered()
            ->get();
    }

    /**
     * Check if a feature is available for a company.
     */
    public function hasFeature(Company $company, string $feature): bool
    {
        $subscription = $this->getOrCreateSubscription($company);
        return $subscription->hasFeature($feature);
    }

    /**
     * Handle user creation - increment count.
     */
    public function handleUserCreated(User $user): void
    {
        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $this->updateUserCount($company);
            }
        }
    }

    /**
     * Handle user deletion/archival - decrement count.
     */
    public function handleUserDeleted(User $user): void
    {
        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $this->updateUserCount($company);
            }
        }
    }

    /**
     * Get companies approaching or over their limits.
     */
    public function getCompaniesNearLimit(): \Illuminate\Database\Eloquent\Collection
    {
        return CompanySubscription::with(['company', 'subscriptionPlan'])
            ->active()
            ->whereNotNull('max_users')
            ->whereRaw('current_user_count >= (max_users * 0.8)')
            ->get();
    }

    /**
     * Migrate existing companies to subscription model.
     * Used for initial setup or data migration.
     */
    public function migrateExistingCompanies(): void
    {
        $companies = Company::whereDoesntHave('subscription')->get();
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();

        if (!$freePlan) {
            Log::error('No free plan found for migration');
            return;
        }

        foreach ($companies as $company) {
            $this->createSubscription($company, $freePlan, [
                'status' => CompanySubscription::STATUS_ACTIVE,
                'trial_ends_at' => null, // No trial for migrated companies
            ]);
        }

        Log::info('Companies migrated to subscription model', [
            'count' => $companies->count()
        ]);
    }
}