<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Exception;
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

    protected TaxServiceConfigurationManager $configManager;

    protected TaxServiceCacheManager $cacheManager;

    protected TaxServiceDatabaseManager $databaseManager;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_ttl' => 3600,
            'enable_caching' => true,
            'round_precision' => 4,
            'batch_size' => 1000,
            'timeout' => 60,
            'retry_attempts' => 3,
        ], $config);

        $stateCode = $this->getStateCode();
        $this->configManager = new TaxServiceConfigurationManager($stateCode, $this->companyId);
        $this->cacheManager = new TaxServiceCacheManager($stateCode, $this->companyId, $this->config);
        $this->databaseManager = new TaxServiceDatabaseManager($stateCode, $this->companyId, $this->config);
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        $this->configManager->setCompanyId($companyId);
        $this->cacheManager->setCompanyId($companyId);
        $this->databaseManager->setCompanyId($companyId);

        return $this;
    }

    public function isConfigured(): bool
    {
        return $this->configManager->isConfigured();
    }

    protected function getApiConfig(): array
    {
        return $this->configManager->getApiConfig();
    }

    public function getConfigurationStatus(): array
    {
        $rateCount = DB::table('service_tax_rates')
            ->where('source', $this->getStateCode().'_official')
            ->where('is_active', 1)
            ->count();

        $apiConfig = $this->configManager->getApiConfig();

        return [
            'configured' => $this->isConfigured(),
            'state_code' => $this->getStateCode(),
            'state_name' => $this->getStateName(),
            'tax_rates' => $rateCount,
            'last_updated' => $apiConfig['last_updated'],
            'auto_update' => $apiConfig['auto_update'],
            'source' => $this->getStateCode().'_official',
            'cost' => 'FREE',
        ];
    }

    protected function makeHttpRequest(string $method, string $url, array $options = []): array
    {
        try {
            $apiConfig = $this->configManager->getApiConfig();

            $headers = array_merge([
                'Accept' => 'application/json',
                'User-Agent' => 'Nestogy-MSP/1.0',
            ], $options['headers'] ?? []);

            if ($apiConfig['api_key']) {
                $headers['x-api-key'] = $apiConfig['api_key'];
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

    protected function generateCacheKey(string $operation, array $params = []): string
    {
        return $this->cacheManager->generateCacheKey($operation, $params);
    }

    protected function getCachedData(string $key, callable $callback)
    {
        return $this->cacheManager->getCachedData($key, $callback);
    }

    public function clearCache(): void
    {
        $this->cacheManager->clearCache();
    }

    protected function bulkInsertTaxRates(array $rates): int
    {
        return $this->databaseManager->bulkInsertTaxRates($rates);
    }

    protected function createOrGetJurisdiction(array $data): int
    {
        return $this->databaseManager->createOrGetJurisdiction($data);
    }

    public function getServiceMetadata(): array
    {
        $apiConfig = $this->configManager->getApiConfig();

        return [
            'state_code' => $this->getStateCode(),
            'state_name' => $this->getStateName(),
            'service_type' => get_class($this),
            'configured' => $this->isConfigured(),
            'api_configured' => ! empty($apiConfig['api_key']),
            'last_updated' => $apiConfig['last_updated'],
            'auto_update' => $apiConfig['auto_update'],
        ];
    }

    abstract public function getStateCode(): string;

    abstract public function getStateName(): string;

    abstract public function downloadTaxRates(): array;

    abstract public function downloadAddressData(?string $jurisdictionCode = null): array;

    abstract public function updateDatabaseWithRates(array $rates): array;

    abstract public function listAvailableFiles(): array;

    abstract public function downloadFile(string $filePath): array;
}
