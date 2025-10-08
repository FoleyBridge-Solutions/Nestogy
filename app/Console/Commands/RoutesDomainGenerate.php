<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DomainRouteManager;
use Illuminate\Console\Command;

class RoutesDomainGenerate extends Command
{
    protected $signature = 'routes:domain-generate {--force : Overwrite existing configuration}';

    protected $description = 'Generate domain route configuration by auto-discovering domains';

    public function handle(DomainRouteManager $routeManager): int
    {
        $configPath = config_path('domains.php');
        $force = $this->option('force');

        if (file_exists($configPath) && ! $force) {
            $this->error('Domain configuration already exists at: '.$configPath);
            $this->info('Use --force to overwrite the existing configuration.');

            return self::FAILURE;
        }

        $this->info('Scanning for domain routes...');

        $discovered = $routeManager->discoverDomains();

        if (empty($discovered)) {
            $this->warn('No domain routes found in app/Domains directory.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($discovered).' domains:');
        foreach (array_keys($discovered) as $domain) {
            $this->line("  • {$domain}");
        }

        return $this->generateConfiguration($routeManager, $configPath, $force);
    }

    protected function generateConfiguration(DomainRouteManager $routeManager, string $configPath, bool $force): int
    {
        if (! $force && ! $this->confirm('Generate configuration file?', true)) {
            $this->info('Configuration generation cancelled.');

            return self::SUCCESS;
        }

        if ($routeManager->generateConfig($force)) {
            $this->info("✓ Configuration generated: {$configPath}");
            $this->info('You can now customize the configuration as needed.');

            return self::SUCCESS;
        }

        $this->error('Failed to generate configuration file.');

        return self::FAILURE;
    }
}
