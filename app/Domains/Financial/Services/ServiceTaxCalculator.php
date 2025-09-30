<?php

namespace App\Domains\Financial\Services;

use App\Models\ServiceTaxRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxCategory;
use App\Models\TaxExemption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Generic Service Tax Calculator
 * 
 * Handles tax calculations for all service types including:
 * - VoIP/Telecom (E911, USF, excise taxes)
 * - Cloud/SaaS services
 * - Professional services
 * - Any future service types
 */
class ServiceTaxCalculator
{
    protected int $companyId;
    protected array $taxConfig;
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->taxConfig = config('services_tax', []);
    }
    
    /**
     * Calculate taxes for service items
     * 
     * @param Collection $items Service items to calculate tax for
     * @param string $serviceType Type of service (voip, cloud, saas, etc.)
     * @param array|null $serviceAddress Service address for jurisdiction determination
     * @param array|null $config Additional configuration
     * @return array Tax calculations
     */
    public function calculate(Collection $items, string $serviceType = 'general', ?array $serviceAddress = null, ?array $config = null): array
    {
        $calculations = [];
        
        // Use LocalTaxRateService for address-based tax calculation
        $localTaxService = new \App\Services\TaxEngine\LocalTaxRateService($this->companyId);
        
        foreach ($items as $item) {
            $itemServiceType = $item->service_type ?? $serviceType;
            $applicableTaxes = $localTaxService->getApplicableTaxRates($itemServiceType, $serviceAddress);
            
            $itemCalculation = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'service_type' => $itemServiceType,
                'subtotal' => $item->subtotal ?? ($item->quantity * $item->price),
                'tax_breakdown' => [],
                'exemptions_applied' => [],
                'total_tax_amount' => 0
            ];
            
            // Check for exemptions (gracefully handle missing table)
            try {
                $exemptions = $this->getApplicableExemptions($item, $itemServiceType);
            } catch (\Exception $e) {
                Log::info('Tax exemptions table not available, skipping exemption checks', [
                    'error' => $e->getMessage()
                ]);
                $exemptions = collect();
            }
            
            // Calculate each applicable tax
            foreach ($applicableTaxes as $tax) {
                // Convert array to object if necessary for compatibility
                $taxObj = is_array($tax) ? (object)$tax : $tax;
                $taxAmount = $this->calculateTaxAmountFromArray($item, $taxObj, $exemptions);
                
                if ($taxAmount > 0) {
                    $itemCalculation['tax_breakdown'][$taxObj->tax_code ?? $taxObj->tax_name] = [
                        'name' => $taxObj->tax_name,
                        'type' => $taxObj->tax_type ?? null,
                        'regulatory_code' => $taxObj->regulatory_code ?? null,
                        'rate' => $taxObj->percentage_rate ?? 0,
                        'amount' => $taxAmount,
                        'authority' => $taxObj->authority_name,
                        'is_recoverable' => $taxObj->is_recoverable ?? false
                    ];
                    
                    $itemCalculation['total_tax_amount'] += $taxAmount;
                }
            }
            
            // Apply exemptions tracking
            if (!empty($exemptions)) {
                $itemCalculation['exemptions_applied'] = $exemptions->map(function ($exemption) {
                    return [
                        'type' => $exemption->exemption_type,
                        'certificate' => $exemption->certificate_number,
                        'percentage' => $exemption->exemption_percentage
                    ];
                })->toArray();
            }
            
            $calculations[] = $itemCalculation;
        }
        
        return $calculations;
    }
    
    /**
     * Get applicable taxes based on service type and jurisdiction
     */
    protected function getApplicableTaxes(string $serviceType, ?TaxJurisdiction $jurisdiction): Collection
    {
        $query = ServiceTaxRate::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('service_type', $serviceType)
            ->where(function ($q) {
                $q->whereNull('effective_date')
                    ->orWhere('effective_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
        
        if ($jurisdiction) {
            $query->where('tax_jurisdiction_id', $jurisdiction->id);
        }
        
        return $query->orderBy('priority')->get();
    }
    
    /**
     * Calculate tax amount for a specific item and tax rate (array format)
     */
    protected function calculateTaxAmountFromArray($item, $tax, Collection $exemptions): float
    {
        $baseAmount = $item->subtotal ?? ($item->quantity * $item->price);
        
        // Check if exempted
        $exemptionRate = $this->getExemptionRateFromArray($tax, $exemptions);
        
        if ($exemptionRate >= 100) {
            return 0.0; // Fully exempt
        }
        
        $taxRate = $tax->percentage_rate ?? 0;
        $grossTax = $baseAmount * ($taxRate / 100);
        
        // Apply exemption
        return $grossTax * (1 - ($exemptionRate / 100));
    }
    
    /**
     * Calculate tax amount for a specific item and tax rate (legacy)
     */
    protected function calculateTaxAmount($item, ServiceTaxRate $tax, Collection $exemptions): float
    {
        $baseAmount = $item->subtotal ?? ($item->quantity * $item->price);
        
        // Check if exempted
        $exemptionRate = $this->getExemptionRate($tax, $exemptions);
        if ($exemptionRate >= 100) {
            return 0;
        }
        
        $taxableAmount = $baseAmount * (1 - $exemptionRate / 100);
        
        switch ($tax->rate_type) {
            case 'percentage':
                $amount = $taxableAmount * ($tax->percentage_rate / 100);
                break;
                
            case 'fixed':
                $amount = $tax->fixed_amount ?? 0;
                break;
                
            case 'per_line':
                // For services like E911 that charge per line
                $lines = $item->line_count ?? $item->quantity ?? 1;
                $amount = $lines * ($tax->fixed_amount ?? 0);
                break;
                
            case 'per_minute':
                // For usage-based telecom taxes
                $minutes = $item->minutes ?? 0;
                $amount = $minutes * ($tax->fixed_amount ?? 0);
                break;
                
            case 'per_unit':
                // Generic per-unit calculation
                $units = $item->quantity ?? 1;
                $amount = $units * ($tax->fixed_amount ?? 0);
                break;
                
            case 'tiered':
                $amount = $this->calculateTieredTax($taxableAmount, $tax);
                break;
                
            default:
                $amount = 0;
        }
        
        // Apply min/max thresholds
        if ($tax->minimum_threshold && $amount < $tax->minimum_threshold) {
            $amount = $tax->minimum_threshold;
        }
        if ($tax->maximum_amount && $amount > $tax->maximum_amount) {
            $amount = $tax->maximum_amount;
        }
        
        return round($amount, 4);
    }
    
    /**
     * Calculate tiered tax amount
     */
    protected function calculateTieredTax(float $amount, ServiceTaxRate $tax): float
    {
        // Implementation would depend on tier configuration in metadata
        $tiers = $tax->metadata['tiers'] ?? [];
        $totalTax = 0;
        
        foreach ($tiers as $tier) {
            $tierMin = $tier['min'] ?? 0;
            $tierMax = $tier['max'] ?? PHP_FLOAT_MAX;
            $tierRate = $tier['rate'] ?? 0;
            
            if ($amount > $tierMin) {
                $taxableInTier = min($amount - $tierMin, $tierMax - $tierMin);
                $totalTax += $taxableInTier * ($tierRate / 100);
            }
        }
        
        return $totalTax;
    }
    
    /**
     * Determine tax jurisdiction based on service address
     */
    protected function determineJurisdiction(?array $serviceAddress): ?TaxJurisdiction
    {
        if (!$serviceAddress) {
            return null;
        }
        
        // Find jurisdiction based on address
        return TaxJurisdiction::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($serviceAddress) {
                // Match by state
                if (isset($serviceAddress['state'])) {
                    $query->where('state_code', $serviceAddress['state']);
                }
                // Match by county
                if (isset($serviceAddress['county'])) {
                    $query->orWhere('county_name', $serviceAddress['county']);
                }
                // Match by city
                if (isset($serviceAddress['city'])) {
                    $query->orWhere('city_name', $serviceAddress['city']);
                }
                // Match by ZIP code
                if (isset($serviceAddress['zip'])) {
                    $query->orWhereJsonContains('zip_codes', $serviceAddress['zip']);
                }
            })
            ->orderBy('jurisdiction_type') // More specific jurisdictions first
            ->first();
    }
    
    /**
     * Get applicable exemptions for an item
     */
    protected function getApplicableExemptions($item, string $serviceType): Collection
    {
        if (!isset($item->client_id)) {
            return collect();
        }
        
        return TaxExemption::where('company_id', $this->companyId)
            ->where('client_id', $item->client_id)
            ->where('status', 'active')
            ->where(function ($query) use ($serviceType) {
                $query->whereJsonContains('applicable_services', $serviceType)
                    ->orWhere('is_blanket_exemption', true);
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->get();
    }
    
    /**
     * Get exemption rate for a specific tax (array format)
     */
    protected function getExemptionRateFromArray($tax, Collection $exemptions): float
    {
        if ($exemptions->isEmpty()) {
            return 0.0;
        }
        
        $taxCode = $tax->tax_code ?? $tax->tax_name ?? '';
        $exemption = $exemptions->first(function ($exemption) use ($taxCode) {
            return $exemption->applies_to_tax_code === $taxCode;
        });
        
        return $exemption ? ($exemption->exemption_percentage ?? 0) : 0.0;
    }
    
    /**
     * Get exemption rate for a specific tax (legacy)
     */
    protected function getExemptionRate(ServiceTaxRate $tax, Collection $exemptions): float
    {
        if ($exemptions->isEmpty()) {
            return 0;
        }
        
        $applicableExemption = $exemptions->first(function ($exemption) use ($tax) {
            // Check if exemption applies to this tax type
            $applicableTaxTypes = $exemption->applicable_tax_types ?? [];
            
            if ($exemption->is_blanket_exemption) {
                return true;
            }
            
            return in_array($tax->tax_type, $applicableTaxTypes) ||
                   in_array($tax->regulatory_code, $applicableTaxTypes);
        });
        
        if ($applicableExemption) {
            return $applicableExemption->exemption_percentage ?? 100;
        }
        
        return 0;
    }
    
    /**
     * Get default tax configuration for a service type
     */
    public function getServiceTypeConfig(string $serviceType): array
    {
        return $this->taxConfig[$serviceType] ?? $this->taxConfig['default'] ?? [];
    }
    
    /**
     * Validate if all required fields are present for tax calculation
     */
    public function validateRequiredFields($item, string $serviceType): array
    {
        $config = $this->getServiceTypeConfig($serviceType);
        $requiredFields = $config['required_fields'] ?? [];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($item->$field) || empty($item->$field)) {
                $missingFields[] = $field;
            }
        }
        
        return $missingFields;
    }
    
    /**
     * Get tax summary for reporting
     */
    public function getTaxSummary(array $calculations): array
    {
        $summary = [
            'total_subtotal' => 0,
            'total_tax' => 0,
            'tax_by_type' => [],
            'tax_by_authority' => [],
            'exemptions_value' => 0
        ];
        
        foreach ($calculations as $calc) {
            $summary['total_subtotal'] += $calc['subtotal'];
            $summary['total_tax'] += $calc['total_tax_amount'];
            
            foreach ($calc['tax_breakdown'] as $taxCode => $tax) {
                // By type
                $type = $tax['type'];
                if (!isset($summary['tax_by_type'][$type])) {
                    $summary['tax_by_type'][$type] = 0;
                }
                $summary['tax_by_type'][$type] += $tax['amount'];
                
                // By authority
                $authority = $tax['authority'];
                if (!isset($summary['tax_by_authority'][$authority])) {
                    $summary['tax_by_authority'][$authority] = 0;
                }
                $summary['tax_by_authority'][$authority] += $tax['amount'];
            }
        }
        
        $summary['total_amount'] = $summary['total_subtotal'] + $summary['total_tax'];
        $summary['effective_tax_rate'] = $summary['total_subtotal'] > 0 
            ? round(($summary['total_tax'] / $summary['total_subtotal']) * 100, 2)
            : 0;
        
        return $summary;
    }
}