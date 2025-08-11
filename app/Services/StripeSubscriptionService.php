<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Subscription;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;

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
        $this->stripe = new StripeClient(config('services.stripe.secret'));
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
                'client_id' => $customerData['client_id'] ?? null
            ]);

            return $customer;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'error' => $e->getMessage(),
                'customer_data' => $customerData
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
                'set_as_default' => $setAsDefault
            ]);

            return $paymentMethod;
        } catch (ApiErrorException $e) {
            Log::error('Failed to attach payment method', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId
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
                'trial_days' => $trialDays
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'price_id' => $priceId
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
                'new_price_id' => $newPriceId
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to update subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'new_price_id' => $newPriceId
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
                'immediately' => $immediately
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    /**
     * Authorize a payment method with a small amount (e.g., $1).
     */
    public function authorizePayment(string $paymentMethodId, int $amount = 100): array
    {
        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => $amount, // $1.00 in cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'capture_method' => 'manual', // Don't actually capture the payment
                'confirm' => true,
                'return_url' => config('app.url'), // Required for some payment methods
            ]);

            // If successful, void the authorization
            if ($intent->status === 'requires_capture') {
                $this->stripe->paymentIntents->cancel($intent->id);
            }

            Log::info('Payment method authorized and voided', [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'intent_id' => $intent->id
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
                'amount' => $amount
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
                $authResult = $this->authorizePayment($data['payment_method_id']);
                if (!$authResult['success']) {
                    throw new \Exception('Payment method authorization failed: ' . $authResult['error']);
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
                'price_id' => $data['price_id']
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
                'data' => $data
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
                'subscription_id' => $subscriptionId
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
        if (!$client->stripe_subscription_id) {
            return false;
        }

        $statusResult = $this->getSubscriptionStatus($client->stripe_subscription_id);
        
        if (!$statusResult['success']) {
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
            'status' => $subscription->status
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
}