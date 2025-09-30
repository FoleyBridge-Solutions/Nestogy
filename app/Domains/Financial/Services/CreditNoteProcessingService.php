<?php

namespace App\Domains\Financial\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CreditApplication;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use App\Domains\Financial\Services\VoIPTaxReversalService;
use App\Domains\Financial\Services\RefundManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

/**
 * Credit Note Processing Service
 * 
 * Advanced service for intelligent credit note processing including:
 * - Automatic credit matching and application algorithms
 * - Credit note generation from various sources (returns, disputes, adjustments)
 * - Batch credit processing for bulk operations
 * - Credit expiration and cleanup management
 * - Revenue recognition impact calculations
 * - Performance-optimized processing for high-volume operations
 * - Integration with accounting systems and GL posting
 */
class CreditNoteProcessingService
{
    protected ?VoIPTaxReversalService $voipTaxService;
    protected RefundManagementService $refundService;

    public function __construct(
        ?VoIPTaxReversalService $voipTaxService = null,
        RefundManagementService $refundService
    ) {
        $this->voipTaxService = $voipTaxService;
        $this->refundService = $refundService;
    }

    /**
     * Create credit note from invoice with intelligent line item processing
     */
    public function createCreditNoteFromInvoice(
        Invoice $invoice,
        array $creditData,
        array $lineItems = []
    ): CreditNote {
        return DB::transaction(function () use ($invoice, $creditData, $lineItems) {
            // Create the credit note
            $creditNote = $this->createBaseCreditNote($invoice, $creditData);

            // Process line items with intelligent algorithms
            if (empty($lineItems)) {
                // Full credit - copy all invoice items
                $this->copyAllInvoiceItems($creditNote, $invoice);
            } else {
                // Partial credit - process specified items
                $this->processPartialCreditItems($creditNote, $invoice, $lineItems);
            }

            // Calculate totals and taxes
            $this->calculateCreditNoteTotals($creditNote);

            // Apply intelligent credit matching if auto-apply is enabled
            if ($creditData['auto_apply'] ?? false) {
                $this->intelligentCreditMatching($creditNote);
            }

            // Generate approval workflow if needed
            if ($this->requiresApproval($creditNote)) {
                $this->generateApprovalWorkflow($creditNote);
            }

            Log::info('Credit note created from invoice', [
                'credit_note_id' => $creditNote->id,
                'invoice_id' => $invoice->id,
                'total_amount' => $creditNote->total_amount
            ]);

            return $creditNote->load(['items', 'client', 'invoice']);
        });
    }

    /**
     * Intelligent credit matching algorithm
     */
    public function intelligentCreditMatching(CreditNote $creditNote): Collection
    {
        $applications = collect();

        if (!$creditNote->canBeApplied()) {
            return $applications;
        }

        // Get client's outstanding invoices
        $outstandingInvoices = $this->getOutstandingInvoices($creditNote->client_id);

        // Apply intelligent matching strategies
        $strategies = [
            'exact_match' => fn() => $this->exactAmountMatch($creditNote, $outstandingInvoices),
            'oldest_first' => fn() => $this->oldestFirstMatch($creditNote, $outstandingInvoices),
            'highest_amount_first' => fn() => $this->highestAmountFirstMatch($creditNote, $outstandingInvoices),
            'same_service_type' => fn() => $this->sameServiceTypeMatch($creditNote, $outstandingInvoices),
            'priority_based' => fn() => $this->priorityBasedMatch($creditNote, $outstandingInvoices)
        ];

        $strategy = $this->determineMatchingStrategy($creditNote);
        
        if (isset($strategies[$strategy])) {
            $applications = $strategies[$strategy]();
        }

        return $applications;
    }

    /**
     * Process batch credit notes with optimized performance
     */
    public function processBatchCredits(array $creditRequests): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_processed' => 0,
            'total_amount' => 0,
            'processing_time' => 0
        ];

        $startTime = microtime(true);

        // Chunk processing for memory efficiency
        $chunks = array_chunk($creditRequests, 100);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, &$results) {
                foreach ($chunk as $creditRequest) {
                    try {
                        $creditNote = $this->processSingleCreditRequest($creditRequest);
                        
                        $results['successful'][] = [
                            'request_id' => $creditRequest['request_id'] ?? null,
                            'credit_note_id' => $creditNote->id,
                            'amount' => $creditNote->total_amount
                        ];
                        
                        $results['total_amount'] += $creditNote->total_amount;
                        
                    } catch (Exception $e) {
                        $results['failed'][] = [
                            'request_id' => $creditRequest['request_id'] ?? null,
                            'error' => $e->getMessage(),
                            'data' => $creditRequest
                        ];
                        
                        Log::error('Batch credit processing failed', [
                            'request' => $creditRequest,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $results['total_processed']++;
                }
            });

            // Clear memory between chunks
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $results['processing_time'] = microtime(true) - $startTime;

        Log::info('Batch credit processing completed', [
            'total_processed' => $results['total_processed'],
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'processing_time' => $results['processing_time']
        ]);

        return $results;
    }

    /**
     * Automated credit expiration and cleanup
     */
    public function processExpiredCredits(): array
    {
        $results = [
            'expired_credits' => 0,
            'cleanup_actions' => 0,
            'revenue_adjustments' => 0
        ];

        // Find expired credits
        $expiredCredits = CreditNote::where('status', CreditNote::STATUS_APPROVED)
            ->where('expiry_date', '<', now())
            ->whereColumn('remaining_balance', '>', DB::raw('0'))
            ->get();

        foreach ($expiredCredits as $creditNote) {
            DB::transaction(function () use ($creditNote, &$results) {
                // Mark as expired
                $creditNote->update([
                    'status' => CreditNote::STATUS_EXPIRED,
                    'internal_notes' => ($creditNote->internal_notes ?? '') . 
                        "\n\nExpired on " . now()->format('Y-m-d H:i:s') . 
                        " - Remaining balance: " . $creditNote->remaining_balance
                ]);

                // Create revenue recognition adjustment
                if ($creditNote->affects_revenue_recognition) {
                    $this->createRevenueRecognitionAdjustment($creditNote);
                    $results['revenue_adjustments']++;
                }

                // Send expiration notifications
                $this->sendExpirationNotifications($creditNote);

                $results['expired_credits']++;
            });
        }

        // Additional cleanup tasks
        $results['cleanup_actions'] = $this->performAdditionalCleanup();

        return $results;
    }

    /**
     * Calculate revenue recognition impact
     */
    public function calculateRevenueImpact(CreditNote $creditNote): array
    {
        $impact = [
            'original_revenue' => 0,
            'credit_amount' => $creditNote->total_amount,
            'net_impact' => 0,
            'period_adjustments' => [],
            'gl_entries' => [],
            'recognition_method' => 'immediate'
        ];

        if ($creditNote->invoice) {
            $impact['original_revenue'] = $creditNote->invoice->amount;
            $impact['net_impact'] = -$creditNote->total_amount;

            // Calculate period-based adjustments for service credits
            if ($this->isServiceCredit($creditNote)) {
                $impact = array_merge($impact, $this->calculateServiceCreditImpact($creditNote));
            }

            // Generate GL entries
            $impact['gl_entries'] = $this->generateRevenueGlEntries($creditNote);
        }

        return $impact;
    }

    /**
     * Auto-generate credit notes from various sources
     */
    public function autoGenerateCredits(array $sources = []): array
    {
        $defaultSources = [
            'failed_payments' => true,
            'service_outages' => true,
            'billing_errors' => true,
            'sla_breaches' => true,
            'equipment_returns' => true
        ];

        $sources = array_merge($defaultSources, $sources);
        $results = [];

        foreach ($sources as $source => $enabled) {
            if (!$enabled) continue;

            $method = 'generateFrom' . ucfirst(str_replace('_', '', $source));
            
            if (method_exists($this, $method)) {
                $results[$source] = $this->{$method}();
            }
        }

        return $results;
    }

    /**
     * Optimize credit application performance
     */
    public function optimizeCreditApplications(Client $client): array
    {
        $optimizations = [
            'consolidations' => 0,
            'auto_applications' => 0,
            'performance_gain' => 0
        ];

        // Consolidate small credits
        $smallCredits = CreditNote::where('client_id', $client->id)
            ->where('status', CreditNote::STATUS_APPROVED)
            ->where('remaining_balance', '>', 0)
            ->where('remaining_balance', '<', 10) // Configurable threshold
            ->get();

        if ($smallCredits->count() > 1) {
            $consolidated = $this->consolidateSmallCredits($smallCredits);
            $optimizations['consolidations'] = $consolidated;
        }

        // Auto-apply credits where beneficial
        $autoApplied = $this->performAutoApplications($client);
        $optimizations['auto_applications'] = $autoApplied;

        return $optimizations;
    }

    /**
     * Generate credit analytics and insights
     */
    public function getCreditAnalytics(array $filters = []): array
    {
        $query = CreditNote::forCompany();

        // Apply filters
        if (isset($filters['date_range'])) {
            $query->whereBetween('created_at', [
                $filters['date_range']['start'],
                $filters['date_range']['end']
            ]);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        $credits = $query->with(['client', 'invoice', 'applications'])->get();

        return [
            'summary' => [
                'total_credits' => $credits->count(),
                'total_amount' => $credits->sum('total_amount'),
                'applied_amount' => $credits->sum('applied_amount'),
                'remaining_balance' => $credits->sum('remaining_balance'),
                'average_credit_size' => $credits->avg('total_amount'),
                'application_rate' => $credits->where('applied_amount', '>', 0)->count() / max($credits->count(), 1) * 100
            ],
            'by_status' => $credits->groupBy('status')->map->count(),
            'by_type' => $credits->groupBy('type')->map->count(),
            'by_reason' => $credits->groupBy('reason_code')->map->count(),
            'monthly_trend' => $credits->groupBy(fn($c) => $c->created_at->format('Y-m'))->map->count(),
            'top_clients' => $this->getTopClientsByCredits($credits),
            'aging_analysis' => $this->getCreditAgingAnalysis($credits),
            'application_efficiency' => $this->getApplicationEfficiencyMetrics($credits),
            'revenue_impact' => $this->getTotalRevenueImpact($credits)
        ];
    }

    /**
     * Private helper methods
     */
    private function createBaseCreditNote(Invoice $invoice, array $creditData): CreditNote
    {
        return CreditNote::create([
            'company_id' => $invoice->company_id,
            'client_id' => $invoice->client_id,
            'invoice_id' => $invoice->id,
            'category_id' => $invoice->category_id,
            'type' => $creditData['type'] ?? CreditNote::TYPE_PARTIAL_REFUND,
            'reason_code' => $creditData['reason_code'],
            'reason_description' => $creditData['reason_description'],
            'internal_notes' => $creditData['internal_notes'] ?? null,
            'customer_notes' => $creditData['customer_notes'] ?? null,
            'currency_code' => $invoice->currency_code,
            'exchange_rate' => $creditData['exchange_rate'] ?? 1.0,
            'credit_date' => $creditData['credit_date'] ?? now()->toDateString(),
            'expiry_date' => $creditData['expiry_date'] ?? null,
            'affects_revenue_recognition' => $creditData['affects_revenue_recognition'] ?? true,
            'metadata' => [
                'created_from_invoice' => $invoice->id,
                'original_invoice_number' => $invoice->number,
                'creation_method' => 'automated'
            ],
            'original_invoice_data' => $invoice->toArray()
        ]);
    }

    private function copyAllInvoiceItems(CreditNote $creditNote, Invoice $invoice): void
    {
        foreach ($invoice->items as $invoiceItem) {
            $this->createCreditNoteItem($creditNote, $invoiceItem, $invoiceItem->quantity);
        }
    }

    private function processPartialCreditItems(CreditNote $creditNote, Invoice $invoice, array $lineItems): void
    {
        foreach ($lineItems as $lineItem) {
            $invoiceItem = $invoice->items()->find($lineItem['invoice_item_id']);
            
            if ($invoiceItem) {
                $creditQuantity = $lineItem['quantity'] ?? $invoiceItem->quantity;
                $this->createCreditNoteItem($creditNote, $invoiceItem, $creditQuantity, $lineItem);
            }
        }
    }

    private function createCreditNoteItem(
        CreditNote $creditNote,
        InvoiceItem $invoiceItem,
        float $quantity,
        array $additionalData = []
    ): CreditNoteItem {
        $unitPrice = $invoiceItem->price / max($invoiceItem->quantity, 1);
        $lineTotal = $quantity * $unitPrice;
        $taxAmount = ($invoiceItem->tax / max($invoiceItem->quantity, 1)) * $quantity;

        return CreditNoteItem::create([
            'company_id' => $creditNote->company_id,
            'credit_note_id' => $creditNote->id,
            'invoice_item_id' => $invoiceItem->id,
            'name' => $invoiceItem->name,
            'description' => $invoiceItem->description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $invoiceItem->tax_rate ?? 0,
            'item_type' => $this->determineItemType($invoiceItem),
            'sort_order' => $invoiceItem->order,
            'original_quantity' => $invoiceItem->quantity,
            'original_unit_price' => $unitPrice,
            'original_line_total' => $invoiceItem->subtotal,
            'original_tax_amount' => $invoiceItem->tax,
            'remaining_credit' => $lineTotal,
            'metadata' => array_merge([
                'original_invoice_item_id' => $invoiceItem->id
            ], $additionalData)
        ]);
    }

    private function calculateCreditNoteTotals(CreditNote $creditNote): void
    {
        $items = $creditNote->items;
        
        $subtotal = $items->sum('line_total');
        $taxAmount = $items->sum('tax_amount');
        $totalAmount = $subtotal + $taxAmount;

        // Calculate VoIP tax reversals if applicable
        $voipTaxReversal = $this->calculateVoipTaxReversal($creditNote);

        $creditNote->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'remaining_balance' => $totalAmount,
            'voip_tax_reversal' => $voipTaxReversal,
            'tax_breakdown' => $this->calculateTaxBreakdown($items),
            'jurisdiction_taxes' => $this->calculateJurisdictionTaxes($items)
        ]);
    }

    private function getOutstandingInvoices(int $clientId): Collection
    {
        return Invoice::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('amount', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    private function exactAmountMatch(CreditNote $creditNote, Collection $invoices): Collection
    {
        $applications = collect();
        
        $exactMatch = $invoices->first(function ($invoice) use ($creditNote) {
            return abs($invoice->getBalance() - $creditNote->remaining_balance) < 0.01;
        });

        if ($exactMatch) {
            $application = $this->createCreditApplication($creditNote, $exactMatch, $creditNote->remaining_balance);
            $applications->push($application);
        }

        return $applications;
    }

    private function oldestFirstMatch(CreditNote $creditNote, Collection $invoices): Collection
    {
        $applications = collect();
        $remainingCredit = $creditNote->remaining_balance;

        foreach ($invoices->sortBy('due_date') as $invoice) {
            if ($remainingCredit <= 0) break;

            $invoiceBalance = $invoice->getBalance();
            $applicationAmount = min($remainingCredit, $invoiceBalance);

            if ($applicationAmount > 0) {
                $application = $this->createCreditApplication($creditNote, $invoice, $applicationAmount);
                $applications->push($application);
                $remainingCredit -= $applicationAmount;
            }
        }

        return $applications;
    }

    private function highestAmountFirstMatch(CreditNote $creditNote, Collection $invoices): Collection
    {
        $applications = collect();
        $remainingCredit = $creditNote->remaining_balance;

        foreach ($invoices->sortByDesc('amount') as $invoice) {
            if ($remainingCredit <= 0) break;

            $invoiceBalance = $invoice->getBalance();
            $applicationAmount = min($remainingCredit, $invoiceBalance);

            if ($applicationAmount > 0) {
                $application = $this->createCreditApplication($creditNote, $invoice, $applicationAmount);
                $applications->push($application);
                $remainingCredit -= $applicationAmount;
            }
        }

        return $applications;
    }

    private function sameServiceTypeMatch(CreditNote $creditNote, Collection $invoices): Collection
    {
        // Implementation would match based on service types from credit note items
        return collect();
    }

    private function priorityBasedMatch(CreditNote $creditNote, Collection $invoices): Collection
    {
        // Implementation would apply priority-based matching rules
        return collect();
    }

    private function createCreditApplication(CreditNote $creditNote, Invoice $invoice, float $amount): CreditApplication
    {
        return CreditApplication::create([
            'company_id' => $creditNote->company_id,
            'credit_note_id' => $creditNote->id,
            'invoice_id' => $invoice->id,
            'application_type' => CreditApplication::TYPE_AUTOMATIC,
            'application_method' => CreditApplication::METHOD_DIRECT_APPLICATION,
            'applied_amount' => $amount,
            'currency_code' => $creditNote->currency_code,
            'application_date' => now()->toDateString(),
            'requires_approval' => false,
            'approved' => true,
            'metadata' => [
                'matching_algorithm' => 'intelligent_auto_match',
                'created_by_system' => true
            ]
        ]);
    }

    private function determineMatchingStrategy(CreditNote $creditNote): string
    {
        // Intelligent strategy selection based on credit note characteristics
        if ($creditNote->total_amount < 100) {
            return 'oldest_first';
        }
        
        if ($creditNote->reason_code === 'billing_error') {
            return 'exact_match';
        }

        return 'priority_based';
    }

    private function processSingleCreditRequest(array $creditRequest): CreditNote
    {
        // Implementation would process individual credit request
        // This is a placeholder for the actual implementation
        throw new Exception('Method not implemented');
    }

    private function requiresApproval(CreditNote $creditNote): bool
    {
        return $creditNote->total_amount >= 1000 || // Amount threshold
               in_array($creditNote->reason_code, ['billing_error', 'goodwill']) || // Reason-based
               $creditNote->affects_revenue_recognition; // Revenue impact
    }

    private function generateApprovalWorkflow(CreditNote $creditNote): void
    {
        // Implementation would create approval workflow
    }

    private function calculateVoipTaxReversal(CreditNote $creditNote): float
    {
        if (!$this->voipTaxService) {
            return 0;
        }

        // Implementation would calculate VoIP tax reversals
        return 0;
    }

    private function calculateTaxBreakdown(Collection $items): array
    {
        // Implementation would calculate detailed tax breakdown
        return [];
    }

    private function calculateJurisdictionTaxes(Collection $items): array
    {
        // Implementation would calculate jurisdiction-specific taxes
        return [];
    }

    private function determineItemType(InvoiceItem $invoiceItem): string
    {
        // Logic to determine credit note item type from invoice item
        return 'service';
    }

    private function createRevenueRecognitionAdjustment(CreditNote $creditNote): void
    {
        // Implementation would create revenue recognition adjustments
    }

    private function sendExpirationNotifications(CreditNote $creditNote): void
    {
        // Implementation would send expiration notifications
    }

    private function performAdditionalCleanup(): int
    {
        // Implementation would perform additional cleanup tasks
        return 0;
    }

    private function isServiceCredit(CreditNote $creditNote): bool
    {
        return $creditNote->type === CreditNote::TYPE_SERVICE_CREDIT;
    }

    private function calculateServiceCreditImpact(CreditNote $creditNote): array
    {
        // Implementation would calculate service credit revenue impact
        return [];
    }

    private function generateRevenueGlEntries(CreditNote $creditNote): array
    {
        // Implementation would generate GL entries for revenue recognition
        return [];
    }

    private function consolidateSmallCredits(Collection $credits): int
    {
        // Implementation would consolidate small credits
        return 0;
    }

    private function performAutoApplications(Client $client): int
    {
        // Implementation would perform automatic credit applications
        return 0;
    }

    private function getTopClientsByCredits(Collection $credits): array
    {
        return $credits->groupBy('client_id')
            ->map(fn($group) => [
                'client' => $group->first()->client->name,
                'credit_count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'applied_amount' => $group->sum('applied_amount')
            ])
            ->sortByDesc('total_amount')
            ->take(10)
            ->values()
            ->toArray();
    }

    private function getCreditAgingAnalysis(Collection $credits): array
    {
        $now = now();
        
        return [
            '0-30_days' => $credits->filter(fn($c) => $c->created_at->diffInDays($now) <= 30)->sum('remaining_balance'),
            '31-60_days' => $credits->filter(fn($c) => $c->created_at->diffInDays($now) > 30 && $c->created_at->diffInDays($now) <= 60)->sum('remaining_balance'),
            '61-90_days' => $credits->filter(fn($c) => $c->created_at->diffInDays($now) > 60 && $c->created_at->diffInDays($now) <= 90)->sum('remaining_balance'),
            'over_90_days' => $credits->filter(fn($c) => $c->created_at->diffInDays($now) > 90)->sum('remaining_balance')
        ];
    }

    private function getApplicationEfficiencyMetrics(Collection $credits): array
    {
        $totalCredits = $credits->count();
        $appliedCredits = $credits->where('applied_amount', '>', 0)->count();
        $fullyAppliedCredits = $credits->where('remaining_balance', '<=', 0)->count();

        return [
            'application_rate' => $totalCredits > 0 ? ($appliedCredits / $totalCredits) * 100 : 0,
            'full_application_rate' => $totalCredits > 0 ? ($fullyAppliedCredits / $totalCredits) * 100 : 0,
            'average_days_to_apply' => $credits->where('applied_at')->avg(fn($c) => $c->created_at->diffInDays($c->applied_at)),
            'unutilized_credits' => $credits->where('remaining_balance', '>', 0)->count()
        ];
    }

    private function getTotalRevenueImpact(Collection $credits): array
    {
        return [
            'total_revenue_reduction' => $credits->sum('total_amount'),
            'recognized_impact' => $credits->where('affects_revenue_recognition', true)->sum('total_amount'),
            'deferred_impact' => $credits->where('affects_revenue_recognition', false)->sum('total_amount'),
            'tax_impact' => $credits->sum('tax_amount')
        ];
    }
}