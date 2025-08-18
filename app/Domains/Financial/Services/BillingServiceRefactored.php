<?php

namespace App\Domains\Financial\Services;

use App\Services\BaseService;
use App\Models\Product;
use App\Models\Service;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingServiceRefactored extends BaseService
{
    protected ProductPricingService $pricingService;

    public function __construct(ProductPricingService $pricingService)
    {
        parent::__construct();
        $this->pricingService = $pricingService;
    }

    protected function initializeService(): void
    {
        $this->modelClass = Invoice::class; // Primary model this service works with
        $this->defaultEagerLoad = ['client', 'items'];
        $this->searchableFields = ['number', 'scope', 'client.name'];
        $this->defaultSortField = 'created_at';
        $this->defaultSortDirection = 'desc';
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
    public function calculateProratedAmount(
        float $monthlyAmount, 
        Carbon $startDate, 
        Carbon $endDate
    ): float {
        $totalDaysInPeriod = $startDate->diffInDays($endDate->copy()->endOfMonth()) + 1;
        $usageDays = $startDate->diffInDays($endDate) + 1;
        
        return ($monthlyAmount / $totalDaysInPeriod) * $usageDays;
    }

    /**
     * Process recurring billing for all clients
     */
    public function processRecurringBilling(Carbon $billingDate = null): array
    {
        $billingDate = $billingDate ?? Carbon::today();
        $companyId = Auth::user()->company_id;
        
        return DB::transaction(function () use ($billingDate, $companyId) {
            $results = [
                'processed' => 0,
                'failed' => 0,
                'total_amount' => 0,
                'invoices' => [],
                'errors' => []
            ];

            // Get all clients with active recurring billing
            $clients = Client::where('company_id', $companyId)
                ->whereHas('recurringInvoices', function ($query) use ($billingDate) {
                    $query->where('next_billing_date', '<=', $billingDate)
                          ->where('status', 'active');
                })
                ->with(['recurringInvoices' => function ($query) use ($billingDate) {
                    $query->where('next_billing_date', '<=', $billingDate)
                          ->where('status', 'active');
                }])
                ->get();

            foreach ($clients as $client) {
                foreach ($client->recurringInvoices as $recurringInvoice) {
                    try {
                        $invoice = $this->createRecurringInvoice($recurringInvoice, $billingDate);
                        
                        $results['processed']++;
                        $results['total_amount'] += $invoice->total;
                        $results['invoices'][] = $invoice->id;
                        
                        // Update next billing date
                        $recurringInvoice->update([
                            'next_billing_date' => $this->getNextBillingDate(
                                $billingDate,
                                $recurringInvoice->billing_cycle
                            ),
                            'last_billed_at' => $billingDate
                        ]);
                        
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'client_id' => $client->id,
                            'recurring_invoice_id' => $recurringInvoice->id,
                            'error' => $e->getMessage()
                        ];
                        
                        $this->logActivity(null, 'recurring_billing_failed', [
                            'client_id' => $client->id,
                            'recurring_invoice_id' => $recurringInvoice->id,
                            'error' => $e->getMessage(),
                            'billing_date' => $billingDate->format('Y-m-d')
                        ]);
                    }
                }
            }

            $this->logActivity(null, 'recurring_billing_processed', [
                'billing_date' => $billingDate->format('Y-m-d'),
                'processed' => $results['processed'],
                'failed' => $results['failed'],
                'total_amount' => $results['total_amount']
            ]);

            return $results;
        });
    }

    /**
     * Create invoice from recurring billing template
     */
    protected function createRecurringInvoice($recurringInvoice, Carbon $billingDate): Invoice
    {
        $invoice = $this->create([
            'client_id' => $recurringInvoice->client_id,
            'number' => $this->generateInvoiceNumber(),
            'scope' => $recurringInvoice->description,
            'status' => 'Draft',
            'issue_date' => $billingDate->format('Y-m-d'),
            'due_date' => $billingDate->copy()->addDays($recurringInvoice->payment_terms ?? 30)->format('Y-m-d'),
            'currency' => $recurringInvoice->currency ?? 'USD',
            'recurring_invoice_id' => $recurringInvoice->id,
            'notes' => $recurringInvoice->notes,
        ]);

        // Create invoice items from recurring template
        foreach ($recurringInvoice->items as $templateItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $templateItem->description,
                'quantity' => $templateItem->quantity,
                'unit_price' => $templateItem->unit_price,
                'tax_rate' => $templateItem->tax_rate,
                'category_id' => $templateItem->category_id,
                'product_id' => $templateItem->product_id,
                'service_id' => $templateItem->service_id,
            ]);
        }

        // Recalculate totals
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Generate usage-based billing for a client
     */
    public function generateUsageBilling(
        Client $client,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $usageData
    ): Invoice {
        return DB::transaction(function () use ($client, $periodStart, $periodEnd, $usageData) {
            $invoice = $this->create([
                'client_id' => $client->id,
                'number' => $this->generateInvoiceNumber(),
                'scope' => "Usage billing for period {$periodStart->format('M Y')}",
                'status' => 'Draft',
                'issue_date' => Carbon::today()->format('Y-m-d'),
                'due_date' => Carbon::today()->addDays($client->net_terms ?? 30)->format('Y-m-d'),
                'currency' => $client->currency_code ?? 'USD',
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
            ]);

            foreach ($usageData as $usage) {
                $pricing = $this->pricingService->calculateUsagePricing(
                    $usage['product_id'],
                    $usage['quantity'],
                    $client->id
                );

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $usage['product_id'],
                    'description' => $usage['description'],
                    'quantity' => $usage['quantity'],
                    'unit_price' => $pricing['unit_price'],
                    'tax_rate' => $pricing['tax_rate'] ?? 0,
                ]);
            }

            $invoice->calculateTotals();
            
            $this->logActivity($invoice, 'usage_billing_generated', [
                'client_id' => $client->id,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'usage_items' => count($usageData)
            ]);

            return $invoice;
        });
    }

    /**
     * Apply billing rules and discounts
     */
    public function applyBillingRules(Invoice $invoice): Invoice
    {
        $client = $invoice->client;
        
        // Apply volume discounts
        if ($client->volume_discount_rate > 0) {
            $this->applyVolumeDiscount($invoice, $client->volume_discount_rate);
        }

        // Apply loyalty discounts
        if ($this->isEligibleForLoyaltyDiscount($client)) {
            $this->applyLoyaltyDiscount($invoice);
        }

        // Apply early payment discounts
        if ($client->early_payment_discount > 0) {
            $this->applyEarlyPaymentDiscount($invoice, $client->early_payment_discount);
        }

        $invoice->calculateTotals();
        
        return $invoice;
    }

    /**
     * Get billing analytics for a date range
     */
    public function getBillingAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $companyId = Auth::user()->company_id;
        
        $invoices = $this->buildBaseQuery()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['client', 'items'])
            ->get();

        return [
            'total_invoiced' => $invoices->sum('total'),
            'total_paid' => $invoices->where('status', 'Paid')->sum('total'),
            'total_outstanding' => $invoices->whereIn('status', ['Sent', 'Viewed'])->sum('total'),
            'invoice_count' => $invoices->count(),
            'average_invoice_value' => $invoices->avg('total'),
            'billing_by_client' => $invoices->groupBy('client_id')->map(function ($clientInvoices) {
                return [
                    'client_name' => $clientInvoices->first()->client->name,
                    'total_invoiced' => $clientInvoices->sum('total'),
                    'invoice_count' => $clientInvoices->count(),
                ];
            }),
            'revenue_by_month' => $invoices->groupBy(function ($invoice) {
                return Carbon::parse($invoice->issue_date)->format('Y-m');
            })->map(function ($monthInvoices) {
                return $monthInvoices->sum('total');
            }),
        ];
    }

    // Helper methods

    protected function getNextBillingDate(Carbon $currentDate, string $billingCycle): Carbon
    {
        switch ($billingCycle) {
            case 'weekly':
                return $currentDate->copy()->addWeek();
            case 'monthly':
                return $currentDate->copy()->addMonth();
            case 'quarterly':
                return $currentDate->copy()->addMonths(3);
            case 'yearly':
                return $currentDate->copy()->addYear();
            default:
                return $currentDate->copy()->addMonth();
        }
    }

    protected function generateBillingDescription(Product $product, Carbon $billingDate): string
    {
        return "{$product->name} - {$billingDate->format('M Y')}";
    }

    protected function generateInvoiceNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $year = Carbon::now()->year;
        
        $lastInvoice = Invoice::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? 
            intval(substr($lastInvoice->number, -4)) + 1 : 
            1;

        return sprintf('INV-%s-%04d', $year, $nextNumber);
    }

    protected function applyVolumeDiscount(Invoice $invoice, float $discountRate): void
    {
        $discountAmount = $invoice->subtotal * ($discountRate / 100);
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Volume Discount',
            'quantity' => 1,
            'unit_price' => -$discountAmount,
            'tax_rate' => 0,
        ]);
    }

    protected function isEligibleForLoyaltyDiscount(Client $client): bool
    {
        $monthsAsClient = Carbon::parse($client->created_at)->diffInMonths(Carbon::now());
        return $monthsAsClient >= 12; // 1 year loyalty
    }

    protected function applyLoyaltyDiscount(Invoice $invoice): void
    {
        $discountAmount = $invoice->subtotal * 0.05; // 5% loyalty discount
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Loyalty Discount (5%)',
            'quantity' => 1,
            'unit_price' => -$discountAmount,
            'tax_rate' => 0,
        ]);
    }

    protected function applyEarlyPaymentDiscount(Invoice $invoice, float $discountRate): void
    {
        $discountAmount = $invoice->subtotal * ($discountRate / 100);
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Early Payment Discount ({$discountRate}%)",
            'quantity' => 1,
            'unit_price' => -$discountAmount,
            'tax_rate' => 0,
        ]);
    }
}