<?php

namespace App\Domains\Financial\Services;

use App\Models\TaxCategory;
use App\Models\VoIPTaxRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VoIPTaxCalculationService
{
    protected ?int $companyId = null;

    protected array $config;

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
            'round_precision' => 4,
        ], $config);
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function calculateFederalTaxes(float $amount, string $serviceType, int $lineCount, int $minutes, Carbon $calculationDate): array
    {
        $taxes = [];

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

    public function calculateStateTaxes(float $amount, string $serviceType, $jurisdictions, TaxCategory $taxCategory, int $lineCount, int $minutes, Carbon $calculationDate): array
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

    public function calculateLocalTaxes(float $amount, string $serviceType, $jurisdictions, TaxCategory $taxCategory, int $lineCount, int $minutes, Carbon $calculationDate): array
    {
        $taxes = [];

        $localJurisdictions = $jurisdictions->whereIn('jurisdiction_type', [
            'county', 'city', 'municipality', 'special_district', 'local',
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

    public function aggregateCalculationResults(array $result): array
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

    protected function isSubjectToFederalExciseTax(string $serviceType): bool
    {
        return in_array($serviceType, [
            self::SERVICE_LOCAL,
            self::SERVICE_LONG_DISTANCE,
            self::SERVICE_VOIP_FIXED,
            self::SERVICE_VOIP_NOMADIC,
        ]);
    }

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

    protected function getCurrentUSFRate(Carbon $calculationDate): float
    {
        $cacheKey = 'usf_rate:'.$calculationDate->quarter.':'.$calculationDate->year;

        return Cache::remember($cacheKey, 86400, function () {
            return self::DEFAULT_USF_RATE;
        });
    }
}
