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

        $headers = ['Domain', 'Status', 'Middleware', 'Prefix', 'Priority', 'Description'];
        $rows = $this->buildDomainRows($domains, $registered);

        $this->table($headers, $rows);

        $this->displaySummary($domains, $registered);

        return self::SUCCESS;
    }

    protected function buildDomainRows(array $domains, array $registered): array
    {
        $rows = [];

        foreach ($domains as $name => $config) {
            if ($this->shouldSkipDomain($config)) {
                continue;
            }

            $rows[] = $this->buildDomainRow($name, $config, $registered);
        }

        return $rows;
    }

    protected function shouldSkipDomain(array $config): bool
    {
        $enabled = $config['enabled'] ?? true;
        $enabledOnly = $this->option('enabled');
        $disabledOnly = $this->option('disabled');

        if ($enabledOnly && ! $enabled) {
            return true;
        }

        if ($disabledOnly && $enabled) {
            return true;
        }

        return false;
    }

    protected function buildDomainRow(string $name, array $config, array $registered): array
    {
        $enabled = $config['enabled'] ?? true;

        return [
            $name,
            $this->getDomainStatus($name, $enabled, $registered),
            $this->getDomainMiddleware($config),
            $config['prefix'] ?? '—',
            $config['priority'] ?? 100,
            $config['description'] ?? '—',
        ];
    }

    protected function getDomainStatus(string $name, bool $enabled, array $registered): string
    {
        if (! $enabled) {
            return '<error>✗ Disabled</error>';
        }

        if (isset($registered[$name])) {
            return '<info>✓ Registered</info>';
        }

        return '<comment>⚠ Enabled but not registered</comment>';
    }

    protected function getDomainMiddleware(array $config): string
    {
        if (isset($config['middleware'])) {
            return is_array($config['middleware']) ?
                implode(', ', $config['middleware']) :
                $config['middleware'];
        }

        if (($config['apply_grouping'] ?? true) === false) {
            return 'Self-managed';
        }

        return 'Defined in routes';
    }

    protected function displaySummary(array $domains, array $registered): void
    {
        $this->newLine();
        $this->info('Total domains: '.count($domains));
        $this->info('Registered: '.count($registered));
        $this->info('Enabled: '.count(array_filter($domains, fn ($config) => $config['enabled'] ?? true)));
    }

    protected function validateDomains(DomainRouteManager $routeManager): int
    {
        $this->info('Validating domain route configuration...');

        $issues = $routeManager->validateConfig();

        if (empty($issues)) {
            $this->info('✓ All domain routes are valid!');

            return self::SUCCESS;
        }

        $this->error('Found '.count($issues).' issues:');
        foreach ($issues as $issue) {
            $this->error("  • {$issue}");
        }

        return self::FAILURE;
    }
}
