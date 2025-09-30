<?php

namespace App\Domains\Financial\Services;

use App\Models\TaxCategory;
use App\Models\TaxJurisdiction;
use App\Models\TaxRateHistory;
use App\Models\VoIPTaxRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tax Rate Management Service
 *
 * Handles creation, update, and management of VoIP tax rates.
 * Supports external API integration for automated rate updates.
 */
class TaxRateManagementService
{
    protected int $companyId;

    protected array $config;

    public function __construct(int $companyId, array $config = [])
    {
        $this->companyId = $companyId;
        $this->config = array_merge([
            'external_api_enabled' => false,
            'external_api_url' => null,
            'external_api_key' => null,
            'auto_update_enabled' => false,
            'backup_before_update' => true,
        ], $config);
    }

    /**
     * Create or update tax rate with history tracking.
     */
    public function createOrUpdateTaxRate(array $data, ?int $userId = null, string $reason = 'Manual update'): VoIPTaxRate
    {
        return DB::transaction(function () use ($data, $userId, $reason) {
            $existingRate = null;

            if (isset($data['id'])) {
                $existingRate = VoIPTaxRate::find($data['id']);
            }

            // If updating existing rate
            if ($existingRate) {
                $oldValues = $existingRate->toArray();
                $existingRate->update($data);

                // Record history
                TaxRateHistory::create([
                    'company_id' => $this->companyId,
                    'voip_tax_rate_id' => $existingRate->id,
                    'old_values' => $oldValues,
                    'new_values' => $existingRate->fresh()->toArray(),
                    'change_reason' => $reason,
                    'changed_by' => $userId,
                    'source' => 'manual',
                ]);

                Log::info('Tax rate updated', [
                    'rate_id' => $existingRate->id,
                    'tax_name' => $existingRate->tax_name,
                    'changed_by' => $userId,
                ]);

                return $existingRate;
            }

            // Create new rate
            $data['company_id'] = $this->companyId;
            $newRate = VoIPTaxRate::create($data);

            Log::info('Tax rate created', [
                'rate_id' => $newRate->id,
                'tax_name' => $newRate->tax_name,
                'created_by' => $userId,
            ]);

            return $newRate;
        });
    }

    /**
     * Bulk import tax rates from array.
     */
    public function bulkImportTaxRates(array $rates, ?int $userId = null, string $source = 'import'): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
            'batch_id' => uniqid('batch_'),
        ];

        DB::transaction(function () use ($rates, $userId, $source, &$results) {
            foreach ($rates as $index => $rateData) {
                try {
                    $rateData['company_id'] = $this->companyId;

                    // Validate required fields
                    $this->validateTaxRateData($rateData);

                    // Check if rate exists
                    $existingRate = $this->findExistingRate($rateData);

                    if ($existingRate) {
                        $this->updateExistingRate($existingRate, $rateData, $userId, $source, $results['batch_id']);
                        $results['updated']++;
                    } else {
                        $this->createNewRate($rateData, $userId, $source, $results['batch_id']);
                        $results['created']++;
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'index' => $index,
                        'data' => $rateData,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        Log::info('Bulk tax rate import completed', [
            'company_id' => $this->companyId,
            'batch_id' => $results['batch_id'],
            'created' => $results['created'],
            'updated' => $results['updated'],
            'errors' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Update tax rates from external API.
     */
    public function updateFromExternalAPI(?int $userId = null): array
    {
        if (! $this->config['external_api_enabled'] || ! $this->config['external_api_url']) {
            throw new \Exception('External API is not configured');
        }

        $results = [
            'updated' => 0,
            'errors' => [],
            'batch_id' => uniqid('api_update_'),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['external_api_key'],
                'Accept' => 'application/json',
            ])->timeout(60)->get($this->config['external_api_url'], [
                'company_id' => $this->companyId,
                'effective_date' => now()->toDateString(),
            ]);

            if ($response->failed()) {
                throw new \Exception('External API request failed: '.$response->status());
            }

            $externalRates = $response->json('data', []);

            if (empty($externalRates)) {
                Log::warning('No tax rates received from external API');

                return $results;
            }

            // Backup current rates if configured
            if ($this->config['backup_before_update']) {
                $this->createBackup($results['batch_id']);
            }

            $importResults = $this->bulkImportTaxRates($externalRates, $userId, 'external_api');
            $results['updated'] = $importResults['created'] + $importResults['updated'];
            $results['errors'] = $importResults['errors'];

        } catch (\Exception $e) {
            Log::error('External API tax rate update failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = [
                'type' => 'api_error',
                'error' => $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Set effective dates for tax rate changes.
     */
    public function scheduleRateChange(int $rateId, Carbon $effectiveDate, array $changes, ?int $userId = null): VoIPTaxRate
    {
        return DB::transaction(function () use ($rateId, $effectiveDate, $changes, $userId) {
            $taxRate = VoIPTaxRate::findOrFail($rateId);

            if ($effectiveDate->isPast()) {
                throw new \InvalidArgumentException('Effective date cannot be in the past');
            }

            // If the effective date is now, apply immediately
            if ($effectiveDate->isToday()) {
                return $this->createOrUpdateTaxRate(
                    array_merge(['id' => $rateId], $changes),
                    $userId,
                    'Scheduled rate change'
                );
            }

            // Create a new rate record with future effective date
            $newRateData = array_merge($taxRate->toArray(), $changes);
            $newRateData['effective_date'] = $effectiveDate;
            unset($newRateData['id'], $newRateData['created_at'], $newRateData['updated_at']);

            return $this->createOrUpdateTaxRate($newRateData, $userId, 'Scheduled rate change');
        });
    }

    /**
     * Expire tax rates.
     */
    public function expireTaxRate(int $rateId, Carbon $expiryDate, ?int $userId = null): VoIPTaxRate
    {
        $taxRate = VoIPTaxRate::findOrFail($rateId);

        return $this->createOrUpdateTaxRate([
            'id' => $rateId,
            'expiry_date' => $expiryDate,
            'is_active' => $expiryDate->isPast() ? false : $taxRate->is_active,
        ], $userId, 'Rate expiration');
    }

    /**
     * Initialize default tax rates for a company.
     */
    public function initializeDefaultRates(): array
    {
        $results = ['created' => 0, 'errors' => []];

        // Create federal jurisdiction if it doesn't exist
        $federalJurisdiction = TaxJurisdiction::firstOrCreate([
            'company_id' => $this->companyId,
            'jurisdiction_type' => 'federal',
            'code' => 'US-FED',
        ], [
            'name' => 'United States Federal',
            'authority_name' => 'Federal Communications Commission',
            'is_active' => true,
            'priority' => 1,
        ]);

        // Create default tax categories
        $categories = TaxCategory::createDefaultCategories($this->companyId);

        // Create default federal tax rates
        $defaultRates = [
            [
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'tax_category_id' => $categories[0]->id, // Local service
                'tax_type' => 'federal',
                'tax_name' => 'Federal Excise Tax',
                'rate_type' => 'percentage',
                'percentage_rate' => 3.0,
                'minimum_threshold' => 0.20,
                'calculation_method' => 'standard',
                'authority_name' => 'Internal Revenue Service',
                'effective_date' => now(),
                'is_active' => true,
                'priority' => 100,
                'service_types' => ['local', 'long_distance', 'voip_fixed', 'voip_nomadic'],
            ],
            [
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'tax_category_id' => $categories[0]->id, // Local service
                'tax_type' => 'federal',
                'tax_name' => 'Universal Service Fund',
                'rate_type' => 'percentage',
                'percentage_rate' => 33.4,
                'calculation_method' => 'standard',
                'authority_name' => 'Federal Communications Commission',
                'effective_date' => now(),
                'is_active' => true,
                'priority' => 110,
                'service_types' => ['local', 'long_distance', 'international', 'voip_fixed', 'voip_nomadic'],
            ],
        ];

        foreach ($defaultRates as $rateData) {
            try {
                $rateData['company_id'] = $this->companyId;
                VoIPTaxRate::create($rateData);
                $results['created']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'data' => $rateData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Default tax rates initialized', [
            'company_id' => $this->companyId,
            'created' => $results['created'],
            'errors' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Get tax rate history for a specific rate.
     */
    public function getTaxRateHistory(int $rateId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return TaxRateHistory::where('voip_tax_rate_id', $rateId)
            ->with(['changedByUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Export tax rates to array.
     */
    public function exportTaxRates(array $filters = []): array
    {
        $query = VoIPTaxRate::with(['jurisdiction', 'category'])
            ->where('company_id', $this->companyId);

        // Apply filters
        if (isset($filters['jurisdiction_id'])) {
            $query->where('tax_jurisdiction_id', $filters['jurisdiction_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('tax_category_id', $filters['category_id']);
        }

        if (isset($filters['tax_type'])) {
            $query->where('tax_type', $filters['tax_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $rates = $query->orderBy('priority')->get();

        return $rates->map(function ($rate) {
            return [
                'id' => $rate->id,
                'jurisdiction_name' => $rate->jurisdiction->name,
                'jurisdiction_code' => $rate->jurisdiction->code,
                'category_name' => $rate->category->name,
                'category_code' => $rate->category->code,
                'tax_type' => $rate->tax_type,
                'tax_name' => $rate->tax_name,
                'rate_type' => $rate->rate_type,
                'percentage_rate' => $rate->percentage_rate,
                'fixed_amount' => $rate->fixed_amount,
                'minimum_threshold' => $rate->minimum_threshold,
                'maximum_amount' => $rate->maximum_amount,
                'calculation_method' => $rate->calculation_method,
                'authority_name' => $rate->authority_name,
                'tax_code' => $rate->tax_code,
                'service_types' => $rate->service_types,
                'is_active' => $rate->is_active,
                'effective_date' => $rate->effective_date->toISOString(),
                'expiry_date' => $rate->expiry_date?->toISOString(),
                'priority' => $rate->priority,
            ];
        })->toArray();
    }

    /**
     * Validate tax rate data.
     */
    protected function validateTaxRateData(array $data): void
    {
        $required = [
            'tax_jurisdiction_id', 'tax_category_id', 'tax_type',
            'tax_name', 'rate_type', 'authority_name', 'effective_date',
        ];

        foreach ($required as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }

        // Validate rate type specific fields
        if ($data['rate_type'] === 'percentage' && ! isset($data['percentage_rate'])) {
            throw new \InvalidArgumentException('Percentage rate is required for percentage rate type');
        }

        if (in_array($data['rate_type'], ['fixed', 'per_line', 'per_minute']) && ! isset($data['fixed_amount'])) {
            throw new \InvalidArgumentException('Fixed amount is required for '.$data['rate_type'].' rate type');
        }
    }

    /**
     * Find existing tax rate based on key fields.
     */
    protected function findExistingRate(array $data): ?VoIPTaxRate
    {
        return VoIPTaxRate::where('company_id', $this->companyId)
            ->where('tax_jurisdiction_id', $data['tax_jurisdiction_id'])
            ->where('tax_category_id', $data['tax_category_id'])
            ->where('tax_type', $data['tax_type'])
            ->where('tax_name', $data['tax_name'])
            ->where('effective_date', $data['effective_date'])
            ->first();
    }

    /**
     * Update existing tax rate.
     */
    protected function updateExistingRate(VoIPTaxRate $rate, array $data, ?int $userId, string $source, string $batchId): void
    {
        $oldValues = $rate->toArray();
        $rate->update($data);

        TaxRateHistory::create([
            'company_id' => $this->companyId,
            'voip_tax_rate_id' => $rate->id,
            'old_values' => $oldValues,
            'new_values' => $rate->fresh()->toArray(),
            'change_reason' => 'Bulk update',
            'changed_by' => $userId,
            'source' => $source,
            'batch_id' => $batchId,
        ]);
    }

    /**
     * Create new tax rate.
     */
    protected function createNewRate(array $data, ?int $userId, string $source, string $batchId): VoIPTaxRate
    {
        $rate = VoIPTaxRate::create($data);

        // Record creation in history
        TaxRateHistory::create([
            'company_id' => $this->companyId,
            'voip_tax_rate_id' => $rate->id,
            'old_values' => [],
            'new_values' => $rate->toArray(),
            'change_reason' => 'Rate created',
            'changed_by' => $userId,
            'source' => $source,
            'batch_id' => $batchId,
        ]);

        return $rate;
    }

    /**
     * Create backup of current rates.
     */
    protected function createBackup(string $batchId): void
    {
        $rates = VoIPTaxRate::where('company_id', $this->companyId)->get();

        $backupData = [
            'batch_id' => $batchId,
            'company_id' => $this->companyId,
            'backup_date' => now()->toISOString(),
            'rates' => $rates->toArray(),
        ];

        // Store backup (could be file system, database, or cloud storage)
        $backupPath = storage_path('app/tax-backups/'.$batchId.'.json');

        if (! file_exists(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }

        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));

        Log::info('Tax rates backup created', [
            'company_id' => $this->companyId,
            'batch_id' => $batchId,
            'backup_path' => $backupPath,
            'rates_count' => $rates->count(),
        ]);
    }

    /**
     * Restore rates from backup.
     */
    public function restoreFromBackup(string $batchId): array
    {
        $backupPath = storage_path('app/tax-backups/'.$batchId.'.json');

        if (! file_exists($backupPath)) {
            throw new \Exception("Backup file not found for batch ID: {$batchId}");
        }

        $backupData = json_decode(file_get_contents($backupPath), true);

        if (! $backupData || $backupData['company_id'] !== $this->companyId) {
            throw new \Exception('Invalid backup data or company mismatch');
        }

        // Delete current rates
        VoIPTaxRate::where('company_id', $this->companyId)->delete();

        // Restore rates
        $results = ['restored' => 0, 'errors' => []];

        foreach ($backupData['rates'] as $rateData) {
            try {
                unset($rateData['id'], $rateData['created_at'], $rateData['updated_at'], $rateData['deleted_at']);
                VoIPTaxRate::create($rateData);
                $results['restored']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'data' => $rateData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Tax rates restored from backup', [
            'company_id' => $this->companyId,
            'batch_id' => $batchId,
            'restored' => $results['restored'],
            'errors' => count($results['errors']),
        ]);

        return $results;
    }
}
