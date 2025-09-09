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
    protected function getConfigRegistry(?int $companyId = null)
    {
        // Use passed companyId or resolve from context
        if ($companyId === null) {
            $companyId = $this->resolveCompanyId();
        }
        
        // Extra safety check to ensure we never pass null
        if ($companyId === null) {
            $companyId = 1;
        }
        
        return new \App\Domains\Contract\Services\ContractConfigurationRegistry($companyId);
    }

    /**
     * Resolve company ID for configuration lookup
     */
    protected function resolveCompanyId(?int $companyId = null): int
    {
        if ($companyId !== null) {
            return $companyId;
        }
        
        if (isset($this->company_id) && $this->company_id !== null) {
            return $this->company_id;
        }
        
        if (auth()->check() && auth()->user()->company_id !== null) {
            return auth()->user()->company_id;
        }
        
        // Fallback to company ID 1 if everything else fails
        return 1;
    }

    /**
     * Get company configuration with caching
     */
    protected function getCompanyConfig(?int $companyId = null): array
    {
        $companyId = $this->resolveCompanyId($companyId);
        $cacheKey = 'contract_config_' . $companyId;
        
        if (!isset(static::$configurationCache[$cacheKey])) {
            // Pass the resolved companyId to getConfigRegistry to avoid re-resolution
            $registry = $this->getConfigRegistry($companyId);
            $config = $registry->getCompanyConfiguration($companyId);
            
            // Debug: Ensure we're getting an array
            if (!is_array($config)) {
                \Log::error('ContractConfigurationRegistry returned non-array', [
                    'type' => gettype($config),
                    'class' => get_class($config),
                    'companyId' => $companyId
                ]);
                // Fallback to empty array to prevent fatal error
                $config = [];
            }
            
            static::$configurationCache[$cacheKey] = $config;
        }
        
        return static::$configurationCache[$cacheKey];
    }

    /**
     * Get configuration value by key
     */
    protected function getConfigValue(string $key, $default = null, ?int $companyId = null)
    {
        $config = $this->getCompanyConfig($companyId);
        
        // Extra safety check to ensure we have an array
        if (!is_array($config)) {
            \Log::error('getConfigValue received non-array config', [
                'type' => gettype($config),
                'key' => $key,
                'companyId' => $companyId
            ]);
            return $default;
        }
        
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