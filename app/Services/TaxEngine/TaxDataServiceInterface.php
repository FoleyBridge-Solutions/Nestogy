<?php

namespace App\Services\TaxEngine;

use Illuminate\Support\Collection;

/**
 * Generic Tax Data Service Interface
 *
 * Defines the contract for state/province-specific tax data services.
 * Each state can implement this interface to provide its own tax calculation logic.
 */
interface TaxDataServiceInterface
{
    /**
     * Get the state/province code this service handles (e.g., 'TX', 'CA', 'NY')
     */
    public function getStateCode(): string;

    /**
     * Get the full state/province name
     */
    public function getStateName(): string;

    /**
     * Check if this service is properly configured for the company
     */
    public function isConfigured(): bool;

    /**
     * Get configuration status with details
     */
    public function getConfigurationStatus(): array;

    /**
     * Download and process tax rate data from official source
     */
    public function downloadTaxRates(): array;

    /**
     * Download and process address jurisdiction data
     */
    public function downloadAddressData(?string $jurisdictionCode = null): array;

    /**
     * Update local database with tax rates
     */
    public function updateDatabaseWithRates(array $rates): array;

    /**
     * Get available data files from the official source
     */
    public function listAvailableFiles(): array;

    /**
     * Download a specific file
     */
    public function downloadFile(string $filePath): array;

    /**
     * Clear cached data
     */
    public function clearCache(): void;

    /**
     * Get service metadata
     */
    public function getServiceMetadata(): array;
}