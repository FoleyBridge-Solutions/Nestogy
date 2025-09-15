<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use App\Exceptions\UserLimitExceededException;
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

        if (!$user || !$user->company) {
            return $next($request);
        }

        // Check if this is a user creation request
        if ($this->isUserCreationRequest($request)) {
            $company = $user->company;

            // If a different company is specified (for super-admins), use that
            if ($request->has('company_id') && $user->canAccessCrossTenant()) {
                $company = \App\Models\Company::find($request->company_id);
            }

            if ($company) {
                // Check if the company can add more users
                if (!$this->subscriptionService->canAddUser($company)) {
                    $subscription = $company->subscription;
                    $planName = $subscription ? $subscription->getDisplayName() : 'current';
                    $limit = $subscription ? $subscription->max_users : 0;

                    $message = "User limit reached. Your {$planName} plan allows {$limit} users. Please upgrade your plan to add more users.";

                    // Add warning if approaching limit
                    if ($this->subscriptionService->isApproachingUserLimit($company)) {
                        session()->flash('subscription_warning', 'You are approaching your user limit. Consider upgrading your plan.');
                    }

                    throw new UserLimitExceededException($message);
                }
            }
        }

        return $next($request);
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