<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Core\Services\StripeSubscriptionService;
use App\Domains\Product\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSetting;
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

    protected SubscriptionService $subscriptionService;

    public function __construct(StripeSubscriptionService $stripeService, SubscriptionService $subscriptionService)
    {
        $this->stripeService = $stripeService;
        $this->subscriptionService = $subscriptionService;
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
        Log::info('=== SIGNUP PROCESS START ===', [
            'time' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'memory_usage' => memory_get_usage(true),
            'session_id' => session()->getId(),
        ]);

        try {
            // Validation
            Log::info('STEP 1: Starting validation');
            $stepStartTime = microtime(true);

            $validator = $this->validateRegistrationData($request);

            $validationDuration = microtime(true) - $stepStartTime;
            Log::info('Validation completed', ['duration_seconds' => $validationDuration]);

            if ($validator->fails()) {
                Log::warning('Validation failed', ['errors' => $validator->errors()]);

                return redirect()->back()->withErrors($validator)->withInput();
            }

            Log::info('STEP 2: Starting database transaction');
            DB::beginTransaction();

            // Get subscription plan
            Log::info('STEP 3: Getting subscription plan');
            $stepStartTime = microtime(true);
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
            $planDuration = microtime(true) - $stepStartTime;
            Log::info('Plan retrieved', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'price_monthly' => $plan->price_monthly,
                'duration_seconds' => $planDuration,
            ]);

            // Create tenant company
            Log::info('STEP 4: Creating tenant company');
            $stepStartTime = microtime(true);
            $company = $this->createTenantCompany($request->all());
            $companyDuration = microtime(true) - $stepStartTime;
            Log::info('Company created', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'duration_seconds' => $companyDuration,
            ]);

            // Create client record
            Log::info('STEP 5: Creating client record');
            $stepStartTime = microtime(true);
            $client = $this->createClientRecord($company, $request->all(), $plan);
            $clientDuration = microtime(true) - $stepStartTime;
            Log::info('Client created', [
                'client_id' => $client->id,
                'duration_seconds' => $clientDuration,
            ]);

            // Create admin user
            Log::info('STEP 6: Creating admin user');
            $stepStartTime = microtime(true);
            $user = $this->createAdminUser($company, $request->all());
            $userDuration = microtime(true) - $stepStartTime;
            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'duration_seconds' => $userDuration,
            ]);

            // Create company subscription
            Log::info('STEP 7: Creating company subscription');
            $stepStartTime = microtime(true);
            $companySubscription = $this->subscriptionService->createSubscription($company, $plan);
            $subscriptionDuration = microtime(true) - $stepStartTime;
            Log::info('Company subscription created', [
                'subscription_id' => $companySubscription->id,
                'duration_seconds' => $subscriptionDuration,
            ]);

            // Stripe setup - THIS IS LIKELY WHERE THE TIMEOUT OCCURS
            if ($request->has('payment_method_id')) {
                Log::info('STEP 8: Starting Stripe setup', [
                    'payment_method_provided' => true,
                    'plan_price' => $plan->price_monthly,
                ]);

                if ($plan->price_monthly > 0) {
                    Log::info('STRIPE: Setting up paid plan subscription');
                    $stripeStartTime = microtime(true);

                    $subscriptionResult = $this->setupStripeSubscription($client, $plan, $request->all());

                    $stripeDuration = microtime(true) - $stripeStartTime;
                    Log::info('STRIPE: Subscription setup completed', [
                        'duration_seconds' => $stripeDuration,
                        'success' => $subscriptionResult['success'],
                    ]);

                    if (! $subscriptionResult['success']) {
                        throw new \Exception('Payment setup failed: '.$subscriptionResult['error']);
                    }
                } else {
                    Log::info('STRIPE: Setting up free plan with auth');
                    $stripeStartTime = microtime(true);

                    $authResult = $this->setupStripeCustomerWithAuth($client, $request->all());

                    $stripeDuration = microtime(true) - $stripeStartTime;
                    Log::info('STRIPE: Customer auth setup completed', [
                        'duration_seconds' => $stripeDuration,
                        'success' => $authResult['success'],
                    ]);

                    if (! $authResult['success']) {
                        throw new \Exception('Identity verification failed: '.$authResult['error']);
                    }

                    // Set free plan as active immediately
                    Log::info('STEP 9: Activating free plan');
                    $client->update([
                        'subscription_status' => 'active',
                        'trial_ends_at' => null,
                        'subscription_started_at' => now(),
                    ]);

                    $companySubscription->update([
                        'status' => 'active',
                        'trial_ends_at' => null,
                    ]);
                }
            } else {
                throw new \Exception('Payment method is required for all plans.');
            }

            // Link records
            Log::info('STEP 10: Linking records');
            $stepStartTime = microtime(true);
            $this->linkRecords($company, $client);
            $linkDuration = microtime(true) - $stepStartTime;
            Log::info('Records linked', ['duration_seconds' => $linkDuration]);

            // Commit transaction
            Log::info('STEP 11: Committing transaction');
            $stepStartTime = microtime(true);
            DB::commit();
            $commitDuration = microtime(true) - $stepStartTime;
            Log::info('Transaction committed', ['duration_seconds' => $commitDuration]);

            // Login user
            Log::info('STEP 12: Logging in user');
            auth()->login($user);

            Log::info('=== SIGNUP PROCESS COMPLETE ===', [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'total_memory_usage' => memory_get_usage(true),
            ]);

            return redirect()->route('dashboard')->with('success',
                'Welcome to Nestogy! Your 14-day free trial has started. You have full access to all features.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== SIGNUP PROCESS FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['admin_password', 'admin_password_confirmation', 'payment_method_id']),
                'memory_usage' => memory_get_usage(true),
            ]);

            return redirect()->back()
                ->withErrors(['registration' => 'Registration failed: '.$e->getMessage()])
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
        Log::info('STRIPE SUBSCRIPTION: Starting setup', [
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'stripe_price_id' => $plan->stripe_price_id,
            'payment_method_id' => substr($data['payment_method_id'], 0, 20).'...',
        ]);

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

            Log::info('STRIPE SUBSCRIPTION: Calling createCompleteSubscription');
            $apiStartTime = microtime(true);

            $result = $this->stripeService->createCompleteSubscription($subscriptionData);

            $apiDuration = microtime(true) - $apiStartTime;
            Log::info('STRIPE SUBSCRIPTION: API call completed', [
                'duration_seconds' => $apiDuration,
                'customer_id' => $result['customer']->id ?? 'unknown',
                'subscription_id' => $result['subscription']->id ?? 'unknown',
            ]);

            // Update client with Stripe IDs
            Log::info('STRIPE SUBSCRIPTION: Updating client with Stripe IDs');
            $client->update([
                'stripe_customer_id' => $result['customer']->id,
                'stripe_subscription_id' => $result['subscription']->id,
            ]);

            // Store payment method in database
            Log::info('STRIPE SUBSCRIPTION: Storing payment method');
            $this->stripeService->storePaymentMethod($client, $result['payment_method']);

            Log::info('STRIPE SUBSCRIPTION: Setup completed successfully');

            return [
                'success' => true,
                'stripe_data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('STRIPE SUBSCRIPTION: Setup failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Setup Stripe customer with $1 authorization for identity verification (free plans).
     */
    protected function setupStripeCustomerWithAuth(Client $client, array $data): array
    {
        Log::info('STRIPE CUSTOMER AUTH: Starting setup', [
            'client_id' => $client->id,
            'payment_method_id' => substr($data['payment_method_id'], 0, 20).'...',
            'authorization_amount' => config('saas.trial.authorization_amount', 100),
        ]);

        try {
            $customerData = [
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
                'authorize_only' => true, // Only authorize $1, don't create subscription
                'authorization_amount' => config('saas.trial.authorization_amount', 100), // $1.00 in cents
            ];

            Log::info('STRIPE CUSTOMER AUTH: Calling createCustomerWithAuth');
            $apiStartTime = microtime(true);

            $result = $this->stripeService->createCustomerWithAuth($customerData);

            $apiDuration = microtime(true) - $apiStartTime;
            Log::info('STRIPE CUSTOMER AUTH: API call completed', [
                'duration_seconds' => $apiDuration,
                'customer_id' => $result['customer']->id ?? 'unknown',
            ]);

            // Update client with Stripe customer ID
            Log::info('STRIPE CUSTOMER AUTH: Updating client with customer ID');
            $client->update([
                'stripe_customer_id' => $result['customer']->id,
            ]);

            // Store payment method in database
            Log::info('STRIPE CUSTOMER AUTH: Storing payment method');
            $this->stripeService->storePaymentMethod($client, $result['payment_method']);

            Log::info('STRIPE CUSTOMER AUTH: Setup completed successfully');

            return [
                'success' => true,
                'stripe_data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('STRIPE CUSTOMER AUTH: Setup failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                    'price_per_user_monthly' => $plan->price_per_user_monthly,
                    'pricing_model' => $plan->pricing_model,
                    'minimum_users' => $plan->minimum_users,
                    'user_limit' => $plan->user_limit,
                    'formatted_price' => $plan->getFormattedPrice(),
                    'starting_price' => $plan->getStartingPrice(),
                    'price_explanation' => $plan->getPriceExplanation(),
                    'user_limit_text' => $plan->getUserLimitText(),
                    'description' => $plan->description,
                    'features' => $plan->features,
                ];
            }),
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
                // Payment method (required for all plans)
                $validator = Validator::make($request->all(), [
                    'payment_method_id' => 'required|string',
                    'terms_accepted' => 'accepted',
                ]);
                break;

            default:
                return response()->json(['valid' => false, 'errors' => ['Invalid step']]);
        }

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors(),
            ]);
        }

        return response()->json(['valid' => true]);
    }
}
