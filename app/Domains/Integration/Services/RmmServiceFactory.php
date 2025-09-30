<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\TacticalRmm\TacticalRmmService;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * RMM Service Factory
 *
 * Factory class for creating appropriate RMM service instances
 * based on the integration type. Provides a clean abstraction
 * for instantiating different RMM providers.
 */
class RmmServiceFactory
{
    /**
     * Create an RMM service instance based on the integration type.
     */
    public function make(RmmIntegration $integration): RmmServiceInterface
    {
        switch ($integration->rmm_type) {
            case RmmIntegration::RMM_TYPE_TACTICAL:
                return new TacticalRmmService($integration);

            default:
                throw new InvalidArgumentException(
                    "Unsupported RMM type: {$integration->rmm_type}"
                );
        }
    }

    /**
     * Create an RMM service instance by RMM type string.
     */
    public function makeByType(string $rmmType, RmmIntegration $integration): RmmServiceInterface
    {
        switch ($rmmType) {
            case RmmIntegration::RMM_TYPE_TACTICAL:
            case 'tactical_rmm':
            case 'tactical':
                return new TacticalRmmService($integration);

            default:
                throw new InvalidArgumentException(
                    "Unsupported RMM type: {$rmmType}"
                );
        }
    }

    /**
     * Create an RMM service instance from raw credentials.
     */
    public function makeFromCredentials(string $rmmType, string $apiUrl, string $apiKey): RmmServiceInterface
    {
        // Create a temporary integration instance with raw credentials
        $tempIntegration = new RmmIntegration;
        $tempIntegration->rmm_type = $rmmType;
        $tempIntegration->api_url_encrypted = Crypt::encryptString($apiUrl);
        $tempIntegration->api_key_encrypted = Crypt::encryptString($apiKey);

        return $this->make($tempIntegration);
    }

    /**
     * Get all available RMM service types.
     */
    public function getAvailableTypes(): array
    {
        return [
            RmmIntegration::RMM_TYPE_TACTICAL => [
                'name' => 'Tactical RMM',
                'class' => TacticalRmmService::class,
                'description' => 'Open source RMM for IT service providers',
                'features' => [
                    'Agent Management',
                    'Alert Processing',
                    'Remote Commands',
                    'Script Execution',
                    'Event Monitoring',
                    'Software Inventory',
                    'Service Management',
                ],
                'requires' => [
                    'api_url' => 'Tactical RMM Server URL',
                    'api_key' => 'API Authentication Key',
                ],
            ],
        ];
    }

    /**
     * Check if an RMM type is supported.
     */
    public function isSupported(string $rmmType): bool
    {
        try {
            $this->validateRmmType($rmmType);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get service class name for an RMM type.
     */
    public function getServiceClass(string $rmmType): string
    {
        switch ($rmmType) {
            case RmmIntegration::RMM_TYPE_TACTICAL:
                return TacticalRmmService::class;

            default:
                throw new InvalidArgumentException(
                    "Unsupported RMM type: {$rmmType}"
                );
        }
    }

    /**
     * Get required configuration fields for an RMM type.
     */
    public function getRequiredFields(string $rmmType): array
    {
        $availableTypes = $this->getAvailableTypes();

        if (! isset($availableTypes[$rmmType])) {
            throw new InvalidArgumentException(
                "Unsupported RMM type: {$rmmType}"
            );
        }

        return $availableTypes[$rmmType]['requires'];
    }

    /**
     * Get supported features for an RMM type.
     */
    public function getSupportedFeatures(string $rmmType): array
    {
        $availableTypes = $this->getAvailableTypes();

        if (! isset($availableTypes[$rmmType])) {
            throw new InvalidArgumentException(
                "Unsupported RMM type: {$rmmType}"
            );
        }

        return $availableTypes[$rmmType]['features'];
    }

    /**
     * Create a service instance with validation.
     */
    public function makeWithValidation(RmmIntegration $integration): RmmServiceInterface
    {
        // Validate integration has required fields
        $this->validateIntegration($integration);

        // Create the service instance
        $service = $this->make($integration);

        // Test connection to ensure it's working
        $connectionTest = $service->testConnection();

        if (! $connectionTest['success']) {
            throw new InvalidArgumentException(
                "Failed to connect to RMM system: {$connectionTest['message']}"
            );
        }

        return $service;
    }

    /**
     * Validate that an integration has all required fields.
     */
    public function validateIntegration(RmmIntegration $integration): void
    {
        $this->validateRmmType($integration->rmm_type);

        $requiredFields = $this->getRequiredFields($integration->rmm_type);

        foreach ($requiredFields as $field => $description) {
            switch ($field) {
                case 'api_url':
                    if (empty($integration->api_url)) {
                        throw new InvalidArgumentException("API URL is required for {$integration->rmm_type}");
                    }
                    break;

                case 'api_key':
                    if (empty($integration->api_key)) {
                        throw new InvalidArgumentException("API Key is required for {$integration->rmm_type}");
                    }
                    break;
            }
        }
    }

    /**
     * Validate RMM type is supported.
     */
    public function validateRmmType(string $rmmType): void
    {
        $supportedTypes = array_keys($this->getAvailableTypes());

        if (! in_array($rmmType, $supportedTypes)) {
            throw new InvalidArgumentException(
                "Unsupported RMM type: {$rmmType}. Supported types: ".implode(', ', $supportedTypes)
            );
        }
    }

    /**
     * Create multiple service instances for bulk operations.
     */
    public function makeMultiple(array $integrations): array
    {
        $services = [];

        foreach ($integrations as $integration) {
            try {
                $services[$integration->id] = $this->make($integration);
            } catch (\Exception $e) {
                // Log error and continue with other integrations
                \Log::warning("Failed to create RMM service for integration {$integration->id}: {$e->getMessage()}");
            }
        }

        return $services;
    }

    /**
     * Get factory statistics.
     */
    public function getStatistics(): array
    {
        $types = $this->getAvailableTypes();

        return [
            'supported_types' => count($types),
            'available_types' => array_keys($types),
            'total_features' => array_sum(array_map(function ($type) {
                return count($type['features']);
            }, $types)),
        ];
    }
}
