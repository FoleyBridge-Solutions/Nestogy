<?php

namespace App\Services;

use App\Models\Client;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\StripeClient;
use Stripe\Subscription;

/**
 * StripeSubscriptionService
 *
 * Handles all Stripe-related operations for subscription management including
 * customer creation, payment method storage, subscription management, and billing.
 */
class StripeSubscriptionService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            throw new \Exception('Stripe is not configured. Please set STRIPE_SECRET in your .env file.');
        }
        $this->stripe = new StripeClient($secret);
    }

    /**
     * Create a Stripe customer for a client.
     */
    public function createCustomer(array $customerData): Customer
    {
        try {
            $customer = $this->stripe->customers->create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'] ?? null,
                'address' => [
                    'line1' => $customerData['address'] ?? null,
                    'city' => $customerData['city'] ?? null,
                    'state' => $customerData['state'] ?? null,
                    'postal_code' => $customerData['zip_code'] ?? null,
                    'country' => $customerData['country'] ?? 'US',
                ],
                'metadata' => [
                    'client_id' => $customerData['client_id'] ?? null,
                    'company_name' => $customerData['company_name'] ?? null,
                ],
            ]);

            Log::info('Stripe customer created', [
                'customer_id' => $customer->id,
                'client_id' => $customerData['client_id'] ?? null,
            ]);

            return $customer;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'error' => $e->getMessage(),
                'customer_data' => $customerData,
            ]);
            throw $e;
        }
    }

    /**
     * Create a customer with payment method and $1 authorization for identity verification.
     */
    public function createCustomerWithAuth(array $data): array
    {
        try {
            // Create the customer
            $customer = $this->createCustomer($data['customer_data']);

            // Attach payment method to customer
            $paymentMethod = $this->attachPaymentMethod(
                $data['payment_method_id'],
                $customer->id,
                true // Set as default
            );

            // Create $1 authorization for identity verification
            $authAmount = $data['authorization_amount'] ?? 100; // $1.00 in cents

            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $authAmount,
                'currency' => 'usd',
                'customer' => $customer->id,
                'payment_method' => $data['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'capture_method' => 'manual', // Don't capture, just authorize
                'description' => 'Identity verification authorization - will be refunded immediately',
                'metadata' => [
                    'purpose' => 'identity_verification',
                    'client_id' => $data['customer_data']['client_id'] ?? null,
                ],
            ]);

            // If authorization succeeded, cancel it immediately
            if ($paymentIntent->status === 'requires_capture') {
                $this->stripe->paymentIntents->cancel($paymentIntent->id);
                Log::info('Identity verification authorization completed and canceled', [
                    'customer_id' => $customer->id,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
            }

            return [
                'customer' => $customer,
                'payment_method' => $paymentMethod,
                'authorization' => $paymentIntent,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to create customer with authorization', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Attach a payment method to a customer and optionally set as default.
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId, bool $setAsDefault = false): StripePaymentMethod
    {
        try {
            // Attach payment method to customer
            $paymentMethod = $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId,
            ]);

            // Set as default if requested
            if ($setAsDefault) {
                $this->stripe->customers->update($customerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
            }

            Log::info('Payment method attached to customer', [
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId,
                'set_as_default' => $setAsDefault,
            ]);

            return $paymentMethod;
        } catch (ApiErrorException $e) {
            Log::error('Failed to attach payment method', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId,
            ]);
            throw $e;
        }
    }

    /**
     * Create a subscription for a customer.
     */
    public function createSubscription(string $customerId, string $priceId, int $trialDays = 14): Subscription
    {
        try {
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [['price' => $priceId]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            // Add trial period if specified
            if ($trialDays > 0) {
                $subscriptionData['trial_period_days'] = $trialDays;
            }

            $subscription = $this->stripe->subscriptions->create($subscriptionData);

            Log::info('Stripe subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'price_id' => $priceId,
                'trial_days' => $trialDays,
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'price_id' => $priceId,
            ]);
            throw $e;
        }
    }

    /**
     * Update a subscription to a new plan.
     */
    public function updateSubscription(string $subscriptionId, string $newPriceId): Subscription
    {
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);

            $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $newPriceId,
                    ],
                ],
                'proration_behavior' => 'always_invoice',
            ]);

            Log::info('Stripe subscription updated', [
                'subscription_id' => $subscriptionId,
                'new_price_id' => $newPriceId,
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to update subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'new_price_id' => $newPriceId,
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately = false): Subscription
    {
        try {
            if ($immediately) {
                $subscription = $this->stripe->subscriptions->cancel($subscriptionId);
            } else {
                $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                    'cancel_at_period_end' => true,
                ]);
            }

            Log::info('Stripe subscription canceled', [
                'subscription_id' => $subscriptionId,
                'immediately' => $immediately,
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);
            throw $e;
        }
    }

    /**
     * Authorize a payment method with a small amount (e.g., $1).
     */
    public function authorizePayment(string $paymentMethodId, int $amount = 100, ?string $customerId = null): array
    {
        try {
            $intentData = [
                'amount' => $amount, // $1.00 in cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'capture_method' => 'manual', // Don't actually capture the payment
                'confirm' => true,
                'return_url' => config('app.url'), // Required for some payment methods
            ];

            // If customer ID is provided, include it (required when payment method belongs to customer)
            if ($customerId) {
                $intentData['customer'] = $customerId;
            }

            $intent = $this->stripe->paymentIntents->create($intentData);

            // If successful, void the authorization
            if ($intent->status === 'requires_capture') {
                $this->stripe->paymentIntents->cancel($intent->id);
            }

            Log::info('Payment method authorized and voided', [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'intent_id' => $intent->id,
            ]);

            return [
                'success' => true,
                'intent_id' => $intent->id,
                'status' => $intent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::warning('Payment method authorization failed', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
            ]);

            $response = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            // Only add decline code for CardException which has this method
            if ($e instanceof CardException) {
                $response['decline_code'] = $e->getDeclineCode();
            }

            return $response;
        }
    }

    /**
     * Create a complete subscription setup (Customer + Payment Method + Subscription).
     */
    public function createCompleteSubscription(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create Stripe customer
            $customer = $this->createCustomer($data['customer_data']);

            // Attach payment method
            $paymentMethod = $this->attachPaymentMethod(
                $data['payment_method_id'],
                $customer->id,
                true // Set as default
            );

            // Optionally authorize payment method
            if ($data['authorize_payment'] ?? false) {
                $authResult = $this->authorizePayment($data['payment_method_id'], 100, $customer->id);
                if (! $authResult['success']) {
                    throw new \Exception('Payment method authorization failed: '.$authResult['error']);
                }
            }

            // Ensure Stripe price exists before creating subscription
            $plan = \App\Models\SubscriptionPlan::where('stripe_price_id', $data['price_id'])->first();
            if ($plan && $plan->price_monthly > 0) {
                $actualPriceId = $this->ensureStripePriceExists($plan);
                if ($actualPriceId) {
                    $data['price_id'] = $actualPriceId;
                }
            }

            // Create subscription
            $subscription = $this->createSubscription(
                $customer->id,
                $data['price_id'],
                $data['trial_days'] ?? 14
            );

            DB::commit();

            Log::info('Complete subscription created successfully', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'price_id' => $data['price_id'],
            ]);

            return [
                'success' => true,
                'customer' => $customer,
                'payment_method' => $paymentMethod,
                'subscription' => $subscription,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create complete subscription', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Store payment method information in the database.
     */
    public function storePaymentMethod(Client $client, StripePaymentMethod $stripePaymentMethod): PaymentMethod
    {
        $paymentMethodData = [
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'type' => $this->mapStripePaymentMethodType($stripePaymentMethod->type),
            'provider' => PaymentMethod::PROVIDER_STRIPE,
            'provider_payment_method_id' => $stripePaymentMethod->id,
            'provider_customer_id' => $stripePaymentMethod->customer,
            'is_active' => true,
            'is_default' => true, // First payment method is default
            'verified' => true,
            'verified_at' => now(),
        ];

        // Add card-specific data
        if ($stripePaymentMethod->type === 'card' && $stripePaymentMethod->card) {
            $card = $stripePaymentMethod->card;
            $paymentMethodData = array_merge($paymentMethodData, [
                'card_brand' => $card->brand,
                'card_last_four' => $card->last4,
                'card_exp_month' => $card->exp_month,
                'card_exp_year' => $card->exp_year,
                'card_funding' => $card->funding,
                'card_country' => $card->country,
            ]);
        }

        // Add billing details if available
        if ($stripePaymentMethod->billing_details) {
            $billing = $stripePaymentMethod->billing_details;
            $paymentMethodData = array_merge($paymentMethodData, [
                'billing_name' => $billing->name,
                'billing_email' => $billing->email,
                'billing_phone' => $billing->phone,
            ]);

            if ($billing->address) {
                $paymentMethodData = array_merge($paymentMethodData, [
                    'billing_address_line1' => $billing->address->line1,
                    'billing_address_line2' => $billing->address->line2,
                    'billing_city' => $billing->address->city,
                    'billing_state' => $billing->address->state,
                    'billing_postal_code' => $billing->address->postal_code,
                    'billing_country' => $billing->address->country,
                ]);
            }
        }

        return PaymentMethod::create($paymentMethodData);
    }

    /**
     * Map Stripe payment method type to our internal type.
     */
    protected function mapStripePaymentMethodType(string $stripeType): string
    {
        $mapping = [
            'card' => PaymentMethod::TYPE_CREDIT_CARD,
            'us_bank_account' => PaymentMethod::TYPE_BANK_ACCOUNT,
            // Add more mappings as needed
        ];

        return $mapping[$stripeType] ?? PaymentMethod::TYPE_CREDIT_CARD;
    }

    /**
     * Get subscription status from Stripe.
     */
    public function getSubscriptionStatus(string $subscriptionId): array
    {
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);

            return [
                'success' => true,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'subscription' => $subscription,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to get subscription status', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync subscription status with Stripe.
     */
    public function syncSubscriptionStatus(Client $client): bool
    {
        if (! $client->stripe_subscription_id) {
            return false;
        }

        $statusResult = $this->getSubscriptionStatus($client->stripe_subscription_id);

        if (! $statusResult['success']) {
            return false;
        }

        $subscription = $statusResult['subscription'];

        // Update client subscription data
        $client->update([
            'subscription_status' => $this->mapStripeSubscriptionStatus($subscription->status),
            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
            'next_billing_date' => date('Y-m-d H:i:s', $subscription->current_period_end),
            'subscription_started_at' => $subscription->created ? date('Y-m-d H:i:s', $subscription->created) : null,
            'subscription_canceled_at' => $subscription->canceled_at ? date('Y-m-d H:i:s', $subscription->canceled_at) : null,
        ]);

        Log::info('Client subscription status synced with Stripe', [
            'client_id' => $client->id,
            'subscription_id' => $client->stripe_subscription_id,
            'status' => $subscription->status,
        ]);

        return true;
    }

    /**
     * Map Stripe subscription status to our internal status.
     */
    protected function mapStripeSubscriptionStatus(string $stripeStatus): string
    {
        $mapping = [
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'unpaid' => 'unpaid',
            'incomplete' => 'trialing', // Treat incomplete as trialing
            'incomplete_expired' => 'canceled',
        ];

        return $mapping[$stripeStatus] ?? 'unpaid';
    }

    /**
     * Ensure a Stripe price exists for a subscription plan, creating it if necessary.
     */
    public function ensureStripePriceExists(\App\Models\SubscriptionPlan $plan): ?string
    {
        // Skip if no Stripe price ID is set
        if (! $plan->stripe_price_id) {
            return null;
        }

        // Skip if this is a free plan (both fixed and per-user)
        if ($plan->pricing_model === 'fixed' && $plan->price_monthly == 0) {
            return null;
        }
        if ($plan->pricing_model === 'per_user' && $plan->price_per_user_monthly == 0) {
            return null;
        }

        try {
            // Try to retrieve the price first
            $price = $this->stripe->prices->retrieve($plan->stripe_price_id);
            Log::info('Stripe price exists', ['price_id' => $price->id]);

            return $price->id;
        } catch (ApiErrorException $e) {
            // If price doesn't exist, create it
            if (strpos($e->getMessage(), 'No such price') !== false) {
                Log::info('Creating Stripe price for plan', [
                    'plan' => $plan->name,
                    'price_id' => $plan->stripe_price_id,
                ]);

                // First, create or get the product
                $product = $this->ensureStripeProductExists($plan);

                // Determine the price amount based on pricing model
                $unitAmount = 0;
                if ($plan->pricing_model === 'per_user') {
                    $unitAmount = (int) ($plan->price_per_user_monthly * 100); // Per user price in cents
                } else {
                    $unitAmount = (int) ($plan->price_monthly * 100); // Fixed price in cents
                }

                // Create the price
                $price = $this->stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => $unitAmount,
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'lookup_key' => $plan->stripe_price_id,
                ]);

                // Update the plan with the actual Stripe price ID
                $plan->update(['stripe_price_id' => $price->id]);

                Log::info('Stripe price created', [
                    'price_id' => $price->id,
                    'amount' => $plan->price_monthly,
                ]);

                return $price->id;
            }

            throw $e;
        }
    }

    /**
     * Ensure a Stripe product exists for a subscription plan.
     */
    protected function ensureStripeProductExists(\App\Models\SubscriptionPlan $plan): \Stripe\Product
    {
        $productId = 'prod_'.preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($plan->slug ?: $plan->name));

        try {
            return $this->stripe->products->retrieve($productId);
        } catch (ApiErrorException $e) {
            // Create the product if it doesn't exist
            return $this->stripe->products->create([
                'id' => $productId,
                'name' => $plan->name.' Plan',
                'description' => $plan->description ?: $plan->name.' subscription plan',
                'metadata' => [
                    'plan_id' => (string) $plan->id,
                    'plan_name' => $plan->name,
                ],
            ]);
        }
    }
}
