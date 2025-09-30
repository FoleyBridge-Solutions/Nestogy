<?php

namespace App\Console\Commands;

use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\AssetSyncService;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * RMM Enhanced Setup Command
 *
 * Helps set up and test the enhanced RMM capabilities.
 */
class RmmEnhancedSetup extends Command
{
    private const DEFAULT_BATCH_SIZE = 100;

    protected $signature = 'rmm:enhanced-setup
                            {action : Action to perform (test|sync|status|capabilities)}
                            {--integration= : Specific integration ID to work with}
                            {--asset= : Specific asset ID to work with}
                            {--company= : Company ID to work with}';

    protected $description = 'Set up and test enhanced RMM capabilities';

    protected AssetSyncService $syncService;

    protected RmmServiceFactory $rmmFactory;

    public function __construct(AssetSyncService $syncService, RmmServiceFactory $rmmFactory)
    {
        parent::__construct();
        $this->syncService = $syncService;
        $this->rmmFactory = $rmmFactory;
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'test' => $this->testRmmConnections(),
            'sync' => $this->syncAssets(),
            'status' => $this->showStatus(),
            'capabilities' => $this->showCapabilities(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Test RMM connections and capabilities.
     */
    protected function testRmmConnections(): int
    {
        $this->info('Testing RMM connections and enhanced capabilities...');

        $query = RmmIntegration::where('is_active', true);

        if ($integrationId = $this->option('integration')) {
            $query->where('id', $integrationId);
        }

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->error('No active RMM integrations found.');

            return 1;
        }

        foreach ($integrations as $integration) {
            $this->line("\n=== Testing {$integration->name} (ID: {$integration->id}) ===");

            try {
                $rmmService = $this->rmmFactory->make($integration);

                // Test basic connection
                $this->info('Testing basic connection...');
                $connectionTest = $rmmService->testConnection();

                if ($connectionTest['success']) {
                    $this->info('✓ Connection successful');

                    // Test enhanced capabilities
                    $this->testEnhancedCapabilities($rmmService, $integration);
                } else {
                    $this->error('✗ Connection failed: '.($connectionTest['message'] ?? 'Unknown error'));
                }

            } catch (\Exception $e) {
                $this->error('✗ Integration test failed: '.$e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Test enhanced RMM capabilities.
     */
    protected function testEnhancedCapabilities($rmmService, $integration): void
    {
        $this->info('Testing enhanced capabilities...');

        // Get agents
        $this->info('- Testing agent retrieval...');
        $agentsResult = $rmmService->getAgents(['limit' => 1]);

        if ($agentsResult['success'] && ! empty($agentsResult['data'])) {
            $this->info('✓ Agent retrieval working');

            $testAgent = $agentsResult['data'][0];
            $agentId = $testAgent['id'];

            // Test comprehensive inventory
            $this->info('- Testing comprehensive inventory...');
            $inventoryResult = $rmmService->getFullDeviceInventory($agentId);

            if ($inventoryResult['success']) {
                $this->info('✓ Comprehensive inventory working');
                $this->line('  Data sections: '.implode(', ', array_keys($inventoryResult['data'])));
            } else {
                $this->warn('⚠ Comprehensive inventory has issues');
            }

            // Test hardware info
            $this->info('- Testing hardware information...');
            $hardwareResult = $rmmService->getDeviceHardware($agentId);

            if ($hardwareResult['success']) {
                $this->info('✓ Hardware information working');
            } else {
                $this->warn('⚠ Hardware information has issues');
            }

            // Test performance metrics
            $this->info('- Testing performance metrics...');
            $perfResult = $rmmService->getDevicePerformance($agentId);

            if ($perfResult['success']) {
                $this->info('✓ Performance metrics working');
            } else {
                $this->warn('⚠ Performance metrics have issues');
            }

            // Test services
            $this->info('- Testing service management...');
            $servicesResult = $rmmService->getAgentServices($agentId);

            if ($servicesResult['success']) {
                $this->info('✓ Service management working');
                $this->line('  Services found: '.count($servicesResult['data']));
            } else {
                $this->warn('⚠ Service management has issues');
            }

        } else {
            $this->warn('⚠ No agents found to test advanced capabilities');
        }
    }

    /**
     * Sync assets using enhanced sync service.
     */
    protected function syncAssets(): int
    {
        $this->info('Starting enhanced asset synchronization...');

        if ($companyId = $this->option('company')) {
            $result = $this->syncService->syncAllAssetsForCompany($companyId);

            $this->info("Sync completed for company {$companyId}:");
            $this->line("- Total synced: {$result['total_synced']}");
            $this->line("- Total errors: {$result['total_errors']}");

            foreach ($result['integration_results'] as $integration => $intResult) {
                $this->line("  {$integration}: {$intResult['synced_count']} synced, {$intResult['error_count']} errors");
            }

        } elseif ($integrationId = $this->option('integration')) {
            $integration = RmmIntegration::findOrFail($integrationId);
            $result = $this->syncService->syncAssetsFromIntegration($integration);

            $this->info("Sync completed for integration {$integration->name}:");
            $this->line("- Synced: {$result['synced_count']}");
            $this->line("- Errors: {$result['error_count']}");

        } else {
            $this->error('Please specify either --company or --integration option');

            return 1;
        }

        return 0;
    }

    /**
     * Show status of RMM enhanced features.
     */
    protected function showStatus(): int
    {
        $this->info('RMM Enhanced Features Status');
        $this->line('================================');

        // Show integrations
        $integrations = RmmIntegration::with('company')->get();
        $this->line("\nActive Integrations:");

        foreach ($integrations as $integration) {
            $status = $integration->is_active ? '✓' : '✗';
            $this->line("  {$status} {$integration->name} ({$integration->provider}) - Company: {$integration->company->name}");
        }

        // Show assets with RMM connections
        $assetsWithRmm = Asset::whereHas('deviceMappings', function ($query) {
            $query->active();
        })->count();

        $totalAssets = Asset::count();

        $this->line("\nAsset Statistics:");
        $this->line("  Total assets: {$totalAssets}");
        $this->line("  Assets with RMM connections: {$assetsWithRmm}");
        $this->line('  Coverage: '.round(($assetsWithRmm / max($totalAssets, 1)) * 100, 1).'%');

        // Show device mappings
        $mappings = DB::table('device_mappings')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN asset_id IS NOT NULL THEN 1 END) as mapped,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active
            ')
            ->first();

        $this->line("\nDevice Mappings:");
        $this->line("  Total mappings: {$mappings->total}");
        $this->line("  Mapped to assets: {$mappings->mapped}");
        $this->line("  Active mappings: {$mappings->active}");

        return 0;
    }

    /**
     * Show enhanced RMM capabilities available.
     */
    protected function showCapabilities(): int
    {
        $this->info('Enhanced RMM Capabilities');
        $this->line('============================');

        $capabilities = [
            'Data Collection' => [
                'Comprehensive hardware inventory',
                'Real-time performance metrics',
                'Network configuration details',
                'Software inventory with versions',
                'Windows services status',
                'Running processes information',
                'Windows updates status',
                'Security information',
            ],
            'Remote Control' => [
                'Execute PowerShell/CMD commands',
                'Run custom scripts',
                'Manage Windows services (start/stop/restart)',
                'Kill processes remotely',
                'Install Windows updates',
                'Reboot devices with options',
                'Wake on LAN support',
            ],
            'System Administration' => [
                'Windows update management',
                'Service control and monitoring',
                'Process management',
                'System information gathering',
                'Performance monitoring',
                'Event log access',
                'File system operations',
            ],
            'Integration Features' => [
                'Bidirectional synchronization',
                'Real-time status updates',
                'Comprehensive inventory sync',
                'Asset lifecycle management',
                'Automated device mapping',
                'Webhook support for alerts',
                'Audit logging of all actions',
            ],
        ];

        foreach ($capabilities as $category => $features) {
            $this->line("\n{$category}:");
            foreach ($features as $feature) {
                $this->line("  ✓ {$feature}");
            }
        }

        $this->line("\nSupported RMM Systems:");
        $this->line('  ✓ TacticalRMM (fully implemented)');
        $this->line('  ⚠ ConnectWise Automate (legacy support)');
        $this->line('  ⚠ Datto RMM (legacy support)');
        $this->line('  ⚠ NinjaOne (legacy support)');

        return 0;
    }
}
