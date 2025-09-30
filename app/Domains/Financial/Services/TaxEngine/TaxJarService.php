<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TaxJar Tax Calculation Service
 *
 * Simple tax calculation using TaxJar's calculator API
 * https://taxjar.netlify.app/.netlify/functions/calculator
 */
class TaxJarService extends BaseTaxDataService
{
    protected string $apiBaseUrl = 'https://taxjar.netlify.app/.netlify/functions/calculator';

    /**
     * Calculate taxes for an item using TaxJar API
     */
    public function calculateTaxes(array $params): array
    {
        $address = $params['service_address'] ?? [];

        if (empty($address['zip_code'])) {
            Log::warning('TaxJar: No zip code provided for tax calculation', [
                'company_id' => $this->companyId,
                'params' => $params,
            ]);

            return [
                'base_amount' => $params['amount'] ?? 0,
                'total_tax_amount' => 0.0,
                'tax_breakdown' => [],
                'jurisdictions' => [],
                'final_amount' => $params['amount'] ?? 0,
                'calculation_date' => now()->toISOString(),
                'service_type' => $params['service_type'] ?? 'general',
                'federal_taxes' => [],
                'state_taxes' => [],
                'local_taxes' => [],
                'exemptions_applied' => [],
            ];
        }

        // Build API URL with parameters
        $queryParams = [
            'street' => $address['address'] ?? '',
            'city' => $address['city'] ?? '',
            'zip' => $address['zip_code'],
            'country' => $address['country'] ?? 'US',
        ];

        $apiUrl = $this->apiBaseUrl.'?'.http_build_query($queryParams);

        try {
            $response = Http::timeout(10)->get($apiUrl);

            if (! $response->successful()) {
                Log::error('TaxJar API request failed', [
                    'url' => $apiUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'company_id' => $this->companyId,
                ]);

                return $this->getFallbackTaxCalculation($params);
            }

            $taxData = $response->json();

            // Debug: show what we got from the API
            Log::info('TaxJar API Response', [
                'url' => $apiUrl,
                'response' => $taxData,
                'company_id' => $this->companyId,
            ]);

            return $this->formatTaxJarResponse($params, $taxData);

        } catch (\Exception $e) {
            Log::error('TaxJar API exception', [
                'url' => $apiUrl,
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
            ]);

            return $this->getFallbackTaxCalculation($params);
        }
    }

    /**
     * Format TaxJar API response into our standard format
     */
    protected function formatTaxJarResponse(array $params, array $taxData): array
    {
        $baseAmount = $params['amount'] ?? 0;
        $rateData = $taxData['rate'] ?? [];

        // Convert decimal rates to percentages (API returns decimals like 0.0625 for 6.25%)
        $stateRate = ($rateData['state_rate'] ?? 0) * 100;
        $countyRate = ($rateData['county_rate'] ?? 0) * 100;
        $cityRate = ($rateData['city_rate'] ?? 0) * 100;
        $districtRate = ($rateData['combined_district_rate'] ?? 0) * 100;

        $stateTax = $baseAmount * ($stateRate / 100);
        $countyTax = $baseAmount * ($countyRate / 100);
        $cityTax = $baseAmount * ($cityRate / 100);
        $districtTax = $baseAmount * ($districtRate / 100);

        $totalTax = $stateTax + $countyTax + $cityTax + $districtTax;

        $taxBreakdown = [];

        if ($stateTax > 0) {
            $taxBreakdown[] = [
                'tax_name' => $rateData['state'].' State Sales Tax',
                'tax_type' => 'state_sales_tax',
                'rate_type' => 'percentage',
                'rate' => $stateRate,
                'base_amount' => $baseAmount,
                'tax_amount' => round($stateTax, 2),
                'authority' => $rateData['state'],
                'jurisdiction' => $rateData['state'],
            ];
        }

        if ($countyTax > 0) {
            $taxBreakdown[] = [
                'tax_name' => $rateData['county'].' County Sales Tax',
                'tax_type' => 'county_sales_tax',
                'rate_type' => 'percentage',
                'rate' => $countyRate,
                'base_amount' => $baseAmount,
                'tax_amount' => round($countyTax, 2),
                'authority' => $rateData['county'],
                'jurisdiction' => $rateData['county'],
            ];
        }

        if ($cityTax > 0) {
            $taxBreakdown[] = [
                'tax_name' => $rateData['city'].' City Sales Tax',
                'tax_type' => 'city_sales_tax',
                'rate_type' => 'percentage',
                'rate' => $cityRate,
                'base_amount' => $baseAmount,
                'tax_amount' => round($cityTax, 2),
                'authority' => $rateData['city'],
                'jurisdiction' => $rateData['city'],
            ];
        }

        if ($districtTax > 0) {
            $taxBreakdown[] = [
                'tax_name' => 'Special District Sales Tax',
                'tax_type' => 'special_district_tax',
                'rate_type' => 'percentage',
                'rate' => $districtRate,
                'base_amount' => $baseAmount,
                'tax_amount' => round($districtTax, 2),
                'authority' => 'Special District',
                'jurisdiction' => $rateData['state'],
            ];
        }

        return [
            'base_amount' => $baseAmount,
            'total_tax_amount' => round($totalTax, 2),
            'tax_breakdown' => $taxBreakdown,
            'jurisdictions' => [
                [
                    'id' => 1,
                    'name' => $taxData['state'] ?? 'Unknown State',
                    'jurisdiction_type' => 'state',
                ],
            ],
            'final_amount' => round($baseAmount + $totalTax, 2),
            'calculation_date' => now()->toISOString(),
            'service_type' => $params['service_type'] ?? 'general',
            'federal_taxes' => [],
            'state_taxes' => $stateTax > 0 ? [$taxBreakdown[0]] : [],
            'local_taxes' => array_slice($taxBreakdown, 1),
            'exemptions_applied' => [],
            'tax_data' => $taxData, // Keep original API response for debugging
        ];
    }

    /**
     * Fallback tax calculation when API fails
     */
    protected function getFallbackTaxCalculation(array $params): array
    {
        $baseAmount = $params['amount'] ?? 0;

        // Simple fallback: assume 8.25% combined rate
        $fallbackRate = 8.25;
        $totalTax = $baseAmount * ($fallbackRate / 100);

        Log::info('TaxJar: Using fallback tax calculation', [
            'base_amount' => $baseAmount,
            'fallback_rate' => $fallbackRate,
            'company_id' => $this->companyId,
        ]);

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
            'service_type' => $params['service_type'] ?? 'general',
            'federal_taxes' => [],
            'state_taxes' => [],
            'local_taxes' => [],
            'exemptions_applied' => [],
            'fallback_used' => true,
        ];
    }

    /**
     * Download tax rates (not needed for TaxJar - rates are calculated on-demand)
     */
    public function downloadTaxRates(): array
    {
        return [
            'success' => true,
            'message' => 'TaxJar uses on-demand calculation, no bulk download needed',
            'count' => 0,
        ];
    }

    /**
     * Download address data (not needed for TaxJar)
     */
    public function downloadAddressData(?string $jurisdictionCode = null): array
    {
        return [
            'success' => true,
            'message' => 'TaxJar handles address data internally',
            'addresses' => 0,
        ];
    }

    /**
     * Update database with rates (not needed for TaxJar)
     */
    public function updateDatabaseWithRates(array $jurisdictions): array
    {
        return [
            'success' => true,
            'message' => 'TaxJar uses on-demand calculation, no database storage needed',
            'inserted' => 0,
        ];
    }

    /**
     * Get state code for this service
     */
    public function getStateCode(): string
    {
        return 'US'; // TaxJar works nationwide
    }

    /**
     * Get state name for this service
     */
    public function getStateName(): string
    {
        return 'United States';
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return true; // TaxJar doesn't require API keys for basic usage
    }

    /**
     * List available files (not applicable for TaxJar)
     */
    public function listAvailableFiles(?string $quarter = null): array
    {
        return [
            'success' => true,
            'files' => [],
            'count' => 0,
            'message' => 'TaxJar uses on-demand calculation, no file listing needed',
        ];
    }

    /**
     * Download file (not applicable for TaxJar)
     */
    public function downloadFile(string $filePath): array
    {
        return [
            'success' => false,
            'error' => 'TaxJar does not support file downloads',
            'content' => null,
        ];
    }
}
