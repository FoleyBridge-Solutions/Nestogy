<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VoIP Tax Service
 * 
 * Handles complex VoIP tax calculations and reversals for credit notes including:
 * - Federal, state, and local tax calculations
 * - Regulatory fee handling (E911, USF, etc.)
 * - Jurisdiction-specific tax rules
 * - International calling tax regulations
 * - Tax reversal calculations for refunds
 * - Multi-currency tax adjustments
 * - Compliance reporting and audit trails
 */
class VoipTaxService
{
    protected array $taxRates = [];
    protected array $jurisdictionRules = [];
    protected string $cachePrefix = 'voip_tax:';
    protected int $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        $this->loadTaxConfiguration();
    }

    /**
     * Calculate tax reversals for credit note
     */
    public function calculateTaxReversals(CreditNote $creditNote): array
    {
        $reversals = [
            'total_tax_reversal' => 0,
            'voip_tax_reversal' => 0,
            'regulatory_fee_reversal' => 0,
            'jurisdiction_reversals' => [],
            'tax_breakdown' => [],
            'compliance_data' => []
        ];

        if (!$creditNote->invoice) {
            return $reversals;
        }

        // Calculate reversals for each credit note item
        foreach ($creditNote->items as $creditItem) {
            $itemReversals = $this->calculateItemTaxReversal($creditItem);
            
            $reversals['total_tax_reversal'] += $itemReversals['total_tax_reversal'];
            $reversals['voip_tax_reversal'] += $itemReversals['voip_tax_reversal'];
            $reversals['regulatory_fee_reversal'] += $itemReversals['regulatory_fee_reversal'];
            
            // Merge jurisdiction reversals
            foreach ($itemReversals['jurisdiction_reversals'] as $jurisdiction => $amount) {
                $reversals['jurisdiction_reversals'][$jurisdiction] = 
                    ($reversals['jurisdiction_reversals'][$jurisdiction] ?? 0) + $amount;
            }
            
            $reversals['tax_breakdown'][] = $itemReversals['breakdown'];
        }

        // Add compliance and audit data
        $reversals['compliance_data'] = $this->generateComplianceData($creditNote, $reversals);

        return $reversals;
    }

    /**
     * Calculate VoIP service-specific tax rates
     */
    public function calculateVoipServiceTax(
        string $serviceType,
        float $amount,
        string $clientState,
        string $jurisdiction = null
    ): array {
        $cacheKey = $this->cachePrefix . "service:{$serviceType}:{$clientState}:{$jurisdiction}:" . md5((string)$amount);
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($serviceType, $amount, $clientState, $jurisdiction) {
            $calculation = [
                'subtotal' => $amount,
                'federal_tax' => 0,
                'state_tax' => 0,
                'local_tax' => 0,
                'regulatory_fees' => 0,
                'total_tax' => 0,
                'effective_rate' => 0,
                'tax_details' => []
            ];

            // Federal taxes
            $federalTax = $this->calculateFederalVoipTax($serviceType, $amount);
            $calculation['federal_tax'] = $federalTax['amount'];
            $calculation['tax_details']['federal'] = $federalTax;

            // State taxes
            $stateTax = $this->calculateStateTax($serviceType, $amount, $clientState);
            $calculation['state_tax'] = $stateTax['amount'];
            $calculation['tax_details']['state'] = $stateTax;

            // Local taxes (if jurisdiction specified)
            if ($jurisdiction) {
                $localTax = $this->calculateLocalTax($serviceType, $amount, $clientState, $jurisdiction);
                $calculation['local_tax'] = $localTax['amount'];
                $calculation['tax_details']['local'] = $localTax;
            }

            // Regulatory fees
            $regulatoryFees = $this->calculateRegulatoryFees($serviceType, $amount, $clientState);
            $calculation['regulatory_fees'] = $regulatoryFees['amount'];
            $calculation['tax_details']['regulatory'] = $regulatoryFees;

            // Calculate totals
            $calculation['total_tax'] = $calculation['federal_tax'] + 
                                     $calculation['state_tax'] + 
                                     $calculation['local_tax'] + 
                                     $calculation['regulatory_fees'];
            
            $calculation['effective_rate'] = $amount > 0 ? 
                ($calculation['total_tax'] / $amount) * 100 : 0;

            return $calculation;
        });
    }

    /**
     * Handle international calling tax calculations
     */
    public function calculateInternationalTax(
        float $amount,
        string $originCountry,
        string $destinationCountry,
        string $serviceProvider = null
    ): array {
        $calculation = [
            'base_amount' => $amount,
            'origin_tax' => 0,
            'destination_tax' => 0,
            'carrier_surcharge' => 0,
            'regulatory_fees' => 0,
            'total_tax' => 0,
            'currency_adjustments' => [],
            'compliance_notes' => []
        ];

        // Origin country taxes (e.g., US federal excise tax)
        if ($originCountry === 'US') {
            $calculation['origin_tax'] = $this->calculateUsFederalExciseTax($amount);
        }

        // Destination country taxes (if applicable)
        $destinationTax = $this->getDestinationCountryTax($destinationCountry, $amount);
        $calculation['destination_tax'] = $destinationTax['amount'];
        $calculation['compliance_notes'] = $destinationTax['compliance_notes'];

        // Carrier-specific surcharges
        if ($serviceProvider) {
            $calculation['carrier_surcharge'] = $this->calculateCarrierSurcharge(
                $serviceProvider, $amount, $originCountry, $destinationCountry
            );
        }

        // International regulatory fees
        $calculation['regulatory_fees'] = $this->calculateInternationalRegulatoryFees(
            $amount, $originCountry, $destinationCountry
        );

        $calculation['total_tax'] = $calculation['origin_tax'] + 
                                  $calculation['destination_tax'] + 
                                  $calculation['carrier_surcharge'] + 
                                  $calculation['regulatory_fees'];

        return $calculation;
    }

    /**
     * Process regulatory fee adjustments
     */
    public function processRegulatoryAdjustment(
        string $feeType,
        float $newRate,
        Carbon $effectiveDate,
        array $affectedServices = []
    ): array {
        $adjustment = [
            'fee_type' => $feeType,
            'old_rate' => $this->getCurrentRegulatoryFeeRate($feeType),
            'new_rate' => $newRate,
            'effective_date' => $effectiveDate,
            'affected_services' => $affectedServices,
            'credit_adjustments' => []
        ];

        // Calculate retroactive adjustments if needed
        if ($effectiveDate < now()) {
            $adjustment['credit_adjustments'] = $this->calculateRetroactiveAdjustments(
                $feeType, $adjustment['old_rate'], $newRate, $effectiveDate, $affectedServices
            );
        }

        // Update fee rates
        $this->updateRegulatoryFeeRate($feeType, $newRate, $effectiveDate);

        Log::info('Regulatory fee adjustment processed', $adjustment);

        return $adjustment;
    }

    /**
     * Generate tax compliance report
     */
    public function generateTaxComplianceReport(
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $report = [
            'reporting_period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ],
            'tax_collected' => [
                'federal' => 0,
                'state' => 0,
                'local' => 0,
                'regulatory' => 0,
                'total' => 0
            ],
            'tax_refunded' => [
                'federal' => 0,
                'state' => 0,
                'local' => 0,
                'regulatory' => 0,
                'total' => 0
            ],
            'net_tax_liability' => 0,
            'jurisdiction_breakdown' => [],
            'service_type_breakdown' => [],
            'compliance_issues' => [],
            'audit_trail' => []
        ];

        // Implementation would generate comprehensive compliance report
        // This is a placeholder for the actual reporting logic
        
        return $report;
    }

    /**
     * Private helper methods
     */
    private function calculateItemTaxReversal(CreditNoteItem $creditItem): array
    {
        $reversal = [
            'total_tax_reversal' => 0,
            'voip_tax_reversal' => 0,
            'regulatory_fee_reversal' => 0,
            'jurisdiction_reversals' => [],
            'breakdown' => []
        ];

        // Get original tax calculation for this item
        $originalTaxData = $this->getOriginalItemTaxData($creditItem);
        
        if (!$originalTaxData) {
            return $reversal;
        }

        // Calculate reversal ratio (for partial credits)
        $reversalRatio = $this->calculateReversalRatio($creditItem);

        // Calculate tax reversals
        foreach ($originalTaxData as $taxType => $taxAmount) {
            $reversalAmount = $taxAmount * $reversalRatio;
            
            switch ($taxType) {
                case 'voip_tax':
                    $reversal['voip_tax_reversal'] += $reversalAmount;
                    break;
                case 'regulatory_fee':
                    $reversal['regulatory_fee_reversal'] += $reversalAmount;
                    break;
                default:
                    // Handle jurisdiction-specific taxes
                    if (strpos($taxType, 'jurisdiction_') === 0) {
                        $jurisdiction = str_replace('jurisdiction_', '', $taxType);
                        $reversal['jurisdiction_reversals'][$jurisdiction] = 
                            ($reversal['jurisdiction_reversals'][$jurisdiction] ?? 0) + $reversalAmount;
                    }
                    break;
            }
            
            $reversal['total_tax_reversal'] += $reversalAmount;
            
            $reversal['breakdown'][] = [
                'tax_type' => $taxType,
                'original_amount' => $taxAmount,
                'reversal_ratio' => $reversalRatio,
                'reversal_amount' => $reversalAmount
            ];
        }

        return $reversal;
    }

    private function calculateFederalVoipTax(string $serviceType, float $amount): array
    {
        $federalRates = [
            'local_service' => 0.00, // No federal tax on local service
            'long_distance' => 3.00, // 3% federal excise tax
            'international' => 3.00,
            'toll_free' => 0.00
        ];

        $rate = $federalRates[$serviceType] ?? 0.00;
        $taxAmount = ($amount * $rate) / 100;

        return [
            'tax_type' => 'federal_excise',
            'rate' => $rate,
            'amount' => $taxAmount,
            'description' => 'Federal Excise Tax on Communications'
        ];
    }

    private function calculateStateTax(string $serviceType, float $amount, string $state): array
    {
        // This would integrate with actual state tax databases
        $stateTaxRates = $this->getStateTaxRates($state);
        
        $rate = $stateTaxRates[$serviceType] ?? 0.00;
        $taxAmount = ($amount * $rate) / 100;

        return [
            'tax_type' => 'state_tax',
            'state' => $state,
            'rate' => $rate,
            'amount' => $taxAmount,
            'description' => "State tax for {$state}"
        ];
    }

    private function calculateLocalTax(string $serviceType, float $amount, string $state, string $jurisdiction): array
    {
        // Local tax calculation logic
        $localRates = $this->getLocalTaxRates($state, $jurisdiction);
        
        $rate = $localRates[$serviceType] ?? 0.00;
        $taxAmount = ($amount * $rate) / 100;

        return [
            'tax_type' => 'local_tax',
            'jurisdiction' => $jurisdiction,
            'rate' => $rate,
            'amount' => $taxAmount,
            'description' => "Local tax for {$jurisdiction}"
        ];
    }

    private function calculateRegulatoryFees(string $serviceType, float $amount, string $state): array
    {
        $fees = [
            'e911_fee' => $this->calculateE911Fee($serviceType, $amount, $state),
            'usf_fee' => $this->calculateUsfFee($serviceType, $amount),
            'trs_fee' => $this->calculateTrsFee($serviceType, $amount, $state),
            'number_pooling_fee' => $this->calculateNumberPoolingFee($serviceType, $amount)
        ];

        $totalAmount = array_sum(array_column($fees, 'amount'));

        return [
            'total_amount' => $totalAmount,
            'amount' => $totalAmount, // For backwards compatibility
            'fee_breakdown' => $fees,
            'description' => 'Regulatory fees and surcharges'
        ];
    }

    private function calculateE911Fee(string $serviceType, float $amount, string $state): array
    {
        // E911 fees are typically per-line charges
        $e911Rates = [
            'CA' => 0.75,
            'TX' => 0.50,
            'NY' => 1.00,
            'FL' => 0.50
        ];

        $feeAmount = $e911Rates[$state] ?? 0.50; // Default rate

        return [
            'fee_type' => 'e911',
            'amount' => $feeAmount,
            'description' => 'Enhanced 911 Service Fee'
        ];
    }

    private function calculateUsfFee(string $serviceType, float $amount): array
    {
        // Universal Service Fund fee (federal)
        $usfRate = 34.4; // Rate changes quarterly - this would be dynamic
        $feeAmount = ($amount * $usfRate) / 100;

        return [
            'fee_type' => 'usf',
            'rate' => $usfRate,
            'amount' => $feeAmount,
            'description' => 'Universal Service Fund Fee'
        ];
    }

    private function calculateTrsFee(string $serviceType, float $amount, string $state): array
    {
        // Telecommunications Relay Service fee (state-specific)
        $trsRates = [
            'CA' => 0.008,
            'TX' => 0.005,
            'NY' => 0.010
        ];

        $rate = $trsRates[$state] ?? 0.005;
        $feeAmount = $amount * $rate;

        return [
            'fee_type' => 'trs',
            'rate' => $rate,
            'amount' => $feeAmount,
            'description' => 'Telecommunications Relay Service Fee'
        ];
    }

    private function calculateNumberPoolingFee(string $serviceType, float $amount): array
    {
        // Number pooling administration fee
        $rate = 0.00034; // $0.00034 per revenue dollar
        $feeAmount = $amount * $rate;

        return [
            'fee_type' => 'number_pooling',
            'rate' => $rate,
            'amount' => $feeAmount,
            'description' => 'Number Pooling Administration Fee'
        ];
    }

    private function calculateUsFederalExciseTax(float $amount): float
    {
        return ($amount * 3.00) / 100; // 3% federal excise tax
    }

    private function getDestinationCountryTax(string $country, float $amount): array
    {
        // This would integrate with international tax databases
        $countryTaxRates = [
            'CA' => ['rate' => 5.00, 'type' => 'GST'],
            'UK' => ['rate' => 20.00, 'type' => 'VAT'],
            'DE' => ['rate' => 19.00, 'type' => 'VAT']
        ];

        $taxInfo = $countryTaxRates[$country] ?? ['rate' => 0.00, 'type' => 'None'];
        
        return [
            'amount' => ($amount * $taxInfo['rate']) / 100,
            'rate' => $taxInfo['rate'],
            'tax_type' => $taxInfo['type'],
            'compliance_notes' => ["Tax calculated for {$country}"]
        ];
    }

    private function calculateCarrierSurcharge(string $provider, float $amount, string $origin, string $destination): float
    {
        // Carrier-specific surcharges would be configured based on agreements
        return 0.00; // Placeholder
    }

    private function calculateInternationalRegulatoryFees(string $amount, string $origin, string $destination): float
    {
        // International regulatory fees
        return 0.00; // Placeholder
    }

    private function getCurrentRegulatoryFeeRate(string $feeType): float
    {
        // Get current rate from database/config
        return 0.00; // Placeholder
    }

    private function calculateRetroactiveAdjustments(string $feeType, float $oldRate, float $newRate, Carbon $effectiveDate, array $services): array
    {
        // Calculate retroactive adjustments for rate changes
        return []; // Placeholder
    }

    private function updateRegulatoryFeeRate(string $feeType, float $newRate, Carbon $effectiveDate): void
    {
        // Update fee rate in database
    }

    private function getOriginalItemTaxData(CreditNoteItem $creditItem): ?array
    {
        // Get original tax data from invoice item
        if ($creditItem->invoice_item_id) {
            $invoiceItem = InvoiceItem::find($creditItem->invoice_item_id);
            return $invoiceItem->tax_breakdown ?? [];
        }
        
        return null;
    }

    private function calculateReversalRatio(CreditNoteItem $creditItem): float
    {
        if (!$creditItem->original_quantity || $creditItem->original_quantity == 0) {
            return 1.0;
        }
        
        return $creditItem->quantity / $creditItem->original_quantity;
    }

    private function generateComplianceData(CreditNote $creditNote, array $reversals): array
    {
        return [
            'credit_note_id' => $creditNote->id,
            'reversal_date' => now()->toISOString(),
            'jurisdiction_compliance' => $this->checkJurisdictionCompliance($reversals),
            'audit_requirements' => $this->getAuditRequirements($creditNote, $reversals),
            'regulatory_notifications' => $this->getRequiredNotifications($reversals)
        ];
    }

    private function checkJurisdictionCompliance(array $reversals): array
    {
        // Check compliance requirements for each jurisdiction
        return [];
    }

    private function getAuditRequirements(CreditNote $creditNote, array $reversals): array
    {
        // Determine audit requirements based on amounts and jurisdictions
        return [];
    }

    private function getRequiredNotifications(array $reversals): array
    {
        // Determine required regulatory notifications
        return [];
    }

    private function loadTaxConfiguration(): void
    {
        // Load tax rates and jurisdiction rules from database/config
        $this->taxRates = Cache::remember($this->cachePrefix . 'rates', 3600, function () {
            // This would load from database
            return [];
        });
        
        $this->jurisdictionRules = Cache::remember($this->cachePrefix . 'rules', 3600, function () {
            // This would load from database
            return [];
        });
    }

    private function getStateTaxRates(string $state): array
    {
        return $this->taxRates['states'][$state] ?? [];
    }

    private function getLocalTaxRates(string $state, string $jurisdiction): array
    {
        return $this->taxRates['local'][$state][$jurisdiction] ?? [];
    }
}