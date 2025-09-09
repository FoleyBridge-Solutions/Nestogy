<?php

namespace App\Services\TaxEngine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TaxEngine\IntelligentJurisdictionDiscoveryService;
use Exception;

/**
 * Texas Comptroller Official Tax Data Service
 * 
 * Downloads and processes official Texas tax rate files from:
 * https://comptroller.texas.gov/taxes/sales/
 * 
 * Files include:
 * - Local Jurisdiction Tax Rate file (CSV with rates)
 * - Texas Address Dataset files (ZIP files by county/MSA)
 */
class TexasComptrollerDataService
{
    protected string $baseUrl = 'https://api.comptroller.texas.gov/sift/v1/sift/public';
    protected string $apiKey;
    protected string $quarter;
    protected int $year;

    public function __construct()
    {
        $this->apiKey = env('TEXAS_COMPTROLLER_API_KEY', '');
        // Default to current quarter
        $this->year = date('Y');
        $this->quarter = 'Q' . ceil(date('n') / 3);
    }

    /**
     * List available files from Texas Comptroller API
     */
    public function listAvailableFiles(string $filterQuarter = null): array
    {
        try {
            if (!$this->apiKey) {
                throw new Exception('Texas Comptroller API key not configured');
            }

            $url = "{$this->baseUrl}/list-files";
            
            if ($filterQuarter) {
                $url .= "?filter-by-quarter=" . urlencode($filterQuarter);
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Texas Comptroller: Listed available files', [
                    'count' => count($data['data'] ?? []),
                    'quarter' => $filterQuarter
                ]);

                return [
                    'success' => true,
                    'files' => $data['data'] ?? [],
                    'count' => count($data['data'] ?? [])
                ];
            } else {
                throw new Exception('API request failed: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Texas Comptroller: Failed to list files', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'files' => []
            ];
        }
    }

    /**
     * Download a specific file using the Texas Comptroller API
     */
    public function downloadFile(string $filePath): array
    {
        try {
            if (!$this->apiKey) {
                throw new Exception('Texas Comptroller API key not configured');
            }

            $url = "{$this->baseUrl}/get-link?file-path=" . urlencode($filePath);

            $response = Http::timeout(30)
                ->withoutRedirecting() // Don't follow the 307 redirect automatically
                ->withHeaders([
                    'x-api-key' => $this->apiKey
                ])
                ->get($url);

            if ($response->status() === 307) {
                // Get the signed URL from the Location header
                $location = $response->header('Location');
                
                if ($location) {
                    // Download the actual file content
                    $fileResponse = Http::timeout(60)->get($location);
                    
                    if ($fileResponse->successful()) {
                        Log::info('Texas Comptroller: Successfully downloaded file', [
                            'file_path' => $filePath,
                            'size' => strlen($fileResponse->body())
                        ]);

                        return [
                            'success' => true,
                            'content' => $fileResponse->body(),
                            'size' => strlen($fileResponse->body()),
                            'file_path' => $filePath
                        ];
                    } else {
                        throw new Exception('Failed to download file content from signed URL');
                    }
                } else {
                    throw new Exception('No Location header in 307 response');
                }
            } else {
                throw new Exception('Expected 307 redirect, got: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Texas Comptroller: Failed to download file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }

    /**
     * Find and download the tax jurisdiction rates file for current quarter
     */
    public function downloadTaxRatesFile(): array
    {
        try {
            // List files for current quarter
            $fileList = $this->listAvailableFiles($this->year . $this->quarter);
            
            if (!$fileList['success']) {
                throw new Exception('Failed to list files: ' . $fileList['error']);
            }

            // Look for the tax jurisdiction rates file
            $taxRatesFile = null;
            foreach ($fileList['files'] as $file) {
                if (str_contains($file['filePath'], 'tax_jurisdiction_rates') && 
                    str_contains($file['filePath'], $this->year . $this->quarter)) {
                    $taxRatesFile = $file;
                    break;
                }
            }

            if (!$taxRatesFile) {
                throw new Exception('Tax jurisdiction rates file not found for ' . $this->year . $this->quarter);
            }

            // Download the tax rates file
            $download = $this->downloadFile($taxRatesFile['filePath']);
            
            if (!$download['success']) {
                throw new Exception('Failed to download tax rates file: ' . $download['error']);
            }

            Log::info('Texas Comptroller: Downloaded tax rates file', [
                'file_path' => $taxRatesFile['filePath'],
                'size' => $download['size']
            ]);

            return [
                'success' => true,
                'content' => $download['content'],
                'file_info' => $taxRatesFile,
                'quarter' => $this->quarter,
                'year' => $this->year
            ];

        } catch (Exception $e) {
            Log::error('Texas Comptroller: Failed to download tax rates file', [
                'error' => $e->getMessage(),
                'quarter' => $this->quarter,
                'year' => $this->year
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse Texas tax jurisdiction rates CSV file
     * Expected format: Local Jurisdiction Name, Authority ID, Tax Rate
     */
    public function parseTaxRatesFile(string $csvContent): array
    {
        try {
            $lines = str_getcsv($csvContent, "\n");
            $jurisdictions = [];
            $header = true;

            foreach ($lines as $line) {
                if ($header) {
                    $header = false;
                    continue; // Skip header row
                }

                $data = str_getcsv($line);
                
                if (count($data) >= 3) {
                    // Convert decimal tax rate to percentage (e.g., 0.0125 -> 1.25%)
                    $decimalRate = (float) trim($data[2]);
                    $percentageRate = $decimalRate * 100;
                    
                    $jurisdictions[] = [
                        'name' => trim($data[0]),
                        'authority_id' => trim($data[1]),
                        'tax_rate' => $percentageRate,
                        'source' => 'texas_comptroller',
                        'quarter' => $this->quarter,
                        'year' => $this->year
                    ];
                }
            }

            Log::info('Texas Comptroller: Parsed tax rates file', [
                'jurisdictions_count' => count($jurisdictions)
            ]);

            return [
                'success' => true,
                'jurisdictions' => $jurisdictions,
                'count' => count($jurisdictions)
            ];

        } catch (Exception $e) {
            Log::error('Texas Comptroller: Failed to parse tax rates file', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'jurisdictions' => []
            ];
        }
    }

    /**
     * Update local database with Texas tax rates and automatically map jurisdiction codes
     */
    public function updateDatabaseWithTexasRates(array $jurisdictions): array
    {
        try {
            DB::beginTransaction();

            // Clear existing Texas rates for current quarter
            DB::table('service_tax_rates')
                ->where('source', 'texas_comptroller')
                ->where('metadata->quarter', $this->quarter)
                ->where('metadata->year', $this->year)
                ->delete();

            $inserted = 0;
            $mapped = 0;

            // First, automatically add Texas State tax rate (6.25%) which applies to all addresses
            $this->insertTexasStateTaxRate();
            $inserted++;
            $mapped++;

            foreach ($jurisdictions as $jurisdiction) {
                // Determine jurisdiction type and location
                $jurisdictionType = $this->determineJurisdictionType($jurisdiction['name']);
                $location = $this->parseJurisdictionLocation($jurisdiction['name']);

                // Create or get jurisdiction record
                $jurisdictionId = $this->createOrGetJurisdiction($jurisdiction, $jurisdictionType, $location);

                // Create unique jurisdiction code based on authority name and ID to prevent collisions
                $externalId = $this->createUniqueJurisdictionCode($jurisdiction);
                
                // Skip if this would map to Texas State (external_id = '1') to avoid duplicates
                if ($externalId === '1') {
                    Log::info('Texas Comptroller: Skipping duplicate Texas State jurisdiction', [
                        'name' => $jurisdiction['name'],
                        'authority_id' => $jurisdiction['authority_id']
                    ]);
                    continue; // Skip this jurisdiction entirely
                }
                
                if ($externalId) {
                    $mapped++;
                    Log::info('Texas Comptroller: Auto-mapped jurisdiction', [
                        'name' => $jurisdiction['name'],
                        'authority_id' => $jurisdiction['authority_id'],
                        'external_id' => $externalId
                    ]);
                }

                // Insert for both equipment and general service types
                foreach (['equipment', 'general'] as $serviceType) {
                    DB::table('service_tax_rates')->insert([
                        'company_id' => 1, // Default company
                        'tax_jurisdiction_id' => $jurisdictionId,
                        'tax_category_id' => 1,
                        'service_type' => $serviceType,
                        'tax_type' => 'sales',
                        'tax_name' => $jurisdiction['name'],
                        'authority_name' => $jurisdiction['name'],
                        'tax_code' => 'TX_' . $jurisdiction['authority_id'] . '_' . strtoupper($serviceType),
                        'external_id' => $externalId, // Automatically mapped jurisdiction code
                        'description' => 'Official Texas Comptroller tax rate',
                        'rate_type' => 'percentage',
                        'percentage_rate' => $jurisdiction['tax_rate'],
                        'calculation_method' => 'standard',
                        'service_types' => json_encode([$serviceType]),
                        'is_active' => 1,
                        'is_recoverable' => 1,
                        'priority' => $this->getJurisdictionPriority($jurisdictionType),
                        'effective_date' => $this->getQuarterStartDate(),
                        'source' => 'texas_comptroller',
                        'metadata' => json_encode([
                            'quarter' => $this->quarter,
                            'year' => $this->year,
                            'authority_id' => $jurisdiction['authority_id'],
                            'jurisdiction_type' => $jurisdictionType,
                            'applicable_states' => ['TX'],
                            'location' => $location,
                            'auto_mapped' => !is_null($externalId)
                        ]),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $inserted++;
            }

            DB::commit();

            Log::info('Texas Comptroller: Updated database with tax rates', [
                'inserted' => $inserted,
                'auto_mapped' => $mapped,
                'quarter' => $this->quarter
            ]);

            return [
                'success' => true,
                'inserted' => $inserted,
                'auto_mapped' => $mapped,
                'quarter' => $this->quarter
            ];

        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('Texas Comptroller: Failed to update database', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'inserted' => 0
            ];
        }
    }

    /**
     * Determine jurisdiction type from name
     */
    protected function determineJurisdictionType(string $name): string
    {
        $name = strtoupper($name);
        
        if (str_contains($name, 'STATE') || str_contains($name, 'TEXAS')) {
            return 'state';
        } elseif (str_contains($name, 'COUNTY')) {
            return 'county';
        } elseif (str_contains($name, 'CITY') || str_contains($name, 'TOWN')) {
            return 'city';
        } elseif (str_contains($name, 'MTA') || str_contains($name, 'TRANSIT') ||
                  str_contains($name, 'ESD') || str_contains($name, 'EMERGENCY') ||
                  str_contains($name, 'MUD') || str_contains($name, 'UTILITY')) {
            return 'special_district'; // Map all special authorities to special_district
        } else {
            return 'special_district';
        }
    }

    /**
     * Parse location information from jurisdiction name
     */
    protected function parseJurisdictionLocation(string $name): array
    {
        // Extract county, city, or other location identifiers
        $location = [];
        
        if (preg_match('/(\w+)\s+COUNTY/', $name, $matches)) {
            $location['county'] = $matches[1];
        }
        
        if (preg_match('/CITY\s+OF\s+(\w+)/', $name, $matches)) {
            $location['city'] = $matches[1];
        }

        return $location;
    }

    /**
     * Get jurisdiction priority for ordering
     */
    protected function getJurisdictionPriority(string $type): int
    {
        $priorities = [
            'federal' => 1,
            'state' => 2,
            'county' => 3,
            'city' => 4,
            'municipality' => 5,
            'special_district' => 6,
            'zip_code' => 7
        ];

        return $priorities[$type] ?? 6;
    }

    /**
     * Get the start date of the current quarter
     */
    protected function getQuarterStartDate(): string
    {
        $quarterStarts = [
            'Q1' => '-01-01',
            'Q2' => '-04-01',
            'Q3' => '-07-01',
            'Q4' => '-10-01'
        ];

        return $this->year . ($quarterStarts[$this->quarter] ?? '-01-01');
    }

    /**
     * Get current configuration status
     */
    public function getConfigurationStatus(): array
    {
        try {
            $texasRateCount = DB::table('service_tax_rates')
                ->where('source', 'texas_comptroller')
                ->where('is_active', 1)
                ->count();

            return [
                'configured' => $texasRateCount > 0,
                'texas_rates' => $texasRateCount,
                'quarter' => $this->quarter,
                'year' => $this->year,
                'source' => 'texas_comptroller_official',
                'cost' => 'FREE'
            ];

        } catch (Exception $e) {
            return [
                'configured' => false,
                'error' => $e->getMessage(),
                'source' => 'texas_comptroller_official',
                'cost' => 'FREE'
            ];
        }
    }

    /**
     * Create sample Texas tax rates for testing
     * Based on the official file structure you provided
     */
    public function createSampleTexasRates(): array
    {
        $sampleRates = [
            [
                'name' => 'TEXAS STATE',
                'authority_id' => '1',
                'tax_rate' => 6.25
            ],
            [
                'name' => 'BEXAR COUNTY ESD 4',
                'authority_id' => '5015682',
                'tax_rate' => 1.50
            ],
            [
                'name' => 'SAN ANTONIO MTA',
                'authority_id' => '3015995',
                'tax_rate' => 0.50
            ],
            [
                'name' => 'HARRIS COUNTY',
                'authority_id' => '2001',
                'tax_rate' => 1.00
            ],
            [
                'name' => 'CITY OF HOUSTON',
                'authority_id' => '4001',
                'tax_rate' => 1.00
            ]
        ];

        return $this->updateDatabaseWithTexasRates($sampleRates);
    }

    /**
     * Automatically insert Texas State tax rate (6.25%) that applies to all addresses
     */
    protected function insertTexasStateTaxRate(): void
    {
        // Create or get Texas State jurisdiction
        $texasJurisdictionId = $this->createOrGetJurisdiction([
            'name' => 'TEXAS STATE',
            'authority_id' => '1'
        ], 'state', ['state' => 'TEXAS']);

        // Insert Texas State tax for both equipment and general service types
        foreach (['equipment', 'general'] as $serviceType) {
            DB::table('service_tax_rates')->insert([
                'company_id' => 1,
                'tax_jurisdiction_id' => $texasJurisdictionId,
                'tax_category_id' => 1,
                'service_type' => $serviceType,
                'tax_type' => 'sales',
                'tax_name' => 'TEXAS STATE',
                'authority_name' => 'TEXAS STATE',
                'tax_code' => 'TX_1_' . strtoupper($serviceType),
                'external_id' => '1', // Maps to jurisdiction code 1 from address data
                'description' => 'Texas State Sales Tax - 6.25% (automatic)',
                'rate_type' => 'percentage',
                'percentage_rate' => 6.25, // Official Texas State rate
                'calculation_method' => 'standard',
                'service_types' => json_encode([$serviceType]),
                'is_active' => 1,
                'is_recoverable' => 1,
                'priority' => 1, // Highest priority for state tax
                'effective_date' => $this->getQuarterStartDate(),
                'source' => 'texas_comptroller',
                'metadata' => json_encode([
                    'quarter' => $this->quarter,
                    'year' => $this->year,
                    'authority_id' => '1',
                    'jurisdiction_type' => 'state',
                    'applicable_states' => ['TX'],
                    'auto_inserted' => true,
                    'note' => 'Texas State tax rate automatically added - applies to all TX addresses'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Log::info('Texas Comptroller: Auto-inserted Texas State tax rate', [
            'rate' => 6.25,
            'external_id' => '1',
            'quarter' => $this->quarter
        ]);
    }

    /**
     * Create unique jurisdiction code to prevent mapping collisions
     * Each unique tax authority gets its own unique jurisdiction code
     */
    protected function createUniqueJurisdictionCode(array $jurisdiction): string
    {
        $name = strtoupper($jurisdiction['name']);
        $authorityId = $jurisdiction['authority_id'];
        
        // Special case: Texas State always gets ID '1'
        if ($authorityId === '1' || $name === 'TEXAS STATE') {
            return '1';
        }
        
        // For non-state jurisdictions, use the authority ID directly if it's unique
        // This ensures each authority gets its own unique code
        if (is_numeric($authorityId) && strlen($authorityId) >= 3) {
            Log::info('Texas Comptroller: Creating unique jurisdiction code', [
                'name' => $name,
                'authority_id' => $authorityId,
                'unique_code' => $authorityId,
                'method' => 'direct_authority_id'
            ]);
            return $authorityId;
        }
        
        // For non-numeric authority IDs, create a hash-based unique code
        $uniqueCode = 'TX_' . hash('crc32', $name . '_' . $authorityId);
        
        Log::info('Texas Comptroller: Creating hash-based jurisdiction code', [
            'name' => $name,
            'authority_id' => $authorityId,
            'unique_code' => $uniqueCode,
            'method' => 'hash_based'
        ]);
        
        return $uniqueCode;
    }
    
    /**
     * Automatically find jurisdiction code from address data using intelligent discovery
     * No hardcoded patterns - uses data-driven approach
     */
    protected function findJurisdictionCodeFromAddressData(array $jurisdiction): ?string
    {
        $name = strtoupper($jurisdiction['name']);
        $authorityId = $jurisdiction['authority_id'];
        
        try {
            // Special case: Texas State (ID 1) is handled separately to avoid duplicates
            if ($authorityId === '1') {
                return '1';
            }
            
            // Use intelligent discovery service instead of hardcoded patterns
            $discoveryService = new IntelligentJurisdictionDiscoveryService();
            $code = $discoveryService->findJurisdictionCode($name, $authorityId);
            
            if ($code) {
                Log::info('Texas Comptroller: Intelligently discovered jurisdiction code', [
                    'name' => $name,
                    'authority_id' => $authorityId,
                    'discovered_code' => $code,
                    'method' => 'intelligent_discovery'
                ]);
                return $code;
            }
            
            // For numeric authority IDs that look like valid TAIDs, use them directly
            if (is_numeric($authorityId) && strlen($authorityId) >= 3) {
                Log::info('Texas Comptroller: Using authority ID as jurisdiction code', [
                    'name' => $name,
                    'authority_id' => $authorityId,
                    'method' => 'direct_taid'
                ]);
                return $authorityId;
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::warning('Texas Comptroller: Failed to discover jurisdiction code', [
                'name' => $name,
                'authority_id' => $authorityId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Create or get jurisdiction record for foreign key relationship
     */
    protected function createOrGetJurisdiction(array $jurisdiction, string $jurisdictionType, array $location): int
    {
        // Check if jurisdiction already exists using metadata
        $existingJurisdiction = DB::table('tax_jurisdictions')
            ->where('code', 'TX_' . $jurisdiction['authority_id'])
            ->where('state_code', 'TX')
            ->whereRaw("JSON_EXTRACT(metadata, '$.authority_id') = ?", [$jurisdiction['authority_id']])
            ->first();

        if ($existingJurisdiction) {
            return $existingJurisdiction->id;
        }

        // Create new jurisdiction record
        $jurisdictionId = DB::table('tax_jurisdictions')->insertGetId([
            'company_id' => 1, // Default company
            'name' => $jurisdiction['name'],
            'jurisdiction_type' => $jurisdictionType === 'special_district' ? 'special_district' : $jurisdictionType,
            'code' => 'TX_' . $jurisdiction['authority_id'],
            'state_code' => 'TX',
            'authority_name' => $jurisdiction['name'],
            'is_active' => 1,
            'priority' => $this->getJurisdictionPriority($jurisdictionType),
            'metadata' => json_encode([
                'quarter' => $this->quarter,
                'year' => $this->year,
                'authority_id' => $jurisdiction['authority_id'],
                'jurisdiction_type' => $jurisdictionType,
                'location' => $location,
                'source' => 'texas_comptroller'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $jurisdictionId;
    }

    /**
     * Download and process county address data from SIFT API
     */
    public function processCountyAddressData(string $countyFips, string $quarter = null): array
    {
        $quarter = $quarter ?: $this->quarter;
        
        try {
            // Check for existing local file first
            $localFilePath = storage_path("app/temp/texas_address_{$countyFips}_{$quarter}.zip");
            
            if (file_exists($localFilePath)) {
                // Use existing local file
                $downloadResult = [
                    'success' => true,
                    'file_path' => $localFilePath,
                    'size' => filesize($localFilePath)
                ];
            } else {
                // Download county-specific address ZIP file from SIFT API
                $downloadResult = $this->downloadCountyAddressFile($countyFips, $quarter);
            }
            
            if (!$downloadResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to download address file: ' . $downloadResult['error']
                ];
            }
            
            // Parse the ZIP file and extract CSV data
            $parseResult = $this->parseAddressZipFile($downloadResult['file_path'], $countyFips);
            
            if (!$parseResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse address file: ' . $parseResult['error']
                ];
            }
            
            // Import address data into database
            $importResult = $this->importAddressDataToDatabase($parseResult['addresses'], $countyFips);
            
            return [
                'success' => $importResult['success'],
                'addresses' => $importResult['count'] ?? 0,
                'error' => $importResult['error'] ?? null
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Download county-specific address ZIP file from SIFT API
     */
    protected function downloadCountyAddressFile(string $countyFips, string $quarter): array
    {
        try {
            if (!$this->apiKey) {
                throw new Exception('Texas Comptroller API key not configured');
            }
            
            // First, get the file list to find the correct file path
            $listUrl = "{$this->baseUrl}/list-files";
            $listResponse = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json'
            ])->timeout(60)->retry(3, 2000)->get($listUrl, [
                'filter-by-quarter' => $quarter
            ]);
            
            if (!$listResponse->successful()) {
                throw new Exception("Failed to list files: HTTP {$listResponse->status()}");
            }
            
            $fileList = $listResponse->json();
            if (!$fileList['success'] || empty($fileList['data'])) {
                throw new Exception("No files available for quarter {$quarter}");
            }
            
            // Find the Bexar County file
            $targetFile = null;
            $expectedPattern = "TX-County-FIPS-{$countyFips}-{$quarter}.zip";
            
            foreach ($fileList['data'] as $file) {
                if (str_contains($file['filePath'], $expectedPattern)) {
                    $targetFile = $file;
                    break;
                }
            }
            
            if (!$targetFile) {
                throw new Exception("County file not found for FIPS {$countyFips} in quarter {$quarter}");
            }
            
            // Use the get-link endpoint to get download URL
            $url = $targetFile['getLinkEndpoint'];
            
            // Download the file using the get-link endpoint (expect 307 redirect)
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/zip',
                'User-Agent' => 'Nestogy-MSP/1.0'
            ])->timeout(300)->retry(2, 5000)->withoutRedirecting()->get($url);
            
            if ($response->status() === 307) {
                // Get the signed URL from the Location header
                $signedUrl = $response->header('Location');
                
                if ($signedUrl) {
                    // Download from the signed URL
                    $fileResponse = Http::timeout(300)->get($signedUrl);
                    
                    if ($fileResponse->successful()) {
                        // Save ZIP file temporarily
                        Storage::makeDirectory('temp');
                        $tempPath = storage_path("app/temp/texas_address_{$countyFips}_{$quarter}.zip");
                        Storage::put("temp/texas_address_{$countyFips}_{$quarter}.zip", $fileResponse->body());
                        
                        return [
                            'success' => true,
                            'file_path' => $tempPath,
                            'size' => strlen($fileResponse->body())
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => "Failed to download from signed URL: HTTP {$fileResponse->status()}"
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'No Location header in 307 response'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => "Expected 307 redirect, got HTTP {$response->status()}: {$response->body()}"
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Parse address ZIP file and extract CSV data
     */
    protected function parseAddressZipFile(string $zipPath, string $countyFips): array
    {
        try {
            $addresses = [];
            
            // Open ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== TRUE) {
                throw new Exception("Cannot open ZIP file: {$zipPath}");
            }
            
            // Find CSV file in ZIP (usually first file)
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($filename), '.csv')) {
                    $csvContent = $zip->getFromIndex($i);
                    if ($csvContent !== false) {
                        $addresses = $this->parseAddressCsvContent($csvContent, $countyFips);
                        break;
                    }
                }
            }
            
            $zip->close();
            
            // Clean up temp file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            
            return [
                'success' => true,
                'addresses' => $addresses,
                'count' => count($addresses)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Parse CSV content into address records based on Texas Comptroller format
     */
    protected function parseAddressCsvContent(string $csvContent, string $countyFips): array
    {
        $addresses = [];
        $lines = str_getcsv($csvContent, "\n");
        
        // Skip header row
        if (count($lines) > 0) {
            array_shift($lines);
        }
        
        foreach ($lines as $line) {
            $fields = str_getcsv($line);
            
            // Ensure we have all required fields (27+ fields based on Texas format)
            if (count($fields) >= 27) {
                $addresses[] = [
                    'county_fips' => $countyFips,
                    'address_from' => max(1, intval($fields[1]) ?: 1), // From
                    'address_to' => max(1, intval($fields[2]) ?: 999999), // To  
                    'address_parity' => $this->parseAddressParity($fields[3]), // Even-Odd
                    'street_pre_dir' => $this->cleanStringField($fields[4], 2), // Pre Dir
                    'street_name' => $this->cleanStringField($fields[5], 50), // Street
                    'street_suffix' => $this->cleanStringField($fields[6], 6), // Suffix
                    'street_post_dir' => $this->cleanStringField($fields[7], 2), // Post Dir
                    'zip_code' => $this->cleanStringField($fields[10], 5), // Zip
                    'zip_plus4' => $this->cleanStringField($fields[11], 4), // Plus 4
                    'county_taid' => $this->cleanStringField($fields[18]), // County TAID
                    'city_taid' => $this->cleanStringField($fields[19]), // City TAID
                    'transit1_taid' => $this->cleanStringField($fields[20]), // Transit Authority 1 TAID
                    'transit2_taid' => $this->cleanStringField($fields[21]), // Transit Authority 2 TAID
                    'spd1_taid' => $this->cleanStringField($fields[22]), // Special Purpose District 1 TAID
                    'spd2_taid' => $this->cleanStringField($fields[23]), // Special Purpose District 2 TAID
                    'spd3_taid' => $this->cleanStringField($fields[24]), // Special Purpose District 3 TAID
                    'spd4_taid' => $this->cleanStringField($fields[25]), // Special Purpose District 4 TAID
                ];
            }
        }
        
        return $addresses;
    }
    
    /**
     * Import parsed address data into optimized database tables with bulk operations
     */
    protected function importAddressDataToDatabase(array $addresses, string $countyFips): array
    {
        try {
            DB::beginTransaction();
            
            Log::info("Texas Comptroller: Starting address import for county {$countyFips}", [
                'total_addresses' => count($addresses),
                'county_fips' => $countyFips
            ]);
            
            // Clear existing data for this county
            $deletedRows = DB::table('address_tax_jurisdictions')
                ->where('county_code', $countyFips)
                ->where('data_source', 'texas_comptroller')
                ->delete();
            
            Log::info("Texas Comptroller: Cleared existing data", [
                'deleted_rows' => $deletedRows,
                'county_fips' => $countyFips
            ]);
            
            $inserted = 0;
            $batchSize = 5000; // Larger batch size for bulk operations
            $chunks = array_chunk($addresses, $batchSize);
            
            $chunkNumber = 0;
            $totalChunks = count($chunks);
            
            foreach ($chunks as $chunk) {
                $chunkNumber++;
                $chunkStartTime = microtime(true);
                $records = [];
                
                foreach ($chunk as $address) {
                    // Get or create jurisdiction IDs for fast lookups
                    $jurisdictionIds = $this->getOrCreateJurisdictionIds($address);
                    
                    $records[] = [
                        'state_code' => 'TX',
                        'county_code' => $countyFips,
                        'address_from' => $address['address_from'],
                        'address_to' => $address['address_to'],
                        'address_parity' => $address['address_parity'],
                        'street_pre_dir' => $address['street_pre_dir'],
                        'street_name' => strtoupper($address['street_name']),
                        'street_suffix' => $address['street_suffix'],
                        'street_post_dir' => $address['street_post_dir'],
                        'zip_code' => $address['zip_code'],
                        'zip_plus4' => $address['zip_plus4'],
                        'state_jurisdiction_id' => $jurisdictionIds['state'],
                        'county_jurisdiction_id' => $jurisdictionIds['county'],
                        'city_jurisdiction_id' => $jurisdictionIds['city'],
                        'primary_transit_id' => $jurisdictionIds['transit1'],
                        'additional_jurisdictions' => json_encode($jurisdictionIds['additional']),
                        'data_source' => 'texas_comptroller',
                        'imported_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($records)) {
                    // Use bulk insert with optimized settings
                    DB::statement('SET foreign_key_checks=0'); // Temporarily disable for speed
                    DB::table('address_tax_jurisdictions')->insert($records);
                    DB::statement('SET foreign_key_checks=1'); // Re-enable
                    
                    $inserted += count($records);
                    $chunkTime = microtime(true) - $chunkStartTime;
                    
                    Log::info("Texas Comptroller: Bulk insert chunk completed", [
                        'county_fips' => $countyFips,
                        'chunk' => "{$chunkNumber}/{$totalChunks}",
                        'records_in_chunk' => count($records),
                        'total_inserted' => $inserted,
                        'chunk_time_seconds' => round($chunkTime, 2),
                        'records_per_second' => round(count($records) / max($chunkTime, 0.01), 0)
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info("Texas Comptroller: Address import completed successfully", [
                'county_fips' => $countyFips,
                'total_inserted' => $inserted,
                'total_chunks' => $totalChunks,
                'batch_size' => $batchSize
            ]);
            
            return [
                'success' => true,
                'count' => $inserted,
                'addresses' => $inserted // For compatibility
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Texas Comptroller: Address import failed", [
                'county_fips' => $countyFips,
                'error' => $e->getMessage(),
                'inserted_before_error' => $inserted
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'addresses' => 0
            ];
        }
    }
    
    /**
     * Helper methods for data processing
     */
    protected function parseAddressParity(string $parity): string
    {
        $parity = strtoupper(trim($parity));
        return match($parity) {
            'E', 'EVEN' => 'even',
            'O', 'ODD' => 'odd',
            default => 'both'
        };
    }
    
    protected function cleanStringField(?string $value, int $maxLength = null): ?string
    {
        if (empty($value)) return null;
        
        $clean = trim($value);
        if ($maxLength && strlen($clean) > $maxLength) {
            $clean = substr($clean, 0, $maxLength);
        }
        
        return $clean ?: null;
    }
    
    protected function getOrCreateJurisdictionIds(array $address): array
    {
        $jurisdictions = [
            'state' => $this->getOrCreateTexasStateJurisdiction(),
            'county' => null,
            'city' => null,
            'transit1' => null,
            'additional' => []
        ];
        
        // Map TAIDs to jurisdiction IDs
        if ($address['county_taid']) {
            $jurisdictions['county'] = $this->getJurisdictionIdByTaid($address['county_taid'], 'county');
        }
        
        if ($address['city_taid']) {
            $jurisdictions['city'] = $this->getJurisdictionIdByTaid($address['city_taid'], 'city');
        }
        
        if ($address['transit1_taid']) {
            $jurisdictions['transit1'] = $this->getJurisdictionIdByTaid($address['transit1_taid'], 'transit');
        }
        
        // Handle additional jurisdictions in JSON
        if ($address['transit2_taid']) {
            $jurisdictions['additional']['transit2'] = $this->getJurisdictionIdByTaid($address['transit2_taid'], 'transit');
        }
        
        foreach (['spd1_taid', 'spd2_taid', 'spd3_taid', 'spd4_taid'] as $field) {
            if ($address[$field]) {
                $spd_key = str_replace('_taid', '', $field);
                $jurisdictions['additional'][$spd_key] = $this->getJurisdictionIdByTaid($address[$field], 'special');
            }
        }
        
        return $jurisdictions;
    }
    
    protected function getOrCreateTexasStateJurisdiction(): int
    {
        $jurisdiction = DB::table('jurisdiction_master')
            ->where('jurisdiction_code', '1')
            ->where('state_code', 'TX')
            ->where('jurisdiction_type', 'state')
            ->first();
        
        if (!$jurisdiction) {
            return DB::table('jurisdiction_master')->insertGetId([
                'jurisdiction_code' => '1',
                'jurisdiction_name' => 'Texas',
                'jurisdiction_type' => 'state',
                'state_code' => 'TX',
                'data_source' => 'texas_comptroller',
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return $jurisdiction->id;
    }
    
    protected function getJurisdictionIdByTaid(string $taid, string $type): ?int
    {
        // Look up existing jurisdiction by TAID
        $jurisdiction = DB::table('jurisdiction_master')
            ->where('jurisdiction_code', $taid)
            ->where('state_code', 'TX')
            ->first();
        
        if (!$jurisdiction) {
            // Create new jurisdiction record with descriptive name
            $name = $this->getJurisdictionNameByTaid($taid, $type);
            
            $id = DB::table('jurisdiction_master')->insertGetId([
                'jurisdiction_code' => $taid,
                'jurisdiction_name' => $name,
                'jurisdiction_type' => $type,
                'state_code' => 'TX',
                'data_source' => 'texas_comptroller',
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return $id;
        }
        
        return $jurisdiction->id;
    }
    
    protected function getJurisdictionNameByTaid(string $taid, string $type): string
    {
        // Try to find the name from existing data instead of hardcoding
        $jurisdiction = DB::table('jurisdiction_master')
            ->where('jurisdiction_code', $taid)
            ->where('state_code', 'TX')
            ->first();
        
        if ($jurisdiction && $jurisdiction->jurisdiction_name) {
            return $jurisdiction->jurisdiction_name;
        }
        
        // Try to find from service_tax_rates table
        $taxRate = DB::table('service_tax_rates')
            ->where('external_id', $taid)
            ->whereNotNull('authority_name')
            ->first();
        
        if ($taxRate && $taxRate->authority_name) {
            return $taxRate->authority_name;
        }
        
        // Generate a descriptive name based on type if not found
        return "TX {$type} {$taid}";
    }
    
    protected function getCountyFipsToNameMapping(): array
    {
        // Texas county FIPS to name mapping
        return [
            '029' => 'Bexar',
            '201' => 'Harris', 
            '113' => 'Dallas',
            '453' => 'Travis',
            '439' => 'Tarrant',
            '157' => 'Fort Bend',
            '085' => 'Collin',
            '121' => 'Denton',
        ];
    }
}