<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Recurring;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * RecurringBillingService
 * 
 * Enterprise-grade recurring billing service with comprehensive VoIP features including
 * automated invoice generation, usage-based billing, tiered pricing calculations,
 * proration handling, contract escalations, and VoIP tax integration.
 */
class RecurringBillingService
{
    protected $voipTaxService;
    protected $voipUsageService;
    protected $voipTieredPricingService;
    protected $emailService;

    public function __construct(
        VoIPTaxService $voipTaxService,
        VoIPUsageService $voipUsageService,
        VoIPTieredPricingService $voipTieredPricingService,
        EmailService $emailService
    ) {
        $this->voipTaxService = $voipTaxService;
        $this->voipUsageService = $voipUsageService;
        $this->voipTieredPricingService = $voipTieredPricingService;
        $this->emailService = $emailService;
    }

    /**
     * Create a new recurring billing record
     */
    public function createRecurring(Client $client, array $data): Recurring
    {
        return DB::transaction(function () use ($client, $data) {
            $recurring = Recurring::create(array_merge($data, [
                'company_id' => Auth::user()->company_id,
                'client_id' => $client->id,
            ]));

            // Create items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $this->createRecurringItems($recurring, $data['items']);
            }

            // If created from quote, copy quote items
            if (isset($data['quote_id']) && $data['quote_id']) {
                $quote = Quote::with('items')->findOrFail($data['quote_id']);
                $this->copyQuoteItemsToRecurring($quote, $recurring);
            }

            // Calculate initial totals
            $recurring->calculateTotals();

            // Set up VoIP configuration if applicable
            if ($this->hasVoIPServices($data)) {
                $this->setupVoIPConfiguration($recurring, $data);
            }

            Log::info('Recurring billing created', [
                'recurring_id' => $recurring->id,
                'client_id' => $client->id,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency
            ]);

            return $recurring;
        });
    }

    /**
     * Update an existing recurring billing record
     */
    public function updateRecurring(Recurring $recurring, array $data): Recurring
    {
        return DB::transaction(function () use ($recurring, $data) {
            // Store original data for comparison
            $originalAmount = $recurring->amount;
            $originalNextDate = $recurring->next_date;

            // Update recurring billing record
            $recurring->update($data);

            // Update items if provided
            if (isset($data['items'])) {
                $this->updateRecurringItems($recurring, $data['items']);
            }

            // Recalculate totals
            $recurring->calculateTotals();

            // Handle proration for mid-cycle changes if enabled
            if ($recurring->proration_enabled && $this->requiresProration($originalAmount, $recurring->amount)) {
                $this->createProrationAdjustment($recurring, $originalAmount, $recurring->amount);
            }

            // Update VoIP configuration if applicable
            if (isset($data['voip_config']) || isset($data['pricing_model']) || isset($data['service_tiers'])) {
                $this->updateVoIPConfiguration($recurring, $data);
            }

            Log::info('Recurring billing updated', [
                'recurring_id' => $recurring->id,
                'original_amount' => $originalAmount,
                'new_amount' => $recurring->amount
            ]);

            return $recurring;
        });
    }

    /**
     * Generate invoice from recurring billing
     */
    public function generateInvoiceFromRecurring(Recurring $recurring): Invoice
    {
        return DB::transaction(function () use ($recurring) {
            // Check if recurring is ready for invoice generation
            if (!$this->isReadyForInvoiceGeneration($recurring)) {
                throw new \Exception('Recurring billing is not ready for invoice generation');
            }

            // Apply contract escalation if due
            if ($recurring->isEscalationDue()) {
                $recurring->applyContractEscalation();
                $recurring->refresh();
            }

            // Generate the invoice
            $invoice = $recurring->generateInvoice();

            // Process additional VoIP-specific logic
            if ($recurring->hasVoIPServices()) {
                $this->processVoIPInvoiceGeneration($recurring, $invoice);
            }

            // Send email if configured
            if ($recurring->email_invoice && $recurring->auto_invoice_generation) {
                try {
                    $this->emailService->sendInvoiceEmail($invoice);
                } catch (\Exception $e) {
                    Log::warning('Failed to send recurring invoice email', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $invoice;
        });
    }

    /**
     * Bulk generate invoices for multiple recurring billings
     */
    public function bulkGenerateInvoices(array $params): array
    {
        $results = ['successful' => [], 'failed' => []];
        
        $query = Recurring::where('company_id', Auth::user()->company_id);

        // Apply filters
        if (isset($params['client_ids']) && !empty($params['client_ids'])) {
            $query->whereIn('client_id', $params['client_ids']);
        }

        if (isset($params['frequency'])) {
            $query->where('frequency', $params['frequency']);
        }

        if (isset($params['billing_type'])) {
            $query->where('billing_type', $params['billing_type']);
        }

        // Get due recurring billings or all if force_generate is true
        if ($params['force_generate'] ?? false) {
            $query->where('status', true);
        } else {
            $query->due();
        }

        $recurringBillings = $query->with(['client', 'items'])->get();

        foreach ($recurringBillings as $recurring) {
            try {
                $invoice = $this->generateInvoiceFromRecurring($recurring);
                $results['successful'][] = [
                    'recurring_id' => $recurring->id,
                    'invoice_id' => $invoice->id,
                    'client_name' => $recurring->client->name,
                    'amount' => $invoice->amount
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'recurring_id' => $recurring->id,
                    'client_name' => $recurring->client->name ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                
                Log::error('Bulk invoice generation failed for recurring', [
                    'recurring_id' => $recurring->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk invoice generation completed', [
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'total_processed' => count($recurringBillings)
        ]);

        return $results;
    }

    /**
     * Preview an upcoming invoice without generating it
     */
    public function previewInvoice(Recurring $recurring): array
    {
        // Create a temporary invoice preview
        $previewData = [
            'recurring_id' => $recurring->id,
            'client' => $recurring->client,
            'billing_period' => [
                'start' => $recurring->next_date->copy()->subMonth(),
                'end' => $recurring->next_date
            ],
            'items' => [],
            'totals' => []
        ];

        // Add base recurring items
        foreach ($recurring->items as $item) {
            $previewData['items'][] = [
                'type' => 'recurring',
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->quantity * $item->price,
                'discount' => $item->discount ?? 0
            ];
        }

        // Calculate usage charges for VoIP services
        if ($recurring->billing_type === Recurring::BILLING_TYPE_USAGE_BASED || 
            $recurring->billing_type === Recurring::BILLING_TYPE_HYBRID) {
            
            $usageCharges = $recurring->calculateUsageCharges();
            
            if ($usageCharges['total'] > 0) {
                $previewData['items'][] = [
                    'type' => 'usage',
                    'name' => 'Usage Charges',
                    'description' => 'Overage charges for billing period',
                    'quantity' => 1,
                    'price' => $usageCharges['total'],
                    'subtotal' => $usageCharges['total'],
                    'discount' => 0,
                    'usage_breakdown' => $usageCharges['breakdown']
                ];
            }
        }

        // Add proration adjustments
        $prorationAdjustments = $recurring->metadata['proration_adjustments'] ?? [];
        foreach ($prorationAdjustments as $adjustment) {
            if ($adjustment['status'] === 'pending') {
                $previewData['items'][] = [
                    'type' => 'proration',
                    'name' => 'Proration Adjustment',
                    'description' => $adjustment['description'],
                    'quantity' => 1,
                    'price' => $adjustment['prorated_amount'],
                    'subtotal' => $adjustment['prorated_amount'],
                    'discount' => 0
                ];
            }
        }

        // Calculate totals
        $subtotal = collect($previewData['items'])->sum('subtotal');
        $discount = $recurring->discount_type === Recurring::DISCOUNT_TYPE_PERCENTAGE 
            ? ($subtotal * $recurring->discount_amount) / 100 
            : $recurring->discount_amount;

        // Calculate taxes if VoIP services are present
        $taxAmount = 0;
        if ($recurring->hasVoIPServices()) {
            // TODO: Calculate VoIP taxes for preview
            // This would use the VoIPTaxService to calculate taxes
        }

        $previewData['totals'] = [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_amount' => $taxAmount,
            'total' => $subtotal - $discount + $taxAmount
        ];

        return $previewData;
    }

    /**
     * Create recurring from approved quote
     */
    public function createRecurringFromQuote(Quote $quote, array $recurringData): Recurring
    {
        return DB::transaction(function () use ($quote, $recurringData) {
            if (!$quote->isAccepted()) {
                throw new \Exception('Quote must be accepted before creating recurring billing');
            }

            $recurringData = array_merge($recurringData, [
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'currency_code' => $quote->currency_code,
                'voip_config' => $quote->voip_config,
                'pricing_model' => $quote->pricing_model,
            ]);

            $recurring = $this->createRecurring($quote->client, $recurringData);

            // Mark quote as converted to recurring billing
            $quote->update([
                'metadata' => array_merge($quote->metadata ?? [], [
                    'converted_to_recurring' => $recurring->id,
                    'converted_at' => now()->toISOString()
                ])
            ]);

            Log::info('Recurring billing created from quote', [
                'quote_id' => $quote->id,
                'recurring_id' => $recurring->id
            ]);

            return $recurring;
        });
    }

    /**
     * Process automated recurring billing for all due records
     */
    public function processAutomatedBilling(): array
    {
        $results = ['processed' => 0, 'successful' => 0, 'failed' => 0, 'errors' => []];
        
        $dueRecurring = Recurring::due()
            ->where('auto_invoice_generation', true)
            ->with(['client', 'items'])
            ->get();

        foreach ($dueRecurring as $recurring) {
            $results['processed']++;
            
            try {
                $this->generateInvoiceFromRecurring($recurring);
                $results['successful']++;
                
                Log::info('Automated recurring billing processed', [
                    'recurring_id' => $recurring->id,
                    'client_id' => $recurring->client_id
                ]);
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recurring_id' => $recurring->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Automated recurring billing failed', [
                    'recurring_id' => $recurring->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Automated recurring billing batch completed', $results);
        
        return $results;
    }

    /**
     * Create recurring items from array data
     */
    protected function createRecurringItems(Recurring $recurring, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $recurring->items()->create(array_merge($itemData, [
                'recurring_id' => $recurring->id,
                'order' => $index + 1
            ]));
        }
    }

    /**
     * Update recurring items
     */
    protected function updateRecurringItems(Recurring $recurring, array $items): void
    {
        // Remove existing items
        $recurring->items()->delete();
        
        // Add new items
        $this->createRecurringItems($recurring, $items);
    }

    /**
     * Copy quote items to recurring billing
     */
    protected function copyQuoteItemsToRecurring(Quote $quote, Recurring $recurring): void
    {
        foreach ($quote->items as $item) {
            $recurring->items()->create([
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'tax_id' => $item->tax_id,
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
                'service_type' => $item->service_type,
                'line_count' => $item->line_count,
                'minutes' => $item->minutes,
                'order' => $item->order
            ]);
        }
    }
    
    /**
     * Create recurring billing record from quote
     */
    public function createFromQuote(Quote $quote, array $recurringData): Recurring
        {
            return DB::transaction(function () use ($quote, $recurringData) {
                // Create the recurring billing record
                $recurring = $this->createRecurring($quote->client, $recurringData);
    
                // Copy quote items to recurring billing
                foreach ($quote->items as $quoteItem) {
                    $recurring->items()->create([
                        'name' => $quoteItem->name,
                        'description' => $quoteItem->description,
                        'quantity' => $quoteItem->quantity,
                        'price' => $quoteItem->price,
                        'tax_id' => $quoteItem->tax_id,
                        'category_id' => $quoteItem->category_id,
                        'discount' => $quoteItem->discount,
                        'order' => $quoteItem->order,
                    ]);
                }
    
                // Copy VoIP-specific configurations if available
                if ($quote->voip_config) {
                    $recurring->update([
                        'voip_config' => $quote->voip_config,
                    ]);
                }
    
                // Set up service tiers based on quote items
                $this->setupServiceTiersFromQuote($recurring, $quote);
    
                // Apply initial escalation settings
                if (!empty($recurringData['escalation_percentage'])) {
                    $this->setupContractEscalation($recurring, $recurringData);
                }
    
                // Calculate and store initial totals
                $totals = $this->calculateTotals($recurring);
                $recurring->update([
                    'subtotal' => $totals['subtotal'],
                    'tax_amount' => $totals['tax_amount'],
                    'total_amount' => $totals['total_amount'],
                    'last_calculation_date' => now(),
                ]);
    
                // Log the creation
                Log::info('Recurring billing created from quote', [
                    'quote_id' => $quote->id,
                    'recurring_id' => $recurring->id,
                    'client_id' => $recurring->client_id,
                    'amount' => $recurring->total_amount,
                ]);
    
                return $recurring;
            });
        }
    
        /**
         * Setup service tiers based on quote items
         */
        protected function setupServiceTiersFromQuote(Recurring $recurring, Quote $quote): void
        {
            $serviceTiers = [];
            
            foreach ($quote->items as $item) {
                // Map quote items to service tiers based on VoIP service types
                $tierName = $this->mapQuoteItemToServiceTier($item);
                
                if ($tierName) {
                    $serviceTiers[] = [
                        'name' => $tierName,
                        'base_price' => $item->price,
                        'included_allowance' => $this->getDefaultAllowanceForTier($tierName),
                        'overage_rate' => $this->getDefaultOverageRate($tierName),
                        'pricing_model' => 'tiered',
                    ];
                }
            }
    
            if (!empty($serviceTiers)) {
                $recurring->update([
                    'service_tiers' => $serviceTiers,
                ]);
            }
        }
    
        /**
         * Map quote item to service tier name
         */
        protected function mapQuoteItemToServiceTier(InvoiceItem $item): ?string
        {
            $itemName = strtolower($item->name);
            
            if (str_contains($itemName, 'pbx') || str_contains($itemName, 'hosted')) {
                return 'Hosted PBX';
            }
            
            if (str_contains($itemName, 'sip') || str_contains($itemName, 'trunk')) {
                return 'SIP Trunking';
            }
            
            if (str_contains($itemName, 'line') || str_contains($itemName, 'voip')) {
                return 'VoIP Lines';
            }
            
            if (str_contains($itemName, 'unified') || str_contains($itemName, 'uc')) {
                return 'Unified Communications';
            }
            
            return null;
        }
    
        /**
         * Get default allowance for service tier
         */
        protected function getDefaultAllowanceForTier(string $tierName): array
        {
            $defaults = [
                'Hosted PBX' => ['minutes' => 1000, 'seats' => 10],
                'SIP Trunking' => ['minutes' => 5000, 'channels' => 5],
                'VoIP Lines' => ['minutes' => 500, 'lines' => 1],
                'Unified Communications' => ['minutes' => 2000, 'users' => 25],
            ];
    
            return $defaults[$tierName] ?? ['minutes' => 1000];
        }
    
        /**
         * Get default overage rate for service tier
         */
        protected function getDefaultOverageRate(string $tierName): float
        {
            $defaults = [
                'Hosted PBX' => 0.05,
                'SIP Trunking' => 0.03,
                'VoIP Lines' => 0.08,
                'Unified Communications' => 0.04,
            ];
    
            return $defaults[$tierName] ?? 0.05;
        }
    
        /**
         * Setup contract escalation settings
         */
        protected function setupContractEscalation(Recurring $recurring, array $recurringData): void
        {
            $escalationData = [
                'percentage' => $recurringData['escalation_percentage'],
                'frequency' => $recurringData['escalation_frequency'] ?? 'annual',
                'next_escalation_date' => $this->calculateNextEscalationDate(
                    $recurring->start_date,
                    $recurringData['escalation_frequency'] ?? 'annual'
                ),
                'max_escalations' => $recurringData['max_escalations'] ?? null,
                'applied_count' => 0,
            ];
    
            $recurring->contractEscalations()->create($escalationData);
        }
    
        /**
         * Calculate next escalation date
         */
        protected function calculateNextEscalationDate(Carbon $startDate, string $frequency): Carbon
        {
            return match($frequency) {
                'annual' => $startDate->copy()->addYear(),
                'biennial' => $startDate->copy()->addYears(2),
                default => $startDate->copy()->addYear(),
            };
        }

    /**
     * Check if data contains VoIP services
     */
    protected function hasVoIPServices(array $data): bool
    {
        return isset($data['voip_config']) || 
               isset($data['pricing_model']) || 
               isset($data['service_tiers']);
    }

    /**
     * Setup VoIP configuration for recurring billing
     */
    protected function setupVoIPConfiguration(Recurring $recurring, array $data): void
    {
        $voipConfig = [];

        if (isset($data['voip_config'])) {
            $voipConfig['services'] = $data['voip_config'];
        }

        if (isset($data['service_tiers'])) {
            $voipConfig['tiers'] = $data['service_tiers'];
        }

        // Set default VoIP tax settings
        $taxSettings = $data['tax_settings'] ?? [];
        $taxSettings['enable_voip_tax'] = $taxSettings['enable_voip_tax'] ?? true;
        $taxSettings['tax_method'] = $taxSettings['tax_method'] ?? 'automatic';

        $recurring->update([
            'voip_config' => $voipConfig,
            'tax_settings' => $taxSettings
        ]);
    }

    /**
     * Update VoIP configuration
     */
    protected function updateVoIPConfiguration(Recurring $recurring, array $data): void
    {
        $updates = [];

        if (isset($data['voip_config'])) {
            $updates['voip_config'] = $data['voip_config'];
        }

        if (isset($data['pricing_model'])) {
            $updates['pricing_model'] = $data['pricing_model'];
        }

        if (isset($data['service_tiers'])) {
            $updates['service_tiers'] = $data['service_tiers'];
        }

        if (isset($data['tax_settings'])) {
            $updates['tax_settings'] = $data['tax_settings'];
        }

        if (!empty($updates)) {
            $recurring->update($updates);
        }
    }

    /**
     * Check if recurring billing is ready for invoice generation
     */
    protected function isReadyForInvoiceGeneration(Recurring $recurring): bool
    {
        if (!$recurring->isActive()) {
            return false;
        }

        if ($recurring->hasReachedMaxInvoices()) {
            return false;
        }

        if ($recurring->isExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Process VoIP-specific invoice generation logic
     */
    protected function processVoIPInvoiceGeneration(Recurring $recurring, Invoice $invoice): void
    {
        // Apply VoIP taxes
        if ($recurring->tax_settings['enable_voip_tax'] ?? true) {
            $invoice->recalculateVoIPTaxes();
        }

        // Store VoIP-specific metadata
        $voipMetadata = [
            'voip_services' => $recurring->voip_config,
            'billing_period' => [
                'start' => $recurring->next_date->copy()->subMonth(),
                'end' => $recurring->next_date
            ],
            'usage_processed' => now()->toISOString()
        ];

        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'voip_billing' => $voipMetadata
            ])
        ]);
    }

    /**
     * Check if proration is required
     */
    protected function requiresProration(float $originalAmount, float $newAmount): bool
    {
        return abs($originalAmount - $newAmount) > 0.01; // Avoid floating point precision issues
    }

    /**
     * Create proration adjustment record
     */
    protected function createProrationAdjustment(Recurring $recurring, float $originalAmount, float $newAmount): void
    {
        $adjustmentAmount = $newAmount - $originalAmount;
        
        $recurring->addProrationAdjustment([
            'adjustment_type' => $adjustmentAmount > 0 ? 'addition' : 'removal',
            'description' => 'Mid-cycle service change adjustment',
            'amount' => abs($adjustmentAmount),
            'effective_date' => now()->toDateString(),
            'reason' => 'Service modification during billing cycle',
            'original_amount' => $originalAmount,
            'new_amount' => $newAmount,
            'prorated_amount' => $adjustmentAmount
        ]);
    }

    /**
     * Calculate VoIP taxes for recurring billing
     */
    public function calculateVoIPTaxes(Recurring $recurring, float $baseAmount, ?Carbon $billingDate = null): array
    {
        if (!$recurring->tax_calculation_enabled) {
            return [
                'base_amount' => $baseAmount,
                'total_tax_amount' => 0.0,
                'final_amount' => $baseAmount,
                'tax_breakdown' => [],
                'exemptions_applied' => []
            ];
        }

        $billingDate = $billingDate ?? now();
        $client = $recurring->client;
        
        // Determine service type from recurring service configuration
        $serviceType = $this->mapRecurringToVoIPServiceType($recurring);
        
        // Get client service address
        $serviceAddress = $this->getClientServiceAddress($client);
        
        // Initialize VoIP tax service for the company
        $voipTaxService = new VoIPTaxService($recurring->company_id, [
            'cache_ttl' => 3600,
            'enable_caching' => true,
            'round_precision' => 4
        ]);

        $taxParams = [
            'amount' => $baseAmount,
            'service_type' => $serviceType,
            'service_address' => $serviceAddress,
            'client_id' => $client->id,
            'calculation_date' => $billingDate->toISOString(),
            'line_count' => $this->getLineCount($recurring),
            'minutes' => $this->getEstimatedMinutes($recurring, $billingDate),
        ];

        try {
            $taxCalculation = $voipTaxService->calculateTaxes($taxParams);
            
            Log::info('VoIP taxes calculated for recurring billing', [
                'recurring_id' => $recurring->id,
                'base_amount' => $baseAmount,
                'total_tax' => $taxCalculation['total_tax_amount'],
                'service_type' => $serviceType
            ]);

            return $taxCalculation;
        } catch (\Exception $e) {
            Log::error('VoIP tax calculation failed for recurring billing', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'base_amount' => $baseAmount
            ]);

            // Return no tax calculation on error
            return [
                'base_amount' => $baseAmount,
                'total_tax_amount' => 0.0,
                'final_amount' => $baseAmount,
                'tax_breakdown' => [],
                'exemptions_applied' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Apply VoIP tax calculation to invoice generation
     */
    protected function applyVoIPTaxesToInvoice(Invoice $invoice, Recurring $recurring, array $invoiceItems): Invoice
    {
        if (!$recurring->tax_calculation_enabled) {
            return $invoice;
        }

        $totalTaxAmount = 0.0;
        $allTaxBreakdown = [];

        foreach ($invoiceItems as $item) {
            $taxCalculation = $this->calculateVoIPTaxes($recurring, $item['amount']);
            
            if ($taxCalculation['total_tax_amount'] > 0) {
                $totalTaxAmount += $taxCalculation['total_tax_amount'];
                $allTaxBreakdown[] = [
                    'item_description' => $item['description'],
                    'item_amount' => $item['amount'],
                    'tax_calculation' => $taxCalculation
                ];

                // Record exemption usage if any exemptions were applied
                if (!empty($taxCalculation['exemptions_applied'])) {
                    $this->recordVoIPTaxExemptionUsage(
                        $taxCalculation['exemptions_applied'], 
                        $invoice->id
                    );
                }
            }
        }

        // Update invoice with tax information
        if ($totalTaxAmount > 0) {
            $invoice->update([
                'tax_amount' => $totalTaxAmount,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'voip_tax_breakdown' => $allTaxBreakdown,
                    'tax_calculation_date' => now()->toISOString()
                ])
            ]);

            // Recalculate invoice totals
            $invoice->calculateTotals();
        }

        return $invoice;
    }

    /**
     * Record VoIP tax exemption usage for audit purposes
     */
    protected function recordVoIPTaxExemptionUsage(array $exemptionsApplied, int $invoiceId): void
    {
        $voipTaxService = new VoIPTaxService(Auth::user()->company_id);
        $voipTaxService->recordExemptionUsage($exemptionsApplied, $invoiceId);
    }

    /**
     * Map recurring service configuration to VoIP service type
     */
    protected function mapRecurringToVoIPServiceType(Recurring $recurring): string
    {
        $serviceTypeMapping = [
            'hosted_pbx' => VoIPTaxService::SERVICE_VOIP_FIXED,
            'sip_trunking' => VoIPTaxService::SERVICE_VOIP_FIXED,
            'did_numbers' => VoIPTaxService::SERVICE_LOCAL,
            'long_distance' => VoIPTaxService::SERVICE_LONG_DISTANCE,
            'international' => VoIPTaxService::SERVICE_INTERNATIONAL,
            'local_calling' => VoIPTaxService::SERVICE_LOCAL,
            'toll_free' => VoIPTaxService::SERVICE_LOCAL,
            'unified_communications' => VoIPTaxService::SERVICE_VOIP_FIXED,
        ];

        return $serviceTypeMapping[$recurring->service_type] ?? VoIPTaxService::SERVICE_VOIP_FIXED;
    }

    /**
     * Get client service address for tax jurisdiction determination
     */
    protected function getClientServiceAddress(Client $client): array
    {
        // Try to get service address, fall back to billing address
        $address = $client->service_address ?? $client->address ?? [];
        
        if (is_string($address)) {
            // Parse string address into components
            return $this->parseAddressString($address);
        }

        return $address;
    }

    /**
     * Parse address string into components
     */
    protected function parseAddressString(string $address): array
    {
        // Basic address parsing - in production this would be more sophisticated
        $parts = explode(',', $address);
        $parsed = ['full_address' => $address];

        if (count($parts) >= 3) {
            $parsed['street'] = trim($parts[0]);
            $parsed['city'] = trim($parts[1]);
            
            // Try to extract state and zip from last part
            $lastPart = trim($parts[2]);
            if (preg_match('/^(.+)\s+(\d{5}(-\d{4})?)$/', $lastPart, $matches)) {
                $parsed['state'] = trim($matches[1]);
                $parsed['zip_code'] = $matches[2];
            } else {
                $parsed['state'] = $lastPart;
            }
        }

        return $parsed;
    }

    /**
     * Get line count for tax calculation
     */
    protected function getLineCount(Recurring $recurring): int
    {
        // Extract line count from service configuration or metadata
        if (isset($recurring->metadata['line_count'])) {
            return (int) $recurring->metadata['line_count'];
        }

        // For service tiers, sum up allowances that represent lines
        $lineCount = 0;
        foreach ($recurring->service_tiers ?? [] as $tier) {
            if (in_array($tier['service_type'], ['extensions', 'lines', 'seats'])) {
                $lineCount += (int) ($tier['monthly_allowance'] ?? 1);
            }
        }

        return max(1, $lineCount); // At least 1 line
    }

    /**
     * Get estimated minutes for usage-based tax calculations
     */
    protected function getEstimatedMinutes(Recurring $recurring, Carbon $billingDate): int
    {
        if (!in_array($recurring->billing_type, ['usage_based', 'hybrid'])) {
            return 0;
        }

        // Get actual usage data for the billing period if available
        $startDate = $this->calculateBillingPeriodStart($recurring, $billingDate);
        $usageSummary = $this->voipUsageService->getUsageSummary(
            $recurring->client_id, 
            $startDate, 
            $billingDate
        );

        if ($usageSummary['total_usage'] > 0) {
            return (int) $usageSummary['total_usage'];
        }

        // Estimate based on service tier allowances
        $estimatedMinutes = 0;
        foreach ($recurring->service_tiers ?? [] as $tier) {
            if (in_array($tier['service_type'], ['minutes', 'calling', 'usage'])) {
                $estimatedMinutes += (int) ($tier['monthly_allowance'] ?? 0);
            }
        }

        return $estimatedMinutes;
    }

    /**
     * Calculate billing period start date
     */
    protected function calculateBillingPeriodStart(Recurring $recurring, Carbon $billingDate): Carbon
    {
        switch ($recurring->billing_cycle) {
            case 'weekly':
                return $billingDate->copy()->subWeek();
            case 'bi_weekly':
                return $billingDate->copy()->subWeeks(2);
            case 'monthly':
                return $billingDate->copy()->subMonth();
            case 'quarterly':
                return $billingDate->copy()->subQuarter();
            case 'semi_annually':
                return $billingDate->copy()->subMonths(6);
            case 'annually':
                return $billingDate->copy()->subYear();
            default:
                return $billingDate->copy()->subMonth();
        }
    }

    /**
     * Get VoIP tax summary for a set of recurring billing records
     */
    public function getVoIPTaxSummary(array $recurringIds, ?Carbon $calculationDate = null): array
    {
        $calculationDate = $calculationDate ?? now();
        $summary = [
            'total_base_amount' => 0.0,
            'total_tax_amount' => 0.0,
            'total_final_amount' => 0.0,
            'federal_taxes' => 0.0,
            'state_taxes' => 0.0,
            'local_taxes' => 0.0,
            'exemptions_total' => 0.0,
            'by_service_type' => [],
            'calculations' => []
        ];

        $recurring = Recurring::whereIn('id', $recurringIds)
            ->with('client')
            ->get();

        foreach ($recurring as $record) {
            if ($record->tax_calculation_enabled) {
                $taxCalculation = $this->calculateVoIPTaxes($record, $record->amount, $calculationDate);
                
                $summary['total_base_amount'] += $taxCalculation['base_amount'];
                $summary['total_tax_amount'] += $taxCalculation['total_tax_amount'];
                $summary['total_final_amount'] += $taxCalculation['final_amount'];

                // Aggregate by tax level
                foreach ($taxCalculation['federal_taxes'] ?? [] as $tax) {
                    $summary['federal_taxes'] += $tax['tax_amount'];
                }
                foreach ($taxCalculation['state_taxes'] ?? [] as $tax) {
                    $summary['state_taxes'] += $tax['tax_amount'];
                }
                foreach ($taxCalculation['local_taxes'] ?? [] as $tax) {
                    $summary['local_taxes'] += $tax['tax_amount'];
                }

                // Track by service type
                $serviceType = $record->service_type;
                if (!isset($summary['by_service_type'][$serviceType])) {
                    $summary['by_service_type'][$serviceType] = [
                        'base_amount' => 0.0,
                        'tax_amount' => 0.0,
                        'count' => 0
                    ];
                }
                $summary['by_service_type'][$serviceType]['base_amount'] += $taxCalculation['base_amount'];
                $summary['by_service_type'][$serviceType]['tax_amount'] += $taxCalculation['total_tax_amount'];
                $summary['by_service_type'][$serviceType]['count']++;

                $summary['calculations'][] = array_merge($taxCalculation, [
                    'recurring_id' => $record->id,
                    'client_name' => $record->client->name,
                    'service_name' => $record->name
                ]);
            }
        }

        return $summary;
    }
}