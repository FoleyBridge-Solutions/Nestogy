<?php

namespace App\Domains\Contract\Services;

use App\Contracts\BillingCalculatorInterface;
use App\Contracts\ContractFieldInterface;
use App\Contracts\ContractPluginInterface;
use App\Contracts\StatusTransitionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Contract Plugin Manager
 *
 * Manages registration, discovery, and instantiation of contract plugins
 */
class ContractPluginManager
{
    protected array $plugins = [];

    protected array $billingCalculators = [];

    protected array $statusTransitions = [];

    protected array $fieldTypes = [];

    protected array $instances = [];

    protected int $cacheTimeout = 3600;

    /**
     * Register a plugin
     */
    public function registerPlugin(string $type, string $identifier, string $class): void
    {
        if (! class_exists($class)) {
            throw new \InvalidArgumentException("Plugin class {$class} does not exist");
        }

        if (! $this->implementsInterface($class, ContractPluginInterface::class)) {
            throw new \InvalidArgumentException("Plugin class {$class} must implement ContractPluginInterface");
        }

        $this->plugins[$type][$identifier] = $class;

        // Register in specific type registries
        switch ($type) {
            case 'billing':
                if ($this->implementsInterface($class, BillingCalculatorInterface::class)) {
                    $this->billingCalculators[$identifier] = $class;
                }
                break;

            case 'status_transition':
                if ($this->implementsInterface($class, StatusTransitionInterface::class)) {
                    $this->statusTransitions[$identifier] = $class;
                }
                break;

            case 'field':
                if ($this->implementsInterface($class, ContractFieldInterface::class)) {
                    $this->fieldTypes[$identifier] = $class;
                }
                break;
        }

        // Clear relevant caches
        $this->clearPluginCache($type);
    }

    /**
     * Get billing calculator plugin
     */
    public function getBillingCalculator(string $identifier): BillingCalculatorInterface
    {
        if (! isset($this->billingCalculators[$identifier])) {
            throw new \InvalidArgumentException("Billing calculator '{$identifier}' not found");
        }

        return $this->getInstance('billing', $identifier);
    }

    /**
     * Get status transition plugin
     */
    public function getStatusTransitionHandler(string $identifier): StatusTransitionInterface
    {
        if (! isset($this->statusTransitions[$identifier])) {
            throw new \InvalidArgumentException("Status transition handler '{$identifier}' not found");
        }

        return $this->getInstance('status_transition', $identifier);
    }

    /**
     * Get field type plugin
     */
    public function getFieldRenderer(string $identifier): ContractFieldInterface
    {
        if (! isset($this->fieldTypes[$identifier])) {
            throw new \InvalidArgumentException("Field type '{$identifier}' not found");
        }

        return $this->getInstance('field', $identifier);
    }

    /**
     * Get plugin instance
     */
    protected function getInstance(string $type, string $identifier): ContractPluginInterface
    {
        $key = "{$type}.{$identifier}";

        if (! isset($this->instances[$key])) {
            $class = $this->plugins[$type][$identifier] ?? null;

            if (! $class) {
                throw new \InvalidArgumentException("Plugin '{$identifier}' of type '{$type}' not found");
            }

            $this->instances[$key] = app($class);
        }

        return $this->instances[$key];
    }

    /**
     * Get all plugins of a type
     */
    public function getPluginsByType(string $type): array
    {
        return $this->plugins[$type] ?? [];
    }

    /**
     * Get available billing calculators
     */
    public function getAvailableBillingCalculators(): array
    {
        $calculators = [];

        foreach ($this->billingCalculators as $identifier => $class) {
            try {
                $instance = $this->getInstance('billing', $identifier);
                $calculators[$identifier] = [
                    'name' => $instance->getName(),
                    'description' => $instance->getDescription(),
                    'version' => $instance->getVersion(),
                    'author' => $instance->getAuthor(),
                    'supported_models' => $instance->getSupportedModels(),
                    'required_fields' => $instance->getRequiredFields(),
                    'configuration_schema' => $instance->getConfigurationSchema(),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to load billing calculator '{$identifier}': ".$e->getMessage());
            }
        }

        return $calculators;
    }

    /**
     * Get available status transitions
     */
    public function getAvailableStatusTransitions(): array
    {
        $transitions = [];

        foreach ($this->statusTransitions as $identifier => $class) {
            try {
                $instance = $this->getInstance('status_transition', $identifier);
                $transitions[$identifier] = [
                    'name' => $instance->getName(),
                    'description' => $instance->getDescription(),
                    'version' => $instance->getVersion(),
                    'author' => $instance->getAuthor(),
                    'supports_bulk' => $instance->supportsBulkTransition(),
                    'configuration_schema' => $instance->getConfigurationSchema(),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to load status transition handler '{$identifier}': ".$e->getMessage());
            }
        }

        return $transitions;
    }

    /**
     * Get available field types
     */
    public function getAvailableFieldTypes(): array
    {
        $fieldTypes = [];

        foreach ($this->fieldTypes as $identifier => $class) {
            try {
                $instance = $this->getInstance('field', $identifier);
                $fieldTypes[$identifier] = [
                    'name' => $instance->getName(),
                    'description' => $instance->getDescription(),
                    'version' => $instance->getVersion(),
                    'author' => $instance->getAuthor(),
                    'field_type' => $instance->getFieldType(),
                    'searchable' => $instance->isSearchable(),
                    'sortable' => $instance->isSortable(),
                    'filterable' => $instance->isFilterable(),
                    'configuration_options' => $instance->getConfigurationOptions(),
                    'validation_types' => $instance->getSupportedValidationTypes(),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to load field type '{$identifier}': ".$e->getMessage());
            }
        }

        return $fieldTypes;
    }

    /**
     * Discover and auto-register plugins
     */
    public function discoverPlugins(): void
    {
        $this->discoverBillingCalculators();
        $this->discoverStatusTransitions();
        $this->discoverFieldTypes();
    }

    /**
     * Discover billing calculator plugins
     */
    protected function discoverBillingCalculators(): void
    {
        $pluginDir = app_path('Plugins/BillingCalculators');

        if (! is_dir($pluginDir)) {
            return;
        }

        $files = glob($pluginDir.'/*.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className) &&
                $this->implementsInterface($className, BillingCalculatorInterface::class)) {

                $identifier = strtolower(str_replace('Calculator', '', class_basename($className)));
                $this->registerPlugin('billing', $identifier, $className);
            }
        }
    }

    /**
     * Discover status transition plugins
     */
    protected function discoverStatusTransitions(): void
    {
        $pluginDir = app_path('Plugins/StatusTransitions');

        if (! is_dir($pluginDir)) {
            return;
        }

        $files = glob($pluginDir.'/*.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className) &&
                $this->implementsInterface($className, StatusTransitionInterface::class)) {

                $identifier = strtolower(str_replace('Transition', '', class_basename($className)));
                $this->registerPlugin('status_transition', $identifier, $className);
            }
        }
    }

    /**
     * Discover field type plugins
     */
    protected function discoverFieldTypes(): void
    {
        $pluginDir = app_path('Plugins/FieldTypes');

        if (! is_dir($pluginDir)) {
            return;
        }

        $files = glob($pluginDir.'/*.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className) &&
                $this->implementsInterface($className, ContractFieldInterface::class)) {

                $identifier = strtolower(str_replace('FieldType', '', class_basename($className)));
                $this->registerPlugin('field', $identifier, $className);
            }
        }
    }

    /**
     * Get class name from file
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches) &&
            preg_match('/class\s+([^\s]+)/', $content, $classMatches)) {

            return $namespaceMatches[1].'\\'.$classMatches[1];
        }

        return null;
    }

    /**
     * Check if class implements interface
     */
    protected function implementsInterface(string $class, string $interface): bool
    {
        return in_array($interface, class_implements($class) ?: []);
    }

    /**
     * Validate plugin
     */
    public function validatePlugin(string $class, array $config = []): array
    {
        $errors = [];

        if (! class_exists($class)) {
            $errors[] = "Plugin class '{$class}' does not exist";

            return $errors;
        }

        if (! $this->implementsInterface($class, ContractPluginInterface::class)) {
            $errors[] = "Plugin class '{$class}' must implement ContractPluginInterface";

            return $errors;
        }

        try {
            $instance = app($class);

            // Check compatibility
            if (! $instance->isCompatible()) {
                $errors[] = "Plugin '{$class}' is not compatible with current system";
            }

            // Validate configuration if provided
            if (! empty($config)) {
                $configErrors = $instance->validateConfiguration($config);
                $errors = array_merge($errors, $configErrors);
            }

            // Check dependencies
            $dependencies = $instance->getDependencies();
            foreach ($dependencies as $dependency) {
                if (! $this->isDependencyAvailable($dependency)) {
                    $errors[] = "Plugin '{$class}' requires dependency '{$dependency}' which is not available";
                }
            }

        } catch (\Exception $e) {
            $errors[] = "Failed to instantiate plugin '{$class}': ".$e->getMessage();
        }

        return $errors;
    }

    /**
     * Check if dependency is available
     */
    protected function isDependencyAvailable(string $dependency): bool
    {
        // Check if it's a class
        if (class_exists($dependency)) {
            return true;
        }

        // Check if it's a registered plugin
        foreach ($this->plugins as $type => $plugins) {
            if (isset($plugins[$dependency])) {
                return true;
            }
        }

        // Check if it's a Laravel service
        try {
            app($dependency);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get plugin information
     */
    public function getPluginInfo(string $type, string $identifier): array
    {
        $instance = $this->getInstance($type, $identifier);

        return [
            'name' => $instance->getName(),
            'description' => $instance->getDescription(),
            'version' => $instance->getVersion(),
            'author' => $instance->getAuthor(),
            'compatible' => $instance->isCompatible(),
            'dependencies' => $instance->getDependencies(),
            'required_permissions' => $instance->getRequiredPermissions(),
            'configuration_schema' => $instance->getConfigurationSchema(),
        ];
    }

    /**
     * Unregister plugin
     */
    public function unregisterPlugin(string $type, string $identifier): void
    {
        unset($this->plugins[$type][$identifier]);
        unset($this->instances["{$type}.{$identifier}"]);

        switch ($type) {
            case 'billing':
                unset($this->billingCalculators[$identifier]);
                break;
            case 'status_transition':
                unset($this->statusTransitions[$identifier]);
                break;
            case 'field':
                unset($this->fieldTypes[$identifier]);
                break;
        }

        $this->clearPluginCache($type);
    }

    /**
     * Clear plugin cache with error handling
     */
    protected function clearPluginCache(string $type): void
    {
        try {
            // Use cache tags for better management if Redis supports them
            if (config('cache.stores.redis.supports_tags', false)) {
                Cache::tags(['contract_plugins', "contract_plugins_{$type}"])->flush();
            } else {
                Cache::forget("contract_plugins_{$type}");
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the entire request
            \Illuminate\Support\Facades\Log::warning(
                "Failed to clear contract plugin cache for type {$type}: ".$e->getMessage(),
                ['type' => $type, 'exception' => $e]
            );
        }
    }

    /**
     * Get all registered plugins
     */
    public function getAllPlugins(): array
    {
        return [
            'billing_calculators' => $this->getAvailableBillingCalculators(),
            'status_transitions' => $this->getAvailableStatusTransitions(),
            'field_types' => $this->getAvailableFieldTypes(),
        ];
    }

    /**
     * Check if plugin is registered
     */
    public function isPluginRegistered(string $type, string $identifier): bool
    {
        return isset($this->plugins[$type][$identifier]);
    }

    /**
     * Get plugin statistics
     */
    public function getPluginStats(): array
    {
        return [
            'total_plugins' => array_sum(array_map('count', $this->plugins)),
            'billing_calculators' => count($this->billingCalculators),
            'status_transitions' => count($this->statusTransitions),
            'field_types' => count($this->fieldTypes),
            'loaded_instances' => count($this->instances),
        ];
    }
}
