<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fast Address-to-Jurisdiction Lookup Service
 * 
 * Uses performance-optimized database schema with indexed columns
 * for sub-millisecond address-to-jurisdiction mapping.
 */
class AddressJurisdictionLookupService
{
    /**
     * Look up tax jurisdictions for a specific address using fast indexed queries
     */
    public function lookupJurisdictions(string $address, string $city, string $state, string $zip): array
    {
        try {
            // Parse address components
            $addressComponents = $this->parseAddress($address);
            
            if (!$addressComponents) {
                return $this->getNoResultsResponse();
            }
            
            // Fast database lookup using indexed columns
            $addressRecord = $this->performAddressLookup(
                $addressComponents['street_number'],
                $addressComponents['street_name'],
                $addressComponents['street_suffix'],
                $zip,
                $state
            );
            
            if (!$addressRecord) {
                return $this->getNoResultsResponse();
            }
            
            // Convert to jurisdiction response format
            return $this->formatJurisdictionResponse($addressRecord);
            
        } catch (\Exception $e) {
            Log::error('Address jurisdiction lookup failed', [
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'error' => $e->getMessage()
            ]);
            
            return $this->getErrorResponse($e->getMessage());
        }
    }
    
    /**
     * Parse address string into components
     */
    protected function parseAddress(string $address): ?array
    {
        // Remove common prefixes and clean
        $clean = trim(strtoupper($address));
        
        // Extract street number, name, and suffix
        $pattern = '/^(\d+)\s+(.+?)(?:\s+(ST|AVE|RD|BLVD|DR|LN|WAY|CT|PL|PKWY|CIR|TRL|PATH))?$/';
        
        if (preg_match($pattern, $clean, $matches)) {
            return [
                'street_number' => intval($matches[1]),
                'street_name' => $this->normalizeStreetName($matches[2]),
                'street_suffix' => $matches[3] ?? null
            ];
        }
        
        return null;
    }
    
    /**
     * Normalize street name for consistent matching
     */
    protected function normalizeStreetName(string $streetName): string
    {
        // Remove common words and normalize
        $clean = strtoupper(trim($streetName));
        
        // Remove common street type words if they appear in the middle
        $cleanWords = ['STREET', 'AVENUE', 'ROAD', 'BOULEVARD', 'DRIVE', 'LANE', 'RD', 'ST', 'AVE', 'BLVD', 'DR', 'LN'];
        foreach ($cleanWords as $word) {
            $clean = str_replace(' ' . $word, '', $clean);
        }
        
        // Handle O'Connor special case - remove apostrophe and spaces
        $clean = str_replace("O'CONNOR", 'OCONNOR', $clean);
        $clean = str_replace('O CONNOR', 'OCONNOR', $clean);
        
        // Remove apostrophes and special characters for matching
        $clean = str_replace("'", '', $clean);
        $clean = preg_replace('/[^A-Z0-9\s]/', '', $clean);
        
        return trim($clean);
    }
    
    /**
     * Perform fast address lookup using indexed database query
     */
    protected function performAddressLookup(int $streetNumber, string $streetName, ?string $streetSuffix, string $zip, string $state): ?object
    {
        // Build potential street name variations for O'Connor
        $streetNameVariations = [$streetName];
        if ($streetName === 'OCONNOR') {
            $streetNameVariations[] = "O'CONNOR";
        } elseif ($streetName === "O'CONNOR") {
            $streetNameVariations[] = 'OCONNOR';
        }
        
        $query = DB::table('address_tax_jurisdictions')
            ->where('state_code', strtoupper($state))
            ->where('zip_code', $zip)
            ->whereIn('street_name', $streetNameVariations)
            ->where('address_from', '<=', $streetNumber)
            ->where('address_to', '>=', $streetNumber);
        
        // Add suffix filtering if provided
        if ($streetSuffix) {
            $query->where(function ($q) use ($streetSuffix) {
                $q->where('street_suffix', $streetSuffix)
                  ->orWhereNull('street_suffix');
            });
        }
        
        // Add address parity check (even/odd)
        $query->where(function ($q) use ($streetNumber) {
            $q->where('address_parity', 'both')
              ->orWhere(function ($parityQuery) use ($streetNumber) {
                  if ($streetNumber % 2 === 0) {
                      $parityQuery->where('address_parity', 'even');
                  } else {
                      $parityQuery->where('address_parity', 'odd');
                  }
              });
        });
        
        return $query->first();
    }
    
    /**
     * Format database result into jurisdiction response
     */
    protected function formatJurisdictionResponse(object $addressRecord): array
    {
        $jurisdictions = [];
        
        // Add primary jurisdictions
        if ($addressRecord->state_jurisdiction_id) {
            $jurisdictions[] = $addressRecord->state_jurisdiction_id;
        }
        
        if ($addressRecord->county_jurisdiction_id) {
            $jurisdictions[] = $addressRecord->county_jurisdiction_id;
        }
        
        if ($addressRecord->city_jurisdiction_id) {
            $jurisdictions[] = $addressRecord->city_jurisdiction_id;
        }
        
        if ($addressRecord->primary_transit_id) {
            $jurisdictions[] = $addressRecord->primary_transit_id;
        }
        
        // Add additional jurisdictions from JSON
        if ($addressRecord->additional_jurisdictions) {
            $additional = json_decode($addressRecord->additional_jurisdictions, true);
            if (is_array($additional)) {
                foreach ($additional as $jurisdictionId) {
                    if (is_numeric($jurisdictionId)) {
                        $jurisdictions[] = intval($jurisdictionId);
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'jurisdiction_ids' => array_values(array_unique(array_filter($jurisdictions, function($id) { 
                return !is_null($id) && $id !== '';
            }))),
            'source' => 'address_lookup_optimized',
            'data_source' => $addressRecord->data_source ?? 'unknown'
        ];
    }
    
    /**
     * Get jurisdiction details by IDs for tax calculation
     */
    public function getJurisdictionDetailsByIds(array $jurisdictionIds): array
    {
        if (empty($jurisdictionIds)) {
            return [];
        }
        
        return DB::table('jurisdiction_master')
            ->whereIn('id', $jurisdictionIds)
            ->select(['id', 'jurisdiction_code', 'jurisdiction_name', 'jurisdiction_type'])
            ->get()
            ->toArray();
    }
    
    /**
     * Test address lookup performance
     */
    public function testPerformance(string $address, string $city, string $state, string $zip): array
    {
        $startTime = microtime(true);
        
        $result = $this->lookupJurisdictions($address, $city, $state, $zip);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        return [
            'result' => $result,
            'execution_time_ms' => round($executionTime, 3),
            'performance_target' => '< 1ms',
            'meets_target' => $executionTime < 1.0
        ];
    }
    
    /**
     * Helper methods for response formatting
     */
    protected function getNoResultsResponse(): array
    {
        return [
            'success' => true,
            'jurisdiction_ids' => [],
            'source' => 'address_lookup_optimized',
            'message' => 'No jurisdictions found for this address'
        ];
    }
    
    protected function getErrorResponse(string $error): array
    {
        return [
            'success' => false,
            'jurisdiction_ids' => [],
            'source' => 'address_lookup_optimized',
            'error' => $error
        ];
    }
}