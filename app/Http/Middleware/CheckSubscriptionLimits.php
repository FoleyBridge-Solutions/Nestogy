<?php

namespace App\Http\Middleware;

use App\Domains\Product\Services\SubscriptionService;
use App\Exceptions\UserLimitExceededException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscriptionLimits Middleware
 *
 * Checks subscription limits before allowing user creation.
 * This middleware ensures companies cannot exceed their plan's user limit.
 */
class CheckSubscriptionLimits
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($this->shouldSkipCheck($user)) {
            return $next($request);
        }

        if ($this->isUserCreationRequest($request)) {
            $company = $this->getTargetCompany($request, $user);
            $this->validateUserLimit($company);
        }

        return $next($request);
    }

    protected function shouldSkipCheck($user): bool
    {
        return ! $user || ! $user->company;
    }

    protected function getTargetCompany(Request $request, $user)
    {
        if ($request->has('company_id') && $user->canAccessCrossTenant()) {
            return \App\Models\Company::find($request->company_id);
        }

        return $user->company;
    }

    protected function validateUserLimit($company): void
    {
        if (! $company) {
            return;
        }

        $subscription = $company->subscription;

        if ($this->canAddMoreUsers($subscription)) {
            return;
        }

        $this->addApproachingLimitWarning($subscription);
        $this->throwUserLimitException($subscription);
    }

    protected function canAddMoreUsers($subscription): bool
    {
        if (! $subscription) {
            return true;
        }

        return $subscription->canAddUser();
    }

    protected function addApproachingLimitWarning($subscription): void
    {
        if (! $subscription || ! $subscription->approachingUserLimit()) {
            return;
        }

        session()->flash('subscription_warning', 'You are approaching your user limit. Consider upgrading your plan.');
    }

    protected function throwUserLimitException($subscription): void
    {
        $planName = $subscription ? $subscription->getDisplayName() : 'current';
        $limit = $subscription ? $subscription->max_users : 0;
        $message = "User limit reached. Your {$planName} plan allows {$limit} users. Please upgrade your plan to add more users.";

        throw new UserLimitExceededException($message);
    }

    /**
     * Check if this is a user creation request.
     */
    protected function isUserCreationRequest(Request $request): bool
    {
        // Check if this is a POST request to user creation endpoints
        return $request->isMethod('POST') && (
            $request->routeIs('users.store') ||
            $request->routeIs('api.users.store') ||
            $request->is('users') ||
            $request->is('api/users')
        );
    }
}
