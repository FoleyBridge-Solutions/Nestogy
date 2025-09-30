<?php

namespace App\Domains\Financial\Services;

use App\Models\Client;
use App\Models\TaxJurisdiction;
use App\Models\TaxCategory;
use App\Models\VoIPTaxRate;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;
use App\Domains\Financial\Services\TaxEngine\TaxServiceFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VoIP Tax Calculation Service
 * 
 * Comprehensive tax calculation engine for VoIP telecommunications services.
 * Handles federal, state, and local tax calculations with exemption support.
 */
class VoIPTaxService
{
    protected ?int $companyId = null;
    protected array $config;
    protected array $calculationCache = [];

    /**
     * Federal tax constants
     */
    const FEDERAL_EXCISE_TAX_RATE = 3.0; // 3% on amounts over $0.20
    const FEDERAL_EXCISE_THRESHOLD = 0.20;
    const DEFAULT_USF_RATE = 33.4; // Current USF contribution factor (changes quarterly)

    /**
     * Service type constants
     */
    const SERVICE_LOCAL = 'local';
    const SERVICE_LONG_DISTANCE = 'long_distance';
    const SERVICE_INTERNATIONAL = 'international';
    const SERVICE_VOIP_FIXED = 'voip_fixed';
    const SERVICE_VOIP_NOMADIC = 'voip_nomadic';
    const SERVICE_DATA = 'data';
    const SERVICE_EQUIPMENT = 'equipment';

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_ttl' => 3600, // 1 hour
            'enable_caching' => true,
            'round_precision' => 4,
            'calculation_method' => 'exclusive', // exclusive or inclusive
        ], $config);
    }

    /**
     * Set the company ID for calculations
     */
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * Calculate comprehensive taxes for a service amount.
     */
    public function calculateTaxes(array $params): array
    {
        if ($this->companyId === null) {
            throw new \InvalidArgumentException('Company ID must be set before calculating taxes. Use setCompanyId() method.');
        }

        $this->validateCalculationParams($params);

        $cacheKey = $this->generateCacheKey($params);

        if ($this->config['enable_caching'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Use TaxJar for sales tax calculation
        $result = $this->calculateWithTaxJar($params);

        if ($this->config['enable_caching']) {
            Cache::put($cacheKey, $result, $this->config['cache_ttl']);
        }

        return $result;
    }

    /**
     * Calculate taxes using TaxJar service
     */
    protected function calculateWithTaxJar(array $params): array
    {
        try {
            $taxService = TaxServiceFactory::getService('US', $this->companyId);

            if (!$taxService) {
                Log::warning('VoIPTaxService: No tax service available, using fallback', [
                    'company_id' => $this->companyId
                ]);
                return $this->getFallbackCalculation($params);
            }

            $taxResult = $taxService->calculateTaxes($params);

            // Add VoIP-specific metadata
            $taxResult['service_type'] = $params['service_type'] ?? 'voip';
            $taxResult['calculation_method'] = 'taxjar_api';

            Log::info('VoIPTaxService: Tax calculation completed with TaxJar', [
                'company_id' => $this->companyId,
                'base_amount' => $params['amount'],
                'total_tax' => $taxResult['total_tax_amount'],
                'service_type' => $params['service_type'] ?? 'voip'
            ]);

            return $taxResult;

        } catch (\Exception $e) {
            Log::error('VoIPTaxService: TaxJar calculation failed, using fallback', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return $this->getFallbackCalculation($params);
        }
    }

    /**
     * Fallback calculation when TaxJar fails
     */
    protected function getFallbackCalculation(array $params): array
    {
        $baseAmount = $params['amount'];
        $fallbackRate = 8.25; // Default combined rate
        $totalTax = $baseAmount * ($fallbackRate / 100);

        return [
            'base_amount' => $baseAmount,
            'total_tax_amount' => round($totalTax, 2),
            'tax_breakdown' => [
                [
                    'tax_name' => 'Combined Sales Tax (Fallback)',
                    'tax_type' => 'combined_sales_tax',
                    'rate_type' => 'percentage',
                    'rate' => $fallbackRate,
                    'base_amount' => $baseAmount,
                    'tax_amount' => round($totalTax, 2),
                    'authority' => 'Fallback Calculation',
                    'jurisdiction' => 'Unknown',
                ]
            ],
            'jurisdictions' => [],
            'final_amount' => round($baseAmount + $totalTax, 2),
            'calculation_date' => now()->toISOString(),
            'service_type' => $params['service_type'] ?? 'voip',
            'federal_taxes' => [],
            'state_taxes' => [],
            'local_taxes' => [],
            'exemptions_applied' => [],
            'fallback_used' => true,
            'calculation_method' => 'fallback',
        ];
    }

    /**
     * Perform the actual tax calculation.
     */
    protected function performTaxCalculation(array $params): array
    {
        $baseAmount = $params['amount'];
        $serviceType = $params['service_type'] ?? self::SERVICE_LOCAL;
        $address = $params['service_address'] ?? [];
        $clientId = $params['client_id'] ?? null;
        $calculationDate = Carbon::parse($params['calculation_date'] ?? now());
        $lineCount = $params['line_count'] ?? 1;
        $minutes = $params['minutes'] ?? 0;

        // Initialize calculation result
        $result = [
            'base_amount' => $baseAmount,
            'service_type' => $serviceType,
            'calculation_date' => $calculationDate->toISOString(),
            'federal_taxes' => [],
            'state_taxes' => [],
            'local_taxes' => [],
            'exemptions_applied' => [],
            'total_tax_amount' => 0.0,
            'final_amount' => $baseAmount,
            'tax_breakdown' => [],
            'jurisdictions' => [],
        ];

        // Step 1: Detect applicable jurisdictions
        $jurisdictions = $this->detectJurisdictions($address);
        $result['jurisdictions'] = $jurisdictions->pluck(['id', 'name', 'jurisdiction_type'])->toArray();

        // Step 2: Find applicable tax category
        $taxCategory = $this->findTaxCategory($serviceType);
        if (!$taxCategory || !$taxCategory->isTaxable()) {
            Log::info('Service not taxable', ['service_type' => $serviceType, 'category' => $taxCategory?->name]);
            return $result;
        }

        // Step 3: Get client exemptions
        $exemptions = $this->getClientExemptions($clientId, $jurisdictions);

        // Step 4: Calculate federal taxes
        $federalTaxes = $this->calculateFederalTaxes($baseAmount, $serviceType, $lineCount, $minutes, $calculationDate);
        $result['federal_taxes'] = $this->applyExemptions($federalTaxes, $exemptions, 'federal');

        // Step 5: Calculate state taxes
        $stateTaxes = $this->calculateStateTaxes($baseAmount, $serviceType, $jurisdictions, $taxCategory, $lineCount, $minutes, $calculationDate);
        $result['state_taxes'] = $this->applyExemptions($stateTaxes, $exemptions, 'state');

        // Step 6: Calculate local taxes
        $localTaxes = $this->calculateLocalTaxes($baseAmount, $serviceType, $jurisdictions, $taxCategory, $lineCount, $minutes, $calculationDate);
        $result['local_taxes'] = $this->applyExemptions($localTaxes, $exemptions, 'local');

        // Step 7: Aggregate results
        $result = $this->aggregateCalculationResults($result);

        Log::info('Tax calculation completed', [
            'company_id' => $this->companyId,
            'base_amount' => $baseAmount,
            'total_tax' => $result['total_tax_amount'],
            'jurisdictions_count' => count($result['jurisdictions'])
        ]);

        return $result;
    }

    /**
     * Detect applicable tax jurisdictions based on service address.
     */
    protected function detectJurisdictions(array $address): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($address)) {
            // Return federal jurisdiction only if no address provided
            return TaxJurisdiction::where('company_id', $this->companyId)
                ->federal()
                ->active()
                ->get();
        }

        $cacheKey = "jurisdictions:" . md5(json_encode($address)) . ":" . $this->companyId;

        if ($this->config['enable_caching'] && Cache::has($cacheKey)) {
            $jurisdictionIds = Cache::get($cacheKey);
            return TaxJurisdiction::whereIn('id', $jurisdictionIds)->get();
        }

        $jurisdictions = TaxJurisdiction::findByAddress($address)
            ->where('company_id', $this->companyId);

        if ($this->config['enable_caching']) {
            Cache::put($cacheKey, $jurisdictions->pluck('id')->toArray(), $this->config['cache_ttl']);
        }

        return $jurisdictions;
    }

    /**
     * Find the applicable tax category for a service type.
     */
    protected function findTaxCategory(string $serviceType): ?TaxCategory
    {
        return TaxCategory::where('company_id', $this->companyId)
            ->active()
            ->where(function ($query) use ($serviceType) {
                $query->whereNull('service_types')
                    ->orWhereJsonContains('service_types', $serviceType);
            })
            ->orderBy('priority')
            ->first();
    }

    /**
     * Get applicable exemptions for a client.
     */
    protected function getClientExemptions(?int $clientId, $jurisdictions): \Illuminate\Database\Eloquent\Collection
    {
        if (!$clientId) {
            return collect();
        }

        $jurisdictionIds = $jurisdictions->pluck('id')->toArray();

        return TaxExemption::where('company_id', $this->companyId)
            ->where('client_id', $clientId)
            ->valid()
            ->where(function ($query) use ($jurisdictionIds) {
                $query->where('is_blanket_exemption', true)
                    ->orWhereIn('tax_jurisdiction_id', $jurisdictionIds);
            })
            ->orderBy('priority')
            ->get();
    }

    /**
     * Calculate federal taxes.
     */
    protected function calculateFederalTaxes(float $amount, string $serviceType, int $lineCount, int $minutes, Carbon $calculationDate): array
    {
        $taxes = [];

        // Federal Excise Tax (3% on telecommunications services over $0.20)
        if ($this->isSubjectToFederalExciseTax($serviceType) && $amount > self::FEDERAL_EXCISE_THRESHOLD) {
            $exciseTax = $amount * (self::FEDERAL_EXCISE_TAX_RATE / 100);
            $taxes[] = [
                'tax_name' => 'Federal Excise Tax',
                'tax_type' => 'federal_excise_tax',
                'rate_type' => 'percentage',
                'rate' => self::FEDERAL_EXCISE_TAX_RATE,
                'base_amount' => $amount,
                'tax_amount' => round($exciseTax, $this->config['round_precision']),
                'authority' => 'Internal Revenue Service',
            ];
        }

        // Universal Service Fund (USF)
        if ($this->isSubjectToUSF($serviceType)) {
            $usfRate = $this->getCurrentUSFRate($calculationDate);
            $usfTax = $amount * ($usfRate / 100);
            $taxes[] = [
                'tax_name' => 'Universal Service Fund',
                'tax_type' => 'universal_service_fund',
                'rate_type' => 'percentage',
                'rate' => $usfRate,
                'base_amount' => $amount,
                'tax_amount' => round($usfTax, $this->config['round_precision']),
                'authority' => 'Federal Communications Commission',
            ];
        }

        return $taxes;
    }

    /**
     * Calculate state taxes.
     */
    protected function calculateStateTaxes(float $amount, string $serviceType, $jurisdictions, TaxCategory $taxCategory, int $lineCount, int $minutes, Carbon $calculationDate): array
    {
        $taxes = [];
        
        $stateJurisdictions = $jurisdictions->where('jurisdiction_type', 'state');

        foreach ($stateJurisdictions as $jurisdiction) {
            $taxRates = VoIPTaxRate::where('company_id', $this->companyId)
                ->byJurisdiction($jurisdiction->id)
                ->byCategory($taxCategory->id)
                ->state()
                ->active()
                ->orderByPriority()
                ->get();

            foreach ($taxRates as $taxRate) {
                if ($taxRate->appliesTo($serviceType)) {
                    $taxAmount = $taxRate->calculateTaxAmount($amount, [
                        'line_count' => $lineCount,
                        'minutes' => $minutes,
                        'service_type' => $serviceType,
                    ]);

                    if ($taxAmount > 0) {
                        $taxes[] = [
                            'tax_name' => $taxRate->tax_name,
                            'tax_type' => $taxRate->tax_type,
                            'rate_type' => $taxRate->rate_type,
                            'rate' => $taxRate->percentage_rate ?? $taxRate->fixed_amount,
                            'base_amount' => $amount,
                            'tax_amount' => $taxAmount,
                            'authority' => $taxRate->authority_name,
                            'jurisdiction' => $jurisdiction->name,
                            'tax_rate_id' => $taxRate->id,
                        ];
                    }
                }
            }
        }

        return $taxes;
    }

    /**
     * Calculate local taxes (county, city, municipal, special district).
     */
    protected function calculateLocalTaxes(float $amount, string $serviceType, $jurisdictions, TaxCategory $taxCategory, int $lineCount, int $minutes, Carbon $calculationDate): array
    {
        $taxes = [];
        
        $localJurisdictions = $jurisdictions->whereIn('jurisdiction_type', [
            'county', 'city', 'municipality', 'special_district', 'local'
        ]);

        foreach ($localJurisdictions as $jurisdiction) {
            $taxRates = VoIPTaxRate::where('company_id', $this->companyId)
                ->byJurisdiction($jurisdiction->id)
                ->byCategory($taxCategory->id)
                ->local()
                ->active()
                ->orderByPriority()
                ->get();

            foreach ($taxRates as $taxRate) {
                if ($taxRate->appliesTo($serviceType)) {
                    $taxAmount = $taxRate->calculateTaxAmount($amount, [
                        'line_count' => $lineCount,
                        'minutes' => $minutes,
                        'service_type' => $serviceType,
                    ]);

                    if ($taxAmount > 0) {
                        $taxes[] = [
                            'tax_name' => $taxRate->tax_name,
                            'tax_type' => $taxRate->tax_type,
                            'rate_type' => $taxRate->rate_type,
                            'rate' => $taxRate->percentage_rate ?? $taxRate->fixed_amount,
                            'base_amount' => $amount,
                            'tax_amount' => $taxAmount,
                            'authority' => $taxRate->authority_name,
                            'jurisdiction' => $jurisdiction->name,
                            'tax_rate_id' => $taxRate->id,
                        ];
                    }
                }
            }
        }

        return $taxes;
    }

    /**
     * Apply exemptions to calculated taxes.
     */
    protected function applyExemptions(array $taxes, $exemptions, string $taxLevel): array
    {
        if ($exemptions->isEmpty()) {
            return $taxes;
        }

        $exemptedTaxes = [];
        $exemptionsApplied = [];

        foreach ($taxes as $tax) {
            $originalTaxAmount = $tax['tax_amount'];
            $exemptedAmount = 0.0;

            foreach ($exemptions as $exemption) {
                if ($exemption->appliesToTaxType($tax['tax_type'])) {
                    $exemptionAmount = $exemption->calculateExemptionAmount($tax['tax_amount'], [
                        'service_type' => $tax['service_type'] ?? null,
                        'amount' => $tax['base_amount'],
                    ]);

                    if ($exemptionAmount > 0) {
                        $exemptedAmount += $exemptionAmount;
                        $exemptionsApplied[] = [
                            'exemption_id' => $exemption->id,
                            'exemption_name' => $exemption->exemption_name,
                            'tax_name' => $tax['tax_name'],
                            'original_amount' => $originalTaxAmount,
                            'exempted_amount' => $exemptionAmount,
                        ];
                    }
                }
            }

            // Apply exemption but don't go negative
            $tax['tax_amount'] = max(0, $originalTaxAmount - $exemptedAmount);
            $tax['exempted_amount'] = min($exemptedAmount, $originalTaxAmount);
            
            $exemptedTaxes[] = $tax;
        }

        return $exemptedTaxes;
    }

    /**
     * Aggregate all calculation results.
     */
    protected function aggregateCalculationResults(array $result): array
    {
        $allTaxes = array_merge(
            $result['federal_taxes'],
            $result['state_taxes'],
            $result['local_taxes']
        );

        $totalTaxAmount = 0.0;
        $taxBreakdown = [];

        foreach ($allTaxes as $tax) {
            $totalTaxAmount += $tax['tax_amount'];
            $taxBreakdown[] = $tax;
        }

        $result['total_tax_amount'] = round($totalTaxAmount, $this->config['round_precision']);
        $result['final_amount'] = $result['base_amount'] + $result['total_tax_amount'];
        $result['tax_breakdown'] = $taxBreakdown;

        return $result;
    }

    /**
     * Check if service is subject to federal excise tax.
     */
    protected function isSubjectToFederalExciseTax(string $serviceType): bool
    {
        return in_array($serviceType, [
            self::SERVICE_LOCAL,
            self::SERVICE_LONG_DISTANCE,
            self::SERVICE_VOIP_FIXED,
            self::SERVICE_VOIP_NOMADIC,
        ]);
    }

    /**
     * Check if service is subject to USF.
     */
    protected function isSubjectToUSF(string $serviceType): bool
    {
        return in_array($serviceType, [
            self::SERVICE_LOCAL,
            self::SERVICE_LONG_DISTANCE,
            self::SERVICE_INTERNATIONAL,
            self::SERVICE_VOIP_FIXED,
            self::SERVICE_VOIP_NOMADIC,
        ]);
    }

    /**
     * Get current USF rate (should be updated quarterly).
     */
    protected function getCurrentUSFRate(Carbon $calculationDate): float
    {
        // In a real implementation, this would query current rates from FCC
        // or from a database table that's updated quarterly
        $cacheKey = "usf_rate:" . $calculationDate->quarter . ":" . $calculationDate->year;
        
        return Cache::remember($cacheKey, 86400, function () {
            // Default rate, should be updated from external source
            return self::DEFAULT_USF_RATE;
        });
    }

    /**
     * Record exemption usage for audit purposes.
     */
    public function recordExemptionUsage(array $exemptionsApplied, ?int $invoiceId = null, ?int $quoteId = null): void
    {
        foreach ($exemptionsApplied as $exemptionData) {
            TaxExemptionUsage::create([
                'company_id' => $this->companyId,
                'tax_exemption_id' => $exemptionData['exemption_id'],
                'invoice_id' => $invoiceId,
                'quote_id' => $quoteId,
                'original_tax_amount' => $exemptionData['original_amount'],
                'exempted_amount' => $exemptionData['exempted_amount'],
                'final_tax_amount' => $exemptionData['original_amount'] - $exemptionData['exempted_amount'],
                'exemption_reason' => $exemptionData['exemption_name'],
                'used_at' => now(),
            ]);
        }
    }

    /**
     * Validate calculation parameters.
     */
    protected function validateCalculationParams(array $params): void
    {
        if (!isset($params['amount']) || !is_numeric($params['amount']) || $params['amount'] < 0) {
            throw new \InvalidArgumentException('Amount must be a non-negative number');
        }

        if (isset($params['line_count']) && (!is_numeric($params['line_count']) || $params['line_count'] < 1)) {
            throw new \InvalidArgumentException('Line count must be a positive integer');
        }

        if (isset($params['minutes']) && (!is_numeric($params['minutes']) || $params['minutes'] < 0)) {
            throw new \InvalidArgumentException('Minutes must be a non-negative number');
        }
    }

    /**
     * Generate cache key for calculation parameters.
     */
    protected function generateCacheKey(array $params): string
    {
        $keyData = [
            'company_id' => $this->companyId,
            'amount' => $params['amount'],
            'service_type' => $params['service_type'] ?? 'local',
            'address' => $params['service_address'] ?? [],
            'client_id' => $params['client_id'] ?? null,
            'date' => Carbon::parse($params['calculation_date'] ?? now())->toDateString(),
            'line_count' => $params['line_count'] ?? 1,
            'minutes' => $params['minutes'] ?? 0,
        ];

        return 'voip_tax:' . md5(json_encode($keyData));
    }

    /**
     * Clear calculation cache.
     */
    public function clearCache(?string $pattern = null): void
    {
        if ($pattern) {
            // Clear specific pattern if cache driver supports it
            if (config('cache.default') === 'redis') {
                $prefix = config('cache.prefix', '');
                $fullPattern = $prefix . $pattern;
                $keys = Cache::getRedis()->keys($fullPattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // Fallback to full flush for other cache drivers
                Cache::flush();
            }
        } else {
            Cache::flush();
        }

        Log::info('VoIP tax calculation cache cleared', [
            'company_id' => $this->companyId,
            'pattern' => $pattern
        ]);
    }

    /**
     * Get tax calculation summary for reporting.
     */
    public function getCalculationSummary(array $calculations): array
    {
        $summary = [
            'total_base_amount' => 0.0,
            'total_tax_amount' => 0.0,
            'total_final_amount' => 0.0,
            'federal_taxes' => 0.0,
            'state_taxes' => 0.0,
            'local_taxes' => 0.0,
            'exemptions_total' => 0.0,
            'tax_by_type' => [],
            'jurisdictions' => [],
        ];

        foreach ($calculations as $calc) {
            $summary['total_base_amount'] += $calc['base_amount'];
            $summary['total_tax_amount'] += $calc['total_tax_amount'];
            $summary['total_final_amount'] += $calc['final_amount'];

            // Aggregate taxes by level
            foreach ($calc['federal_taxes'] as $tax) {
                $summary['federal_taxes'] += $tax['tax_amount'];
            }
            foreach ($calc['state_taxes'] as $tax) {
                $summary['state_taxes'] += $tax['tax_amount'];
            }
            foreach ($calc['local_taxes'] as $tax) {
                $summary['local_taxes'] += $tax['tax_amount'];
            }

            // Track jurisdictions
            foreach ($calc['jurisdictions'] as $jurisdiction) {
                $summary['jurisdictions'][$jurisdiction['id']] = $jurisdiction['name'];
            }
        }

        return $summary;
    }
}