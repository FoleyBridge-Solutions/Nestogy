<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\DB;

class TaxServiceConfigurationManager
{
    protected ?int $companyId = null;

    protected string $stateCode;

    protected array $apiConfig;

    public function __construct(string $stateCode, ?int $companyId = null)
    {
        $this->stateCode = $stateCode;
        $this->companyId = $companyId;
        $this->loadApiConfiguration();
    }

    protected function loadApiConfiguration(): void
    {
        if ($this->companyId) {
            $this->apiConfig = $this->loadFromDatabase();
        } else {
            $this->apiConfig = $this->loadFromConfig();
        }
    }

    protected function loadFromDatabase(): array
    {
        $config = DB::table('company_tax_configurations')
            ->where('company_id', $this->companyId)
            ->where('state_code', $this->stateCode)
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

    protected function loadFromConfig(): array
    {
        $configKey = strtolower($this->stateCode).'_tax';
        $config = config($configKey, []);

        return array_merge($this->getDefaultConfig(), $config);
    }

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

    public function isConfigured(): bool
    {
        return ! empty($this->apiConfig['api_key']) &&
               ! empty($this->apiConfig['base_url']) &&
               $this->apiConfig['enabled'];
    }

    public function getApiConfig(): array
    {
        return $this->apiConfig;
    }

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
        $this->loadApiConfiguration();
    }
}
