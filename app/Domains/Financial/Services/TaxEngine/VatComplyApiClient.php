<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;

/**
 * VATcomply API Client
 *
 * Free API service for VAT number validation, VAT rates, IP geolocation,
 * and IBAN validation. Open source alternative to paid VAT APIs.
 *
 * API Documentation: https://www.vatcomply.com/documentation
 * GitHub: https://github.com/madisvain/vatcomply
 */
class VatComplyApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://api.vatcomply.com';

    public function __construct(int $companyId, array $config = [])
    {
        parent::__construct($companyId, TaxApiQueryCache::PROVIDER_VATCOMPLY, $config);
    }

    /**
     * Get rate limits for VATcomply API
     * VATcomply supports 1000+ calls/second, but we'll be conservative
     */
    protected function getRateLimits(): array
    {
        return [
            TaxApiQueryCache::TYPE_VAT_VALIDATION => [
                'max_requests' => 100,
                'window' => 60, // 60 seconds
            ],
            TaxApiQueryCache::TYPE_VAT_RATES => [
                'max_requests' => 100,
                'window' => 60,
            ],
            TaxApiQueryCache::TYPE_GEOCODING => [
                'max_requests' => 100,
                'window' => 60,
            ],
        ];
    }

    /**
     * Validate a VAT number
     *
     * @param  string  $vatNumber  VAT number to validate (e.g., "GB123456789")
     * @return array Validation result with company information
     */
    public function validateVatNumber(string $vatNumber): array
    {
        $parameters = ['vat_number' => $vatNumber];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_VAT_VALIDATION,
            $parameters,
            function () use ($vatNumber) {
                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/vat", ['vat_number' => $vatNumber]);

                if (! $response->successful()) {
                    throw new Exception('VATcomply VAT validation failed: '.$response->body());
                }

                $data = $response->json();

                return [
                    'valid' => $data['valid'] ?? false,
                    'country_code' => $data['country_code'] ?? null,
                    'vat_number' => $data['vat_number'] ?? $vatNumber,
                    'company_name' => $data['company_name'] ?? null,
                    'company_address' => $data['company_address'] ?? null,
                    'query_date' => now()->toISOString(),
                    'source' => 'vatcomply',
                ];
            },
            7 // Cache VAT validations for 7 days
        );
    }

    /**
     * Get VAT rates for a country
     *
     * @param  string  $countryCode  ISO country code (e.g., "GB", "DE")
     * @return array VAT rates for the country
     */
    public function getVatRates(string $countryCode): array
    {
        $parameters = ['country_code' => strtoupper($countryCode)];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_VAT_RATES,
            $parameters,
            function () use ($countryCode) {
                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/rates", ['country_code' => strtoupper($countryCode)]);

                if (! $response->successful()) {
                    throw new Exception('VATcomply VAT rates failed: '.$response->body());
                }

                $data = $response->json();

                return [
                    'country_code' => $data['country_code'] ?? $countryCode,
                    'country_name' => $data['country'] ?? null,
                    'standard_rate' => $data['standard_rate'] ?? null,
                    'reduced_rates' => $data['reduced_rates'] ?? [],
                    'reduced_rate' => $data['reduced_rate'] ?? null,
                    'super_reduced_rate' => $data['super_reduced_rate'] ?? null,
                    'parking_rate' => $data['parking_rate'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'member_state' => $data['member_state'] ?? false,
                    'rates_updated' => now()->toISOString(),
                    'source' => 'vatcomply',
                ];
            },
            30 // Cache VAT rates for 30 days
        );
    }

    /**
     * Get country information by IP address
     *
     * @param  string  $ipAddress  IP address to geolocate
     * @return array Country information including VAT rates
     */
    public function getCountryByIp(string $ipAddress): array
    {
        $parameters = ['ip' => $ipAddress];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_GEOCODING,
            $parameters,
            function () use ($ipAddress) {
                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/geolocate", ['ip_address' => $ipAddress]);

                if (! $response->successful()) {
                    throw new Exception('VATcomply IP geolocation failed: '.$response->body());
                }

                $data = $response->json();

                return [
                    'ip_address' => $ipAddress,
                    'country_code' => $data['country_code'] ?? null,
                    'country_name' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'vat_rates' => $data['rates'] ?? null,
                    'query_date' => now()->toISOString(),
                    'source' => 'vatcomply',
                ];
            },
            1 // Cache IP geolocation for 1 day
        );
    }

    /**
     * Calculate VAT for an amount
     *
     * @param  float  $amount  Amount to calculate VAT for
     * @param  string  $countryCode  Country code for VAT rate
     * @param  string  $rateType  Rate type ('standard', 'reduced', 'super_reduced', 'parking')
     * @return array VAT calculation result
     */
    public function calculateVat(float $amount, string $countryCode, string $rateType = 'standard'): array
    {
        // Get VAT rates for the country
        $rates = $this->getVatRates($countryCode);

        // Determine the VAT rate to use
        $vatRate = match ($rateType) {
            'standard' => $rates['standard_rate'],
            'reduced' => $rates['reduced_rate'],
            'super_reduced' => $rates['super_reduced_rate'],
            'parking' => $rates['parking_rate'],
            default => $rates['standard_rate'],
        };

        if ($vatRate === null) {
            throw new Exception("VAT rate '{$rateType}' not available for country '{$countryCode}'");
        }

        $vatAmount = $amount * ($vatRate / 100);
        $totalAmount = $amount + $vatAmount;

        return [
            'country_code' => $countryCode,
            'country_name' => $rates['country_name'],
            'rate_type' => $rateType,
            'vat_rate' => $vatRate,
            'net_amount' => $amount,
            'vat_amount' => round($vatAmount, 2),
            'gross_amount' => round($totalAmount, 2),
            'currency' => $rates['currency'],
            'calculation_date' => now()->toISOString(),
            'source' => 'vatcomply',
        ];
    }

    /**
     * Validate IBAN (bonus feature from VATcomply)
     *
     * @param  string  $iban  IBAN to validate
     * @return array IBAN validation result
     */
    public function validateIban(string $iban): array
    {
        $parameters = ['iban' => $iban];

        return $this->makeRequest(
            'iban_validation',
            $parameters,
            function () use ($iban) {
                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/iban", ['iban' => $iban]);

                if (! $response->successful()) {
                    throw new Exception('VATcomply IBAN validation failed: '.$response->body());
                }

                $data = $response->json();

                return [
                    'valid' => $data['valid'] ?? false,
                    'iban' => $data['iban'] ?? $iban,
                    'country_code' => $data['country_code'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'query_date' => now()->toISOString(),
                    'source' => 'vatcomply',
                ];
            },
            7 // Cache IBAN validations for 7 days
        );
    }

    /**
     * Get all VAT rates for EU countries
     *
     * @return array All EU VAT rates
     */
    public function getAllEuVatRates(): array
    {
        $parameters = ['all_eu_rates' => true];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_VAT_RATES,
            $parameters,
            function () {
                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/rates");

                if (! $response->successful()) {
                    throw new Exception('VATcomply EU VAT rates failed: '.$response->body());
                }

                $data = $response->json();

                return [
                    'rates' => $data,
                    'last_updated' => now()->toISOString(),
                    'source' => 'vatcomply',
                ];
            },
            30 // Cache all EU rates for 30 days
        );
    }

    /**
     * Check if a country is in the EU for VAT purposes
     *
     * @param  string  $countryCode  Country code to check
     * @return bool True if country is EU member state
     */
    public function isEuMemberState(string $countryCode): bool
    {
        $rates = $this->getVatRates($countryCode);

        return $rates['member_state'] ?? false;
    }

    /**
     * Get the appropriate VAT treatment for a transaction
     *
     * @param  string  $supplierCountry  Supplier country code
     * @param  string  $customerCountry  Customer country code
     * @param  bool  $customerIsBusinesss  Whether customer is a business
     * @param  string|null  $customerVatNumber  Customer VAT number (if business)
     * @return array VAT treatment recommendation
     */
    public function getVatTreatment(
        string $supplierCountry,
        string $customerCountry,
        bool $customerIsBusiness = false,
        ?string $customerVatNumber = null
    ): array {
        $supplierInEu = $this->isEuMemberState($supplierCountry);
        $customerInEu = $this->isEuMemberState($customerCountry);

        // Validate customer VAT number if provided
        $vatValidation = null;
        if ($customerVatNumber) {
            $vatValidation = $this->validateVatNumber($customerVatNumber);
        }

        // Determine VAT treatment
        if ($supplierCountry === $customerCountry) {
            // Domestic transaction
            $treatment = 'domestic';
            $vatRate = $this->getVatRates($supplierCountry)['standard_rate'];
            $chargeVat = true;
        } elseif ($supplierInEu && $customerInEu && $customerIsBusiness && $vatValidation && $vatValidation['valid']) {
            // EU B2B with valid VAT number - reverse charge
            $treatment = 'eu_reverse_charge';
            $vatRate = 0;
            $chargeVat = false;
        } elseif ($supplierInEu && $customerInEu) {
            // EU B2C or B2B without valid VAT number
            $treatment = 'eu_cross_border';
            $vatRate = $this->getVatRates($customerCountry)['standard_rate'];
            $chargeVat = true;
        } else {
            // Non-EU transaction
            $treatment = 'non_eu';
            $vatRate = 0;
            $chargeVat = false;
        }

        return [
            'treatment' => $treatment,
            'charge_vat' => $chargeVat,
            'vat_rate' => $vatRate,
            'supplier_country' => $supplierCountry,
            'customer_country' => $customerCountry,
            'customer_is_business' => $customerIsBusiness,
            'vat_validation' => $vatValidation,
            'notes' => $this->getVatTreatmentNotes($treatment),
            'calculation_date' => now()->toISOString(),
        ];
    }

    /**
     * Get explanatory notes for VAT treatment
     */
    private function getVatTreatmentNotes(string $treatment): string
    {
        return match ($treatment) {
            'domestic' => 'Domestic transaction - charge local VAT rate',
            'eu_reverse_charge' => 'EU B2B with valid VAT number - apply reverse charge (0% VAT)',
            'eu_cross_border' => 'EU B2C or B2B without valid VAT - charge customer country VAT rate',
            'non_eu' => 'Non-EU transaction - no VAT charged',
            default => 'Unknown VAT treatment',
        };
    }
}
