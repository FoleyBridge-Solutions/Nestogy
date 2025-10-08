<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\Services\TaxEngine\TaxServiceFactory;
use App\Models\Client;
use App\Models\TaxCategory;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;
use App\Models\TaxJurisdiction;
use App\Models\VoIPTaxRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    protected VoIPTaxJurisdictionService $jurisdictionService;

    protected VoIPTaxCalculationService $calculationService;

    protected VoIPTaxExemptionService $exemptionService;

    const FEDERAL_EXCISE_TAX_RATE = 3.0;

    const FEDERAL_EXCISE_THRESHOLD = 0.20;

    const DEFAULT_USF_RATE = 33.4;

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
            'cache_ttl' => 3600,
            'enable_caching' => true,
            'round_precision' => 4,
            'calculation_method' => 'exclusive',
        ], $config);

        $this->jurisdictionService = new VoIPTaxJurisdictionService($this->config);
        $this->calculationService = new VoIPTaxCalculationService($this->config);
        $this->exemptionService = new VoIPTaxExemptionService();
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        $this->jurisdictionService->setCompanyId($companyId);
        $this->calculationService->setCompanyId($companyId);
        $this->exemptionService->setCompanyId($companyId);

        return $this;
    }

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

        $result = $this->calculateWithTaxJar($params);

        if ($this->config['enable_caching']) {
            Cache::put($cacheKey, $result, $this->config['cache_ttl']);
        }

        return $result;
    }

    protected function calculateWithTaxJar(array $params): array
    {
        try {
            $taxService = TaxServiceFactory::getService('US', $this->companyId);

            if (! $taxService) {
                Log::warning('VoIPTaxService: No tax service available, using fallback', [
                    'company_id' => $this->companyId,
                ]);

                return $this->getFallbackCalculation($params);
            }

            $taxResult = $taxService->calculateTaxes($params);

            $taxResult['service_type'] = $params['service_type'] ?? 'voip';
            $taxResult['calculation_method'] = 'taxjar_api';

            Log::info('VoIPTaxService: Tax calculation completed with TaxJar', [
                'company_id' => $this->companyId,
                'base_amount' => $params['amount'],
                'total_tax' => $taxResult['total_tax_amount'],
                'service_type' => $params['service_type'] ?? 'voip',
            ]);

            return $taxResult;

        } catch (\Exception $e) {
            Log::error('VoIPTaxService: TaxJar calculation failed, using fallback', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return $this->getFallbackCalculation($params);
        }
    }

    protected function getFallbackCalculation(array $params): array
    {
        $baseAmount = $params['amount'];
        $fallbackRate = 8.25;
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
                ],
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

    protected function performTaxCalculation(array $params): array
    {
        $baseAmount = $params['amount'];
        $serviceType = $params['service_type'] ?? self::SERVICE_LOCAL;
        $address = $params['service_address'] ?? [];
        $clientId = $params['client_id'] ?? null;
        $calculationDate = Carbon::parse($params['calculation_date'] ?? now());
        $lineCount = $params['line_count'] ?? 1;
        $minutes = $params['minutes'] ?? 0;

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

        $jurisdictions = $this->jurisdictionService->detectJurisdictions($address);
        $result['jurisdictions'] = $jurisdictions->pluck(['id', 'name', 'jurisdiction_type'])->toArray();

        $taxCategory = $this->jurisdictionService->findTaxCategory($serviceType);
        if (! $taxCategory || ! $taxCategory->isTaxable()) {
            Log::info('Service not taxable', ['service_type' => $serviceType, 'category' => $taxCategory?->name]);

            return $result;
        }

        $exemptions = $this->exemptionService->getClientExemptions($clientId, $jurisdictions);

        $federalTaxes = $this->calculationService->calculateFederalTaxes($baseAmount, $serviceType, $lineCount, $minutes, $calculationDate);
        $result['federal_taxes'] = $this->exemptionService->applyExemptions($federalTaxes, $exemptions, 'federal');

        $stateTaxes = $this->calculationService->calculateStateTaxes($baseAmount, $serviceType, $jurisdictions, $taxCategory, $lineCount, $minutes, $calculationDate);
        $result['state_taxes'] = $this->exemptionService->applyExemptions($stateTaxes, $exemptions, 'state');

        $localTaxes = $this->calculationService->calculateLocalTaxes($baseAmount, $serviceType, $jurisdictions, $taxCategory, $lineCount, $minutes, $calculationDate);
        $result['local_taxes'] = $this->exemptionService->applyExemptions($localTaxes, $exemptions, 'local');

        $result = $this->calculationService->aggregateCalculationResults($result);

        Log::info('Tax calculation completed', [
            'company_id' => $this->companyId,
            'base_amount' => $baseAmount,
            'total_tax' => $result['total_tax_amount'],
            'jurisdictions_count' => count($result['jurisdictions']),
        ]);

        return $result;
    }

    public function recordExemptionUsage(array $exemptionsApplied, ?int $invoiceId = null, ?int $quoteId = null): void
    {
        $this->exemptionService->recordExemptionUsage($exemptionsApplied, $invoiceId, $quoteId);
    }

    protected function validateCalculationParams(array $params): void
    {
        if (! isset($params['amount']) || ! is_numeric($params['amount']) || $params['amount'] < 0) {
            throw new \InvalidArgumentException('Amount must be a non-negative number');
        }

        if (isset($params['line_count']) && (! is_numeric($params['line_count']) || $params['line_count'] < 1)) {
            throw new \InvalidArgumentException('Line count must be a positive integer');
        }

        if (isset($params['minutes']) && (! is_numeric($params['minutes']) || $params['minutes'] < 0)) {
            throw new \InvalidArgumentException('Minutes must be a non-negative number');
        }
    }

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

        return 'voip_tax:'.md5(json_encode($keyData));
    }

    public function clearCache(?string $pattern = null): void
    {
        if ($pattern) {
            if (config('cache.default') === 'redis') {
                $prefix = config('cache.prefix', '');
                $fullPattern = $prefix.$pattern;
                $keys = Cache::getRedis()->keys($fullPattern);
                if (! empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                Cache::flush();
            }
        } else {
            Cache::flush();
        }

        Log::info('VoIP tax calculation cache cleared', [
            'company_id' => $this->companyId,
            'pattern' => $pattern,
        ]);
    }

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

            foreach ($calc['federal_taxes'] as $tax) {
                $summary['federal_taxes'] += $tax['tax_amount'];
            }
            foreach ($calc['state_taxes'] as $tax) {
                $summary['state_taxes'] += $tax['tax_amount'];
            }
            foreach ($calc['local_taxes'] as $tax) {
                $summary['local_taxes'] += $tax['tax_amount'];
            }

            foreach ($calc['jurisdictions'] as $jurisdiction) {
                $summary['jurisdictions'][$jurisdiction['id']] = $jurisdiction['name'];
            }
        }

        return $summary;
    }
}
