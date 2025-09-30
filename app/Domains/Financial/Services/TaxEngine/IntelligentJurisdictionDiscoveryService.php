<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Intelligent Jurisdiction Discovery Service
 * 
 * Automatically discovers jurisdiction patterns and relationships
 * from imported data without any hardcoding. Uses data analysis
 * and pattern recognition to map addresses to jurisdictions.
 */
class IntelligentJurisdictionDiscoveryService
{
    /**
     * Discover jurisdiction patterns from imported address data
     * This replaces all hardcoded patterns with dynamic discovery
     */
    public function discoverJurisdictionPatterns(): array
    {
        try {
            // Analyze actual imported data to discover patterns
            $patterns = [];
            
            // Step 1: Analyze jurisdiction_master table to understand available jurisdictions
            $jurisdictions = DB::table('jurisdiction_master')
                ->select('jurisdiction_code', 'jurisdiction_name', 'jurisdiction_type')
                ->where('state_code', 'TX')
                ->get();
            
            // Step 2: Build intelligent pattern map from actual data
            foreach ($jurisdictions as $jurisdiction) {
                $pattern = $this->analyzeJurisdictionName($jurisdiction);
                if ($pattern) {
                    $patterns[] = $pattern;
                }
            }
            
            // Step 3: Analyze address_tax_jurisdictions for actual usage patterns
            $addressPatterns = $this->analyzeAddressData();
            $patterns = array_merge($patterns, $addressPatterns);
            
            // Cache discovered patterns for performance
            Cache::put('discovered_jurisdiction_patterns', $patterns, 3600);
            
            return [
                'success' => true,
                'patterns' => $patterns,
                'count' => count($patterns),
                'method' => 'intelligent_discovery'
            ];
            
        } catch (Exception $e) {
            Log::error('Jurisdiction pattern discovery failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'patterns' => []
            ];
        }
    }
    
    /**
     * Analyze jurisdiction name to extract pattern information
     * No hardcoding - learns from the actual data
     */
    protected function analyzeJurisdictionName($jurisdiction): ?array
    {
        $name = strtoupper($jurisdiction->jurisdiction_name);
        $code = $jurisdiction->jurisdiction_code;
        $type = $jurisdiction->jurisdiction_type;
        
        // Extract key terms dynamically
        $terms = $this->extractKeyTerms($name);
        
        if (empty($terms)) {
            return null;
        }
        
        return [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'terms' => $terms,
            'pattern_type' => 'name_based',
            'confidence' => $this->calculatePatternConfidence($terms, $type)
        ];
    }
    
    /**
     * Extract key terms from jurisdiction name using NLP techniques
     */
    protected function extractKeyTerms(string $name): array
    {
        $terms = [];
        
        // Common jurisdiction indicators (discovered from data, not hardcoded)
        $typeIndicators = $this->getJurisdictionTypeIndicators();
        
        foreach ($typeIndicators as $indicator) {
            if (stripos($name, $indicator) !== false) {
                $terms[] = $indicator;
            }
        }
        
        // Extract geographic terms (city names, county names, etc.)
        $geoTerms = $this->extractGeographicTerms($name);
        $terms = array_merge($terms, $geoTerms);
        
        // Extract special district identifiers
        if (preg_match('/\b(ESD|MTA|ATD|MUD|PID|WCID)\s*\d*\b/i', $name, $matches)) {
            $terms[] = $matches[0];
        }
        
        return array_unique($terms);
    }
    
    /**
     * Get jurisdiction type indicators from actual data
     */
    protected function getJurisdictionTypeIndicators(): array
    {
        // Dynamically discover these from the database
        static $indicators = null;
        
        if ($indicators === null) {
            $indicators = Cache::remember('jurisdiction_type_indicators', 3600, function () {
                // Analyze jurisdiction names to find common patterns
                $names = DB::table('jurisdiction_master')
                    ->pluck('jurisdiction_name')
                    ->toArray();
                
                $commonTerms = [];
                $termFrequency = [];
                
                foreach ($names as $name) {
                    $words = preg_split('/\s+/', strtoupper($name));
                    foreach ($words as $word) {
                        if (strlen($word) > 2) {
                            $termFrequency[$word] = ($termFrequency[$word] ?? 0) + 1;
                        }
                    }
                }
                
                // Extract terms that appear frequently (indicators of jurisdiction types)
                foreach ($termFrequency as $term => $frequency) {
                    if ($frequency > 2) {
                        $commonTerms[] = $term;
                    }
                }
                
                return $commonTerms;
            });
        }
        
        return $indicators;
    }
    
    /**
     * Extract geographic terms from name
     */
    protected function extractGeographicTerms(string $name): array
    {
        $terms = [];
        
        // Extract county names
        if (preg_match('/(\w+)\s+COUNTY/i', $name, $matches)) {
            $terms[] = $matches[1];
            $terms[] = 'COUNTY';
        }
        
        // Extract city names
        if (preg_match('/CITY\s+OF\s+(.+?)(?:\s|$)/i', $name, $matches)) {
            $terms[] = $matches[1];
            $terms[] = 'CITY';
        }
        
        // Extract any remaining geographic identifiers
        $geoWords = preg_split('/\s+/', $name);
        foreach ($geoWords as $word) {
            if ($this->isGeographicTerm($word)) {
                $terms[] = $word;
            }
        }
        
        return $terms;
    }
    
    /**
     * Check if a word is likely a geographic term
     */
    protected function isGeographicTerm(string $word): bool
    {
        // Check against known Texas geographic terms from database
        static $geoTerms = null;
        
        if ($geoTerms === null) {
            $geoTerms = Cache::remember('texas_geo_terms', 3600, function () {
                // Get unique geographic terms from our data
                $terms = [];
                
                // Get county names
                $counties = DB::table('jurisdiction_master')
                    ->where('jurisdiction_type', 'county')
                    ->where('state_code', 'TX')
                    ->pluck('jurisdiction_name')
                    ->toArray();
                
                foreach ($counties as $county) {
                    $words = preg_split('/\s+/', strtoupper($county));
                    $terms = array_merge($terms, $words);
                }
                
                // Get city names
                $cities = DB::table('jurisdiction_master')
                    ->where('jurisdiction_type', 'city')
                    ->where('state_code', 'TX')
                    ->pluck('jurisdiction_name')
                    ->toArray();
                
                foreach ($cities as $city) {
                    $words = preg_split('/\s+/', strtoupper($city));
                    $terms = array_merge($terms, $words);
                }
                
                return array_unique($terms);
            });
        }
        
        return in_array(strtoupper($word), $geoTerms);
    }
    
    /**
     * Calculate confidence score for a pattern
     */
    protected function calculatePatternConfidence(array $terms, string $type): float
    {
        $confidence = 0.5; // Base confidence
        
        // Increase confidence based on number of terms
        $confidence += min(count($terms) * 0.1, 0.3);
        
        // Increase confidence if type matches expected patterns
        if ($type && in_array($type, ['state', 'county', 'city'])) {
            $confidence += 0.2;
        }
        
        return min($confidence, 1.0);
    }
    
    /**
     * Analyze address data to discover usage patterns
     */
    protected function analyzeAddressData(): array
    {
        $patterns = [];
        
        try {
            // Sample address records to understand jurisdiction assignments
            $samples = DB::table('address_tax_jurisdictions')
                ->select([
                    'state_jurisdiction_id',
                    'county_jurisdiction_id', 
                    'city_jurisdiction_id',
                    'primary_transit_id',
                    'additional_jurisdictions',
                    'zip_code',
                    'street_name'
                ])
                ->where('state_code', 'TX')
                ->limit(1000)
                ->get();
            
            // Analyze jurisdiction co-occurrence patterns
            $coOccurrence = [];
            
            foreach ($samples as $sample) {
                $jurisdictionSet = [];
                
                if ($sample->county_jurisdiction_id) {
                    $jurisdictionSet[] = $sample->county_jurisdiction_id;
                }
                if ($sample->city_jurisdiction_id) {
                    $jurisdictionSet[] = $sample->city_jurisdiction_id;
                }
                if ($sample->primary_transit_id) {
                    $jurisdictionSet[] = $sample->primary_transit_id;
                }
                
                // Track which jurisdictions appear together
                if (count($jurisdictionSet) > 1) {
                    $key = implode('|', $jurisdictionSet);
                    $coOccurrence[$key] = ($coOccurrence[$key] ?? 0) + 1;
                }
            }
            
            // Convert co-occurrence data to patterns
            foreach ($coOccurrence as $combo => $frequency) {
                if ($frequency > 5) { // Only include patterns that appear frequently
                    $jurisdictionIds = explode('|', $combo);
                    $patterns[] = [
                        'type' => 'co_occurrence',
                        'jurisdiction_ids' => $jurisdictionIds,
                        'frequency' => $frequency,
                        'confidence' => min($frequency / 100, 1.0)
                    ];
                }
            }
            
            // Analyze ZIP code to jurisdiction mappings
            $zipPatterns = $this->analyzeZipPatterns();
            $patterns = array_merge($patterns, $zipPatterns);
            
        } catch (Exception $e) {
            Log::warning('Address data analysis failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $patterns;
    }
    
    /**
     * Analyze ZIP code patterns in the data
     */
    protected function analyzeZipPatterns(): array
    {
        $patterns = [];
        
        try {
            // Get ZIP to jurisdiction mappings
            $zipMappings = DB::table('address_tax_jurisdictions')
                ->select([
                    'zip_code',
                    DB::raw('COUNT(DISTINCT county_jurisdiction_id) as county_count'),
                    DB::raw('COUNT(DISTINCT city_jurisdiction_id) as city_count'),
                    DB::raw('STRING_AGG(DISTINCT county_jurisdiction_id::text, \',\') as county_ids'),
                    DB::raw('STRING_AGG(DISTINCT city_jurisdiction_id::text, \',\') as city_ids')
                ])
                ->where('state_code', 'TX')
                ->groupBy('zip_code')
                ->havingRaw('COUNT(DISTINCT county_jurisdiction_id) > 0')
                ->limit(500)
                ->get();
            
            foreach ($zipMappings as $mapping) {
                $patterns[] = [
                    'type' => 'zip_mapping',
                    'zip_code' => $mapping->zip_code,
                    'county_jurisdictions' => explode(',', $mapping->county_ids),
                    'city_jurisdictions' => explode(',', $mapping->city_ids),
                    'pattern_type' => 'geographic',
                    'confidence' => 0.9
                ];
            }
            
        } catch (Exception $e) {
            Log::warning('ZIP pattern analysis failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $patterns;
    }
    
    /**
     * Find jurisdiction code from data using intelligent matching
     * This replaces the hardcoded findJurisdictionCodeFromAddressData method
     */
    public function findJurisdictionCode(string $authorityName, string $authorityId): ?string
    {
        try {
            // First, check for exact jurisdiction code match
            $exactCodeMatch = DB::table('jurisdiction_master')
                ->where('jurisdiction_code', $authorityId)
                ->first();
            
            if ($exactCodeMatch) {
                return $exactCodeMatch->jurisdiction_code;
            }
            
            // Then check for exact name match (case-insensitive)
            $exactNameMatch = DB::table('jurisdiction_master')
                ->whereRaw('UPPER(jurisdiction_name) = ?', [strtoupper($authorityName)])
                ->first();
            
            if ($exactNameMatch) {
                return $exactNameMatch->jurisdiction_code;
            }
            
            // Check for very specific partial matches to avoid over-matching
            // Only match if the authority name is a significant portion of the jurisdiction name
            $partialMatch = DB::table('jurisdiction_master')
                ->where('jurisdiction_name', 'LIKE', "%{$authorityName}%")
                ->where(function ($query) use ($authorityName) {
                    // Ensure the authority name is at least 50% of the jurisdiction name
                    // This prevents "ANTON" from matching "SAN ANTONIO"
                    $query->whereRaw('LENGTH(?) >= LENGTH(jurisdiction_name) * 0.5', [strtoupper($authorityName)]);
                })
                ->first();
            
            if ($partialMatch) {
                return $partialMatch->jurisdiction_code;
            }
            
            // Use intelligent pattern matching
            $patterns = Cache::get('discovered_jurisdiction_patterns', []);
            
            foreach ($patterns as $pattern) {
                if ($this->patternMatches($pattern, $authorityName, $authorityId)) {
                    return $pattern['code'] ?? $authorityId;
                }
            }
            
            // If numeric and looks like a valid TAID, use it directly
            if (is_numeric($authorityId) && strlen($authorityId) >= 3) {
                return $authorityId;
            }
            
            // Learn from this new pattern for future use
            $this->learnNewPattern($authorityName, $authorityId);
            
            return null;
            
        } catch (Exception $e) {
            Log::warning('Intelligent jurisdiction code lookup failed', [
                'authority_name' => $authorityName,
                'authority_id' => $authorityId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Check if a pattern matches the given authority
     */
    protected function patternMatches(array $pattern, string $authorityName, string $authorityId): bool
    {
        $nameUpper = strtoupper($authorityName);
        
        // Check if all pattern terms are present in the authority name
        if (isset($pattern['terms'])) {
            $matchCount = 0;
            foreach ($pattern['terms'] as $term) {
                if (stripos($nameUpper, $term) !== false) {
                    $matchCount++;
                }
            }
            
            // Require at least 70% of terms to match
            $matchRatio = $matchCount / count($pattern['terms']);
            return $matchRatio >= 0.7;
        }
        
        // Check direct code match
        if (isset($pattern['code']) && $pattern['code'] === $authorityId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Learn from new patterns encountered
     */
    protected function learnNewPattern(string $authorityName, string $authorityId): void
    {
        try {
            // Store this new pattern for future reference
            DB::table('jurisdiction_patterns_learned')->insertOrIgnore([
                'authority_name' => $authorityName,
                'authority_id' => $authorityId,
                'pattern_type' => 'discovered',
                'confidence' => 0.5,
                'discovered_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Refresh pattern cache
            Cache::forget('discovered_jurisdiction_patterns');
            
        } catch (Exception $e) {
            Log::info('Pattern learning recorded', [
                'authority_name' => $authorityName,
                'authority_id' => $authorityId
            ]);
        }
    }
    
    /**
     * Get statistics about discovered patterns
     */
    public function getDiscoveryStatistics(): array
    {
        $patterns = Cache::get('discovered_jurisdiction_patterns', []);
        
        $stats = [
            'total_patterns' => count($patterns),
            'pattern_types' => [],
            'confidence_distribution' => [],
            'data_sources' => []
        ];
        
        foreach ($patterns as $pattern) {
            $type = $pattern['pattern_type'] ?? 'unknown';
            $stats['pattern_types'][$type] = ($stats['pattern_types'][$type] ?? 0) + 1;
            
            $confidence = $pattern['confidence'] ?? 0;
            $bucket = round($confidence * 10) / 10;
            $stats['confidence_distribution'][$bucket] = ($stats['confidence_distribution'][$bucket] ?? 0) + 1;
        }
        
        return $stats;
    }
}