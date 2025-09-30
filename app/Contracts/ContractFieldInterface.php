<?php

namespace App\Contracts;

/**
 * Interface for contract field type plugins
 */
interface ContractFieldInterface extends ContractPluginInterface
{
    /**
     * Validate field value
     */
    public function validate($value, array $config, array $context = []): \App\Support\ValidationResult;

    /**
     * Render field for forms
     */
    public function render(array $config, $currentValue = null, array $context = []): string;

    /**
     * Render field for display (read-only)
     */
    public function renderDisplay($value, array $config, array $context = []): string;

    /**
     * Process input value before storage
     */
    public function processValue($inputValue, array $config, array $context = []): mixed;

    /**
     * Format value for output
     */
    public function formatValue($value, array $config, array $context = []): mixed;

    /**
     * Get field type identifier
     */
    public function getFieldType(): string;

    /**
     * Get field configuration options
     */
    public function getConfigurationOptions(): array;

    /**
     * Get default field configuration
     */
    public function getDefaultConfiguration(): array;

    /**
     * Get validation rules schema
     */
    public function getValidationRulesSchema(): array;

    /**
     * Get supported validation types
     */
    public function getSupportedValidationTypes(): array;

    /**
     * Check if field supports search
     */
    public function isSearchable(): bool;

    /**
     * Check if field supports sorting
     */
    public function isSortable(): bool;

    /**
     * Check if field supports filtering
     */
    public function isFilterable(): bool;

    /**
     * Get search query modification
     */
    public function modifySearchQuery($query, string $field, $value, string $operator = '='): mixed;

    /**
     * Get filter options for UI
     */
    public function getFilterOptions(array $config): array;

    /**
     * Get JavaScript for client-side behavior
     */
    public function getClientScript(array $config): string;

    /**
     * Get CSS styles for field
     */
    public function getStyles(array $config): string;

    /**
     * Get field dependencies (other fields this field depends on)
     */
    public function getFieldDependencies(array $config): array;

    /**
     * Handle conditional field display
     */
    public function shouldDisplay(array $config, array $formData, array $context = []): bool;

    /**
     * Get field data for export
     */
    public function getExportValue($value, array $config): mixed;

    /**
     * Import value from external source
     */
    public function importValue($importValue, array $config): mixed;

    /**
     * Get field contribution to contract summary
     */
    public function getContractSummary($value, array $config): array;
}
