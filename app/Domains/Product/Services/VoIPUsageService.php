<?php

namespace App\Domains\Product\Services;

use App\Models\Recurring;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VoIPUsageService
 * 
 * Handles VoIP usage data processing, CDR imports, usage calculations,
 * and tiered pricing logic for sophisticated VoIP billing scenarios.
 */
class VoIPUsageService
{
    /**
     * Process usage data for recurring billing
     */
    public function processUsageData(Recurring $recurring, array $data): array
    {
        $results = ['processed_count' => 0, 'total_usage' => 0, 'total_cost' => 0, 'errors' => []];

        try {
            DB::transaction(function () use ($recurring, $data, &$results) {
                // Handle different data sources
                if (isset($data['cdr_records'])) {
                    $this->processCDRRecords($recurring, $data['cdr_records'], $results);
                }

                if (isset($data['usage_file'])) {
                    $this->processUsageFile($recurring, $data['usage_file'], $results);
                }

                if (isset($data['manual_usage'])) {
                    $this->processManualUsage($recurring, $data['manual_usage'], $results);
                }

                // Store processed usage data in recurring metadata
                $this->storeUsageDataInMetadata($recurring, $results);
            });

            Log::info('Usage data processed successfully', [
                'recurring_id' => $recurring->id,
                'processed_count' => $results['processed_count'],
                'total_usage' => $results['total_usage']
            ]);

        } catch (\Exception $e) {
            Log::error('Usage data processing failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage()
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get usage summary for a client over a date range
     */
    public function getUsageSummary(int $clientId, Carbon $startDate, Carbon $endDate): array
    {
        // Get usage data from recurring metadata (until models are created)
        $recurring = Recurring::where('client_id', $clientId)
            ->where('status', true)
            ->first();

        if (!$recurring) {
            return ['total_usage' => 0, 'services' => [], 'period' => []];
        }

        $usageData = $recurring->metadata['usage_data'] ?? [];
        $periodUsage = collect($usageData)
            ->filter(function ($record) use ($startDate, $endDate) {
                $usageDate = Carbon::parse($record['usage_date']);
                return $usageDate->between($startDate, $endDate);
            });

        $summary = [
            'total_usage' => $periodUsage->sum('usage_amount'),
            'total_cost' => $periodUsage->sum('cost'),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'services' => []
        ];

        // Group by service type
        $serviceGroups = $periodUsage->groupBy('service_type');
        foreach ($serviceGroups as $serviceType => $records) {
            $summary['services'][$serviceType] = [
                'usage' => $records->sum('usage_amount'),
                'cost' => $records->sum('cost'),
                'unit' => $records->first()['usage_unit'] ?? 'minutes',
                'record_count' => $records->count()
            ];
        }

        return $summary;
    }

    /**
     * Calculate tiered pricing for usage
     */
    public function calculateTieredPricing(float $usage, array $tiers, string $serviceType = 'default'): array
    {
        $calculation = [
            'usage' => $usage,
            'total_cost' => 0,
            'tier_breakdown' => [],
            'service_type' => $serviceType
        ];

        $remainingUsage = $usage;
        
        foreach ($tiers as $tierIndex => $tier) {
            if ($remainingUsage <= 0) {
                break;
            }

            $tierMin = $tier['min_usage'] ?? 0;
            $tierMax = $tier['max_usage'] ?? null;
            $tierRate = $tier['rate'] ?? 0;

            // Calculate usage for this tier
            $tierUsage = 0;
            if ($tierMax === null) {
                // Unlimited tier
                $tierUsage = $remainingUsage;
            } else {
                $tierUsage = min($remainingUsage, $tierMax - $tierMin);
            }

            $tierCost = $tierUsage * $tierRate;
            $calculation['total_cost'] += $tierCost;

            $calculation['tier_breakdown'][] = [
                'tier' => $tierIndex + 1,
                'tier_name' => $tier['name'] ?? "Tier " . ($tierIndex + 1),
                'usage_range' => [
                    'min' => $tierMin,
                    'max' => $tierMax
                ],
                'rate' => $tierRate,
                'usage_in_tier' => $tierUsage,
                'cost' => $tierCost
            ];

            $remainingUsage -= $tierUsage;
        }

        return $calculation;
    }

    /**
     * Calculate overage charges
     */
    public function calculateOverageCharges(Recurring $recurring, array $usageSummary): array
    {
        $overageCharges = ['total' => 0, 'breakdown' => []];
        $serviceTiers = $recurring->service_tiers ?? [];

        foreach ($serviceTiers as $tier) {
            $serviceType = $tier['service_type'];
            $monthlyAllowance = $tier['monthly_allowance'] ?? 0;
            $overageRate = $tier['overage_rate'] ?? 0;

            $actualUsage = $usageSummary['services'][$serviceType]['usage'] ?? 0;

            if ($actualUsage > $monthlyAllowance) {
                $overage = $actualUsage - $monthlyAllowance;
                $overageCharge = $overage * $overageRate;

                // Apply any overage caps
                if (isset($tier['overage_maximum']) && $overageCharge > $tier['overage_maximum']) {
                    $overageCharge = $tier['overage_maximum'];
                }

                // Apply minimum overage charge
                if (isset($tier['overage_minimum']) && $overageCharge < $tier['overage_minimum']) {
                    $overageCharge = $tier['overage_minimum'];
                }

                $overageCharges['total'] += $overageCharge;
                $overageCharges['breakdown'][] = [
                    'service_type' => $serviceType,
                    'allowance' => $monthlyAllowance,
                    'usage' => $actualUsage,
                    'overage' => $overage,
                    'rate' => $overageRate,
                    'charge' => $overageCharge
                ];
            }
        }

        return $overageCharges;
    }

    /**
     * Import CDR (Call Detail Records) data
     */
    public function importCDRData(Recurring $recurring, array $cdrRecords): array
    {
        $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($cdrRecords as $cdr) {
            try {
                $usageRecord = $this->convertCDRToUsageRecord($cdr);
                $this->storeUsageRecord($recurring, $usageRecord);
                $results['imported']++;
            } catch (\Exception $e) {
                $results['skipped']++;
                $results['errors'][] = [
                    'cdr_id' => $cdr['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('CDR data import completed', [
            'recurring_id' => $recurring->id,
            'imported' => $results['imported'],
            'skipped' => $results['skipped']
        ]);

        return $results;
    }

    /**
     * Process international calling charges
     */
    public function processInternationalCharges(array $usageData, array $internationalRates): array
    {
        $charges = ['total' => 0, 'breakdown' => []];

        foreach ($usageData as $record) {
            if ($record['call_type'] === 'international') {
                $destination = $this->getCountryFromNumber($record['to_number']);
                $rate = $internationalRates[$destination] ?? $internationalRates['default'] ?? 0;
                
                $duration = $record['duration_seconds'] / 60; // Convert to minutes
                $charge = $duration * $rate;

                $charges['total'] += $charge;
                $charges['breakdown'][] = [
                    'destination' => $destination,
                    'duration_minutes' => $duration,
                    'rate' => $rate,
                    'charge' => $charge,
                    'call_details' => $record
                ];
            }
        }

        return $charges;
    }

    /**
     * Generate usage report
     */
    public function generateUsageReport(Recurring $recurring, Carbon $startDate, Carbon $endDate): array
    {
        $usageData = $this->getUsageDataForPeriod($recurring, $startDate, $endDate);
        
        $report = [
            'recurring_id' => $recurring->id,
            'client_name' => $recurring->client->name,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'summary' => [
                'total_calls' => 0,
                'total_minutes' => 0,
                'total_cost' => 0
            ],
            'by_service_type' => [],
            'by_call_type' => [],
            'by_day' => []
        ];

        foreach ($usageData as $record) {
            $serviceType = $record['service_type'];
            $callType = $record['call_type'] ?? 'unknown';
            $usageDate = Carbon::parse($record['usage_date'])->toDateString();

            // Summary totals
            $report['summary']['total_calls']++;
            $report['summary']['total_minutes'] += $record['usage_amount'];
            $report['summary']['total_cost'] += $record['cost'];

            // By service type
            if (!isset($report['by_service_type'][$serviceType])) {
                $report['by_service_type'][$serviceType] = [
                    'calls' => 0, 'minutes' => 0, 'cost' => 0
                ];
            }
            $report['by_service_type'][$serviceType]['calls']++;
            $report['by_service_type'][$serviceType]['minutes'] += $record['usage_amount'];
            $report['by_service_type'][$serviceType]['cost'] += $record['cost'];

            // By call type
            if (!isset($report['by_call_type'][$callType])) {
                $report['by_call_type'][$callType] = [
                    'calls' => 0, 'minutes' => 0, 'cost' => 0
                ];
            }
            $report['by_call_type'][$callType]['calls']++;
            $report['by_call_type'][$callType]['minutes'] += $record['usage_amount'];
            $report['by_call_type'][$callType]['cost'] += $record['cost'];

            // By day
            if (!isset($report['by_day'][$usageDate])) {
                $report['by_day'][$usageDate] = [
                    'calls' => 0, 'minutes' => 0, 'cost' => 0
                ];
            }
            $report['by_day'][$usageDate]['calls']++;
            $report['by_day'][$usageDate]['minutes'] += $record['usage_amount'];
            $report['by_day'][$usageDate]['cost'] += $record['cost'];
        }

        return $report;
    }

    /**
     * Process CDR records
     */
    protected function processCDRRecords(Recurring $recurring, array $cdrRecords, array &$results): void
    {
        foreach ($cdrRecords as $cdr) {
            $usageRecord = [
                'service_type' => $this->determineServiceType($cdr),
                'usage_date' => Carbon::parse($cdr['call_date'])->toDateString(),
                'usage_amount' => ($cdr['duration'] ?? 0) / 60, // Convert seconds to minutes
                'usage_unit' => 'minutes',
                'from_number' => $cdr['from_number'] ?? '',
                'to_number' => $cdr['to_number'] ?? '',
                'call_type' => $this->determineCallType($cdr),
                'duration_seconds' => $cdr['duration'] ?? 0,
                'rate' => $this->getRate($recurring, $cdr),
                'cost' => 0, // Will be calculated
                'external_id' => $cdr['id'] ?? null
            ];

            $usageRecord['cost'] = $usageRecord['usage_amount'] * $usageRecord['rate'];

            $this->storeUsageRecord($recurring, $usageRecord);
            
            $results['processed_count']++;
            $results['total_usage'] += $usageRecord['usage_amount'];
            $results['total_cost'] += $usageRecord['cost'];
        }
    }

    /**
     * Process usage file
     */
    protected function processUsageFile(Recurring $recurring, string $filePath, array &$results): void
    {
        // TODO: Implement file processing logic
        // This would handle CSV, JSON, or other file formats containing usage data
        $results['processed_count'] = 0;
    }

    /**
     * Process manual usage entries
     */
    protected function processManualUsage(Recurring $recurring, array $manualEntries, array &$results): void
    {
        foreach ($manualEntries as $entry) {
            $usageRecord = [
                'service_type' => $entry['service_type'],
                'usage_date' => $entry['usage_date'],
                'usage_amount' => $entry['usage_amount'],
                'usage_unit' => $entry['usage_unit'] ?? 'minutes',
                'rate' => $entry['rate'] ?? 0,
                'cost' => $entry['cost'] ?? 0,
                'description' => $entry['description'] ?? 'Manual entry'
            ];

            $this->storeUsageRecord($recurring, $usageRecord);
            
            $results['processed_count']++;
            $results['total_usage'] += $usageRecord['usage_amount'];
            $results['total_cost'] += $usageRecord['cost'];
        }
    }

    /**
     * Store usage record in metadata
     */
    protected function storeUsageRecord(Recurring $recurring, array $usageRecord): void
    {
        $metadata = $recurring->metadata ?? [];
        $usageData = $metadata['usage_data'] ?? [];
        
        $usageData[] = array_merge($usageRecord, [
            'created_at' => now()->toISOString(),
            'id' => count($usageData) + 1
        ]);
        
        $metadata['usage_data'] = $usageData;
        $recurring->update(['metadata' => $metadata]);
    }

    /**
     * Store processed usage data summary in metadata
     */
    protected function storeUsageDataInMetadata(Recurring $recurring, array $results): void
    {
        $metadata = $recurring->metadata ?? [];
        $processingSummary = $metadata['usage_processing_summary'] ?? [];
        
        $processingSummary[] = [
            'processed_at' => now()->toISOString(),
            'processed_count' => $results['processed_count'],
            'total_usage' => $results['total_usage'],
            'total_cost' => $results['total_cost'],
            'errors_count' => count($results['errors'])
        ];
        
        $metadata['usage_processing_summary'] = $processingSummary;
        $recurring->update(['metadata' => $metadata]);
    }

    /**
     * Determine service type from CDR
     */
    protected function determineServiceType(array $cdr): string
    {
        // Logic to determine service type based on CDR data
        if (isset($cdr['service_type'])) {
            return $cdr['service_type'];
        }

        // Default determination logic
        $toNumber = $cdr['to_number'] ?? '';
        if (preg_match('/^011/', $toNumber)) {
            return 'international';
        } elseif (preg_match('/^1[0-9]{10}$/', $toNumber)) {
            return 'long_distance';
        } else {
            return 'local';
        }
    }

    /**
     * Determine call type
     */
    protected function determineCallType(array $cdr): string
    {
        $toNumber = $cdr['to_number'] ?? '';
        
        if (preg_match('/^011/', $toNumber)) {
            return 'international';
        } elseif (preg_match('/^1[0-9]{10}$/', $toNumber)) {
            return 'long_distance';
        } else {
            return 'local';
        }
    }

    /**
     * Get rate for CDR
     */
    protected function getRate(Recurring $recurring, array $cdr): float
    {
        $serviceTiers = $recurring->service_tiers ?? [];
        $serviceType = $this->determineServiceType($cdr);
        
        foreach ($serviceTiers as $tier) {
            if ($tier['service_type'] === $serviceType) {
                return $tier['base_rate'] ?? 0;
            }
        }
        
        return 0.05; // Default rate
    }

    /**
     * Get country from phone number
     */
    protected function getCountryFromNumber(string $phoneNumber): string
    {
        // Simplified country detection - in production this would use a proper lookup table
        if (preg_match('/^011(\d{1,3})/', $phoneNumber, $matches)) {
            $countryCode = $matches[1];
            
            $countryCodes = [
                '44' => 'UK',
                '33' => 'France',
                '49' => 'Germany',
                '86' => 'China',
                '81' => 'Japan'
            ];
            
            return $countryCodes[$countryCode] ?? 'Unknown';
        }
        
        return 'Domestic';
    }

    /**
     * Get usage data for a specific period
     */
    protected function getUsageDataForPeriod(Recurring $recurring, Carbon $startDate, Carbon $endDate): array
    {
        $usageData = $recurring->metadata['usage_data'] ?? [];
        
        return collect($usageData)
            ->filter(function ($record) use ($startDate, $endDate) {
                $usageDate = Carbon::parse($record['usage_date']);
                return $usageDate->between($startDate, $endDate);
            })
            ->toArray();
    }

    /**
     * Convert CDR to standardized usage record format
     */
    protected function convertCDRToUsageRecord(array $cdr): array
    {
        return [
            'service_type' => $this->determineServiceType($cdr),
            'usage_date' => Carbon::parse($cdr['call_date'] ?? $cdr['date'])->toDateString(),
            'usage_amount' => ($cdr['duration'] ?? 0) / 60,
            'usage_unit' => 'minutes',
            'from_number' => $cdr['from_number'] ?? $cdr['caller_id'] ?? '',
            'to_number' => $cdr['to_number'] ?? $cdr['called_number'] ?? '',
            'call_type' => $this->determineCallType($cdr),
            'duration_seconds' => $cdr['duration'] ?? 0,
            'external_id' => $cdr['id'] ?? $cdr['call_id'] ?? null,
            'imported_at' => now()->toISOString()
        ];
    }
}