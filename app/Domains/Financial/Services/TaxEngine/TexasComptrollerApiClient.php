<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Texas Comptroller GIS API Client
 * 
 * Uses the free Texas Comptroller GIS system to get accurate tax rates
 * for Texas addresses. This is the official state system and provides
 * real-time jurisdiction-specific tax rates at no cost.
 * 
 * API: https://gis.cpa.texas.gov/search/
 */
class TexasComptrollerApiClient
{
    protected string $baseUrl = 'https://gis.cpa.texas.gov';
    protected int $timeout = 15;

    public function __construct(int $companyId, array $config = [])
    {
        $this->timeout = $config['timeout'] ?? 15;
    }

    /**
     * Calculate tax for Texas addresses using the official Comptroller API
     */
    public function calculateTexasTax(
        float $amount,
        array $destination,
        array $origin = null,
        string $customerId = null,
        array $lineItems = []
    ): array {
        if (!$this->isTexasAddress($destination)) {
            return $this->getNotTexasResponse($amount);
        }

        try {
            $taxRate = $this->getTaxRateForAddress($destination);
            
            if ($taxRate['success']) {
                $taxAmount = $amount * $taxRate['total_rate'];
                
                return [
                    'success' => true,
                    'subtotal' => $amount,
                    'tax_amount' => $taxAmount,
                    'total' => $amount + $taxAmount,
                    'tax_rate' => $taxRate['total_rate'] * 100,
                    'jurisdictions' => $taxRate['jurisdictions'],
                    'source' => 'texas_comptroller',
                    'address' => $taxRate['formatted_address']
                ];
            } else {
                throw new Exception($taxRate['error']);
            }

        } catch (Exception $e) {
            Log::error('Texas Comptroller tax calculation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'destination' => $destination
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'subtotal' => $amount,
                'tax_amount' => 0,
                'total' => $amount,
                'tax_rate' => 0,
                'source' => 'texas_comptroller_error'
            ];
        }
    }

    /**
     * Get tax rate for a specific Texas address
     */
    protected function getTaxRateForAddress(array $address): array
    {
        $zipCode = $this->extractZipCode($address);
        $fullAddress = $this->formatAddressForSearch($address);
        
        // Cache key for this address
        $cacheKey = "texas_tax_rate_" . md5($fullAddress);
        
        // Check cache first (cache for 24 hours)
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // Make request to Texas Comptroller GIS API
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/search/", [
                    'Address' => $address['line1'] ?? $address['street'] ?? '',
                    'City' => $address['city'] ?? '',
                    'State' => 'TX',
                    'ZipCode' => $zipCode
                ]);

            if ($response->successful()) {
                $html = $response->body();
                $taxData = $this->parseTexasComptrollerResponse($html);
                
                if ($taxData['success']) {
                    // Cache the result for 24 hours
                    Cache::put($cacheKey, $taxData, now()->addHours(24));
                }
                
                return $taxData;
            } else {
                throw new Exception("API request failed with status: " . $response->status());
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to get tax rate from Texas Comptroller: ' . $e->getMessage(),
                'total_rate' => 0,
                'jurisdictions' => []
            ];
        }
    }

    /**
     * Parse the HTML response from Texas Comptroller to extract tax data
     */
    protected function parseTexasComptrollerResponse(string $html): array
    {
        try {
            $jurisdictions = [];
            $totalRate = 0;
            $foundResults = false;

            // Look for the tax rate table in the HTML
            if (preg_match('/JURISDICTION NAME\s+TEXAS.*?TOTAL TAX RATE\s+([\d.]+)/s', $html, $matches)) {
                $foundResults = true;
                
                // Extract all jurisdiction entries
                preg_match_all('/JURISDICTION NAME\s+([^\n]+)\s+Code\s+(\d+)\s+Type\s+([^\n]+)\s+Tax Rate\s+([\d.]+)/s', $html, $jurisdictionMatches, PREG_SET_ORDER);
                
                foreach ($jurisdictionMatches as $match) {
                    $name = trim($match[1]);
                    $code = trim($match[2]);
                    $type = trim($match[3]);
                    $rate = (float) trim($match[4]);
                    
                    $jurisdictions[] = [
                        'name' => $name,
                        'code' => $code,
                        'type' => $this->normalizeJurisdictionType($type),
                        'tax_amount' => 0, // Will be calculated when applied to amount
                        'tax_rate' => $rate * 100 // Convert to percentage
                    ];
                    
                    $totalRate += $rate;
                }
                
                // Get the total rate from the parsed data
                if (isset($matches[1])) {
                    $totalRate = (float) $matches[1];
                }
            }

            if (!$foundResults) {
                return [
                    'success' => false,
                    'error' => 'No tax rate data found in response',
                    'total_rate' => 0,
                    'jurisdictions' => []
                ];
            }

            return [
                'success' => true,
                'total_rate' => $totalRate,
                'jurisdictions' => $jurisdictions,
                'formatted_address' => $this->extractFormattedAddress($html)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to parse tax rate response: ' . $e->getMessage(),
                'total_rate' => 0,
                'jurisdictions' => []
            ];
        }
    }

    /**
     * Normalize jurisdiction types to standard values
     */
    protected function normalizeJurisdictionType(string $type): string
    {
        $type = strtoupper(trim($type));
        
        $typeMap = [
            'STATE' => 'state',
            'SPD' => 'special_district',
            'TRANSIT' => 'transit_authority',
            'CITY' => 'city',
            'COUNTY' => 'county',
            'MUD' => 'municipal_utility_district',
            'ESD' => 'emergency_services_district'
        ];

        return $typeMap[$type] ?? strtolower($type);
    }

    /**
     * Extract formatted address from response
     */
    protected function extractFormattedAddress(string $html): string
    {
        if (preg_match('/Results found:\s*\d+\s*([^\n]+)/i', $html, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Check if address is in Texas
     */
    protected function isTexasAddress(array $address): bool
    {
        $state = $address['state'] ?? $address['state_code'] ?? '';
        return strtoupper($state) === 'TX';
    }

    /**
     * Extract zip code from address
     */
    protected function extractZipCode(array $address): string
    {
        $zip = $address['zip'] ?? $address['postal_code'] ?? $address['zip_code'] ?? '';
        
        // Extract just the 5-digit zip if it includes +4
        if (preg_match('/(\d{5})/', $zip, $matches)) {
            return $matches[1];
        }
        
        return $zip;
    }

    /**
     * Format address for search
     */
    protected function formatAddressForSearch(array $address): string
    {
        $parts = [
            $address['line1'] ?? $address['street'] ?? '',
            $address['city'] ?? '',
            'TX',
            $this->extractZipCode($address)
        ];
        
        return implode(', ', array_filter($parts));
    }

    /**
     * Return response for non-Texas addresses
     */
    protected function getNotTexasResponse(float $amount): array
    {
        return [
            'success' => false,
            'error' => 'Texas Comptroller API only supports Texas addresses',
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total' => $amount,
            'tax_rate' => 0,
            'source' => 'texas_comptroller_out_of_state'
        ];
    }

    /**
     * Test the connection to Texas Comptroller API
     */
    public function testConnection(): array
    {
        try {
            // Test with a known Texas address
            $testResult = $this->calculateTexasTax(
                100.00,
                [
                    'line1' => '25334 TRIANGLE LOOP',
                    'city' => 'SAN ANTONIO',
                    'state' => 'TX',
                    'zip' => '78255'
                ]
            );

            return [
                'success' => $testResult['success'],
                'connection_status' => $testResult['success'] ? 'Connected' : 'Failed',
                'test_result' => $testResult,
                'api_version' => 'texas_comptroller'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'connection_status' => 'Failed',
                'error' => $e->getMessage(),
                'api_version' => 'texas_comptroller'
            ];
        }
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        return [
            'configured' => true, // No credentials needed - it's a free public API
            'api_url' => $this->baseUrl,
            'api_version' => 'texas_comptroller',
            'cost' => 'FREE'
        ];
    }
}