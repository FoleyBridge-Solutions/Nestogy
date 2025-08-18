<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Recurring Billing Service
 * 
 * Focused service responsible only for recurring billing operations.
 * Demonstrates composition by using other specialized services.
 */
class RecurringBillingService
{
    public function __construct(
        private ProductPricingService $pricingService,
        private BillingScheduleService $scheduleService
    ) {}

    /**
     * Process recurring billing for a specific date.
     */
    public function processRecurringBilling(Carbon $billingDate = null): Collection
    {
        $billingDate = $billingDate ?? Carbon::today();
        $invoices = collect();

        try {
            $subscriptions = $this->getSubscriptionsDueForBilling($billingDate);
            
            Log::info('Processing recurring billing', [
                'billing_date' => $billingDate->format('Y-m-d'),
                'subscriptions_count' => $subscriptions->count()
            ]);

            foreach ($subscriptions as $subscription) {
                try {
                    $invoice = $this->createSubscriptionInvoice($subscription, $billingDate);
                    $invoices->push($invoice);

                } catch (\Exception $e) {
                    Log::error('Recurring billing failed for subscription', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $invoices;

        } catch (\Exception $e) {
            Log::error('Recurring billing process failed', [
                'billing_date' => $billingDate->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Create invoice for a subscription using composed services.
     */
    public function createSubscriptionInvoice($subscription, Carbon $billingDate): Invoice
    {
        $product = $subscription->product;
        $client = $subscription->client;
        
        // Use composed pricing service
        $pricing = $this->pricingService->calculatePrice(
            $product, 
            $client, 
            $subscription->quantity ?? 1
        );

        // Create invoice
        $invoice = Invoice::create([
            'company_id' => $product->company_id,
            'client_id' => $client->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => $billingDate,
            'due_date' => $billingDate->copy()->addDays($product->payment_terms ?? 30),
            'subtotal' => $pricing['subtotal'],
            'tax' => $pricing['tax'],
            'total' => $pricing['total'],
            'status' => 'draft',
            'type' => 'subscription',
            'notes' => 'Recurring billing for ' . $product->name,
            'billing_period_start' => $billingDate,
            'billing_period_end' => $this->scheduleService->getNextBillingDate($billingDate, $product->billing_cycle)
        ]);

        // Add invoice items
        $this->createInvoiceItems($invoice, $subscription, $pricing);

        return $invoice;
    }

    /**
     * Get subscriptions due for billing.
     */
    protected function getSubscriptionsDueForBilling(Carbon $billingDate)
    {
        // Implementation would depend on subscription model
        return collect();
    }

    protected function createInvoiceItems(Invoice $invoice, $subscription, array $pricing): void
    {
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $subscription->product->id,
            'description' => $this->scheduleService->generatePeriodDescription(
                $subscription->product, 
                $invoice->billing_period_start
            ),
            'quantity' => $subscription->quantity ?? 1,
            'unit_price' => $pricing['unit_price'],
            'subtotal' => $pricing['subtotal'],
            'tax' => $pricing['tax'],
            'total' => $pricing['total']
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'REC';
        $year = Carbon::now()->year;
        $sequence = 1; // Simplified for demo
        
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}