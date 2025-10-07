<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class DomainRouteManager
{
    protected array $domainConfig = [];

    protected array $registeredDomains = [];

    protected string $configPath;

    public function __construct()
    {
        $this->configPath = config_path('domains.php');
        $this->loadDomainConfig();
    }

    /**
     * Load domain configuration from config file
     */
    protected function loadDomainConfig(): void
    {
        if (File::exists($this->configPath)) {
            $this->domainConfig = require $this->configPath;
        } else {
            // Use Laravel's config system as fallback
            $this->domainConfig = config('domains', []);
        }

        // If still empty, auto-discover
        if (empty($this->domainConfig)) {
            $this->domainConfig = $this->discoverDomains();
        }
    }

    /**
     * Auto-discover domains by scanning the Domains directory
     */
    public function discoverDomains(): array
    {
        $domainsPath = app_path('Domains');
        $discovered = [];

        if (! File::exists($domainsPath)) {
            return $discovered;
        }

        $directories = File::directories($domainsPath);

        foreach ($directories as $directory) {
            $domainName = basename($directory);
            $routeFile = "{$directory}/routes.php";

            if (File::exists($routeFile)) {
                $discovered[$domainName] = [
                    'enabled' => true,
                    'middleware' => ['web'],
                    'prefix' => null,
                    'name' => null,
                    'priority' => 100,
                    'description' => "Auto-discovered {$domainName} domain",
                    'route_file' => $routeFile,
                    'auto_discovered' => true,
                ];
            }
        }

        return $discovered;
    }

    /**
     * Register all domain routes based on configuration
     */
    public function registerDomainRoutes(): void
    {
        $domains = $this->getDomainConfig();

        // Sort by priority (lower number = higher priority)
        uasort($domains, fn ($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        foreach ($domains as $domainName => $config) {
            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            $this->registerDomainRoute($domainName, $config);
        }
    }

    /**
     * Register a single domain's routes
     */
    protected function registerDomainRoute(string $domainName, array $config): void
    {
        $routeFile = $config['route_file'] ?? app_path("Domains/{$domainName}/routes.php");

        if (! $this->isValidRouteFile($routeFile, $domainName)) {
            return;
        }

        $applyGrouping = ($config['apply_grouping'] ?? true);

        if ($this->shouldApplyGrouping($applyGrouping, $config)) {
            $this->registerWithGrouping($domainName, $config, $routeFile);
        } else {
            $this->registerWithoutGrouping($domainName, $config, $routeFile);
        }
    }

    /**
     * Check if route file is valid and exists
     */
    protected function isValidRouteFile(string $routeFile, string $domainName): bool
    {
        if (File::exists($routeFile) && filesize($routeFile) > 10) {
            return true;
        }

        if (app()->environment('local')) {
            logger()->info("Skipping domain '{$domainName}': route file missing or empty", [
                'file' => $routeFile,
                'exists' => File::exists($routeFile),
                'size' => File::exists($routeFile) ? filesize($routeFile) : 0,
            ]);
        }

        return false;
    }

    /**
     * Determine if grouping should be applied
     */
    protected function shouldApplyGrouping(bool $applyGrouping, array $config): bool
    {
        return $applyGrouping && (
            ! empty($config['middleware']) || 
            ! empty($config['prefix']) || 
            ! empty($config['name'])
        );
    }

    /**
     * Register routes with middleware/prefix/name grouping
     */
    protected function registerWithGrouping(string $domainName, array $config, string $routeFile): void
    {
        $route = Route::middleware($config['middleware'] ?? []);

        if ($prefix = $config['prefix'] ?? null) {
            $route = $route->prefix($prefix);
        }

        if ($name = $config['name'] ?? null) {
            $route = $route->name($name);
        }

        $this->executeRouteRegistration($domainName, $config, $routeFile, function () use ($route, $routeFile) {
            $route->group($routeFile);
        });
    }

    /**
     * Register routes without grouping
     */
    protected function registerWithoutGrouping(string $domainName, array $config, string $routeFile): void
    {
        $this->executeRouteRegistration($domainName, $config, $routeFile, function () use ($routeFile) {
            Route::group([], function () use ($routeFile) {
                require $routeFile;
            });
        });
    }

    /**
     * Execute route registration with error handling
     */
    protected function executeRouteRegistration(string $domainName, array $config, string $routeFile, callable $registration): void
    {
        try {
            $registration();
            $this->registeredDomains[$domainName] = $config;
        } catch (\Exception $e) {
            $this->handleRegistrationError($domainName, $routeFile, $e);
        }
    }

    /**
     * Handle route registration errors
     */
    protected function handleRegistrationError(string $domainName, string $routeFile, \Exception $e): void
    {
        if (app()->environment('local')) {
            throw new \RuntimeException(
                "Failed to register routes for domain '{$domainName}': {$e->getMessage()}",
                previous: $e
            );
        }

        logger()->error('Domain route registration failed', [
            'domain' => $domainName,
            'file' => $routeFile,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Get domain configuration (from config file or auto-discovered)
     */
    public function getDomainConfig(): array
    {
        return empty($this->domainConfig) ? $this->discoverDomains() : $this->domainConfig;
    }

    /**
     * Get list of registered domains by checking actual routes
     */
    public function getRegisteredDomains(): array
    {
        // Check which domains actually have routes registered
        $registered = [];
        $routes = Route::getRoutes();
        $domains = $this->getDomainConfig();

        foreach ($domains as $domain => $config) {
            $prefix = $config['prefix'] ?? '';
            $hasRoutes = false;

            // Check if any routes match this domain's configuration
            foreach ($routes as $route) {
                $uri = $route->uri();

                // Check if route matches domain's prefix
                if ($prefix && str_starts_with($uri, $prefix.'/')) {
                    $hasRoutes = true;
                    break;
                }

                // For domains without prefix, check common patterns
                if (! $prefix) {
                    $domainLower = strtolower($domain);
                    if (str_contains($uri, $domainLower)) {
                        $hasRoutes = true;
                        break;
                    }
                }
            }

            if ($hasRoutes) {
                $registered[$domain] = $config;
            }
        }

        return $registered;
    }

    /**
     * Generate domain configuration file
     */
    public function generateConfig(bool $overwrite = false): bool
    {
        if (File::exists($this->configPath) && ! $overwrite) {
            return false;
        }

        $discovered = $this->discoverDomains();
        $config = $this->buildConfigArray($discovered);

        // Ensure config directory exists
        File::ensureDirectoryExists(dirname($this->configPath));

        $content = "<?php\n\n// Auto-generated domain configuration\n// Generated at: ".now()->toDateTimeString()."\n\nreturn ".var_export($config, true).";\n";

        return File::put($this->configPath, $content) !== false;
    }

    /**
     * Build configuration array from discovered domains
     */
    protected function buildConfigArray(array $discovered): array
    {
        $config = [];

        foreach ($discovered as $domain => $settings) {
            $config[$domain] = [
                'enabled' => true,
                'middleware' => $settings['middleware'],
                'prefix' => $this->inferPrefix($domain),
                'name' => $this->inferName($domain),
                'priority' => $this->inferPriority($domain),
                'description' => $settings['description'] ?? "Routes for {$domain} domain",
                'route_file' => $settings['route_file'],
                'tags' => $this->inferTags($domain),
            ];
        }

        return $config;
    }

    /**
     * Infer appropriate prefix for a domain
     */
    protected function inferPrefix(string $domain): ?string
    {
        // Some domains might need special prefixes
        $prefixMap = [
            'Financial' => 'financial',
            'Integration' => 'api',
            'Email' => 'email',
            'Ticket' => 'tickets',
        ];

        return $prefixMap[$domain] ?? null;
    }

    /**
     * Infer appropriate name prefix for a domain
     */
    protected function inferName(string $domain): ?string
    {
        $nameMap = [
            'Financial' => 'financial.',
            'Integration' => 'api.',
            'Email' => 'email.',
            'Ticket' => 'tickets.',
        ];

        return $nameMap[$domain] ?? null;
    }

    /**
     * Infer priority based on domain type
     */
    protected function inferPriority(string $domain): int
    {
        $priorityMap = [
            'Security' => 10,    // Highest priority
            'Auth' => 20,
            'Financial' => 50,
            'Client' => 60,
            'Ticket' => 70,
            'Integration' => 80,
            'Asset' => 90,
            'Email' => 95,
        ];

        return $priorityMap[$domain] ?? 100;
    }

    /**
     * Infer tags for categorization
     */
    protected function inferTags(string $domain): array
    {
        $tagMap = [
            'Financial' => ['business', 'billing', 'finance'],
            'Client' => ['crm', 'customers'],
            'Ticket' => ['support', 'helpdesk'],
            'Asset' => ['inventory', 'equipment'],
            'Security' => ['auth', 'security'],
            'Integration' => ['api', 'external'],
            'Email' => ['communication'],
            'Project' => ['management'],
        ];

        return $tagMap[$domain] ?? ['general'];
    }

    /**
     * Validate route configuration
     */
    public function validateConfig(): array
    {
        $issues = [];
        $config = $this->getDomainConfig();

        foreach ($config as $domain => $settings) {
            $routeFile = $settings['route_file'] ?? app_path("Domains/{$domain}/routes.php");

            if (! File::exists($routeFile)) {
                $issues[] = "Route file missing for domain '{$domain}': {$routeFile}";

                continue;
            }

            if (filesize($routeFile) <= 10) {
                $issues[] = "Route file for domain '{$domain}' appears to be empty: {$routeFile}";
            }

            // Check for conflicting prefixes
            foreach ($config as $otherDomain => $otherSettings) {
                if ($domain !== $otherDomain &&
                    isset($settings['prefix'], $otherSettings['prefix']) &&
                    $settings['prefix'] === $otherSettings['prefix']) {
                    $issues[] = "Duplicate prefix '{$settings['prefix']}' found in domains '{$domain}' and '{$otherDomain}'";
                }
            }
        }

        return $issues;
    }
}
