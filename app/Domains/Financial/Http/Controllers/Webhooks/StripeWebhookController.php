<?php

namespace App\Domains\Financial\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Domains\Core\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * StripeWebhookController
 * 
 * Handles webhooks from Stripe for subscription events, payment updates,
 * invoice notifications, and other billing-related events.
 */
class StripeWebhookController extends Controller
{
    protected StripeSubscriptionService $stripeService;

    public function __construct(StripeSubscriptionService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle incoming Stripe webhooks.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'payload' => substr($payload, 0, 200),
                'signature' => substr($sigHeader, 0, 50)
            ]);
            
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => substr($payload, 0, 200)
            ]);
            
            return response('Webhook error', 400);
        }

        Log::info('Stripe webhook received', [
            'event_id' => $event->id,
            'event_type' => $event->type,
            'created' => $event->created
        ]);

        try {
            // Handle the event based on type
            switch ($event->type) {
                // Subscription events
                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($event);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event);
                    break;

                case 'customer.subscription.trial_will_end':
                    $this->handleTrialWillEnd($event);
                    break;

                // Payment method events
                case 'payment_method.attached':
                    $this->handlePaymentMethodAttached($event);
                    break;

                case 'payment_method.detached':
                    $this->handlePaymentMethodDetached($event);
                    break;

                case 'payment_method.automatically_updated':
                    $this->handlePaymentMethodUpdated($event);
                    break;

                // Invoice events
                case 'invoice.created':
                    $this->handleInvoiceCreated($event);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event);
                    break;

                case 'invoice.finalized':
                    $this->handleInvoiceFinalized($event);
                    break;

                // Customer events
                case 'customer.updated':
                    $this->handleCustomerUpdated($event);
                    break;

                case 'customer.deleted':
                    $this->handleCustomerDeleted($event);
                    break;

                // Payment intent events
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', [
                        'event_type' => $event->type,
                        'event_id' => $event->id
                    ]);
                    break;
            }

            return response('Webhook handled', 200);

        } catch (\Exception $e) {
            Log::error('Error handling Stripe webhook', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Webhook processing error', 500);
        }
    }

    /**
     * Handle subscription created event.
     */
    protected function handleSubscriptionCreated(Event $event)
    {
        $subscription = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($subscription->customer);
        if (!$client) {
            Log::warning('Client not found for subscription created', [
                'customer_id' => $subscription->customer,
                'subscription_id' => $subscription->id
            ]);
            return;
        }

        $client->update([
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => $this->mapStripeSubscriptionStatus($subscription->status),
            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
            'next_billing_date' => date('Y-m-d H:i:s', $subscription->current_period_end),
            'subscription_started_at' => date('Y-m-d H:i:s', $subscription->created),
        ]);

        Log::info('Subscription created and synced', [
            'client_id' => $client->id,
            'subscription_id' => $subscription->id
        ]);
    }

    /**
     * Handle subscription updated event.
     */
    protected function handleSubscriptionUpdated(Event $event)
    {
        $subscription = $event->data->object;
        
        $client = $this->findClientByStripeSubscriptionId($subscription->id);
        if (!$client) {
            Log::warning('Client not found for subscription updated', [
                'subscription_id' => $subscription->id
            ]);
            return;
        }

        $client->update([
            'subscription_status' => $this->mapStripeSubscriptionStatus($subscription->status),
            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
            'next_billing_date' => date('Y-m-d H:i:s', $subscription->current_period_end),
            'subscription_canceled_at' => $subscription->canceled_at ? date('Y-m-d H:i:s', $subscription->canceled_at) : null,
        ]);

        // Handle status changes
        if ($subscription->status === 'active' && $client->wasChanged('subscription_status')) {
            $this->handleSubscriptionActivated($client);
        } elseif ($subscription->status === 'past_due') {
            $this->handleSubscriptionPastDue($client);
        } elseif (in_array($subscription->status, ['canceled', 'unpaid'])) {
            $this->handleSubscriptionDeactivated($client);
        }

        Log::info('Subscription updated and synced', [
            'client_id' => $client->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);
    }

    /**
     * Handle subscription deleted event.
     */
    protected function handleSubscriptionDeleted(Event $event)
    {
        $subscription = $event->data->object;
        
        $client = $this->findClientByStripeSubscriptionId($subscription->id);
        if (!$client) {
            Log::warning('Client not found for subscription deleted', [
                'subscription_id' => $subscription->id
            ]);
            return;
        }

        $client->update([
            'subscription_status' => 'canceled',
            'subscription_canceled_at' => now(),
        ]);

        $this->handleSubscriptionDeactivated($client);

        Log::info('Subscription deleted and client updated', [
            'client_id' => $client->id,
            'subscription_id' => $subscription->id
        ]);
    }

    /**
     * Handle trial will end event (sent 3 days before trial ends).
     */
    protected function handleTrialWillEnd(Event $event)
    {
        $subscription = $event->data->object;
        
        $client = $this->findClientByStripeSubscriptionId($subscription->id);
        if (!$client) {
            return;
        }

        // Send trial ending notification
        // You can implement email notification here
        Log::info('Trial will end notification', [
            'client_id' => $client->id,
            'trial_end' => date('Y-m-d H:i:s', $subscription->trial_end)
        ]);
    }

    /**
     * Handle payment method attached event.
     */
    protected function handlePaymentMethodAttached(Event $event)
    {
        $paymentMethod = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($paymentMethod->customer);
        if (!$client) {
            return;
        }

        // Update or create payment method record
        $existingPaymentMethod = PaymentMethod::where('provider_payment_method_id', $paymentMethod->id)->first();
        
        if (!$existingPaymentMethod) {
            $this->stripeService->storePaymentMethod($client, $paymentMethod);
            Log::info('New payment method stored from webhook', [
                'client_id' => $client->id,
                'payment_method_id' => $paymentMethod->id
            ]);
        }
    }

    /**
     * Handle payment method detached event.
     */
    protected function handlePaymentMethodDetached(Event $event)
    {
        $paymentMethod = $event->data->object;
        
        $existingPaymentMethod = PaymentMethod::where('provider_payment_method_id', $paymentMethod->id)->first();
        if ($existingPaymentMethod) {
            $existingPaymentMethod->deactivate('Detached from Stripe');
            Log::info('Payment method deactivated from webhook', [
                'payment_method_id' => $existingPaymentMethod->id,
                'stripe_id' => $paymentMethod->id
            ]);
        }
    }

    /**
     * Handle payment method automatically updated event.
     */
    protected function handlePaymentMethodUpdated(Event $event)
    {
        $paymentMethod = $event->data->object;
        
        $existingPaymentMethod = PaymentMethod::where('provider_payment_method_id', $paymentMethod->id)->first();
        if ($existingPaymentMethod && $paymentMethod->card) {
            $existingPaymentMethod->update([
                'card_exp_month' => $paymentMethod->card->exp_month,
                'card_exp_year' => $paymentMethod->card->exp_year,
            ]);
            
            Log::info('Payment method updated from webhook', [
                'payment_method_id' => $existingPaymentMethod->id,
                'new_expiry' => $paymentMethod->card->exp_month . '/' . $paymentMethod->card->exp_year
            ]);
        }
    }

    /**
     * Handle invoice payment succeeded event.
     */
    protected function handleInvoicePaymentSucceeded(Event $event)
    {
        $invoice = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($invoice->customer);
        if (!$client) {
            return;
        }

        // Update payment method success stats
        if ($invoice->payment_method) {
            $paymentMethod = PaymentMethod::where('provider_payment_method_id', $invoice->payment_method)->first();
            if ($paymentMethod) {
                $paymentMethod->recordSuccessfulPayment($invoice->amount_paid / 100);
            }
        }

        Log::info('Invoice payment succeeded', [
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid / 100
        ]);
    }

    /**
     * Handle invoice payment failed event.
     */
    protected function handleInvoicePaymentFailed(Event $event)
    {
        $invoice = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($invoice->customer);
        if (!$client) {
            return;
        }

        // Update payment method failure stats
        if ($invoice->payment_method) {
            $paymentMethod = PaymentMethod::where('provider_payment_method_id', $invoice->payment_method)->first();
            if ($paymentMethod) {
                $paymentMethod->recordFailedPayment('Invoice payment failed');
            }
        }

        Log::info('Invoice payment failed', [
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due / 100
        ]);
    }

    /**
     * Handle other invoice events.
     */
    protected function handleInvoiceCreated(Event $event)
    {
        $invoice = $event->data->object;
        Log::info('Invoice created', ['invoice_id' => $invoice->id]);
    }

    protected function handleInvoiceFinalized(Event $event)
    {
        $invoice = $event->data->object;
        Log::info('Invoice finalized', ['invoice_id' => $invoice->id]);
    }

    /**
     * Handle customer updated event.
     */
    protected function handleCustomerUpdated(Event $event)
    {
        $customer = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($customer->id);
        if ($client) {
            // You could sync customer data here if needed
            Log::info('Customer updated', [
                'client_id' => $client->id,
                'customer_id' => $customer->id
            ]);
        }
    }

    /**
     * Handle customer deleted event.
     */
    protected function handleCustomerDeleted(Event $event)
    {
        $customer = $event->data->object;
        
        $client = $this->findClientByStripeCustomerId($customer->id);
        if ($client) {
            $client->update(['stripe_customer_id' => null]);
            Log::info('Customer deleted, client updated', [
                'client_id' => $client->id,
                'customer_id' => $customer->id
            ]);
        }
    }

    /**
     * Handle payment succeeded event.
     */
    protected function handlePaymentSucceeded(Event $event)
    {
        $paymentIntent = $event->data->object;
        Log::info('Payment succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100
        ]);
    }

    /**
     * Handle payment failed event.
     */
    protected function handlePaymentFailed(Event $event)
    {
        $paymentIntent = $event->data->object;
        Log::info('Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown'
        ]);
    }

    /**
     * Handle subscription activated (trial ended or first payment).
     */
    protected function handleSubscriptionActivated(Client $client)
    {
        // Activate the linked company if it was suspended
        if ($client->linkedCompany && $client->linkedCompany->suspended_at) {
            $client->linkedCompany->update([
                'is_active' => true,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }

        Log::info('Subscription activated, company reactivated', [
            'client_id' => $client->id,
            'company_id' => $client->company_link_id
        ]);
    }

    /**
     * Handle subscription past due.
     */
    protected function handleSubscriptionPastDue(Client $client)
    {
        // Optionally send notifications or take action
        Log::warning('Subscription past due', [
            'client_id' => $client->id,
            'company_id' => $client->company_link_id
        ]);
    }

    /**
     * Handle subscription deactivated.
     */
    protected function handleSubscriptionDeactivated(Client $client)
    {
        // Suspend the linked company
        if ($client->linkedCompany) {
            $client->linkedCompany->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Subscription canceled or unpaid',
            ]);
        }

        Log::warning('Subscription deactivated, company suspended', [
            'client_id' => $client->id,
            'company_id' => $client->company_link_id
        ]);
    }

    /**
     * Find client by Stripe customer ID.
     */
    protected function findClientByStripeCustomerId(string $customerId): ?Client
    {
        return Client::where('stripe_customer_id', $customerId)->first();
    }

    /**
     * Find client by Stripe subscription ID.
     */
    protected function findClientByStripeSubscriptionId(string $subscriptionId): ?Client
    {
        return Client::where('stripe_subscription_id', $subscriptionId)->first();
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
            'incomplete' => 'trialing',
            'incomplete_expired' => 'canceled',
        ];

        return $mapping[$stripeStatus] ?? 'unpaid';
    }
}