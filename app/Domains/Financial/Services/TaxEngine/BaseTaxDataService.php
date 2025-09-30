<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base Tax Data Service
 *
 * Provides common functionality for all state-specific tax data services.
 * Handles caching, HTTP requests, database operations, and error handling.
 */
abstract class BaseTaxDataService implements TaxDataServiceInterface
{
    protected ?int $companyId = null;

    protected array $config;

    protected string $stateCode;

    protected string $stateName;

    protected array $apiConfig;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_ttl' => 3600, // 1 hour
            'enable_caching' => true,
            'round_precision' => 4,
            'batch_size' => 1000,
            'timeout' => 60,
            'retry_attempts' => 3,
        ], $config);

        $this->loadApiConfiguration();
    }

    /**
     * Set the company ID for operations
     */
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * Load API configuration from database or config files
     */
    protected function loadApiConfiguration(): void
    {
        // Load from company-specific configuration in database
        if ($this->companyId) {
            $this->apiConfig = $this->loadFromDatabase();
        } else {
            // Fallback to config files
            $this->apiConfig = $this->loadFromConfig();
        }
    }

    /**
     * Load configuration from database
     */
    protected function loadFromDatabase(): array
    {
        $config = DB::table('company_tax_configurations')
            ->where('company_id', $this->companyId)
            ->where('state_code', $this->getStateCode())
            ->where('is_active', true)
            ->first();

        if ($config) {
            return [
                'api_key' => $config->api_key,
                'base_url' => $config->api_base_url,
                'enabled' => $config->is_enabled,
                'auto_update' => $config->auto_update_enabled,
                'update_frequency' => $config->update_frequency,
                'last_updated' => $config->last_updated,
                'metadata' => json_decode($config->metadata ?? '{}', true),
            ];
        }

        return $this->getDefaultConfig();
    }

    /**
     * Load configuration from Laravel config files
     */
    protected function loadFromConfig(): array
    {
        $configKey = strtolower($this->getStateCode()).'_tax';
        $config = config($configKey, []);

        return array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'api_key' => null,
            'base_url' => null,
            'enabled' => false,
            'auto_update' => false,
            'update_frequency' => 'quarterly',
            'last_updated' => null,
            'metadata' => [],
        ];
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiConfig['api_key']) &&
               ! empty($this->apiConfig['base_url']) &&
               $this->apiConfig['enabled'];
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        $rateCount = DB::table('service_tax_rates')
            ->where('source', $this->getStateCode().'_official')
            ->where('is_active', 1)
            ->count();

        return [
            'configured' => $this->isConfigured(),
            'state_code' => $this->getStateCode(),
            'state_name' => $this->getStateName(),
            'tax_rates' => $rateCount,
            'last_updated' => $this->apiConfig['last_updated'],
            'auto_update' => $this->apiConfig['auto_update'],
            'source' => $this->getStateCode().'_official',
            'cost' => 'FREE', // Most official sources are free
        ];
    }

    /**
     * Make HTTP request with retry logic
     */
    protected function makeHttpRequest(string $method, string $url, array $options = []): array
    {
        try {
            $headers = array_merge([
                'Accept' => 'application/json',
                'User-Agent' => 'Nestogy-MSP/1.0',
            ], $options['headers'] ?? []);

            if ($this->apiConfig['api_key']) {
                $headers['x-api-key'] = $this->apiConfig['api_key'];
            }

            $request = Http::withHeaders($headers)
                ->timeout($this->config['timeout'])
                ->retry($this->config['retry_attempts'], 1000);

            if (isset($options['without_redirecting']) && $options['without_redirecting']) {
                $request = $request->withoutRedirecting();
            }

            $response = $request->{$method}($url, $options['data'] ?? []);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status' => $response->status(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: {$response->body()}",
                    'status' => $response->status(),
                ];
            }

        } catch (Exception $e) {
            Log::error("HTTP request failed for {$this->getStateCode()}", [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate cache key
     */
    protected function generateCacheKey(string $operation, array $params = []): string
    {
        $keyData = array_merge([
            'company_id' => $this->companyId,
            'state' => $this->getStateCode(),
            'operation' => $operation,
        ], $params);

        return 'tax_'.md5(json_encode($keyData));
    }

    /**
     * Get cached data or execute callback
     */
    protected function getCachedData(string $key, callable $callback)
    {
        if (! $this->config['enable_caching']) {
            return $callback();
        }

        return Cache::remember($key, $this->config['cache_ttl'], $callback);
    }

    /**
     * Clear cache for this service
     */
    public function clearCache(): void
    {
        $pattern = "tax_*{$this->getStateCode()}*";

        // Clear cache entries matching pattern
        if (config('cache.default') === 'redis') {
            $prefix = config('cache.prefix', '');
            $fullPattern = $prefix.$pattern;
            $keys = Cache::getRedis()->keys($fullPattern);
            if (! empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            Cache::flush(); // Fallback for other drivers
        }

        Log::info("Cache cleared for {$this->getStateCode()} tax service", [
            'company_id' => $this->companyId,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Bulk insert tax rates
     */
    protected function bulkInsertTaxRates(array $rates): int
    {
        $inserted = 0;
        $chunks = array_chunk($rates, $this->config['batch_size']);

        foreach ($chunks as $chunk) {
            DB::table('service_tax_rates')->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    /**
     * Create or get jurisdiction record
     */
    protected function createOrGetJurisdiction(array $data): int
    {
        $existing = DB::table('tax_jurisdictions')
            ->where('code', $data['code'])
            ->where('state_code', $this->getStateCode())
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('tax_jurisdictions')->insertGetId(array_merge($data, [
            'company_id' => $this->companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Get service metadata
     */
    public function getServiceMetadata(): array
    {
        return [
            'state_code' => $this->getStateCode(),
            'state_name' => $this->getStateName(),
            'service_type' => get_class($this),
            'configured' => $this->isConfigured(),
            'api_configured' => ! empty($this->apiConfig['api_key']),
            'last_updated' => $this->apiConfig['last_updated'],
            'auto_update' => $this->apiConfig['auto_update'],
        ];
    }

    // Abstract methods that must be implemented by concrete classes
    abstract public function getStateCode(): string;

    abstract public function getStateName(): string;

    abstract public function downloadTaxRates(): array;

    abstract public function downloadAddressData(?string $jurisdictionCode = null): array;

    abstract public function updateDatabaseWithRates(array $rates): array;

    abstract public function listAvailableFiles(): array;

    abstract public function downloadFile(string $filePath): array;
}
