<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

/**
 * Stripe Gateway Service
 *
 * Handles all Stripe API interactions for payment processing
 */
class StripeGatewayService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create or get Stripe customer for client
     */
    public function getOrCreateCustomer(Client $client): array
    {
        try {
            // Check if client already has a Stripe customer ID
            if ($client->stripe_customer_id) {
                try {
                    $customer = $this->stripe->customers->retrieve($client->stripe_customer_id);
                    
                    return [
                        'success' => true,
                        'customer_id' => $customer->id,
                        'customer' => $customer,
                    ];
                } catch (ApiErrorException $e) {
                    // Customer doesn't exist, create new one
                    Log::warning('Stripe customer not found, creating new', [
                        'client_id' => $client->id,
                        'old_stripe_id' => $client->stripe_customer_id,
                    ]);
                }
            }

            // Create new customer
            $customer = $this->stripe->customers->create([
                'email' => $client->email,
                'name' => $client->name,
                'phone' => $client->phone,
                'metadata' => [
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                ],
            ]);

            // Update client with Stripe customer ID
            $client->update(['stripe_customer_id' => $customer->id]);

            return [
                'success' => true,
                'customer_id' => $customer->id,
                'customer' => $customer,
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Stripe customer', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => 'Failed to create customer account',
            ];
        }
    }

    /**
     * Create payment method from Stripe Payment Method ID
     */
    public function createPaymentMethod(Client $client, string $stripePaymentMethodId): array
    {
        try {
            // Get or create Stripe customer
            $customerResult = $this->getOrCreateCustomer($client);
            if (!$customerResult['success']) {
                return $customerResult;
            }

            // Retrieve payment method details from Stripe
            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($stripePaymentMethodId);

            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($stripePaymentMethodId, [
                'customer' => $customerResult['customer_id'],
            ]);

            // Extract card details
            $card = $stripePaymentMethod->card;

            return [
                'success' => true,
                'payment_method_id' => $stripePaymentMethod->id,
                'customer_id' => $customerResult['customer_id'],
                'token' => $stripePaymentMethod->id,
                'card_brand' => $card->brand ?? null,
                'card_last_four' => $card->last4 ?? null,
                'card_exp_month' => $card->exp_month ?? null,
                'card_exp_year' => $card->exp_year ?? null,
                'card_country' => $card->country ?? null,
                'card_funding' => $card->funding ?? null,
                'requires_3d_secure' => ($card->three_d_secure_usage->supported ?? false),
                'security_checks' => [
                    'cvc_check' => $card->checks->cvc_check ?? null,
                    'address_line1_check' => $card->checks->address_line1_check ?? null,
                    'address_postal_code_check' => $card->checks->address_postal_code_check ?? null,
                ],
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment method creation failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment through Stripe
     */
    public function processPayment(
        Client $client,
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency = 'USD',
        array $metadata = []
    ): array {
        try {
            // Create Payment Intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'customer' => $paymentMethod->provider_customer_id,
                'payment_method' => $paymentMethod->provider_payment_method_id,
                'confirm' => true,
                'off_session' => true,
                'metadata' => array_merge([
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                    'payment_method_id' => $paymentMethod->id,
                ], $metadata),
            ]);

            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'transaction_id' => $paymentIntent->id,
                    'gateway_fee' => $this->calculateStripeFee($amount),
                    'metadata' => [
                        'payment_intent_id' => $paymentIntent->id,
                        'charge_id' => $paymentIntent->latest_charge ?? null,
                    ],
                ];
            } elseif ($paymentIntent->status === 'requires_action') {
                return [
                    'success' => false,
                    'error_message' => '3D Secure authentication required',
                    'error_code' => 'REQUIRES_3DS',
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                ];
            } else {
                return [
                    'success' => false,
                    'error_message' => 'Payment failed: ' . ($paymentIntent->status ?? 'unknown status'),
                    'error_code' => 'PAYMENT_FAILED',
                ];
            }

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment processing failed', [
                'client_id' => $client->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => $this->getUserFriendlyErrorMessage($e),
                'error_code' => $e->getStripeCode() ?? 'GATEWAY_ERROR',
            ];
        }
    }

    /**
     * Detach payment method from customer
     */
    public function detachPaymentMethod(string $paymentMethodId): bool
    {
        try {
            $this->stripe->paymentMethods->detach($paymentMethodId);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to detach Stripe payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate Stripe processing fee
     */
    protected function calculateStripeFee(float $amount): float
    {
        // Stripe charges 2.9% + $0.30 per successful card charge
        return round(($amount * 0.029) + 0.30, 2);
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyErrorMessage(ApiErrorException $e): string
    {
        return match ($e->getStripeCode()) {
            'card_declined' => 'Your card was declined. Please try a different card.',
            'insufficient_funds' => 'Insufficient funds. Please use a different payment method.',
            'incorrect_cvc' => 'The card security code is incorrect.',
            'expired_card' => 'This card has expired. Please use a different card.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'incorrect_number' => 'The card number is incorrect.',
            default => 'Payment failed. Please try again or contact support.',
        };
    }
}
