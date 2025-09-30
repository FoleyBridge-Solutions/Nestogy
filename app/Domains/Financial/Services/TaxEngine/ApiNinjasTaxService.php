<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * API Ninjas Tax Service
 * 
 * Simple, reliable US-wide sales tax calculation using API Ninjas.
 * Provides accurate tax rates for all US ZIP codes, cities, and states.
 */
class ApiNinjasTaxService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.api-ninjas.com/v1';
    protected int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->apiKey = config('services.api_ninjas.key');
        
        if (!$this->apiKey) {
            throw new \Exception('API Ninjas API key not configured');
        }
    }

    /**
     * Calculate tax for any US address using API Ninjas
     */
    public function calculateTax(
        float $amount,
        string $serviceType = 'general',
        array $destination = null,
        array $lineItems = []
    ): array {
        try {
            if (!$destination) {
                return $this->getNoTaxResponse($amount);
            }

            // Get tax rates from API Ninjas
            $taxRates = $this->getTaxRates($destination);
            
            if (!$taxRates['success']) {
                return [
                    'success' => false,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'jurisdictions' => [],
                    'error' => $taxRates['error']
                ];
            }

            $rates = $taxRates['data'];
            $jurisdictions = [];
            $totalTaxAmount = 0;
            $totalRate = 0;

            // Build detailed jurisdiction breakdown
            if ($rates['state_rate'] > 0) {
                $stateAmount = $amount * $rates['state_rate'];
                $jurisdictions[] = [
                    'name' => $this->getStateName($destination['state'] ?? ''),
                    'type' => 'state',
                    'tax_rate' => $rates['state_rate'] * 100,
                    'tax_amount' => $stateAmount,
                    'authority' => $this->getStateName($destination['state'] ?? '') . ' State',
                    'code' => 'STATE_' . strtoupper($destination['state'] ?? '')
                ];
                $totalTaxAmount += $stateAmount;
                $totalRate += $rates['state_rate'] * 100;
            }

            if (isset($rates['county_rate']) && $rates['county_rate'] > 0) {
                $countyAmount = $amount * $rates['county_rate'];
                $jurisdictions[] = [
                    'name' => ($destination['city'] ?? '') . ' County',
                    'type' => 'county',
                    'tax_rate' => $rates['county_rate'] * 100,
                    'tax_amount' => $countyAmount,
                    'authority' => ($destination['city'] ?? '') . ' County',
                    'code' => 'COUNTY_' . strtoupper($destination['zip'] ?? '')
                ];
                $totalTaxAmount += $countyAmount;
                $totalRate += $rates['county_rate'] * 100;
            }

            if (isset($rates['city_rate']) && $rates['city_rate'] > 0) {
                $cityAmount = $amount * $rates['city_rate'];
                $jurisdictions[] = [
                    'name' => $destination['city'] ?? 'City',
                    'type' => 'city',
                    'tax_rate' => $rates['city_rate'] * 100,
                    'tax_amount' => $cityAmount,
                    'authority' => $destination['city'] ?? 'City',
                    'code' => 'CITY_' . strtoupper($destination['zip'] ?? '')
                ];
                $totalTaxAmount += $cityAmount;
                $totalRate += $rates['city_rate'] * 100;
            }

            if (isset($rates['additional_rate']) && $rates['additional_rate'] > 0) {
                $additionalAmount = $amount * $rates['additional_rate'];
                $jurisdictions[] = [
                    'name' => 'Special Districts',
                    'type' => 'special_district',
                    'tax_rate' => $rates['additional_rate'] * 100,
                    'tax_amount' => $additionalAmount,
                    'authority' => 'Special Districts',
                    'code' => 'SPECIAL_' . strtoupper($destination['zip'] ?? '')
                ];
                $totalTaxAmount += $additionalAmount;
                $totalRate += $rates['additional_rate'] * 100;
            }

            return [
                'success' => true,
                'tax_amount' => $totalTaxAmount,
                'tax_rate' => $totalRate,
                'jurisdictions' => $jurisdictions,
                'service_type' => $serviceType,
                'calculation_source' => 'api_ninjas',
                'calculation_date' => now()->toISOString(),
                'zip_code' => $destination['zip'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('API Ninjas tax calculation failed', [
                'amount' => $amount,
                'service_type' => $serviceType,
                'destination' => $destination,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'tax_amount' => 0,
                'tax_rate' => 0,
                'jurisdictions' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get tax rates from API Ninjas with caching
     */
    protected function getTaxRates(array $destination): array
    {
        try {
            // Create cache key based on location
            $cacheKey = 'api_ninjas_tax_' . md5(json_encode($destination));
            
            // Try cache first (cache for 1 hour)
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // Prepare API request parameters
            $params = [];
            
            if (!empty($destination['zip'])) {
                $params['zip_code'] = $destination['zip'];
            } elseif (!empty($destination['city']) && !empty($destination['state'])) {
                $params['city'] = $destination['city'];
                $params['state'] = $destination['state'];
            } else {
                return [
                    'success' => false,
                    'error' => 'Insufficient address information for tax lookup'
                ];
            }

            // Make API request
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json'
            ])->timeout(10)->retry(3, 1000)->get($this->baseUrl . '/salestax', $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "API Ninjas request failed: HTTP {$response->status()}"
                ];
            }

            $data = $response->json();
            
            if (empty($data) || !is_array($data)) {
                return [
                    'success' => false,
                    'error' => 'No tax data found for location'
                ];
            }

            $taxData = $data[0]; // API returns array, take first result
            
            $result = [
                'success' => true,
                'data' => [
                    'zip_code' => $taxData['zip_code'] ?? null,
                    'state_rate' => (float)($taxData['state_rate'] ?? 0),
                    'county_rate' => (float)($taxData['county_rate'] ?? 0),
                    'city_rate' => (float)($taxData['city_rate'] ?? 0),
                    'additional_rate' => (float)($taxData['additional_rate'] ?? 0),
                    'total_rate' => (float)($taxData['total_rate'] ?? 0)
                ]
            ];

            // Cache successful results
            Cache::put($cacheKey, $result, 3600);

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get state name from abbreviation
     */
    protected function getStateName(string $stateCode): string
    {
        $states = [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia'
        ];

        return $states[strtoupper($stateCode)] ?? $stateCode;
    }

    /**
     * Get response when no tax calculation possible
     */
    protected function getNoTaxResponse(float $amount): array
    {
        return [
            'success' => true,
            'tax_amount' => 0,
            'tax_rate' => 0,
            'jurisdictions' => [],
            'service_type' => 'general',
            'calculation_source' => 'api_ninjas_no_location',
            'message' => 'No location provided for tax calculation'
        ];
    }

    /**
     * Test the API Ninjas connection
     */
    public function testConnection(string $zipCode = '78247'): array
    {
        try {
            $result = $this->getTaxRates(['zip' => $zipCode]);
            
            return [
                'success' => $result['success'],
                'test_zip' => $zipCode,
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'api_status' => 'API Ninjas connection successful'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_status' => 'API Ninjas connection failed'
            ];
        }
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        return [
            'configured' => !empty($this->apiKey),
            'service' => 'API Ninjas',
            'coverage' => 'All US ZIP codes, cities, and states',
            'cost' => 'Paid API service - much more affordable than TaxCloud',
            'features' => [
                'State tax rates',
                'County tax rates', 
                'City tax rates',
                'Special district rates',
                'Real-time updates',
                'High reliability'
            ]
        ];
    }
}