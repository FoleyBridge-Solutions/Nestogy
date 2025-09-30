<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DomainRouteManager;
use Illuminate\Console\Command;

class RoutesDomainList extends Command
{
    protected $signature = 'routes:domain-list {--enabled} {--disabled} {--validate}';
    
    protected $description = 'List all domain routes and their configuration';

    public function handle(DomainRouteManager $routeManager): int
    {
        if ($this->option('validate')) {
            return $this->validateDomains($routeManager);
        }

        $domains = $routeManager->getDomainConfig();
        $registered = $routeManager->getRegisteredDomains();

        $enabledOnly = $this->option('enabled');
        $disabledOnly = $this->option('disabled');

        $headers = ['Domain', 'Status', 'Middleware', 'Prefix', 'Priority', 'Description'];
        $rows = [];

        foreach ($domains as $name => $config) {
            $enabled = $config['enabled'] ?? true;
            
            if ($enabledOnly && !$enabled) continue;
            if ($disabledOnly && $enabled) continue;

            $status = $enabled ? 
                (isset($registered[$name]) ? '<info>✓ Registered</info>' : '<comment>⚠ Enabled but not registered</comment>') : 
                '<error>✗ Disabled</error>';

            // Handle different config structures
            $middleware = 'Defined in routes';
            if (isset($config['middleware'])) {
                $middleware = is_array($config['middleware']) ? 
                    implode(', ', $config['middleware']) : 
                    $config['middleware'];
            } elseif (($config['apply_grouping'] ?? true) === false) {
                $middleware = 'Self-managed';
            }

            $rows[] = [
                $name,
                $status,
                $middleware,
                $config['prefix'] ?? '—',
                $config['priority'] ?? 100,
                $config['description'] ?? '—'
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Total domains: ' . count($domains));
        $this->info('Registered: ' . count($registered));
        $this->info('Enabled: ' . count(array_filter($domains, fn($config) => $config['enabled'] ?? true)));

        return self::SUCCESS;
    }

    protected function validateDomains(DomainRouteManager $routeManager): int
    {
        $this->info('Validating domain route configuration...');
        
        $issues = $routeManager->validateConfig();
        
        if (empty($issues)) {
            $this->info('✓ All domain routes are valid!');
            return self::SUCCESS;
        }

        $this->error('Found ' . count($issues) . ' issues:');
        foreach ($issues as $issue) {
            $this->error("  • {$issue}");
        }

        return self::FAILURE;
    }
}