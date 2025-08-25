<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use App\Models\Client;
use App\Domains\Asset\Services\AssetSupportService;

class TestAssetSupportEvaluation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:asset-support {--reset : Reset test data and start fresh}';

    /**
     * The console command description.
     */
    protected $description = 'Test the asset support evaluation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Asset Support Evaluation System');
        $this->info('=========================================');

        $assetSupportService = app(AssetSupportService::class);

        // Get a test client
        $client = Client::first();
        if (!$client) {
            $this->error('No clients found. Cannot run test.');
            return 1;
        }

        $this->info("Using client: {$client->name} (ID: {$client->id}, Company: {$client->company_id})");

        // Clean up any existing test data if reset flag is used
        if ($this->option('reset')) {
            $this->info('Cleaning up existing test data...');
            Contract::where('contract_number', 'LIKE', 'TEST-%')->delete();
        }

        // Create or get test contract
        $contract = Contract::where('client_id', $client->id)
            ->where('contract_number', 'LIKE', 'TEST-%')
            ->first();

        if (!$contract) {
            $this->info('Creating test contract...');
            $contract = Contract::create([
                'company_id' => $client->company_id,
                'client_id' => $client->id,
                'contract_number' => 'TEST-' . date('Ymd-His'),
                'contract_type' => 'support_agreement',
                'title' => 'Test MSP Support Contract',
                'status' => 'active',
                'start_date' => now(),
                'contract_value' => 5000.00,
            ]);
            $this->info("Created contract: {$contract->contract_number}");
        } else {
            $this->info("Using existing contract: {$contract->contract_number}");
        }

        // Create test infrastructure schedule
        $schedule = $contract->schedules()
            ->where('schedule_type', 'A')
            ->first();

        if (!$schedule) {
            $this->info('Creating infrastructure schedule (Schedule A)...');
            $schedule = ContractSchedule::create([
                'company_id' => $client->company_id,
                'contract_id' => $contract->id,
                'schedule_type' => 'A',
                'schedule_letter' => 'A',
                'title' => 'Infrastructure Support Schedule',
                'description' => 'Defines supported infrastructure and service levels',
                'content' => 'This schedule covers all server and network infrastructure.',
                'supported_asset_types' => ['Server', 'Router', 'Switch', 'Firewall', 'Desktop', 'Laptop'],
                'service_levels' => [
                    'Server' => ['level' => 'premium', 'response_time' => '4 hours'],
                    'Desktop' => ['level' => 'basic', 'response_time' => '24 hours'],
                    'Laptop' => ['level' => 'basic', 'response_time' => '24 hours'],
                    'Router' => ['level' => 'standard', 'response_time' => '8 hours'],
                    'Switch' => ['level' => 'standard', 'response_time' => '8 hours'],
                    'Firewall' => ['level' => 'premium', 'response_time' => '2 hours'],
                ],
                'auto_assign_assets' => true,
                'status' => 'active',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'effective_date' => now(),
            ]);
            $this->info("Created schedule: {$schedule->title}");
        } else {
            $this->info("Using existing schedule: {$schedule->title}");
        }

        $supportedTypes = implode(', ', $schedule->supported_asset_types ?? []);
        $this->info("Schedule supports: {$supportedTypes}");
        $this->info("Auto-assign enabled: " . ($schedule->auto_assign_assets ? 'YES' : 'NO'));
        $this->info("Schedule is effective: " . ($schedule->isEffective() ? 'YES' : 'NO'));

        // Test with assets from this client
        $assets = Asset::where('client_id', $client->id)->limit(5)->get();

        if ($assets->isEmpty()) {
            $this->warn('No assets found for this client.');
            // Test with any asset but update its client_id temporarily
            $asset = Asset::first();
            if ($asset) {
                $originalClientId = $asset->client_id;
                $asset->update(['client_id' => $client->id]);
                $this->info("Temporarily assigned asset {$asset->name} to test client");
                $assets = collect([$asset]);
            }
        }

        $this->info("\nTesting Asset Support Evaluation:");
        $this->info("=================================");

        foreach ($assets as $asset) {
            $this->info("\nAsset: {$asset->name} (Type: {$asset->type})");
            $this->info("Current support status: {$asset->support_status}");

            // Evaluate support
            $evaluation = $assetSupportService->evaluateAssetSupport($asset, true);

            $this->info("Evaluation results:");
            $this->info("- Previous status: {$evaluation['previous_status']}");
            $this->info("- New status: {$evaluation['new_status']}");
            $this->info("- Reason: {$evaluation['reason']}");

            if (isset($evaluation['supporting_schedule'])) {
                $this->info("- Supporting schedule: {$evaluation['supporting_schedule']['title']}");
                $this->info("- Support level: {$evaluation['support_level']}");
                $this->info("- Auto-assigned: " . ($evaluation['auto_assigned'] ? 'YES' : 'NO'));
            }

            if (!empty($evaluation['recommendations'])) {
                $this->info("- Recommendations:");
                foreach ($evaluation['recommendations'] as $rec) {
                    $this->info("  * {$rec['action']}");
                }
            }

            // Refresh and show final status
            $asset->refresh();
            $this->info("Final status: {$asset->support_status} ({$asset->support_status_display})");
            $this->info("Support level: " . ($asset->support_level_display ?? 'None'));
        }

        // Show summary statistics
        $this->info("\nSupport Status Summary:");
        $this->info("======================");

        $stats = $assetSupportService->getClientSupportStatistics($client->id);
        $this->info("Total assets: {$stats['total_assets']}");

        foreach ($stats['by_status'] as $status => $count) {
            $this->info("- {$status}: {$count}");
        }

        if (!empty($stats['by_level'])) {
            $this->info("Support levels:");
            foreach ($stats['by_level'] as $level => $count) {
                $this->info("- {$level}: {$count}");
            }
        }

        $this->info("Auto-assigned: {$stats['auto_assigned_percentage']}%");

        $this->info("\n✅ Asset support evaluation test completed successfully!");

        return 0;
    }
}
