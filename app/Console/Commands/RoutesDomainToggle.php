<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RoutesDomainToggle extends Command
{
    protected $signature = 'routes:domain-toggle {domain} {--enable} {--disable}';
    
    protected $description = 'Enable or disable a specific domain\'s routes';

    public function handle(): int
    {
        $domain = $this->argument('domain');
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if ($enable && $disable) {
            $this->error('Cannot use both --enable and --disable options.');
            return self::FAILURE;
        }

        if (!$enable && !$disable) {
            $this->error('Must specify either --enable or --disable option.');
            return self::FAILURE;
        }

        $configPath = config_path('domains.php');
        
        if (!File::exists($configPath)) {
            $this->error('Domain configuration file not found. Run routes:domain-generate first.');
            return self::FAILURE;
        }

        $config = require $configPath;
        
        if (!isset($config[$domain])) {
            $this->error("Domain '{$domain}' not found in configuration.");
            $this->info('Available domains: ' . implode(', ', array_keys($config)));
            return self::FAILURE;
        }

        $newStatus = $enable ? true : false;
        $currentStatus = $config[$domain]['enabled'] ?? true;

        if ($currentStatus === $newStatus) {
            $status = $newStatus ? 'enabled' : 'disabled';
            $this->info("Domain '{$domain}' is already {$status}.");
            return self::SUCCESS;
        }

        $config[$domain]['enabled'] = $newStatus;

        $content = "<?php\n\n// Domain Route Configuration\n// Last modified: " . now()->toDateTimeString() . "\n\nreturn " . var_export($config, true) . ";\n";
        
        if (File::put($configPath, $content)) {
            $action = $newStatus ? 'enabled' : 'disabled';
            $this->info("✓ Domain '{$domain}' has been {$action}.");
            $this->warn('Run `php artisan route:clear` to apply changes.');
            return self::SUCCESS;
        } else {
            $this->error('Failed to update configuration file.');
            return self::FAILURE;
        }
    }
}