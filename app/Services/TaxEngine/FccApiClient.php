<?php

namespace App\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;
use Carbon\Carbon;

/**
 * FCC API Client
 * 
 * Free API for accessing FCC telecommunications data including:
 * - USF contribution factors
 * - PSAP (911) registry
 * - Area and Census Block data
 * - License information
 * 
 * API Documentation: https://www.fcc.gov/reports-research/developers
 */
class FccApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://api.fcc.gov';
    protected string $areaApiUrl = 'https://geo.fcc.gov/api/census';
    
    // Current USF rates (updated quarterly)
    protected array $currentUsfRates = [
        '2025-Q1' => 0.360, // 36.0%
        '2024-Q4' => 0.344, // 34.4%
        '2024-Q3' => 0.344, // 34.4%
    ];

    public function __construct(int $companyId, array $config = [])
    {
        parent::__construct($companyId, TaxApiQueryCache::PROVIDER_FCC, $config);
    }

    /**
     * Get rate limits for FCC APIs
     * Government APIs typically have generous limits
     */
    protected function getRateLimits(): array
    {
        return [
            TaxApiQueryCache::TYPE_USF_RATES => [
                'max_requests' => 100,
                'window' => 60,
            ],
            'area_api' => [
                'max_requests' => 100,
                'window' => 60,
            ],
            'psap_registry' => [
                'max_requests' => 50,
                'window' => 60,
            ],
        ];
    }

    /**
     * Get current USF contribution factor
     * 
     * @param string|null $quarter Quarter to get rate for (e.g., '2025-Q1')
     * @return array USF rate information
     */
    public function getUsfRate(?string $quarter = null): array
    {
        $quarter = $quarter ?? $this->getCurrentQuarter();
        $parameters = ['quarter' => $quarter];
        
        return $this->makeRequest(
            TaxApiQueryCache::TYPE_USF_RATES,
            $parameters,
            function () use ($quarter) {
                // For now, use hardcoded rates as FCC doesn't have a direct API
                // In production, this would scrape or fetch from FCC announcements
                
                $rate = $this->currentUsfRates[$quarter] ?? null;
                
                if ($rate === null) {
                    // Try to get the most recent rate
                    $quarters = array_keys($this->currentUsfRates);
                    rsort($quarters); // Sort in descending order
                    $latestQuarter = $quarters[0] ?? null;
                    $rate = $this->currentUsfRates[$latestQuarter] ?? 0.344; // Fallback
                    
                    return [
                        'found' => false,
                        'quarter' => $quarter,
                        'rate' => $rate,
                        'rate_percentage' => $rate * 100,
                        'latest_available_quarter' => $latestQuarter,
                        'note' => "Rate not available for {$quarter}, using latest available rate",
                        'source' => 'fcc_hardcoded',
                        'last_updated' => now()->toISOString(),
                    ];
                }

                return [
                    'found' => true,
                    'quarter' => $quarter,
                    'rate' => $rate,
                    'rate_percentage' => $rate * 100,
                    'effective_date' => $this->getQuarterStartDate($quarter)->toISOString(),
                    'next_update' => $this->getNextQuarterStartDate($quarter)->toISOString(),
                    'source' => 'fcc_hardcoded',
                    'last_updated' => now()->toISOString(),
                ];
            },
            90 // Cache USF rates for 90 days (they change quarterly)
        );
    }

    /**
     * Get area information by coordinates
     * 
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return array Area information including market data
     */
    public function getAreaInfo(float $latitude, float $longitude): array
    {
        $parameters = [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
        ];
        
        return $this->makeRequest(
            'area_api',
            $parameters,
            function () use ($parameters) {
                $response = $this->createHttpClient()
                    ->get("{$this->areaApiUrl}/area", $parameters);

                if (!$response->successful()) {
                    throw new Exception("FCC Area API failed: " . $response->body());
                }

                $data = $response->json();
                
                return [
                    'found' => !empty($data['results']),
                    'coordinates' => [
                        'latitude' => $parameters['lat'],
                        'longitude' => $parameters['lon'],
                    ],
                    'results' => $data['results'] ?? [],
                    'county' => $data['County'] ?? null,
                    'state' => $data['State'] ?? null,
                    'block_fips' => $data['Block FIPS'] ?? null,
                    'dma' => $data['DMA'] ?? null,
                    'msa' => $data['MSA'] ?? null,
                    'source' => 'fcc',
                ];
            },
            30 // Cache area info for 30 days
        );
    }

    /**
     * Get Census Block information by coordinates
     * 
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return array Census block information
     */
    public function getCensusBlock(float $latitude, float $longitude): array
    {
        $parameters = [
            'lat' => $latitude,
            'lon' => $longitude,
            'censusYear' => 2020,
            'format' => 'json',
        ];
        
        return $this->makeRequest(
            'census_block',
            $parameters,
            function () use ($parameters) {
                $response = $this->createHttpClient()
                    ->get("{$this->areaApiUrl}/block/find", $parameters);

                if (!$response->successful()) {
                    throw new Exception("FCC Census Block API failed: " . $response->body());
                }

                $data = $response->json();
                
                return [
                    'found' => !empty($data['Block']),
                    'coordinates' => [
                        'latitude' => $parameters['lat'],
                        'longitude' => $parameters['lon'],
                    ],
                    'block' => $data['Block'] ?? null,
                    'county' => $data['County'] ?? null,
                    'state' => $data['State'] ?? null,
                    'fips' => $data['FIPS'] ?? null,
                    'source' => 'fcc',
                ];
            },
            30 // Cache census block info for 30 days
        );
    }

    /**
     * Calculate federal excise tax
     * 
     * @param float $amount Service amount
     * @return array Federal excise tax calculation
     */
    public function calculateFederalExciseTax(float $amount): array
    {
        $rate = 0.03; // 3% federal excise tax
        $threshold = 0.20; // $0.20 minimum threshold
        
        $parameters = [
            'amount' => $amount,
            'rate' => $rate,
            'threshold' => $threshold,
        ];
        
        return $this->makeRequest(
            'federal_excise_tax',
            $parameters,
            function () use ($amount, $rate, $threshold) {
                $taxableAmount = max(0, $amount - $threshold);
                $taxAmount = $taxableAmount * $rate;
                
                return [
                    'base_amount' => $amount,
                    'threshold' => $threshold,
                    'taxable_amount' => $taxableAmount,
                    'tax_rate' => $rate,
                    'tax_rate_percentage' => $rate * 100,
                    'tax_amount' => round($taxAmount, 2),
                    'total_amount' => round($amount + $taxAmount, 2),
                    'applies' => $amount > $threshold,
                    'calculation_date' => now()->toISOString(),
                    'source' => 'fcc_calculation',
                ];
            },
            1 // Cache calculations for 1 day
        );
    }

    /**
     * Calculate USF contribution
     * 
     * @param float $amount Interstate revenue amount
     * @param string|null $quarter Quarter for USF rate
     * @return array USF calculation
     */
    public function calculateUsfContribution(float $amount, ?string $quarter = null): array
    {
        $usfData = $this->getUsfRate($quarter);
        $rate = $usfData['rate'];
        
        $parameters = [
            'amount' => $amount,
            'quarter' => $quarter ?? $this->getCurrentQuarter(),
        ];
        
        return $this->makeRequest(
            'usf_calculation',
            $parameters,
            function () use ($amount, $rate, $usfData) {
                $contributionAmount = $amount * $rate;
                
                return [
                    'interstate_revenue' => $amount,
                    'usf_rate' => $rate,
                    'usf_rate_percentage' => $rate * 100,
                    'contribution_amount' => round($contributionAmount, 2),
                    'quarter' => $usfData['quarter'],
                    'effective_date' => $usfData['effective_date'] ?? null,
                    'calculation_date' => now()->toISOString(),
                    'source' => 'fcc_calculation',
                ];
            },
            1 // Cache calculations for 1 day
        );
    }

    /**
     * Get E911 fee information (estimated - actual rates vary by state/locality)
     * 
     * @param string $stateCode State code
     * @param int $lineCount Number of lines
     * @return array E911 fee calculation
     */
    public function calculateE911Fee(string $stateCode, int $lineCount = 1): array
    {
        // Estimated E911 fees by state (actual fees vary and change frequently)
        $e911Rates = [
            'CA' => 0.75,  // California
            'NY' => 1.00,  // New York
            'TX' => 0.50,  // Texas
            'FL' => 0.50,  // Florida
            'IL' => 0.87,  // Illinois
            'PA' => 1.65,  // Pennsylvania
            'OH' => 0.25,  // Ohio
            'GA' => 1.50,  // Georgia
            'NC' => 0.60,  // North Carolina
            'MI' => 0.19,  // Michigan
            // Add more states as needed
        ];
        
        $parameters = [
            'state_code' => strtoupper($stateCode),
            'line_count' => $lineCount,
        ];
        
        return $this->makeRequest(
            'e911_calculation',
            $parameters,
            function () use ($stateCode, $lineCount, $e911Rates) {
                $stateCode = strtoupper($stateCode);
                $feePerLine = $e911Rates[$stateCode] ?? 0.75; // Default rate
                $totalFee = $feePerLine * $lineCount;
                
                return [
                    'state_code' => $stateCode,
                    'line_count' => $lineCount,
                    'fee_per_line' => $feePerLine,
                    'total_fee' => round($totalFee, 2),
                    'rate_available' => isset($e911Rates[$stateCode]),
                    'note' => isset($e911Rates[$stateCode]) 
                        ? 'Estimated rate for ' . $stateCode 
                        : 'Using default rate - actual rate may vary',
                    'calculation_date' => now()->toISOString(),
                    'source' => 'fcc_estimated',
                ];
            },
            7 // Cache E911 calculations for 7 days
        );
    }

    /**
     * Get comprehensive telecom tax calculation
     * 
     * @param float $localAmount Local service amount
     * @param float $longDistanceAmount Long distance amount
     * @param string $stateCode State code for E911
     * @param int $lineCount Number of lines
     * @return array Complete telecom tax breakdown
     */
    public function calculateTelecomTaxes(
        float $localAmount, 
        float $longDistanceAmount, 
        string $stateCode, 
        int $lineCount = 1
    ): array {
        $totalAmount = $localAmount + $longDistanceAmount;
        
        // Calculate each tax component
        $federalExcise = $this->calculateFederalExciseTax($totalAmount);
        $usfContribution = $this->calculateUsfContribution($totalAmount);
        $e911Fee = $this->calculateE911Fee($stateCode, $lineCount);
        
        $totalTax = $federalExcise['tax_amount'] + 
                   $usfContribution['contribution_amount'] + 
                   $e911Fee['total_fee'];
        
        return [
            'service_amounts' => [
                'local_service' => $localAmount,
                'long_distance' => $longDistanceAmount,
                'total_service' => $totalAmount,
            ],
            'tax_breakdown' => [
                'federal_excise_tax' => $federalExcise,
                'usf_contribution' => $usfContribution,
                'e911_fee' => $e911Fee,
            ],
            'summary' => [
                'total_service_amount' => $totalAmount,
                'total_tax_amount' => round($totalTax, 2),
                'total_amount_due' => round($totalAmount + $totalTax, 2),
                'effective_tax_rate' => $totalAmount > 0 ? round(($totalTax / $totalAmount) * 100, 2) : 0,
            ],
            'state_code' => strtoupper($stateCode),
            'line_count' => $lineCount,
            'calculation_date' => now()->toISOString(),
            'source' => 'fcc_comprehensive',
        ];
    }

    /**
     * Get current quarter string
     */
    private function getCurrentQuarter(): string
    {
        $now = now();
        $quarter = ceil($now->month / 3);
        return $now->year . '-Q' . $quarter;
    }

    /**
     * Get quarter start date
     */
    private function getQuarterStartDate(string $quarter): Carbon
    {
        [$year, $q] = explode('-Q', $quarter);
        $quarterNum = (int) $q;
        $month = ($quarterNum - 1) * 3 + 1;
        
        return Carbon::create($year, $month, 1);
    }

    /**
     * Get next quarter start date
     */
    private function getNextQuarterStartDate(string $quarter): Carbon
    {
        $currentQuarterStart = $this->getQuarterStartDate($quarter);
        return $currentQuarterStart->addMonths(3);
    }

    /**
     * Update USF rates (manual method for when new rates are announced)
     * 
     * @param string $quarter Quarter (e.g., '2025-Q2')
     * @param float $rate USF rate as decimal (e.g., 0.360 for 36.0%)
     */
    public function updateUsfRate(string $quarter, float $rate): void
    {
        $this->currentUsfRates[$quarter] = $rate;
        
        // Clear cache for USF rates
        TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', TaxApiQueryCache::PROVIDER_FCC)
            ->where('query_type', TaxApiQueryCache::TYPE_USF_RATES)
            ->delete();
    }

    /**
     * Get all available USF rates
     */
    public function getAllUsfRates(): array
    {
        return [
            'available_quarters' => array_keys($this->currentUsfRates),
            'rates' => $this->currentUsfRates,
            'current_quarter' => $this->getCurrentQuarter(),
            'current_rate' => $this->getUsfRate()['rate'],
            'source' => 'fcc_hardcoded',
        ];
    }
}