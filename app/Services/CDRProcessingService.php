<?php

namespace App\Services;

use App\Models\UsageRecord;
use App\Models\UsagePool;
use App\Models\UsageBucket;
use App\Models\Client;
use App\Models\PricingRule;
use App\Models\UsageAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Exception;

/**
 * CDR Processing Service
 * 
 * Real-time Call Detail Record (CDR) ingestion and parsing service with support for
 * multi-format CDR data, intelligent usage categorization, duplicate detection,
 * fraud prevention, and real-time usage allocation to pools and buckets.
 */
class CDRProcessingService
{
    protected UsageBillingService $usageBillingService;
    protected PricingEngineService $pricingEngineService;
    
    /**
     * Supported CDR formats
     */
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_PROPRIETARY = 'proprietary';
    
    /**
     * CDR validation status
     */
    const VALIDATION_PASSED = 'passed';
    const VALIDATION_FAILED = 'failed';
    const VALIDATION_WARNING = 'warning';
    
    /**
     * Fraud detection flags
     */
    const FRAUD_NONE = 'none';
    const FRAUD_SUSPICIOUS = 'suspicious';
    const FRAUD_HIGH_RISK = 'high_risk';
    const FRAUD_CONFIRMED = 'confirmed';

    public function __construct(
        UsageBillingService $usageBillingService,
        PricingEngineService $pricingEngineService
    ) {
        $this->usageBillingService = $usageBillingService;
        $this->pricingEngineService = $pricingEngineService;
    }

    /**
     * Process a single CDR record.
     */
    public function processCDR(array $cdrData, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Step 1: Validate CDR data structure
            $validation = $this->validateCDR($cdrData, $options);
            if ($validation['status'] === self::VALIDATION_FAILED) {
                return $this->createProcessingResult(false, $validation['errors'], [], $startTime);
            }

            // Step 2: Parse and normalize CDR data
            $normalizedCDR = $this->normalizeCDR($cdrData, $options);

            // Step 3: Check for duplicates
            if ($this->isDuplicate($normalizedCDR)) {
                Log::warning('Duplicate CDR detected', ['cdr_id' => $normalizedCDR['cdr_id']]);
                return $this->createProcessingResult(false, ['Duplicate CDR detected'], [], $startTime);
            }

            // Step 4: Fraud detection
            $fraudCheck = $this->detectFraud($normalizedCDR);
            if ($fraudCheck['is_fraud']) {
                Log::alert('Fraudulent CDR detected', [
                    'cdr_id' => $normalizedCDR['cdr_id'],
                    'fraud_indicators' => $fraudCheck['indicators']
                ]);
            }

            // Step 5: Identify client and service classification
            $client = $this->identifyClient($normalizedCDR);
            if (!$client) {
                return $this->createProcessingResult(false, ['Client not found'], [], $startTime);
            }

            // Step 6: Classify usage type and service type
            $classification = $this->classifyUsage($normalizedCDR);

            // Step 7: Calculate usage metrics
            $usageMetrics = $this->calculateUsageMetrics($normalizedCDR, $classification);

            // Step 8: Create usage record
            $usageRecord = $this->createUsageRecord(
                $client,
                $normalizedCDR,
                $classification,
                $usageMetrics,
                $fraudCheck
            );

            // Step 9: Apply usage to pools and buckets
            $allocationResult = $this->allocateUsage($usageRecord, $client);

            // Step 10: Calculate real-time pricing
            $pricingResult = $this->calculateRealTimePricing($usageRecord, $allocationResult);

            // Step 11: Update usage record with pricing
            $usageRecord = $this->updateUsageRecordPricing($usageRecord, $pricingResult);

            // Step 12: Check usage alerts and thresholds
            $this->checkUsageAlerts($usageRecord, $client, $allocationResult);

            // Step 13: Queue for batch processing if needed
            if ($options['enable_batching'] ?? true) {
                $this->queueForBatchProcessing($usageRecord);
            }

            return $this->createProcessingResult(true, [], [
                'usage_record_id' => $usageRecord->id,
                'allocated_pools' => $allocationResult['pools'],
                'allocated_buckets' => $allocationResult['buckets'],
                'pricing_result' => $pricingResult,
                'fraud_score' => $fraudCheck['score'],
            ], $startTime);

        } catch (Exception $e) {
            Log::error('CDR processing failed', [
                'cdr_data' => $cdrData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->createProcessingResult(false, [$e->getMessage()], [], $startTime);
        }
    }

    /**
     * Process multiple CDRs in batch.
     */
    public function processCDRBatch(array $cdrBatch, array $options = []): array
    {
        $batchId = $options['batch_id'] ?? 'BATCH-' . uniqid();
        $startTime = microtime(true);
        
        Log::info('Starting CDR batch processing', [
            'batch_id' => $batchId,
            'cdr_count' => count($cdrBatch)
        ]);

        $results = [
            'batch_id' => $batchId,
            'total_cdrs' => count($cdrBatch),
            'successful' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'fraud_detected' => 0,
            'processing_time' => 0,
            'errors' => [],
            'processed_records' => []
        ];

        // Process CDRs in chunks for memory efficiency
        $chunkSize = $options['chunk_size'] ?? 1000;
        $chunks = array_chunk($cdrBatch, $chunkSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $cdrIndex => $cdrData) {
                    $cdrData['batch_id'] = $batchId;
                    $cdrData['batch_sequence'] = ($chunkIndex * $chunkSize) + $cdrIndex;
                    
                    $result = $this->processCDR($cdrData, $options);
                    
                    if ($result['success']) {
                        $results['successful']++;
                        $results['processed_records'][] = $result['data'];
                    } else {
                        $results['failed']++;
                        $results['errors'] = array_merge($results['errors'], $result['errors']);
                        
                        if (in_array('Duplicate CDR detected', $result['errors'])) {
                            $results['duplicates']++;
                        }
                    }
                }
                
                DB::commit();
                
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('CDR batch chunk processing failed', [
                    'batch_id' => $batchId,
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage()
                ]);
                $results['failed'] += count($chunk);
                $results['errors'][] = 'Chunk processing failed: ' . $e->getMessage();
            }
        }

        $results['processing_time'] = microtime(true) - $startTime;
        
        Log::info('CDR batch processing completed', $results);
        
        return $results;
    }

    /**
     * Process CDR from file upload.
     */
    public function processCDRFile(string $filePath, string $format, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Parse file based on format
            $cdrData = $this->parseFile($filePath, $format, $options);
            
            // Process the batch
            return $this->processCDRBatch($cdrData, array_merge($options, [
                'source_file' => basename($filePath),
                'file_format' => $format
            ]));
            
        } catch (Exception $e) {
            Log::error('CDR file processing failed', [
                'file_path' => $filePath,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
                'processing_time' => microtime(true) - $startTime
            ];
        }
    }

    /**
     * Parse CDR file based on format.
     */
    protected function parseFile(string $filePath, string $format, array $options = []): array
    {
        switch ($format) {
            case self::FORMAT_CSV:
                return $this->parseCSVFile($filePath, $options);
            case self::FORMAT_JSON:
                return $this->parseJSONFile($filePath, $options);
            case self::FORMAT_XML:
                return $this->parseXMLFile($filePath, $options);
            case self::FORMAT_PROPRIETARY:
                return $this->parseProprietaryFile($filePath, $options);
            default:
                throw new Exception("Unsupported CDR format: {$format}");
        }
    }

    /**
     * Parse CSV CDR file.
     */
    protected function parseCSVFile(string $filePath, array $options = []): array
    {
        $csvData = [];
        $delimiter = $options['csv_delimiter'] ?? ',';
        $hasHeader = $options['csv_has_header'] ?? true;
        $fieldMapping = $options['field_mapping'] ?? [];
        
        if (($handle = fopen($filePath, "r")) !== false) {
            $headerRow = null;
            $rowIndex = 0;
            
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if ($hasHeader && $rowIndex === 0) {
                    $headerRow = $data;
                    $rowIndex++;
                    continue;
                }
                
                if ($hasHeader && $headerRow) {
                    $record = array_combine($headerRow, $data);
                } else {
                    $record = $data;
                }
                
                // Apply field mapping
                if (!empty($fieldMapping)) {
                    $record = $this->applyFieldMapping($record, $fieldMapping);
                }
                
                $csvData[] = $record;
                $rowIndex++;
            }
            fclose($handle);
        }
        
        return $csvData;
    }

    /**
     * Parse JSON CDR file.
     */
    protected function parseJSONFile(string $filePath, array $options = []): array
    {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        // Handle different JSON structures
        if (isset($options['json_root_key'])) {
            $data = $data[$options['json_root_key']] ?? [];
        }
        
        // Apply field mapping
        if (!empty($options['field_mapping'])) {
            $data = array_map(function($record) use ($options) {
                return $this->applyFieldMapping($record, $options['field_mapping']);
            }, $data);
        }
        
        return is_array($data) ? $data : [$data];
    }

    /**
     * Parse XML CDR file.
     */
    protected function parseXMLFile(string $filePath, array $options = []): array
    {
        $xmlContent = file_get_contents($filePath);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new Exception('Invalid XML format');
        }
        
        $recordPath = $options['xml_record_path'] ?? 'record';
        $records = [];
        
        foreach ($xml->xpath("//{$recordPath}") as $record) {
            $recordArray = json_decode(json_encode($record), true);
            
            // Apply field mapping
            if (!empty($options['field_mapping'])) {
                $recordArray = $this->applyFieldMapping($recordArray, $options['field_mapping']);
            }
            
            $records[] = $recordArray;
        }
        
        return $records;
    }

    /**
     * Parse proprietary CDR file format.
     */
    protected function parseProprietaryFile(string $filePath, array $options = []): array
    {
        $parser = $options['proprietary_parser'] ?? null;
        
        if (!$parser || !is_callable($parser)) {
            throw new Exception('Proprietary parser not provided or not callable');
        }
        
        return $parser($filePath, $options);
    }

    /**
     * Apply field mapping to CDR record.
     */
    protected function applyFieldMapping(array $record, array $fieldMapping): array
    {
        $mappedRecord = [];
        
        foreach ($fieldMapping as $sourceField => $targetField) {
            if (isset($record[$sourceField])) {
                $mappedRecord[$targetField] = $record[$sourceField];
            }
        }
        
        return $mappedRecord;
    }

    /**
     * Validate CDR data structure.
     */
    protected function validateCDR(array $cdrData, array $options = []): array
    {
        $errors = [];
        $warnings = [];
        
        // Required fields validation
        $requiredFields = $options['required_fields'] ?? [
            'origination_number',
            'destination_number',
            'usage_start_time',
            'duration_seconds'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($cdrData[$field]) || empty($cdrData[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Data type validation
        if (isset($cdrData['duration_seconds']) && !is_numeric($cdrData['duration_seconds'])) {
            $errors[] = 'Duration seconds must be numeric';
        }
        
        if (isset($cdrData['usage_start_time'])) {
            try {
                Carbon::parse($cdrData['usage_start_time']);
            } catch (Exception $e) {
                $errors[] = 'Invalid usage start time format';
            }
        }
        
        // Business logic validation
        if (isset($cdrData['duration_seconds']) && $cdrData['duration_seconds'] < 0) {
            $errors[] = 'Duration cannot be negative';
        }
        
        if (isset($cdrData['duration_seconds']) && $cdrData['duration_seconds'] > 86400) {
            $warnings[] = 'Unusually long call duration (>24 hours)';
        }
        
        return [
            'status' => empty($errors) ? (empty($warnings) ? self::VALIDATION_PASSED : self::VALIDATION_WARNING) : self::VALIDATION_FAILED,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Normalize CDR data to standard format.
     */
    protected function normalizeCDR(array $cdrData, array $options = []): array
    {
        return [
            'cdr_id' => $cdrData['cdr_id'] ?? uniqid('CDR_'),
            'external_id' => $cdrData['external_id'] ?? null,
            'batch_id' => $cdrData['batch_id'] ?? null,
            'origination_number' => $this->normalizePhoneNumber($cdrData['origination_number'] ?? ''),
            'destination_number' => $this->normalizePhoneNumber($cdrData['destination_number'] ?? ''),
            'usage_start_time' => Carbon::parse($cdrData['usage_start_time']),
            'usage_end_time' => isset($cdrData['usage_end_time']) ? Carbon::parse($cdrData['usage_end_time']) : null,
            'duration_seconds' => (float) ($cdrData['duration_seconds'] ?? 0),
            'data_volume_mb' => (float) ($cdrData['data_volume_mb'] ?? 0),
            'origination_country' => $cdrData['origination_country'] ?? null,
            'destination_country' => $cdrData['destination_country'] ?? null,
            'carrier_name' => $cdrData['carrier_name'] ?? null,
            'call_quality' => $cdrData['call_quality'] ?? null,
            'completion_status' => $cdrData['completion_status'] ?? UsageRecord::COMPLETION_COMPLETED,
            'protocol' => $cdrData['protocol'] ?? null,
            'codec' => $cdrData['codec'] ?? null,
            'route_type' => $cdrData['route_type'] ?? null,
            'cdr_source' => $options['cdr_source'] ?? 'api',
            'cdr_received_at' => now(),
            'raw_cdr_data' => $cdrData,
            'processing_version' => config('app.version', '1.0'),
        ];
    }

    /**
     * Normalize phone number format.
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if missing
        if (strlen($cleaned) === 10 && !str_starts_with($cleaned, '1')) {
            $cleaned = '1' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Check for duplicate CDR.
     */
    protected function isDuplicate(array $normalizedCDR): bool
    {
        $cacheKey = 'cdr_duplicate_' . md5(json_encode([
            'cdr_id' => $normalizedCDR['cdr_id'],
            'origination_number' => $normalizedCDR['origination_number'],
            'destination_number' => $normalizedCDR['destination_number'],
            'usage_start_time' => $normalizedCDR['usage_start_time']->toISOString(),
            'duration_seconds' => $normalizedCDR['duration_seconds']
        ]));
        
        if (Cache::has($cacheKey)) {
            return true;
        }
        
        // Check database for duplicate
        $exists = UsageRecord::where('cdr_id', $normalizedCDR['cdr_id'])
            ->where('origination_number', $normalizedCDR['origination_number'])
            ->where('destination_number', $normalizedCDR['destination_number'])
            ->where('usage_start_time', $normalizedCDR['usage_start_time'])
            ->where('duration_seconds', $normalizedCDR['duration_seconds'])
            ->exists();
        
        if (!$exists) {
            // Cache for 1 hour to prevent duplicates
            Cache::put($cacheKey, true, 3600);
        }
        
        return $exists;
    }

    /**
     * Detect potential fraud indicators.
     */
    protected function detectFraud(array $normalizedCDR): array
    {
        $fraudScore = 0;
        $indicators = [];
        
        // Check for unusually high duration
        if ($normalizedCDR['duration_seconds'] > 7200) { // > 2 hours
            $fraudScore += 20;
            $indicators[] = 'excessive_duration';
        }
        
        // Check for premium rate destinations
        $destination = $normalizedCDR['destination_number'];
        if ($this->isPremiumRateNumber($destination)) {
            $fraudScore += 30;
            $indicators[] = 'premium_rate_destination';
        }
        
        // Check for international calls from suspicious origins
        if ($this->isSuspiciousInternationalCall($normalizedCDR)) {
            $fraudScore += 25;
            $indicators[] = 'suspicious_international';
        }
        
        // Check call frequency from same origination
        $recentCallCount = $this->getRecentCallCount($normalizedCDR['origination_number'], 60); // Last 60 minutes
        if ($recentCallCount > 100) {
            $fraudScore += 40;
            $indicators[] = 'excessive_call_frequency';
        }
        
        // Check for unusual call patterns
        if ($this->hasUnusualCallPattern($normalizedCDR)) {
            $fraudScore += 15;
            $indicators[] = 'unusual_pattern';
        }
        
        $fraudLevel = $this->determineFraudLevel($fraudScore);
        
        return [
            'is_fraud' => $fraudLevel !== self::FRAUD_NONE,
            'score' => $fraudScore,
            'level' => $fraudLevel,
            'indicators' => $indicators
        ];
    }

    /**
     * Check if number is premium rate.
     */
    protected function isPremiumRateNumber(string $phoneNumber): bool
    {
        $premiumPrefixes = ['1900', '1976', '+44871', '+44872', '+44873'];
        
        foreach ($premiumPrefixes as $prefix) {
            if (str_starts_with($phoneNumber, $prefix)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for suspicious international calls.
     */
    protected function isSuspiciousInternationalCall(array $cdr): bool
    {
        $suspiciousCountries = ['SY', 'IR', 'KP', 'CU']; // Example suspicious country codes
        
        return in_array($cdr['destination_country'] ?? '', $suspiciousCountries);
    }

    /**
     * Get recent call count from origination number.
     */
    protected function getRecentCallCount(string $originationNumber, int $minutes): int
    {
        $cutoff = now()->subMinutes($minutes);
        
        return UsageRecord::where('origination_number', $originationNumber)
            ->where('usage_start_time', '>=', $cutoff)
            ->count();
    }

    /**
     * Check for unusual call patterns.
     */
    protected function hasUnusualCallPattern(array $cdr): bool
    {
        // Check for very short calls (< 5 seconds) which might indicate scanning
        if ($cdr['duration_seconds'] < 5) {
            return true;
        }
        
        // Check for exact duration matches (might indicate artificial generation)
        $exactDurationCount = UsageRecord::where('origination_number', $cdr['origination_number'])
            ->where('duration_seconds', $cdr['duration_seconds'])
            ->where('usage_start_time', '>=', now()->subHour())
            ->count();
        
        return $exactDurationCount > 10;
    }

    /**
     * Determine fraud level based on score.
     */
    protected function determineFraudLevel(int $score): string
    {
        if ($score >= 80) {
            return self::FRAUD_CONFIRMED;
        } elseif ($score >= 50) {
            return self::FRAUD_HIGH_RISK;
        } elseif ($score >= 25) {
            return self::FRAUD_SUSPICIOUS;
        }
        
        return self::FRAUD_NONE;
    }

    /**
     * Identify client from CDR data.
     */
    protected function identifyClient(array $normalizedCDR): ?Client
    {
        // Try to identify by origination number first
        $client = Client::where('phone', $normalizedCDR['origination_number'])->first();
        
        if (!$client) {
            // Try other identification methods
            // This could be extended to use DID ranges, account IDs, etc.
            $client = $this->identifyClientByCustomLogic($normalizedCDR);
        }
        
        return $client;
    }

    /**
     * Custom client identification logic.
     */
    protected function identifyClientByCustomLogic(array $normalizedCDR): ?Client
    {
        // Implement custom logic based on business requirements
        // For example, using DID ranges, SIP accounts, etc.
        return null;
    }

    /**
     * Classify usage type and service type.
     */
    protected function classifyUsage(array $normalizedCDR): array
    {
        $usageType = UsageRecord::USAGE_TYPE_VOICE; // Default
        $serviceType = $this->determineServiceType($normalizedCDR);
        $category = $this->determineUsageCategory($normalizedCDR);
        
        // Determine usage type based on CDR data
        if ($normalizedCDR['data_volume_mb'] > 0) {
            $usageType = UsageRecord::USAGE_TYPE_DATA;
        }
        
        return [
            'usage_type' => $usageType,
            'service_type' => $serviceType,
            'usage_category' => $category,
            'billing_category' => $this->determineBillingCategory($serviceType, $category)
        ];
    }

    /**
     * Determine service type from CDR.
     */
    protected function determineServiceType(array $cdr): string
    {
        $destination = $cdr['destination_number'];
        $originationCountry = $cdr['origination_country'] ?? 'US';
        $destinationCountry = $cdr['destination_country'] ?? 'US';
        
        // International calls
        if ($originationCountry !== $destinationCountry) {
            return UsageRecord::SERVICE_TYPE_INTERNATIONAL;
        }
        
        // Long distance (different area codes in same country)
        if ($this->isLongDistance($cdr['origination_number'], $destination)) {
            return UsageRecord::SERVICE_TYPE_LONG_DISTANCE;
        }
        
        return UsageRecord::SERVICE_TYPE_LOCAL;
    }

    /**
     * Check if call is long distance.
     */
    protected function isLongDistance(string $origination, string $destination): bool
    {
        if (strlen($origination) >= 10 && strlen($destination) >= 10) {
            $originNPA = substr($origination, -10, 3); // Area code
            $destNPA = substr($destination, -10, 3);
            return $originNPA !== $destNPA;
        }
        
        return false;
    }

    /**
     * Determine usage category.
     */
    protected function determineUsageCategory(array $cdr): string
    {
        // Categorize based on destination patterns
        $destination = $cdr['destination_number'];
        
        if (str_starts_with($destination, '911') || str_starts_with($destination, '112')) {
            return 'emergency';
        }
        
        if (str_starts_with($destination, '1800') || str_starts_with($destination, '1888')) {
            return 'toll_free';
        }
        
        if ($this->isPremiumRateNumber($destination)) {
            return 'premium';
        }
        
        return 'standard';
    }

    /**
     * Determine billing category.
     */
    protected function determineBillingCategory(string $serviceType, string $category): string
    {
        return $serviceType . '_' . $category;
    }

    /**
     * Calculate usage metrics.
     */
    protected function calculateUsageMetrics(array $normalizedCDR, array $classification): array
    {
        $quantity = 0;
        $unitType = UsageRecord::UNIT_TYPE_MINUTE;
        
        switch ($classification['usage_type']) {
            case UsageRecord::USAGE_TYPE_VOICE:
                $quantity = ceil($normalizedCDR['duration_seconds'] / 60); // Round up to next minute
                $unitType = UsageRecord::UNIT_TYPE_MINUTE;
                break;
                
            case UsageRecord::USAGE_TYPE_DATA:
                $quantity = $normalizedCDR['data_volume_mb'];
                $unitType = UsageRecord::UNIT_TYPE_MB;
                break;
                
            default:
                $quantity = 1;
                $unitType = UsageRecord::UNIT_TYPE_CALL;
        }
        
        return [
            'quantity' => $quantity,
            'unit_type' => $unitType,
            'duration_seconds' => $normalizedCDR['duration_seconds'],
            'data_volume_mb' => $normalizedCDR['data_volume_mb'],
            'line_count' => 1 // Default to 1 line
        ];
    }

    /**
     * Create usage record.
     */
    protected function createUsageRecord(
        Client $client,
        array $normalizedCDR,
        array $classification,
        array $usageMetrics,
        array $fraudCheck
    ): UsageRecord {
        $usageDate = $normalizedCDR['usage_start_time']->toDateString();
        $billingPeriod = $normalizedCDR['usage_start_time']->format('Y-m');
        
        return UsageRecord::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'transaction_id' => 'TXN-' . uniqid() . '-' . time(),
            'cdr_id' => $normalizedCDR['cdr_id'],
            'external_id' => $normalizedCDR['external_id'],
            'batch_id' => $normalizedCDR['batch_id'],
            'usage_type' => $classification['usage_type'],
            'service_type' => $classification['service_type'],
            'usage_category' => $classification['usage_category'],
            'billing_category' => $classification['billing_category'],
            'quantity' => $usageMetrics['quantity'],
            'unit_type