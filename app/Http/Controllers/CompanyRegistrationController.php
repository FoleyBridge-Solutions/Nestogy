<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * CompanyRegistrationController
 * 
 * Handles the complete signup flow for new companies including:
 * - Company creation (tenant)
 * - Client creation under Company 1 (billing)
 * - Admin user creation
 * - Stripe subscription setup with payment method
 * - 14-day trial activation
 */
class CompanyRegistrationController extends Controller
{
    protected StripeSubscriptionService $stripeService;

    public function __construct(StripeSubscriptionService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Show the company registration form.
     */
    public function showRegistrationForm()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('auth.company-register', compact('plans'));
    }

    /**
     * Handle the company registration process.
     */
    public function register(Request $request)
    {
        // Validate the registration data
        $validator = $this->validateRegistrationData($request);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Get the selected subscription plan
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

            // Step 1: Create the tenant company
            $company = $this->createTenantCompany($request->all());

            // Step 2: Create the client record under Company 1
            $client = $this->createClientRecord($company, $request->all(), $plan);

            // Step 3: Create the admin user for the tenant company
            $user = $this->createAdminUser($company, $request->all());

            // Step 4: Set up Stripe subscription (if payment method provided)
            if ($request->has('payment_method_id')) {
                $subscriptionResult = $this->setupStripeSubscription($client, $plan, $request->all());
                
                if (!$subscriptionResult['success']) {
                    throw new \Exception('Payment setup failed: ' . $subscriptionResult['error']);
                }
            }

            // Step 5: Link the records together
            $this->linkRecords($company, $client);

            DB::commit();

            Log::info('New company registration completed successfully', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id
            ]);

            // Log in the user and redirect to their tenant dashboard
            auth()->login($user);

            return redirect()->route('dashboard')->with('success', 
                'Welcome to Nestogy! Your 14-day free trial has started. You have full access to all features.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Company registration failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password', 'password_confirmation', 'payment_method_id'])
            ]);

            return redirect()->back()
                ->withErrors(['registration' => 'Registration failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Validate the registration data.
     */
    protected function validateRegistrationData(Request $request)
    {
        return Validator::make($request->all(), [
            // Company information
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_zip' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_website' => 'nullable|url|max:255',
            
            // Admin user information
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            
            // Subscription information
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_method_id' => 'nullable|string', // Stripe payment method ID
            
            // Terms and conditions
            'terms_accepted' => 'accepted',
        ]);
    }

    /**
     * Create the tenant company.
     */
    protected function createTenantCompany(array $data): Company
    {
        return Company::create([
            'name' => $data['company_name'],
            'email' => $data['company_email'],
            'phone' => $data['company_phone'] ?? null,
            'address' => $data['company_address'] ?? null,
            'city' => $data['company_city'] ?? null,
            'state' => $data['company_state'] ?? null,
            'zip' => $data['company_zip'] ?? null,
            'country' => $data['company_country'] ?? 'United States',
            'website' => $data['company_website'] ?? null,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * Create the client record under Company 1.
     */
    protected function createClientRecord(Company $tenantCompany, array $data, SubscriptionPlan $plan): Client
    {
        return Client::create([
            'company_id' => 1, // Always Company 1 for billing
            'name' => $data['admin_name'], // Primary contact name
            'company_name' => $data['company_name'],
            'email' => $data['admin_email'],
            'phone' => $data['company_phone'] ?? null,
            'address' => $data['company_address'] ?? null,
            'city' => $data['company_city'] ?? null,
            'state' => $data['company_state'] ?? null,
            'zip_code' => $data['company_zip'] ?? null,
            'country' => $data['company_country'] ?? 'United States',
            'website' => $data['company_website'] ?? null,
            'status' => 'active',
            'type' => 'saas_customer',
            'lead' => false,
            
            // Subscription fields
            'company_link_id' => $tenantCompany->id,
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'current_user_count' => 1,
            'subscription_started_at' => now(),
        ]);
    }

    /**
     * Create the admin user for the tenant company.
     */
    protected function createAdminUser(Company $company, array $data): User
    {
        // Create the user
        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'status' => true,
            'email_verified_at' => now(), // Auto-verify for new registrations
        ]);

        // Create user settings with admin role
        UserSetting::createDefaultForUser($user->id, UserSetting::ROLE_ADMIN, $company->id);

        return $user;
    }

    /**
     * Set up Stripe subscription.
     */
    protected function setupStripeSubscription(Client $client, SubscriptionPlan $plan, array $data): array
    {
        try {
            $subscriptionData = [
                'customer_data' => [
                    'name' => $client->company_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'address' => $client->address,
                    'city' => $client->city,
                    'state' => $client->state,
                    'zip_code' => $client->zip_code,
                    'country' => $client->country,
                    'client_id' => $client->id,
                    'company_name' => $client->company_name,
                ],
                'payment_method_id' => $data['payment_method_id'],
                'price_id' => $plan->stripe_price_id,
                'trial_days' => 14,
                'authorize_payment' => true, // Authorize $1 to verify payment method
            ];

            $result = $this->stripeService->createCompleteSubscription($subscriptionData);

            // Update client with Stripe IDs
            $client->update([
                'stripe_customer_id' => $result['customer']->id,
                'stripe_subscription_id' => $result['subscription']->id,
            ]);

            // Store payment method in database
            $this->stripeService->storePaymentMethod($client, $result['payment_method']);

            return [
                'success' => true,
                'stripe_data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Stripe subscription setup failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Link the company and client records.
     */
    protected function linkRecords(Company $company, Client $client): void
    {
        // Update company with client record ID
        $company->update([
            'client_record_id' => $client->id,
        ]);

        // Client already has company_link_id set during creation
    }

    /**
     * Get available subscription plans for the registration form.
     */
    public function getPlans()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return response()->json([
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => $plan->price_monthly,
                    'formatted_price' => $plan->getFormattedPrice(),
                    'user_limit_text' => $plan->getUserLimitText(),
                    'description' => $plan->description,
                    'features' => $plan->features,
                ];
            })
        ]);
    }

    /**
     * Handle step-by-step registration (for AJAX-based multi-step forms).
     */
    public function validateStep(Request $request)
    {
        $step = $request->input('step', 1);
        
        switch ($step) {
            case 1:
                // Company information
                $validator = Validator::make($request->all(), [
                    'company_name' => 'required|string|max:255',
                    'company_email' => 'required|email|max:255',
                    'company_phone' => 'nullable|string|max:20',
                ]);
                break;
                
            case 2:
                // Admin user information
                $validator = Validator::make($request->all(), [
                    'admin_name' => 'required|string|max:255',
                    'admin_email' => 'required|email|max:255|unique:users,email',
                    'admin_password' => ['required', 'confirmed', Password::defaults()],
                ]);
                break;
                
            case 3:
                // Subscription plan
                $validator = Validator::make($request->all(), [
                    'subscription_plan_id' => 'required|exists:subscription_plans,id',
                ]);
                break;
                
            case 4:
                // Payment method (optional during trial)
                $validator = Validator::make($request->all(), [
                    'payment_method_id' => 'nullable|string',
                    'terms_accepted' => 'accepted',
                ]);
                break;
                
            default:
                return response()->json(['valid' => false, 'errors' => ['Invalid step']]);
        }

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()
            ]);
        }

        return response()->json(['valid' => true]);
    }
}