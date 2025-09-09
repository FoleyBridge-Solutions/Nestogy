<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionManagementController
 * 
 * Allows Company 1 super-admins to manage all tenant subscriptions,
 * view billing information, handle trials, and manage customer accounts.
 */
class SubscriptionManagementController extends Controller
{
    protected StripeSubscriptionService $stripeService;

    public function __construct(StripeSubscriptionService $stripeService)
    {
        $this->middleware('auth');
        $this->stripeService = $stripeService;
    }

    /**
     * Display subscription dashboard.
     */
    public function index(Request $request)
    {
        Gate::authorize('manage-subscriptions');

        $query = Client::with(['linkedCompany', 'subscriptionPlan'])
            ->where('company_id', 1) // Only Company 1's clients (billing records)
            ->whereNotNull('company_link_id'); // Only SaaS customers

        // Apply filters
        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', $request->plan);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('linkedCompany', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $subscriptions = $query->paginate(25);

        $stats = [
            'total' => Client::where('company_id', 1)->whereNotNull('company_link_id')->count(),
            'active' => Client::where('company_id', 1)->whereNotNull('company_link_id')->where('subscription_status', 'active')->count(),
            'trialing' => Client::where('company_id', 1)->whereNotNull('company_link_id')->where('subscription_status', 'trialing')->count(),
            'past_due' => Client::where('company_id', 1)->whereNotNull('company_link_id')->where('subscription_status', 'past_due')->count(),
            'canceled' => Client::where('company_id', 1)->whereNotNull('company_link_id')->where('subscription_status', 'canceled')->count(),
        ];

        $plans = SubscriptionPlan::all();

        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans'));
    }

    /**
     * Show detailed subscription information.
     */
    public function show(Client $client)
    {
        Gate::authorize('manage-subscriptions');

        // Ensure this is a SaaS customer
        if ($client->company_id !== 1 || !$client->company_link_id) {
            abort(404);
        }

        $client->load(['linkedCompany', 'subscriptionPlan', 'paymentMethods']);

        // Sync with Stripe if we have a subscription ID
        if ($client->stripe_subscription_id) {
            $this->stripeService->syncSubscriptionStatus($client);
            $client->refresh();
        }

        return view('admin.subscriptions.show', compact('client'));
    }

    /**
     * Create a new tenant company from existing client.
     */
    public function createTenant(Client $client)
    {
        Gate::authorize('manage-subscriptions');

        if ($client->company_id !== 1 || $client->company_link_id) {
            return redirect()->back()->withErrors(['error' => 'Invalid client for tenant creation.']);
        }

        try {
            // Create the tenant company
            $company = Company::create([
                'name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'state' => $client->state,
                'zip' => $client->zip_code,
                'country' => $client->country,
                'website' => $client->website,
                'currency' => 'USD',
                'is_active' => true,
            ]);

            // Link the records
            $client->update(['company_link_id' => $company->id]);
            $company->update(['client_record_id' => $client->id]);

            Log::info('Tenant company created from existing client', [
                'client_id' => $client->id,
                'company_id' => $company->id,
                'admin_user' => auth()->id()
            ]);

            return redirect()->route('admin.subscriptions.show', $client)
                ->with('success', 'Tenant company created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create tenant from client', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to create tenant company.']);
        }
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request, Client $client)
    {
        Gate::authorize('manage-subscriptions');

        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        if ($client->company_id !== 1 || !$client->company_link_id) {
            return redirect()->back()->withErrors(['error' => 'Invalid subscription for plan change.']);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        try {
            // Update Stripe subscription if active
            if ($client->stripe_subscription_id && in_array($client->subscription_status, ['active', 'trialing'])) {
                $this->stripeService->updateSubscription($client->stripe_subscription_id, $newPlan->stripe_price_id);
            }

            // Update local record
            $client->update(['subscription_plan_id' => $newPlan->id]);

            Log::info('Subscription plan changed', [
                'client_id' => $client->id,
                'old_plan' => $client->subscription_plan_id,
                'new_plan' => $newPlan->id,
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Subscription plan updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to change subscription plan', [
                'client_id' => $client->id,
                'new_plan' => $newPlan->id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to change subscription plan.']);
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request, Client $client)
    {
        Gate::authorize('manage-subscriptions');

        $request->validate([
            'immediately' => 'boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($client->company_id !== 1 || !$client->stripe_subscription_id) {
            return redirect()->back()->withErrors(['error' => 'Invalid subscription for cancellation.']);
        }

        try {
            // Cancel in Stripe
            $immediately = $request->boolean('immediately');
            $this->stripeService->cancelSubscription($client->stripe_subscription_id, $immediately);

            // Update local record
            $client->update([
                'subscription_status' => $immediately ? 'canceled' : 'active',
                'subscription_canceled_at' => $immediately ? now() : null,
            ]);

            // Suspend company if immediate
            if ($immediately && $client->linkedCompany) {
                $client->linkedCompany->update([
                    'is_active' => false,
                    'suspended_at' => now(),
                    'suspension_reason' => 'Subscription canceled by admin: ' . ($request->reason ?? 'No reason provided'),
                ]);
            }

            Log::info('Subscription canceled by admin', [
                'client_id' => $client->id,
                'immediately' => $immediately,
                'reason' => $request->reason,
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->with('success', 
                $immediately ? 'Subscription canceled immediately.' : 'Subscription will cancel at period end.');

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to cancel subscription.']);
        }
    }

    /**
     * Reactivate canceled subscription.
     */
    public function reactivate(Client $client)
    {
        Gate::authorize('manage-subscriptions');

        if ($client->company_id !== 1 || !$client->company_link_id) {
            return redirect()->back()->withErrors(['error' => 'Invalid subscription for reactivation.']);
        }

        try {
            // Create new subscription in Stripe if needed
            if (!$client->stripe_subscription_id || $client->subscription_status === 'canceled') {
                // This would require creating a new subscription
                // For now, just reactivate locally and let admin handle Stripe manually
                $client->update([
                    'subscription_status' => 'active',
                    'subscription_canceled_at' => null,
                ]);
            }

            // Reactivate company
            if ($client->linkedCompany && $client->linkedCompany->suspended_at) {
                $client->linkedCompany->update([
                    'is_active' => true,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            }

            Log::info('Subscription reactivated by admin', [
                'client_id' => $client->id,
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Subscription reactivated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to reactivate subscription', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to reactivate subscription.']);
        }
    }

    /**
     * Suspend tenant company.
     */
    public function suspendTenant(Request $request, Client $client)
    {
        Gate::authorize('manage-subscriptions');

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($client->company_id !== 1 || !$client->linkedCompany) {
            return redirect()->back()->withErrors(['error' => 'Invalid tenant for suspension.']);
        }

        $client->linkedCompany->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        Log::warning('Tenant company suspended by admin', [
            'client_id' => $client->id,
            'company_id' => $client->linkedCompany->id,
            'reason' => $request->reason,
            'admin_user' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Tenant company suspended.');
    }

    /**
     * Reactivate suspended tenant company.
     */
    public function reactivateTenant(Client $client)
    {
        Gate::authorize('manage-subscriptions');

        if ($client->company_id !== 1 || !$client->linkedCompany) {
            return redirect()->back()->withErrors(['error' => 'Invalid tenant for reactivation.']);
        }

        $client->linkedCompany->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        Log::info('Tenant company reactivated by admin', [
            'client_id' => $client->id,
            'company_id' => $client->linkedCompany->id,
            'admin_user' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Tenant company reactivated.');
    }

    /**
     * Export subscription data.
     */
    public function export(Request $request)
    {
        Gate::authorize('manage-subscriptions');

        $query = Client::with(['linkedCompany', 'subscriptionPlan'])
            ->where('company_id', 1)
            ->whereNotNull('company_link_id');

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }
        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', $request->plan);
        }

        $subscriptions = $query->get();

        $csv = "Company,Email,Plan,Status,Trial Ends,Next Billing,Total Users,Created\n";
        
        foreach ($subscriptions as $subscription) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $subscription->company_name),
                $subscription->email,
                $subscription->subscriptionPlan->name ?? 'None',
                ucfirst($subscription->subscription_status),
                $subscription->trial_ends_at?->format('Y-m-d') ?? 'N/A',
                $subscription->next_billing_date?->format('Y-m-d') ?? 'N/A',
                $subscription->current_user_count,
                $subscription->created_at->format('Y-m-d')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="subscriptions-' . date('Y-m-d') . '.csv"');
    }

    /**
     * Get subscription analytics data.
     */
    public function analytics()
    {
        Gate::authorize('manage-subscriptions');

        $data = [
            'monthly_recurring_revenue' => Client::where('company_id', 1)
                ->whereNotNull('company_link_id')
                ->where('subscription_status', 'active')
                ->with('subscriptionPlan')
                ->get()
                ->sum(function ($client) {
                    return $client->subscriptionPlan?->price_monthly ?? 0;
                }),
            
            'total_customers' => Client::where('company_id', 1)->whereNotNull('company_link_id')->count(),
            
            'trial_conversions' => Client::where('company_id', 1)
                ->whereNotNull('company_link_id')
                ->where('subscription_status', 'active')
                ->whereNotNull('trial_ends_at')
                ->count(),
            
            'churn_rate' => $this->calculateChurnRate(),
            
            'plan_distribution' => SubscriptionPlan::withCount(['clients' => function ($query) {
                $query->where('company_id', 1)
                      ->whereNotNull('company_link_id')
                      ->where('subscription_status', 'active');
            }])->get(),
        ];

        return response()->json($data);
    }

    /**
     * Calculate monthly churn rate.
     */
    protected function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $activeAtStart = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->where('created_at', '<', $startOfMonth)
            ->where(function ($query) use ($startOfMonth) {
                $query->whereNull('subscription_canceled_at')
                      ->orWhere('subscription_canceled_at', '>=', $startOfMonth);
            })
            ->count();

        $canceledThisMonth = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->whereBetween('subscription_canceled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return $activeAtStart > 0 ? ($canceledThisMonth / $activeAtStart) * 100 : 0;
    }
}