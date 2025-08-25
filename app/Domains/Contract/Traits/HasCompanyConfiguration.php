<?php

namespace App\Domains\Contract\Traits;

use App\Domains\Contract\Services\ContractConfigurationRegistry;

/**
 * HasCompanyConfiguration Trait
 * 
 * Provides common methods for retrieving company-specific contract configuration.
 * Eliminates duplication across contract models.
 */
trait HasCompanyConfiguration
{
    /**
     * Dynamic configuration cache
     */
    protected static $configurationCache = [];

    /**
     * Get configuration registry instance
     */
    protected function getConfigRegistry()
    {
        $companyId = $this->resolveCompanyId();
        return new \App\Domains\Contract\Services\ContractConfigurationRegistry($companyId);
    }

    /**
     * Resolve company ID for configuration lookup
     */
    protected function resolveCompanyId(?int $companyId = null): int
    {
        return $companyId ?? $this->company_id ?? auth()->user()->company_id ?? 1;
    }

    /**
     * Get company configuration with caching
     */
    protected function getCompanyConfig(?int $companyId = null): array
    {
        $companyId = $this->resolveCompanyId($companyId);
        $cacheKey = 'contract_config_' . $companyId;
        
        if (!isset(static::$configurationCache[$cacheKey])) {
            static::$configurationCache[$cacheKey] = $this->getConfigRegistry()
                ->getCompanyConfiguration($companyId);
        }
        
        return static::$configurationCache[$cacheKey];
    }

    /**
     * Get configuration value by key
     */
    protected function getConfigValue(string $key, $default = null, ?int $companyId = null)
    {
        $config = $this->getCompanyConfig($companyId);
        return $config[$key] ?? $default;
    }

    /**
     * Check if status is in allowed list
     */
    protected function isStatusInList(string $status, string $configKey, array $defaultList = []): bool
    {
        $allowedStatuses = $this->getConfigValue($configKey, $defaultList);
        return in_array($status, $allowedStatuses);
    }

    /**
     * Get available contract types for company
     */
    public function getAvailableContractTypes(?int $companyId = null): array
    {
        return $this->getConfigValue('contract_types', [], $companyId);
    }

    /**
     * Get available statuses for company
     */
    public function getAvailableStatusValues(?int $companyId = null): array
    {
        $statuses = $this->getConfigValue('statuses', [], $companyId);
        return array_keys($statuses);
    }

    /**
     * Get available renewal types for company
     */
    public function getAvailableRenewalTypes(?int $companyId = null): array
    {
        return $this->getConfigValue('renewal_types', [], $companyId);
    }

    /**
     * Get available signature statuses for company
     */
    public function getAvailableSignatureStatuses(?int $companyId = null): array
    {
        return $this->getConfigValue('signature_statuses', [], $companyId);
    }

    /**
     * Get status configuration
     */
    public function getStatusConfig(string $status, ?int $companyId = null): array
    {
        $statuses = $this->getConfigValue('statuses', [], $companyId);
        return $statuses[$status] ?? [];
    }
}