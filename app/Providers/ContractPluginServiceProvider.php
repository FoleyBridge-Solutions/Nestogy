<?php

namespace App\Providers;

use App\Domains\Contract\Services\ContractPluginManager;
use App\Plugins\BillingCalculators\AssetBasedCalculator;
use App\Plugins\FieldTypes\AssetSelectorFieldType;
use App\Plugins\StatusTransitions\StandardStatusTransition;
use Illuminate\Support\ServiceProvider;

/**
 * Contract Plugin Service Provider
 *
 * Registers and manages contract plugins
 */
class ContractPluginServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register the plugin manager
        $this->app->singleton(ContractPluginManager::class, function ($app) {
            return new ContractPluginManager;
        });

        $this->app->alias(ContractPluginManager::class, 'contract.plugin.manager');
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Performance optimization: Only load contract plugins for contract-related routes
        if (! $this->shouldLoadContractPlugins()) {
            return;
        }

        // Log when contract plugins are being loaded for debugging
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::debug('Loading contract plugins for route: '.request()->path());
        }

        $pluginManager = $this->app->make(ContractPluginManager::class);

        // Register core plugins
        $this->registerCorePlugins($pluginManager);

        // Discover and register additional plugins
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            $pluginManager->discoverPlugins();
        }
    }

    /**
     * Determine if contract plugins should be loaded for the current request
     */
    protected function shouldLoadContractPlugins(): bool
    {
        // Always load in tests, but be more selective in console
        if ($this->app->runningUnitTests()) {
            return true;
        }

        // For console commands, only load if it's a contract-related command
        if ($this->app->runningInConsole()) {
            return $this->isContractRelatedConsoleCommand();
        }

        // Get the current request
        $request = request();
        if (! $request) {
            return false;
        }

        $path = $request->path();

        // Check if we're on a contract-related route
        $contractPaths = [
            'contracts',
            'api/contracts',
            'admin/contracts',
            'webhooks/contracts',
        ];

        foreach ($contractPaths as $contractPath) {
            if ($path === $contractPath || str_starts_with($path, $contractPath.'/')) {
                return true;
            }
        }

        // Check if the route name is contract-related
        $routeName = $request->route()?->getName();
        if ($routeName && str_starts_with($routeName, 'contracts.')) {
            return true;
        }

        // Check if we're in a contract domain context
        if ($request->has('contract_id') || $request->has('contract_type')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current console command is contract-related
     */
    protected function isContractRelatedConsoleCommand(): bool
    {
        $command = $_SERVER['argv'][1] ?? '';
        $contractCommands = ['contracts:', 'plugin:', 'migrate', 'seed', 'tinker'];

        foreach ($contractCommands as $contractCommand) {
            if (str_starts_with($command, $contractCommand)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register core plugins
     */
    protected function registerCorePlugins(ContractPluginManager $pluginManager): void
    {
        // Register billing calculators
        $pluginManager->registerPlugin('billing', 'asset_based', AssetBasedCalculator::class);
        $pluginManager->registerPlugin('billing', 'contact_based', ContactBasedCalculator::class);
        $pluginManager->registerPlugin('billing', 'usage_based', UsageBasedCalculator::class);
        $pluginManager->registerPlugin('billing', 'tiered_asset', TieredAssetCalculator::class);
        $pluginManager->registerPlugin('billing', 'custom_formula', CustomFormulaCalculator::class);

        // Register status transitions
        $pluginManager->registerPlugin('status_transition', 'standard', StandardStatusTransition::class);
        $pluginManager->registerPlugin('status_transition', 'approval_workflow', ApprovalWorkflowTransition::class);
        $pluginManager->registerPlugin('status_transition', 'automated', AutomatedStatusTransition::class);

        // Register field types
        $pluginManager->registerPlugin('field', 'asset_selector', AssetSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'client_selector', ClientSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'user_selector', UserSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'contact_selector', ContactSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'service_selector', ServiceSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'location_selector', LocationSelectorFieldType::class);
        $pluginManager->registerPlugin('field', 'billing_schedule', BillingScheduleFieldType::class);
        $pluginManager->registerPlugin('field', 'sla_terms', SlaTermsFieldType::class);
        $pluginManager->registerPlugin('field', 'pricing_matrix', PricingMatrixFieldType::class);
        $pluginManager->registerPlugin('field', 'conditional_logic', ConditionalLogicFieldType::class);
    }

    /**
     * Get the contract plugin manager with lazy loading
     * This ensures plugins are loaded when actually needed
     */
    public static function getPluginManager(): ?ContractPluginManager
    {
        try {
            $app = app();

            // Check if we should load plugins for current context
            $provider = $app->getProvider(static::class);
            if ($provider && ! $provider->shouldLoadContractPlugins()) {
                // Force load plugins now that they're actually needed
                $provider->boot();
            }

            return $app->make(ContractPluginManager::class);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Failed to get contract plugin manager: '.$e->getMessage()
            );

            return null;
        }
    }
}

/**
 * Placeholder classes for additional plugins
 * These would be implemented as separate plugin files
 */
class ContactBasedCalculator extends AssetBasedCalculator
{
    public function getName(): string
    {
        return 'Contact-Based Calculator';
    }

    public function getDescription(): string
    {
        return 'Calculates billing based on number of contacts/users';
    }

    // Implementation would focus on contact counting instead of assets
}

class UsageBasedCalculator extends AssetBasedCalculator
{
    public function getName(): string
    {
        return 'Usage-Based Calculator';
    }

    public function getDescription(): string
    {
        return 'Calculates billing based on usage metrics and consumption';
    }

    // Implementation would integrate with usage tracking systems
}

class TieredAssetCalculator extends AssetBasedCalculator
{
    public function getName(): string
    {
        return 'Tiered Asset Calculator';
    }

    public function getDescription(): string
    {
        return 'Calculates billing using tiered pricing based on asset count ranges';
    }

    // Implementation would focus on complex tiered pricing structures
}

class CustomFormulaCalculator extends AssetBasedCalculator
{
    public function getName(): string
    {
        return 'Custom Formula Calculator';
    }

    public function getDescription(): string
    {
        return 'Calculates billing using custom mathematical formulas';
    }

    // Implementation would allow custom formula definition and execution
}

class ApprovalWorkflowTransition extends StandardStatusTransition
{
    public function getName(): string
    {
        return 'Approval Workflow Transition';
    }

    public function getDescription(): string
    {
        return 'Status transitions with multi-step approval workflows';
    }

    // Implementation would add complex approval routing
}

class AutomatedStatusTransition extends StandardStatusTransition
{
    public function getName(): string
    {
        return 'Automated Status Transition';
    }

    public function getDescription(): string
    {
        return 'Automatic status transitions based on conditions and triggers';
    }

    // Implementation would add time-based and condition-based automatic transitions
}

// Field type placeholder classes
class ClientSelectorFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Client Selector';
    }

    public function getFieldType(): string
    {
        return 'client_selector';
    }
}

class UserSelectorFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'User Selector';
    }

    public function getFieldType(): string
    {
        return 'user_selector';
    }
}

class ContactSelectorFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Contact Selector';
    }

    public function getFieldType(): string
    {
        return 'contact_selector';
    }
}

class ServiceSelectorFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Service Selector';
    }

    public function getFieldType(): string
    {
        return 'service_selector';
    }
}

class LocationSelectorFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Location Selector';
    }

    public function getFieldType(): string
    {
        return 'location_selector';
    }
}

class BillingScheduleFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Billing Schedule';
    }

    public function getFieldType(): string
    {
        return 'billing_schedule';
    }
}

class SlaTermsFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'SLA Terms';
    }

    public function getFieldType(): string
    {
        return 'sla_terms';
    }
}

class PricingMatrixFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Pricing Matrix';
    }

    public function getFieldType(): string
    {
        return 'pricing_matrix';
    }
}

class ConditionalLogicFieldType extends AssetSelectorFieldType
{
    public function getName(): string
    {
        return 'Conditional Logic';
    }

    public function getFieldType(): string
    {
        return 'conditional_logic';
    }
}
