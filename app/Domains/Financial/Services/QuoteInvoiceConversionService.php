<?php

namespace App\Domains\Financial\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Services\ContractGenerationService;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteInvoiceConversion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QuoteInvoiceConversionService
 *
 * Comprehensive Quote-to-Invoice conversion service with contract generation,
 * milestone-based billing, recurring invoice setup, and VoIP-specific features.
 */
class QuoteInvoiceConversionService
{
    protected $contractGenerationService;

    protected $invoiceService;

    protected $recurringInvoiceService;

    public function __construct(
        ContractGenerationService $contractGenerationService,
        InvoiceService $invoiceService,
        ?RecurringInvoiceService $recurringInvoiceService = null
    ) {
        $this->contractGenerationService = $contractGenerationService;
        $this->invoiceService = $invoiceService;
        $this->recurringInvoiceService = $recurringInvoiceService;
    }

    /**
     * Convert quote directly to invoice (simple conversion)
     */
    public function convertDirectToInvoice(
        Quote $quote,
        array $options = []
    ): QuoteInvoiceConversion {
        return DB::transaction(function () use ($quote, $options) {
            Log::info('Starting direct quote to invoice conversion', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id(),
            ]);

            // Validate quote eligibility
            $this->validateQuoteForConversion($quote);

            // Create conversion record
            $conversion = QuoteInvoiceConversion::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'conversion_type' => 'direct_invoice',
                'status' => 'processing',
                'original_quote_value' => $quote->amount,
                'currency_code' => $quote->currency_code,
                'conversion_settings' => $options,
                'tax_calculations' => $quote->getVoIPTaxBreakdown(),
                'initiated_by' => Auth::id(),
                'conversion_started_at' => now(),
            ]);

            try {
                // Create invoice from quote
                $invoice = $this->createInvoiceFromQuote($quote, $options);

                // Update conversion record
                $conversion->update([
                    'invoice_id' => $invoice->id,
                    'status' => 'completed',
                    'converted_value' => $invoice->amount,
                    'conversion_completed_at' => now(),
                    'completed_by' => Auth::id(),
                ]);

                // Update quote status
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                    'converted_invoice_id' => $invoice->id,
                ]);

                Log::info('Direct conversion completed', [
                    'quote_id' => $quote->id,
                    'invoice_id' => $invoice->id,
                    'conversion_id' => $conversion->id,
                ]);

                return $conversion->fresh();

            } catch (\Exception $e) {
                $conversion->update([
                    'status' => 'failed',
                    'error_log' => [['error' => $e->getMessage(), 'timestamp' => now()]],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Convert quote to contract with invoice generation
     */
    public function convertToContractWithInvoice(
        Quote $quote,
        array $contractData,
        array $invoiceOptions = []
    ): QuoteInvoiceConversion {
        return DB::transaction(function () use ($quote, $contractData, $invoiceOptions) {
            Log::info('Starting quote to contract with invoice conversion', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id(),
            ]);

            // Validate quote eligibility
            $this->validateQuoteForConversion($quote);

            // Create conversion record
            $conversion = QuoteInvoiceConversion::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'conversion_type' => 'contract_with_invoice',
                'status' => 'processing',
                'original_quote_value' => $quote->amount,
                'currency_code' => $quote->currency_code,
                'conversion_settings' => array_merge($contractData, $invoiceOptions),
                'tax_calculations' => $quote->getVoIPTaxBreakdown(),
                'voip_service_mapping' => $this->mapVoipServices($quote),
                'initiated_by' => Auth::id(),
                'conversion_started_at' => now(),
            ]);

            try {
                // Generate contract first
                $contractTemplate = $this->selectOptimalTemplate($quote, $contractData);
                $contract = $this->contractGenerationService->generateFromQuote(
                    $quote,
                    $contractTemplate,
                    $contractData
                );

                $conversion->update([
                    'contract_id' => $contract->id,
                    'status' => 'contract_generated',
                ]);

                // Create initial invoice if contract doesn't require signature
                if (! $this->requiresSignatureBeforeInvoicing($contract)) {
                    $invoice = $this->createInvoiceFromQuote($quote, $invoiceOptions, $contract);

                    $conversion->update([
                        'invoice_id' => $invoice->id,
                        'status' => 'completed',
                        'converted_value' => $invoice->amount,
                        'conversion_completed_at' => now(),
                        'completed_by' => Auth::id(),
                    ]);
                } else {
                    $conversion->update(['status' => 'contract_signed']);
                }

                // Update quote status
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                    'converted_invoice_id' => $conversion->invoice_id,
                ]);

                Log::info('Contract with invoice conversion completed', [
                    'quote_id' => $quote->id,
                    'contract_id' => $contract->id,
                    'invoice_id' => $conversion->invoice_id,
                    'conversion_id' => $conversion->id,
                ]);

                return $conversion->fresh();

            } catch (\Exception $e) {
                $conversion->update([
                    'status' => 'failed',
                    'error_log' => [['error' => $e->getMessage(), 'timestamp' => now()]],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Convert quote to milestone-based invoicing
     */
    public function convertToMilestoneInvoicing(
        Quote $quote,
        array $contractData,
        array $milestoneSchedule
    ): QuoteInvoiceConversion {
        return DB::transaction(function () use ($quote, $contractData, $milestoneSchedule) {
            Log::info('Starting milestone-based conversion', [
                'quote_id' => $quote->id,
                'milestones_count' => count($milestoneSchedule),
                'user_id' => Auth::id(),
            ]);

            // Validate quote eligibility
            $this->validateQuoteForConversion($quote);

            // Create conversion record
            $conversion = QuoteInvoiceConversion::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'conversion_type' => 'milestone_invoicing',
                'status' => 'processing',
                'original_quote_value' => $quote->amount,
                'currency_code' => $quote->currency_code,
                'conversion_settings' => $contractData,
                'milestone_schedule' => $milestoneSchedule,
                'tax_calculations' => $quote->getVoIPTaxBreakdown(),
                'voip_service_mapping' => $this->mapVoipServices($quote),
                'initiated_by' => Auth::id(),
                'conversion_started_at' => now(),
            ]);

            try {
                // Generate contract with milestones
                $contractTemplate = $this->selectOptimalTemplate($quote, $contractData);
                $contract = $this->contractGenerationService->generateFromQuote(
                    $quote,
                    $contractTemplate,
                    $contractData
                );

                // Create milestone-based billing schedule
                $this->createMilestoneBillingSchedule($contract, $quote, $milestoneSchedule);

                // Set up revenue recognition schedule
                $revenueSchedule = $this->calculateRevenueRecognition($quote, $milestoneSchedule);

                $conversion->update([
                    'contract_id' => $contract->id,
                    'status' => 'completed',
                    'revenue_schedule' => $revenueSchedule,
                    'conversion_completed_at' => now(),
                    'completed_by' => Auth::id(),
                ]);

                // Update quote status
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                ]);

                Log::info('Milestone-based conversion completed', [
                    'quote_id' => $quote->id,
                    'contract_id' => $contract->id,
                    'conversion_id' => $conversion->id,
                ]);

                return $conversion->fresh();

            } catch (\Exception $e) {
                $conversion->update([
                    'status' => 'failed',
                    'error_log' => [['error' => $e->getMessage(), 'timestamp' => now()]],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Convert quote to recurring invoice setup
     */
    public function convertToRecurringInvoices(
        Quote $quote,
        array $contractData,
        array $recurringOptions
    ): QuoteInvoiceConversion {
        return DB::transaction(function () use ($quote, $contractData, $recurringOptions) {
            Log::info('Starting recurring invoice conversion', [
                'quote_id' => $quote->id,
                'recurring_frequency' => $recurringOptions['frequency'] ?? 'monthly',
                'user_id' => Auth::id(),
            ]);

            // Validate quote eligibility
            $this->validateQuoteForConversion($quote);

            // Create conversion record
            $conversion = QuoteInvoiceConversion::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'conversion_type' => 'recurring_setup',
                'status' => 'processing',
                'original_quote_value' => $quote->amount,
                'currency_code' => $quote->currency_code,
                'conversion_settings' => $contractData,
                'recurring_schedule' => $recurringOptions,
                'tax_calculations' => $quote->getVoIPTaxBreakdown(),
                'voip_service_mapping' => $this->mapVoipServices($quote),
                'initiated_by' => Auth::id(),
                'conversion_started_at' => now(),
            ]);

            try {
                // Generate service contract
                $contractTemplate = $this->selectOptimalTemplate($quote, $contractData);
                $contract = $this->contractGenerationService->generateFromQuote(
                    $quote,
                    $contractTemplate,
                    $contractData
                );

                // Set up recurring invoice schedule
                $recurringInvoices = $this->setupRecurringInvoices($quote, $contract, $recurringOptions);

                // Create initial invoice if immediate billing is required
                $initialInvoice = null;
                if ($recurringOptions['create_initial_invoice'] ?? false) {
                    $initialInvoice = $this->createInitialInvoice($quote, $contract, $recurringOptions);
                }

                $conversion->update([
                    'contract_id' => $contract->id,
                    'invoice_id' => $initialInvoice?->id,
                    'status' => 'recurring_setup',
                    'converted_value' => $initialInvoice?->amount ?? 0,
                    'conversion_completed_at' => now(),
                    'completed_by' => Auth::id(),
                ]);

                // Update quote status
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                    'converted_invoice_id' => $initialInvoice?->id,
                ]);

                Log::info('Recurring invoice conversion completed', [
                    'quote_id' => $quote->id,
                    'contract_id' => $contract->id,
                    'recurring_invoices_count' => count($recurringInvoices),
                    'conversion_id' => $conversion->id,
                ]);

                return $conversion->fresh();

            } catch (\Exception $e) {
                $conversion->update([
                    'status' => 'failed',
                    'error_log' => [['error' => $e->getMessage(), 'timestamp' => now()]],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Convert quote using hybrid approach (multiple contract types)
     */
    public function convertHybrid(
        Quote $quote,
        array $conversionPlan
    ): QuoteInvoiceConversion {
        return DB::transaction(function () use ($quote, $conversionPlan) {
            Log::info('Starting hybrid conversion', [
                'quote_id' => $quote->id,
                'conversion_types' => array_keys($conversionPlan),
                'user_id' => Auth::id(),
            ]);

            // Validate quote eligibility
            $this->validateQuoteForConversion($quote);

            // Create conversion record
            $conversion = QuoteInvoiceConversion::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'conversion_type' => 'hybrid_conversion',
                'status' => 'processing',
                'original_quote_value' => $quote->amount,
                'currency_code' => $quote->currency_code,
                'conversion_settings' => $conversionPlan,
                'tax_calculations' => $quote->getVoIPTaxBreakdown(),
                'voip_service_mapping' => $this->mapVoipServices($quote),
                'initiated_by' => Auth::id(),
                'conversion_started_at' => now(),
            ]);

            try {
                $contracts = [];
                $invoices = [];
                $recurringSetups = [];

                // Process each conversion type in the plan
                foreach ($conversionPlan as $type => $config) {
                    switch ($type) {
                        case 'service_contract':
                            $contracts[] = $this->createServiceContract($quote, $config);
                            break;

                        case 'equipment_contract':
                            $contracts[] = $this->createEquipmentContract($quote, $config);
                            break;

                        case 'immediate_invoice':
                            $invoices[] = $this->createImmediateInvoice($quote, $config);
                            break;

                        case 'recurring_setup':
                            $recurringSetups[] = $this->createRecurringSetup($quote, $config);
                            break;
                    }
                }

                $totalValue = array_sum(array_map(fn ($inv) => $inv->amount, $invoices));

                $conversion->update([
                    'contract_id' => $contracts[0]->id ?? null, // Primary contract
                    'invoice_id' => $invoices[0]->id ?? null, // Primary invoice
                    'status' => 'completed',
                    'converted_value' => $totalValue,
                    'conversion_completed_at' => now(),
                    'completed_by' => Auth::id(),
                    'integration_data' => [
                        'contracts_created' => count($contracts),
                        'invoices_created' => count($invoices),
                        'recurring_setups' => count($recurringSetups),
                    ],
                ]);

                // Update quote status
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                    'converted_invoice_id' => $invoices[0]->id ?? null,
                ]);

                Log::info('Hybrid conversion completed', [
                    'quote_id' => $quote->id,
                    'contracts_created' => count($contracts),
                    'invoices_created' => count($invoices),
                    'conversion_id' => $conversion->id,
                ]);

                return $conversion->fresh();

            } catch (\Exception $e) {
                $conversion->update([
                    'status' => 'failed',
                    'error_log' => [['error' => $e->getMessage(), 'timestamp' => now()]],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Complete conversion when contract is signed
     */
    public function completeSignedContractConversion(Contract $contract): void
    {
        $conversion = QuoteInvoiceConversion::where('contract_id', $contract->id)
            ->where('status', 'contract_signed')
            ->first();

        if (! $conversion) {
            return;
        }

        DB::transaction(function () use ($conversion, $contract) {
            // Create invoice now that contract is signed
            $invoice = $this->createInvoiceFromContract($contract);

            $conversion->update([
                'invoice_id' => $invoice->id,
                'status' => 'completed',
                'converted_value' => $invoice->amount,
                'conversion_completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);

            // Update quote status
            if ($conversion->quote) {
                $conversion->quote->update([
                    'converted_invoice_id' => $invoice->id,
                ]);
            }

            Log::info('Signed contract conversion completed', [
                'contract_id' => $contract->id,
                'invoice_id' => $invoice->id,
                'conversion_id' => $conversion->id,
            ]);
        });
    }

    /**
     * Process milestone completion and generate invoice
     */
    public function processMilestoneCompletion(ContractMilestone $milestone): ?Invoice
    {
        if (! $milestone->isBillable() || ! $milestone->isCompleted()) {
            return null;
        }

        return DB::transaction(function () use ($milestone) {
            $invoice = $this->createMilestoneInvoice($milestone);

            // Update conversion record
            $conversion = QuoteInvoiceConversion::where('contract_id', $milestone->contract_id)->first();
            if ($conversion) {
                $invoiceIds = $conversion->integration_data['milestone_invoices'] ?? [];
                $invoiceIds[] = $invoice->id;

                $conversion->update([
                    'integration_data' => array_merge($conversion->integration_data ?? [], [
                        'milestone_invoices' => $invoiceIds,
                    ]),
                ]);
            }

            Log::info('Milestone invoice generated', [
                'milestone_id' => $milestone->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
            ]);

            return $invoice;
        });
    }

    /**
     * Validate quote eligibility for conversion
     */
    protected function validateQuoteForConversion(Quote $quote): void
    {
        if (! $quote->isAccepted()) {
            throw new \Exception('Quote must be accepted before conversion');
        }

        if (! $quote->isFullyApproved()) {
            throw new \Exception('Quote must be fully approved before conversion');
        }

        if ($quote->isExpired()) {
            throw new \Exception('Cannot convert expired quote');
        }
    }

    /**
     * Create invoice from quote
     */
    protected function createInvoiceFromQuote(
        Quote $quote,
        array $options = [],
        ?Contract $contract = null
    ): Invoice {
        $invoiceData = [
            'client_id' => $quote->client_id,
            'category_id' => $quote->category_id,
            'date' => $options['invoice_date'] ?? now(),
            'due_date' => $options['due_date'] ?? now()->addDays(30),
            'currency_code' => $quote->currency_code,
            'discount_amount' => $quote->getDiscountAmount(),
            'note' => $options['note'] ?? $quote->note,
            'status' => $options['status'] ?? Invoice::STATUS_DRAFT,
        ];

        // Add contract reference if provided
        if ($contract) {
            $invoiceData['contract_id'] = $contract->id;
        }

        $invoice = $this->invoiceService->createInvoice($quote->client, $invoiceData);

        // Copy items from quote to invoice
        foreach ($quote->items as $item) {
            $invoice->items()->create([
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'tax_id' => $item->tax_id,
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
                'service_type' => $item->service_type,
                'voip_tax_data' => $item->voip_tax_data,
                'line_count' => $item->line_count,
                'minutes' => $item->minutes,
            ]);
        }

        $invoice->calculateTotals();

        // Preserve VoIP tax calculations
        if ($quote->hasVoIPServices()) {
            $invoice->recalculateVoIPTaxes();
        }

        return $invoice;
    }

    /**
     * Map VoIP services for conversion tracking
     */
    protected function mapVoipServices(Quote $quote): array
    {
        $mapping = [];

        foreach ($quote->voipItems as $item) {
            $mapping[] = [
                'quote_item_id' => $item->id,
                'service_type' => $item->service_type,
                'service_name' => $item->name,
                'monthly_cost' => $item->price,
                'setup_cost' => $item->setup_cost ?? 0,
                'tax_data' => $item->voip_tax_data,
                'requires_provisioning' => $this->requiresProvisioning($item->service_type),
            ];
        }

        return $mapping;
    }

    /**
     * Select optimal contract template for quote
     */
    protected function selectOptimalTemplate(Quote $quote, array $contractData): \App\Domains\Contract\Models\ContractTemplate
    {
        $templateType = $contractData['template_type'] ?? $this->determineOptimalTemplateType($quote);

        return \App\Domains\Contract\Models\ContractTemplate::where('company_id', $quote->company_id)
            ->where('template_type', $templateType)
            ->where('status', 'active')
            ->where('is_default', true)
            ->firstOrFail();
    }

    /**
     * Determine optimal template type based on quote
     */
    protected function determineOptimalTemplateType(Quote $quote): string
    {
        if ($quote->hasVoIPServices()) {
            $voipItems = $quote->voipItems;

            if ($voipItems->contains('service_type', 'hosted_pbx')) {
                return 'service_agreement';
            }

            if ($voipItems->contains('service_type', 'equipment_lease')) {
                return 'equipment_lease';
            }
        }

        return 'service_agreement'; // Default
    }

    /**
     * Check if contract requires signature before invoicing
     */
    protected function requiresSignatureBeforeInvoicing(Contract $contract): bool
    {
        return $contract->signature_status === Contract::SIGNATURE_PENDING &&
               $contract->signatures()->where('is_required', true)->exists();
    }

    /**
     * Additional helper methods would be implemented here...
     */
    protected function createMilestoneBillingSchedule(Contract $contract, Quote $quote, array $milestoneSchedule): void
    {
        // Implementation for milestone billing schedule
    }

    protected function calculateRevenueRecognition(Quote $quote, array $milestoneSchedule): array
    {
        return []; // Implementation for revenue recognition
    }

    protected function setupRecurringInvoices(Quote $quote, Contract $contract, array $recurringOptions): array
    {
        return []; // Implementation for recurring invoice setup
    }

    protected function createInitialInvoice(Quote $quote, Contract $contract, array $options): Invoice
    {
        return $this->createInvoiceFromQuote($quote, $options, $contract);
    }

    protected function createServiceContract(Quote $quote, array $config): Contract
    {
        // Implementation for service contract creation
        return new Contract;
    }

    protected function createEquipmentContract(Quote $quote, array $config): Contract
    {
        // Implementation for equipment contract creation
        return new Contract;
    }

    protected function createImmediateInvoice(Quote $quote, array $config): Invoice
    {
        return $this->createInvoiceFromQuote($quote, $config);
    }

    protected function createRecurringSetup(Quote $quote, array $config): array
    {
        return []; // Implementation for recurring setup
    }

    protected function createInvoiceFromContract(Contract $contract): Invoice
    {
        // Implementation to create invoice from contract
        return $this->createInvoiceFromQuote($contract->quote);
    }

    protected function createMilestoneInvoice(ContractMilestone $milestone): Invoice
    {
        $invoiceData = [
            'client_id' => $milestone->contract->client_id,
            'contract_id' => $milestone->contract_id,
            'date' => now(),
            'due_date' => now()->addDays(30),
            'currency_code' => $milestone->contract->currency_code,
            'note' => "Invoice for milestone: {$milestone->title}",
            'status' => Invoice::STATUS_DRAFT,
        ];

        $invoice = $this->invoiceService->createInvoice($milestone->contract->client, $invoiceData);

        // Add milestone as invoice item
        $invoice->items()->create([
            'name' => $milestone->title,
            'description' => $milestone->description,
            'quantity' => 1,
            'price' => $milestone->milestone_value,
            'milestone_id' => $milestone->id,
        ]);

        $invoice->calculateTotals();

        return $invoice;
    }

    protected function requiresProvisioning(string $serviceType): bool
    {
        $provisioningTypes = ['hosted_pbx', 'sip_trunking', 'did_numbers'];

        return in_array($serviceType, $provisioningTypes);
    }
}
