<?php

namespace App\Contracts;

/**
 * Base interface for all contract plugins
 */
interface ContractPluginInterface
{
    /**
     * Get plugin name
     */
    public function getName(): string;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Get plugin description
     */
    public function getDescription(): string;

    /**
     * Get plugin author
     */
    public function getAuthor(): string;

    /**
     * Get plugin configuration schema
     */
    public function getConfigurationSchema(): array;

    /**
     * Validate plugin configuration
     */
    public function validateConfiguration(array $config): array;

    /**
     * Initialize plugin with configuration
     */
    public function initialize(array $config = []): void;

    /**
     * Check if plugin is compatible with current system
     */
    public function isCompatible(): bool;

    /**
     * Get required permissions for this plugin
     */
    public function getRequiredPermissions(): array;

    /**
     * Get plugin dependencies
     */
    public function getDependencies(): array;
}