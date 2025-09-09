<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingService
{
    protected ProductPricingService $pricingService;

    public function __construct(ProductPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Generate billing schedule for a product
     */
    public function generateBillingSchedule(Product $product, Carbon $startDate, int $periods = 12): array
    {
        $schedule = [];
        $currentDate = $startDate->copy();

        for ($i = 0; $i < $periods; $i++) {
            $billingDate = $this->getNextBillingDate($currentDate, $product->billing_cycle);
            
            $schedule[] = [
                'period' => $i + 1,
                'billing_date' => $billingDate->format('Y-m-d'),
                'due_date' => $billingDate->copy()->addDays($product->payment_terms ?? 30)->format('Y-m-d'),
                'amount' => $product->base_price,
                'billing_cycle' => $product->billing_cycle,
                'description' => $this->generateBillingDescription($product, $billingDate)
            ];

            $currentDate = $billingDate;
        }

        return $schedule;
    }

    /**
     * Calculate prorated amount for partial period
     */
    public function calculateProratedAmount(Product $product, Carbon $startDate, Carbon $endDate = null): array
    {
        if ($product->billing_model !== 'subscription') {
            return [
                'amount' => $product->base_price,
                'days' => 0,
                'is_prorated' => false
            ];
        }

        $endDate = $endDate ?? $this->getNextBillingDate($startDate, $product->billing_cycle);
        $totalDays = $this->getBillingCycleDays($product->billing_cycle);
        $usedDays = $startDate->diffInDays($endDate);
        
        $proratedAmount = ($product->base_price / $totalDays) * $usedDays;

        return [
            'amount' => round($proratedAmount, 2),
            'days' => $usedDays,
            'total_days' => $totalDays,
            'is_prorated' => true,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];
    }

    /**
     * Calculate usage-based billing
     */
    public function calculateUsageBilling(Product $product, float $usage, Carbon $periodStart, Carbon $periodEnd): array
    {
        if ($product->billing_model !== 'usage_based') {
            throw new \Exception('Product does not support usage-based billing');
        }

        $baseAmount = 0;
        $overage = 0;
        $total = 0;

        // Check if there's an included amount
        $includedUnits = $product->usage_included ?? 0;
        $unitPrice = $product->usage_rate ?? $product->base_price;

        if ($usage <= $includedUnits) {
            // Within included usage
            $baseAmount = $product->base_price;
            $total = $baseAmount;
        } else {
            // Calculate overage
            $baseAmount = $product->base_price;
            $overage = ($usage - $includedUnits) * $unitPrice;
            $total = $baseAmount + $overage;
        }

        return [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'usage' => $usage,
            'included_units' => $includedUnits,
            'overage_units' => max(0, $usage - $includedUnits),
            'unit_price' => $unitPrice,
            'base_amount' => $baseAmount,
            'overage_amount' => $overage,
            'total_amount' => $total,
            'unit_type' => $product->unit_type ?? 'units'
        ];
    }

    /**
     * Process recurring billing for subscriptions
     */
    public function processRecurringBilling(Carbon $billingDate = null): Collection
    {
        $billingDate = $billingDate ?? Carbon::today();
        $invoices = collect();

        // Get all active subscriptions due for billing
        $subscriptions = $this->getSubscriptionsDueForBilling($billingDate);

        foreach ($subscriptions as $subscription) {
            try {
                $invoice = $this->createSubscriptionInvoice($subscription, $billingDate);
                $invoices->push($invoice);
            } catch (\Exception $e) {
                // Log billing failure
                \Log::error('Recurring billing failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $invoices;
    }

    /**
     * Create invoice for subscription
     */
    protected function createSubscriptionInvoice($subscription, Carbon $billingDate): Invoice
    {
        $product = $subscription->product;
        $client = $subscription->client;
        
        // Calculate pricing
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
            'notes' => 'Recurring billing for ' . $product->name
        ]);

        // Add invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => $this->generateBillingDescription($product, $billingDate),
            'quantity' => $subscription->quantity ?? 1,
            'unit_price' => $pricing['unit_price'],
            'subtotal' => $pricing['subtotal'],
            'tax' => $pricing['tax'],
            'total' => $pricing['total']
        ]);

        return $invoice;
    }

    /**
     * Handle service setup fees
     */
    public function calculateServiceSetupFees(Service $service, Client $client = null): array
    {
        $fees = [];
        $totalSetupCost = 0;

        // Base setup fee
        if ($service->has_setup_fee) {
            $fees[] = [
                'type' => 'setup_fee',
                'description' => 'Setup fee for ' . $service->product->name,
                'amount' => $service->setup_fee,
                'is_one_time' => true
            ];
            $totalSetupCost += $service->setup_fee;
        }

        // Additional fees based on requirements
        if (!empty($service->requirements)) {
            foreach ($service->requirements as $requirement) {
                if (isset($requirement['has_fee']) && $requirement['has_fee']) {
                    $fees[] = [
                        'type' => 'requirement_fee',
                        'description' => $requirement['description'] ?? 'Additional requirement',
                        'amount' => $requirement['fee'] ?? 0,
                        'is_one_time' => true
                    ];
                    $totalSetupCost += $requirement['fee'] ?? 0;
                }
            }
        }

        return [
            'fees' => $fees,
            'total_setup_cost' => $totalSetupCost,
            'has_setup_fees' => $totalSetupCost > 0
        ];
    }

    /**
     * Calculate early termination fees
     */
    public function calculateEarlyTerminationFee(Service $service, Carbon $startDate, Carbon $terminationDate): float
    {
        if (!$service->hasMinimumCommitment()) {
            return 0;
        }

        $commitmentEndDate = $startDate->copy()->addMonths($service->minimum_commitment_months);
        
        if ($terminationDate >= $commitmentEndDate) {
            return 0; // No early termination fee
        }

        // Calculate remaining months
        $remainingMonths = $terminationDate->diffInMonths($commitmentEndDate);
        
        // Calculate fee based on remaining commitment
        $monthlyRate = $service->product->base_price;
        $terminationFee = $monthlyRate * $remainingMonths * 0.5; // 50% of remaining value

        return $terminationFee;
    }

    /**
     * Get next billing date based on cycle
     */
    protected function getNextBillingDate(Carbon $currentDate, string $billingCycle): Carbon
    {
        return match($billingCycle) {
            'weekly' => $currentDate->copy()->addWeek(),
            'monthly' => $currentDate->copy()->addMonth(),
            'quarterly' => $currentDate->copy()->addMonths(3),
            'semi-annually' => $currentDate->copy()->addMonths(6),
            'annually' => $currentDate->copy()->addYear(),
            default => $currentDate->copy()->addMonth()
        };
    }

    /**
     * Get billing cycle days for proration
     */
    protected function getBillingCycleDays(string $billingCycle): int
    {
        return match($billingCycle) {
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'semi-annually' => 180,
            'annually' => 365,
            default => 30
        };
    }

    /**
     * Generate billing description
     */
    protected function generateBillingDescription(Product $product, Carbon $billingDate): string
    {
        $period = match($product->billing_cycle) {
            'weekly' => 'Week of ' . $billingDate->format('M d, Y'),
            'monthly' => $billingDate->format('F Y'),
            'quarterly' => 'Q' . $billingDate->quarter . ' ' . $billingDate->year,
            'semi-annually' => ($billingDate->month <= 6 ? 'First' : 'Second') . ' Half ' . $billingDate->year,
            'annually' => 'Year ' . $billingDate->year,
            default => $billingDate->format('M d, Y')
        };

        return $product->name . ' - ' . $period;
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = Carbon::now()->year;
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -4)) + 1) : 1;
        
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    /**
     * Get subscriptions due for billing
     */
    protected function getSubscriptionsDueForBilling(Carbon $billingDate)
    {
        // This would need to be implemented based on your subscription tracking model
        // For now, returning empty collection
        return collect();
    }

    /**
     * Calculate billing for product bundles
     */
    public function calculateBundleBilling($bundle, array $selectedProducts, Client $client = null): array
    {
        $bundlePricing = $this->pricingService->calculateBundlePrice($bundle, $selectedProducts, $client);
        
        $items = [];
        foreach ($bundlePricing['items'] as $item) {
            $product = Product::find($item['product_id']);
            
            $items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['bundle_price'],
                'subtotal' => $item['bundle_price'] * $item['quantity'],
                'is_bundle_item' => true,
                'bundle_id' => $bundle->id
            ];
        }

        return [
            'bundle' => [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'type' => $bundle->bundle_type
            ],
            'items' => $items,
            'subtotal' => $bundlePricing['bundle_price'],
            'savings' => $bundlePricing['savings'],
            'total' => $bundlePricing['bundle_price']
        ];
    }
}