<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * BillingController
 * 
 * Handles customer self-service billing portal for tenant companies.
 * Allows customers to view their subscription, change plans, update payment methods, etc.
 */
class BillingController extends Controller
{
    protected StripeSubscriptionService $stripeService;

    public function __construct(StripeSubscriptionService $stripeService)
    {
        $this->middleware('auth');
        $this->stripeService = $stripeService;
    }

    /**
     * Display billing dashboard for current company.
     */
    public function index()
    {
        $company = Auth::user()->company;
        
        // Get the billing client record (in Company 1)
        $client = $company->clientRecord;
        
        if (!$client) {
            // This company doesn't have billing set up
            return view('billing.not-setup');
        }

        $client->load(['subscriptionPlan', 'paymentMethods' => function ($query) {
            $query->active();
        }]);

        // Sync with Stripe if we have a subscription
        if ($client->stripe_subscription_id) {
            $this->stripeService->syncSubscriptionStatus($client);
            $client->refresh();
        }

        $availablePlans = SubscriptionPlan::active()->ordered()->get();

        return view('billing.index', compact('company', 'client', 'availablePlans'));
    }

    /**
     * Show subscription details.
     */
    public function subscription()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client) {
            abort(404);
        }

        $client->load(['subscriptionPlan', 'paymentMethods']);

        // Get Stripe subscription details if available
        $stripeSubscription = null;
        if ($client->stripe_subscription_id) {
            $result = $this->stripeService->getSubscriptionStatus($client->stripe_subscription_id);
            $stripeSubscription = $result['success'] ? $result['subscription'] : null;
        }

        return view('billing.subscription', compact('company', 'client', 'stripeSubscription'));
    }

    /**
     * Show payment methods management.
     */
    public function paymentMethods()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client) {
            abort(404);
        }

        $paymentMethods = $client->paymentMethods()->active()->orderBy('is_default', 'desc')->get();

        return view('billing.payment-methods', compact('company', 'client', 'paymentMethods'));
    }

    /**
     * Show plan change options.
     */
    public function changePlan()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client || !$client->subscriptionPlan) {
            abort(404);
        }

        $currentPlan = $client->subscriptionPlan;
        $availablePlans = SubscriptionPlan::active()
            ->where('id', '!=', $currentPlan->id)
            ->ordered()
            ->get();

        return view('billing.change-plan', compact('company', 'client', 'currentPlan', 'availablePlans'));
    }

    /**
     * Process plan change request.
     */
    public function updatePlan(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client || !$client->stripe_subscription_id) {
            return redirect()->route('billing.index')
                ->withErrors(['error' => 'No active subscription found.']);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
        
        // Check if user can change to this plan (business rules)
        if (!$this->canChangeToPlan($client, $newPlan)) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot change to the selected plan at this time.']);
        }

        try {
            // Update Stripe subscription
            $this->stripeService->updateSubscription(
                $client->stripe_subscription_id, 
                $newPlan->stripe_price_id
            );

            // Update local record
            $client->update(['subscription_plan_id' => $newPlan->id]);

            Log::info('Customer changed subscription plan', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'old_plan' => $client->subscriptionPlan->name,
                'new_plan' => $newPlan->name,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('billing.index')
                ->with('success', 'Your subscription plan has been updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to change subscription plan', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to change subscription plan. Please try again or contact support.']);
        }
    }

    /**
     * Show invoice history.
     */
    public function invoices()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client) {
            abort(404);
        }

        // Get invoices from the main ERP system
        $invoices = $client->invoices()
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('billing.invoices', compact('company', 'client', 'invoices'));
    }

    /**
     * Request subscription cancellation.
     */
    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client || !$client->stripe_subscription_id) {
            return redirect()->route('billing.index')
                ->withErrors(['error' => 'No active subscription found.']);
        }

        try {
            // Cancel at period end (don't cancel immediately)
            $this->stripeService->cancelSubscription($client->stripe_subscription_id, false);

            Log::info('Customer requested subscription cancellation', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'reason' => $request->reason,
                'feedback' => $request->feedback,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('billing.index')
                ->with('success', 'Your subscription will be canceled at the end of your current billing period.');

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to cancel subscription. Please contact support.']);
        }
    }

    /**
     * Reactivate canceled subscription.
     */
    public function reactivateSubscription()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client || !$client->stripe_subscription_id) {
            return redirect()->route('billing.index')
                ->withErrors(['error' => 'No subscription found.']);
        }

        try {
            // Remove the cancellation (reactivate)
            $subscription = $this->stripeService->updateSubscription($client->stripe_subscription_id, null);

            Log::info('Customer reactivated subscription', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('billing.index')
                ->with('success', 'Your subscription has been reactivated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to reactivate subscription', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to reactivate subscription. Please contact support.']);
        }
    }

    /**
     * Generate Stripe billing portal session.
     */
    public function billingPortal()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client || !$client->stripe_customer_id) {
            return redirect()->route('billing.payment-methods')
                ->withErrors(['error' => 'Billing portal is not available.']);
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $session = $stripe->billingPortal->sessions->create([
                'customer' => $client->stripe_customer_id,
                'return_url' => route('billing.index'),
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Failed to create billing portal session', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Unable to access billing portal. Please try again.']);
        }
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice($invoiceId)
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client) {
            abort(404);
        }

        $invoice = $client->invoices()->findOrFail($invoiceId);

        // Generate and return PDF
        // This would use your existing invoice PDF generation logic
        return $invoice->generatePdf();
    }

    /**
     * Get usage statistics for current billing period.
     */
    public function usage()
    {
        $company = Auth::user()->company;
        $client = $company->clientRecord;

        if (!$client) {
            abort(404);
        }

        $currentPeriodStart = $client->subscription_started_at ?? $company->created_at;
        $currentPeriodEnd = $client->next_billing_date ?? now();

        $usage = [
            'users' => $company->users()->count(),
            'clients' => $company->clients()->count(),
            'tickets_this_month' => $company->tickets()
                ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
                ->count(),
            'storage_used_mb' => $this->calculateStorageUsage($company),
        ];

        $limits = [
            'max_users' => $client->subscriptionPlan->user_limit,
            'max_clients' => $client->subscriptionPlan->max_clients ?? null,
            'max_tickets' => config('saas.limits.max_tickets_per_month'),
            'max_storage_mb' => (config('saas.limits.storage_limit_gb') * 1024),
        ];

        return view('billing.usage', compact('company', 'client', 'usage', 'limits'));
    }

    /**
     * Check if user can change to a specific plan.
     */
    protected function canChangeToPlan(Client $client, SubscriptionPlan $newPlan): bool
    {
        // Add business logic for plan changes
        // For example: prevent downgrades if current usage exceeds new plan limits
        
        $company = $client->linkedCompany;
        if (!$company) {
            return false;
        }

        // Check user limits
        if ($newPlan->user_limit && $company->users()->count() > $newPlan->user_limit) {
            return false;
        }

        // Check other limits as needed
        // You can add more validation here

        return true;
    }

    /**
     * Calculate storage usage for a company.
     */
    protected function calculateStorageUsage(Company $company): int
    {
        // This would calculate actual storage usage
        // For now, return a placeholder value
        return 0;
    }
}