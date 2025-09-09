<?php

namespace App\Services\TaxEngine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\TaxEngine\AddressJurisdictionLookupService;
use App\Services\TaxEngine\IntelligentJurisdictionDiscoveryService;

/**
 * Local Tax Rate Service
 * 
 * Uses realistic tax rate data stored in the service_tax_rates table
 * to provide accurate jurisdiction-specific tax calculations.
 */
class LocalTaxRateService
{
    protected int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Calculate tax for any service type using API Ninjas (primary) with local fallback
     */
    public function calculateTax(
        float $amount,
        string $serviceType = 'general',
        array $destination = null,
        array $lineItems = []
    ): array {
        try {
            // Primary: Use API Ninjas for accurate US-wide coverage
            if ($destination && !empty($destination['zip'])) {
                $apiNinjasService = new \App\Services\TaxEngine\ApiNinjasTaxService($this->companyId);
                $apiResult = $apiNinjasService->calculateTax($amount, $serviceType, $destination, $lineItems);
                
                if ($apiResult['success']) {
                    Log::info('Tax calculation using API Ninjas successful', [
                        'zip' => $destination['zip'],
                        'tax_rate' => $apiResult['tax_rate'],
                        'tax_amount' => $apiResult['tax_amount']
                    ]);
                    return $apiResult;
                }
                
                Log::warning('API Ninjas failed, falling back to local data', [
                    'error' => $apiResult['error'] ?? 'Unknown error'
                ]);
            }

            // Fallback: Use local tax rates for backup or when API Ninjas unavailable
            $taxRates = $this->getApplicableTaxRatesInternal($serviceType, $destination);
            
            if (empty($taxRates)) {
                return $this->getNoTaxResponse($amount);
            }

            // Calculate tax for each applicable rate
            $jurisdictions = [];
            $totalTaxAmount = 0;
            $totalRate = 0;

            foreach ($taxRates as $taxRate) {
                $jurisdictionTax = $this->calculateJurisdictionTax($amount, $taxRate);
                
                if ($jurisdictionTax > 0) {
                    $jurisdictions[] = [
                        'name' => $taxRate->authority_name ?? $taxRate->tax_name,
                        'type' => $this->getJurisdictionType($taxRate),
                        'tax_rate' => $taxRate->percentage_rate,
                        'tax_amount' => $jurisdictionTax,
                        'authority' => $taxRate->authority_name,
                        'code' => $taxRate->external_id ?? $taxRate->tax_code
                    ];
                    
                    $totalTaxAmount += $jurisdictionTax;
                    $totalRate += $taxRate->percentage_rate;
                }
            }

            return [
                'success' => true,
                'tax_amount' => $totalTaxAmount,
                'tax_rate' => $totalRate,
                'jurisdictions' => $jurisdictions,
                'service_type' => $serviceType,
                'calculation_source' => 'local_tax_rates',
                'calculation_date' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Tax calculation failed', [
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
     * Calculate tax for equipment based on stored tax rates
     */
    public function calculateEquipmentTax(
        float $amount,
        array $destination = null,
        array $origin = null,
        string $customerId = null,
        array $lineItems = []
    ): array {
        try {
            // Get applicable tax rates for equipment based on destination
            $taxRates = $this->getApplicableTaxRatesInternal('equipment', $destination);
            
            if (empty($taxRates)) {
                return $this->getNoTaxResponse($amount);
            }

            $subtotal = $amount;
            $totalTaxAmount = 0;
            $jurisdictions = [];
            
            foreach ($taxRates as $rate) {
                $jurisdictionTax = $this->calculateJurisdictionTax($subtotal, $rate);
                $totalTaxAmount += $jurisdictionTax;
                
                $jurisdictions[] = [
                    'name' => $rate->authority_name,
                    'code' => $rate->tax_code,
                    'type' => $this->normalizeJurisdictionType($rate->tax_code),
                    'tax_amount' => $jurisdictionTax,
                    'tax_rate' => $rate->percentage_rate,
                    'description' => $rate->description
                ];
            }

            $totalTaxRate = ($subtotal > 0) ? ($totalTaxAmount / $subtotal) * 100 : 0;

            return [
                'success' => true,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTaxAmount,
                'total' => $subtotal + $totalTaxAmount,
                'tax_rate' => $totalTaxRate,
                'jurisdictions' => $jurisdictions,
                'source' => 'local_tax_rates',
                'calculation_method' => 'real_jurisdiction_data'
            ];

        } catch (\Exception $e) {
            Log::error('Local tax calculation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'company_id' => $this->companyId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'subtotal' => $amount,
                'tax_amount' => 0,
                'total' => $amount,
                'tax_rate' => 0,
                'source' => 'local_tax_error'
            ];
        }
    }

    /**
     * Public method to get applicable tax rates for external services
     */
    public function getApplicableTaxRates(string $serviceType, array $destination = null): array
    {
        return $this->getApplicableTaxRatesInternal($serviceType, $destination);
    }
    
    /**
     * Get applicable tax rates for a service type and location using precise jurisdiction matching
     */
    protected function getApplicableTaxRatesInternal(string $serviceType, array $destination = null): array
    {
        // If we have full address information, use optimized jurisdiction lookup
        if ($destination && $this->hasCompleteAddress($destination)) {
            return $this->getApplicableTaxRatesByAddress($serviceType, $destination);
        }
        
        // Fallback to legacy filtering for incomplete addresses
        return $this->getApplicableTaxRatesLegacy($serviceType, $destination);
    }
    
    /**
     * Get tax rates using precise address-to-jurisdiction mapping (FAST)
     */
    protected function getApplicableTaxRatesByAddress(string $serviceType, array $destination): array
    {
        try {
            $lookupService = new AddressJurisdictionLookupService();
            
            // Look up exact jurisdictions for this address
            $jurisdictionResult = $lookupService->lookupJurisdictions(
                $destination['line1'] ?? '',
                $destination['city'] ?? '',
                $destination['state'] ?? '',
                $destination['zip'] ?? ''
            );
            
            if (!$jurisdictionResult['success'] || empty($jurisdictionResult['jurisdiction_ids'])) {
                Log::info('No jurisdictions found for address, falling back to legacy', [
                    'destination' => $destination
                ]);
                return $this->getApplicableTaxRatesLegacy($serviceType, $destination);
            }
            
            // Get jurisdiction details
            $jurisdictions = $lookupService->getJurisdictionDetailsByIds($jurisdictionResult['jurisdiction_ids']);
            
            if (empty($jurisdictions)) {
                return [];
            }
            
            // Extract jurisdiction codes for tax rate matching
            $jurisdictionCodes = array_column($jurisdictions, 'jurisdiction_code');
            
            // Fast lookup using jurisdiction codes
            $taxRates = DB::table('service_tax_rates')
                ->where('company_id', $this->companyId)
                ->where('service_type', $serviceType)
                ->where('is_active', 1)
                ->where('effective_date', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', now());
                })
                ->whereIn('external_id', $jurisdictionCodes)
                ->orderBy('priority', 'asc')
                ->get()
                ->toArray();
            
            // Apply jurisdiction filtering to prevent over-taxation
            $taxRates = $this->filterJurisdictionsToPreventOverTaxation($taxRates, $jurisdictions);
            
            Log::info('Fast jurisdiction lookup successful', [
                'jurisdiction_count' => count($jurisdictions),
                'tax_rate_count' => count($taxRates),
                'execution_source' => 'optimized_address_lookup'
            ]);
            
            return $taxRates;
            
        } catch (\Exception $e) {
            Log::error('Fast jurisdiction lookup failed, falling back to legacy', [
                'error' => $e->getMessage(),
                'destination' => $destination
            ]);
            
            return $this->getApplicableTaxRatesLegacy($serviceType, $destination);
        }
    }
    
    /**
     * Legacy tax rate filtering (for backward compatibility)
     */
    protected function getApplicableTaxRatesLegacy(string $serviceType, array $destination = null): array
    {
        $query = DB::table('service_tax_rates')
            ->where('company_id', $this->companyId)
            ->where('service_type', $serviceType)
            ->where('is_active', 1)
            ->where('effective_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>', now());
            });

        // Apply location filtering if destination is provided
        if ($destination && isset($destination['state'])) {
            $state = strtoupper($destination['state']);
            $city = isset($destination['city']) ? strtoupper($destination['city']) : null;
            $zip = isset($destination['zip']) ? $destination['zip'] : null;
            
            $query->where(function ($locationQuery) use ($state, $city, $zip) {
                // Only include rates that apply to this specific state
                $locationQuery->whereRaw("JSON_CONTAINS(JSON_EXTRACT(metadata, '$.applicable_states'), JSON_QUOTE(?))", [$state]);
                
                // Apply geographic filtering based on address
                if ($city || $zip) {
                    $locationQuery->where(function ($geoQuery) use ($state, $city, $zip) {
                        // Always include state-level taxes
                        $geoQuery->where('tax_code', 'LIKE', '%STATE%')
                                 ->orWhere('authority_name', 'LIKE', '%STATE%');
                        
                        // Add city-specific filtering based on known jurisdictions
                        if ($city) {
                            $geoQuery = $this->addCitySpecificFiltering($geoQuery, $city, $state, $zip);
                        }
                        
                        // Add ZIP-based filtering if available
                        if ($zip) {
                            $geoQuery = $this->addZipBasedFiltering($geoQuery, $zip);
                        }
                    });
                }
            });
        }

        return $query->orderBy('priority', 'asc')
                    ->get()
                    ->toArray();
    }
    
    /**
     * Check if destination has complete address information for optimized lookup
     */
    protected function hasCompleteAddress(array $destination): bool
    {
        return !empty($destination['line1']) && 
               !empty($destination['city']) && 
               !empty($destination['state']) && 
               !empty($destination['zip']);
    }
    
    /**
     * Add city-specific filtering using intelligent discovery
     * No hardcoded patterns - uses data-driven approach
     */
    protected function addCitySpecificFiltering($query, string $city, string $state, ?string $zip = null): mixed
    {
        $city = strtoupper($city);
        
        // Use intelligent discovery to find relevant jurisdictions for this city
        $jurisdictions = $this->discoverCityJurisdictions($city, $state, $zip);
        
        if (!empty($jurisdictions)) {
            $query->where(function ($cityQuery) use ($jurisdictions) {
                foreach ($jurisdictions as $jurisdiction) {
                    $cityQuery->orWhere(function ($q) use ($jurisdiction) {
                        // Match by authority name or tax code
                        if (isset($jurisdiction['authority_name'])) {
                            $q->where('authority_name', 'LIKE', '%' . $jurisdiction['authority_name'] . '%');
                        }
                        if (isset($jurisdiction['tax_code'])) {
                            $q->orWhere('tax_code', 'LIKE', '%' . $jurisdiction['tax_code'] . '%');
                        }
                        if (isset($jurisdiction['external_id'])) {
                            $q->orWhere('external_id', $jurisdiction['external_id']);
                        }
                    });
                }
            });
        } else {
            // Fallback: generic city matching
            $query->orWhere('authority_name', 'LIKE', "%{$city}%");
        }
        
        return $query;
    }
    
    /**
     * Add ZIP code-based filtering (future enhancement)
     */
    protected function addZipBasedFiltering($query, string $zip): mixed
    {
        // For now, just add the ZIP to future metadata searches
        // This would be enhanced when we have ZIP-to-jurisdiction mapping
        $query->orWhereRaw("JSON_EXTRACT(metadata, '$.zip_codes') LIKE ?", ["%{$zip}%"]);
        
        return $query;
    }
    
    /**
     * Get Emergency Services District mapping using data-driven discovery
     * No hardcoded ZIP mappings - learns from actual data
     */
    protected function getESDMappingForZip(string $zip): array
    {
        // Use cached discovered mappings for performance
        $cacheKey = "esd_mapping_zip_{$zip}";
        
        return Cache::remember($cacheKey, 3600, function () use ($zip) {
            // Query actual data to find ESDs for this ZIP
            $esdJurisdictions = DB::table('address_tax_jurisdictions')
                ->select('additional_jurisdictions')
                ->where('zip_code', $zip)
                ->where('state_code', 'TX')
                ->whereNotNull('additional_jurisdictions')
                ->limit(100)
                ->get();
            
            $esds = [];
            foreach ($esdJurisdictions as $record) {
                $additional = json_decode($record->additional_jurisdictions, true);
                if (is_array($additional)) {
                    foreach ($additional as $key => $jurisdictionId) {
                        // Check if this is an ESD by looking at the jurisdiction details
                        if (str_starts_with($key, 'spd') || str_contains($key, 'esd')) {
                            $jurisdiction = DB::table('jurisdiction_master')
                                ->where('id', $jurisdictionId)
                                ->first();
                            
                            if ($jurisdiction && str_contains(strtoupper($jurisdiction->jurisdiction_name), 'ESD')) {
                                // Extract ESD number from name
                                if (preg_match('/ESD\s*(\d+)/i', $jurisdiction->jurisdiction_name, $matches)) {
                                    $esds[] = 'ESD ' . $matches[1];
                                }
                            }
                        }
                    }
                }
            }
            
            return array_unique($esds);
        });
    }

    /**
     * Get jurisdiction type based on tax rate metadata
     */
    protected function getJurisdictionType($taxRate): string
    {
        // Try to get from metadata first
        if (isset($taxRate->metadata)) {
            $metadata = is_string($taxRate->metadata) ? json_decode($taxRate->metadata, true) : $taxRate->metadata;
            if (isset($metadata['jurisdiction_type'])) {
                return $metadata['jurisdiction_type'];
            }
        }
        
        // Fallback to authority name analysis
        $authorityName = strtoupper($taxRate->authority_name ?? $taxRate->tax_name ?? '');
        
        if (strpos($authorityName, 'STATE') !== false) {
            return 'state';
        } elseif (strpos($authorityName, 'COUNTY') !== false) {
            return 'county';
        } elseif (strpos($authorityName, 'MTA') !== false || strpos($authorityName, 'ATD') !== false || strpos($authorityName, 'TRANSIT') !== false) {
            return 'transit';
        } elseif (strpos($authorityName, 'DISTRICT') !== false) {
            return 'special_district';
        } else {
            return 'city';
        }
    }
    
    /**
     * Calculate tax for a specific jurisdiction
     */
    protected function calculateJurisdictionTax(float $amount, $taxRate): float
    {
        switch ($taxRate->rate_type) {
            case 'percentage':
                return $amount * ($taxRate->percentage_rate / 100);
            
            case 'fixed':
                return $taxRate->fixed_amount ?? 0;
            
            default:
                return 0;
        }
    }

    /**
     * Normalize jurisdiction type based on tax code
     */
    protected function normalizeJurisdictionType(string $taxCode): string
    {
        $code = strtoupper($taxCode);
        
        if (str_contains($code, 'STATE')) {
            return 'state';
        } elseif (str_contains($code, 'COUNTY')) {
            return 'county';
        } elseif (str_contains($code, 'CITY')) {
            return 'city';
        } elseif (str_contains($code, 'MTA') || str_contains($code, 'TRANSIT')) {
            return 'transit_authority';
        } elseif (str_contains($code, 'ESD')) {
            return 'emergency_services_district';
        } else {
            return 'special_district';
        }
    }

    /**
     * Get response when no tax rates are found
     */
    protected function getNoTaxResponse(float $amount): array
    {
        return [
            'success' => true,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total' => $amount,
            'tax_rate' => 0,
            'jurisdictions' => [],
            'source' => 'local_tax_no_rates',
            'message' => 'No applicable tax rates found'
        ];
    }

    /**
     * Test the local tax rate system
     */
    public function testCalculation(): array
    {
        try {
            $testResult = $this->calculateEquipmentTax(100.00);
            
            return [
                'success' => $testResult['success'],
                'test_result' => $testResult,
                'configuration_status' => 'Local tax rates configured'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'configuration_status' => 'Local tax rates error'
            ];
        }
    }

    /**
     * Discover city jurisdictions using intelligent pattern matching
     */
    protected function discoverCityJurisdictions(string $city, string $state, ?string $zip = null): array
    {
        $cacheKey = "city_jurisdictions_{$state}_{$city}" . ($zip ? "_{$zip}" : '');
        
        return Cache::remember($cacheKey, 3600, function () use ($city, $state, $zip) {
            $jurisdictions = [];
            
            // Query actual data to find jurisdictions for this city
            $query = DB::table('service_tax_rates')
                ->select('authority_name', 'tax_code', 'external_id')
                ->where('is_active', 1)
                ->whereRaw("JSON_CONTAINS(JSON_EXTRACT(metadata, '$.applicable_states'), JSON_QUOTE(?))", [$state]);
            
            // If we have a ZIP, use it to find more precise jurisdictions
            if ($zip) {
                // Find jurisdictions from address data
                $addressJurisdictions = DB::table('address_tax_jurisdictions')
                    ->select(['county_jurisdiction_id', 'city_jurisdiction_id', 'primary_transit_id'])
                    ->where('zip_code', $zip)
                    ->where('state_code', $state)
                    ->limit(10)
                    ->get();
                
                $jurisdictionIds = [];
                foreach ($addressJurisdictions as $addr) {
                    if ($addr->county_jurisdiction_id) $jurisdictionIds[] = $addr->county_jurisdiction_id;
                    if ($addr->city_jurisdiction_id) $jurisdictionIds[] = $addr->city_jurisdiction_id;
                    if ($addr->primary_transit_id) $jurisdictionIds[] = $addr->primary_transit_id;
                }
                
                if (!empty($jurisdictionIds)) {
                    // Get jurisdiction codes
                    $codes = DB::table('jurisdiction_master')
                        ->whereIn('id', array_unique($jurisdictionIds))
                        ->pluck('jurisdiction_code')
                        ->toArray();
                    
                    if (!empty($codes)) {
                        $query->whereIn('external_id', $codes);
                    }
                }
            }
            
            // Also include jurisdictions that match the city name
            $query->orWhere('authority_name', 'LIKE', "%{$city}%");
            
            $results = $query->distinct()->get();
            
            foreach ($results as $result) {
                $jurisdictions[] = [
                    'authority_name' => $result->authority_name,
                    'tax_code' => $result->tax_code,
                    'external_id' => $result->external_id
                ];
            }
            
            // Also check for county jurisdictions that typically apply to cities
            if (empty($jurisdictions)) {
                // Try to find the county for this city
                $countyName = $this->discoverCountyForCity($city, $state);
                if ($countyName) {
                    $countyJurisdictions = DB::table('service_tax_rates')
                        ->select('authority_name', 'tax_code', 'external_id')
                        ->where('is_active', 1)
                        ->where('authority_name', 'LIKE', "%{$countyName}%")
                        ->distinct()
                        ->get();
                    
                    foreach ($countyJurisdictions as $result) {
                        $jurisdictions[] = [
                            'authority_name' => $result->authority_name,
                            'tax_code' => $result->tax_code,
                            'external_id' => $result->external_id
                        ];
                    }
                }
            }
            
            return $jurisdictions;
        });
    }
    
    /**
     * Discover which county a city belongs to
     */
    protected function discoverCountyForCity(string $city, string $state): ?string
    {
        // Query address data to find the county for this city
        $sample = DB::table('address_tax_jurisdictions as a')
            ->join('jurisdiction_master as j', 'a.county_jurisdiction_id', '=', 'j.id')
            ->where('a.state_code', $state)
            ->whereRaw("UPPER(a.street_name) LIKE ?", ["%{$city}%"])
            ->select('j.jurisdiction_name')
            ->first();
        
        if ($sample && $sample->jurisdiction_name) {
            // Extract county name
            if (preg_match('/(\w+)\s*COUNTY/i', $sample->jurisdiction_name, $matches)) {
                return $matches[1];
            }
            return $sample->jurisdiction_name;
        }
        
        return null;
    }
    
    /**
     * Filter jurisdictions to prevent over-taxation by removing duplicate or overlapping authorities
     */
    protected function filterJurisdictionsToPreventOverTaxation(array $taxRates, array $jurisdictions): array
    {
        // Group tax rates by external_id to identify duplicates
        $groupedRates = [];
        foreach ($taxRates as $rate) {
            $externalId = $rate->external_id ?? 'unknown';
            if (!isset($groupedRates[$externalId])) {
                $groupedRates[$externalId] = [];
            }
            $groupedRates[$externalId][] = $rate;
        }
        
        $filteredRates = [];
        
        foreach ($groupedRates as $externalId => $rates) {
            if (count($rates) === 1) {
                // Single rate for this jurisdiction - include it
                $filteredRates[] = $rates[0];
            } else {
                // Multiple rates for the same jurisdiction - need to pick the correct one
                $selectedRate = $this->selectBestRateFromDuplicates($rates);
                if ($selectedRate) {
                    $filteredRates[] = $selectedRate;
                }
            }
        }
        
        // Log filtering results
        $originalCount = count($taxRates);
        $filteredCount = count($filteredRates);
        
        if ($originalCount !== $filteredCount) {
            Log::info('Jurisdiction filtering applied to prevent over-taxation', [
                'original_count' => $originalCount,
                'filtered_count' => $filteredCount,
                'removed_duplicates' => $originalCount - $filteredCount
            ]);
        }
        
        return $filteredRates;
    }
    
    /**
     * Select the best tax rate from duplicate entries for the same jurisdiction
     */
    protected function selectBestRateFromDuplicates(array $rates): ?object
    {
        // Sort by priority (lower = higher priority) and created date (newer = better)
        usort($rates, function ($a, $b) {
            // First by priority
            $priorityCompare = ($a->priority ?? 999) <=> ($b->priority ?? 999);
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }
            
            // Then by creation date (newer first)
            $createdA = $a->created_at ?? '1970-01-01';
            $createdB = $b->created_at ?? '1970-01-01';
            return strcmp($createdB, $createdA);
        });
        
        $selectedRate = $rates[0];
        
        // Log which rate was selected and why
        Log::info('Selected best rate from duplicates', [
            'external_id' => $selectedRate->external_id,
            'selected_authority' => $selectedRate->authority_name,
            'selected_rate' => $selectedRate->percentage_rate,
            'total_duplicates' => count($rates),
            'reason' => 'highest_priority_newest_date'
        ]);
        
        return $selectedRate;
    }
    
    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        try {
            $rateCount = DB::table('service_tax_rates')
                ->where('company_id', $this->companyId)
                ->where('is_active', 1)
                ->count();
            
            // Check if intelligent discovery is active
            $discoveryService = new IntelligentJurisdictionDiscoveryService();
            $discoveryStats = $discoveryService->getDiscoveryStatistics();

            return [
                'configured' => $rateCount > 0,
                'active_rates' => $rateCount,
                'source' => 'intelligent_local_system',
                'discovery_enabled' => true,
                'discovered_patterns' => $discoveryStats['total_patterns'] ?? 0,
                'cost' => 'FREE'
            ];

        } catch (\Exception $e) {
            return [
                'configured' => false,
                'error' => $e->getMessage(),
                'source' => 'intelligent_local_system',
                'cost' => 'FREE'
            ];
        }
    }
}