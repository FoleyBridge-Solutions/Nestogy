<?php

namespace App\Console\Commands;

use App\Domains\Integration\Models\RmmIntegration;
use App\Jobs\SyncRmmAgents;
use Illuminate\Console\Command;

class DebugRmmSync extends Command
{
    protected $signature = 'debug:rmm-sync';
    protected $description = 'Debug RMM sync - manually trigger and show results';

    public function handle()
    {
        $this->info('=== RMM Sync Debug ===');
        $this->info('Current time: ' . now()->toDateTimeString());
        $this->newLine();

        $integrations = RmmIntegration::where('is_active', true)->get();
        
        $this->info("Found {$integrations->count()} active RMM integration(s)");
        $this->newLine();

        if ($integrations->isEmpty()) {
            $this->error('No active RMM integrations found!');
            return 1;
        }

        foreach ($integrations as $integration) {
            $this->info("Integration #{$integration->id}: {$integration->company->name}");
            $this->info("  Type: {$integration->rmm_type}");
            $this->info("  Company ID: {$integration->company_id}");
            
            try {
                $this->info("  Dispatching SyncRmmAgents job...");
                SyncRmmAgents::dispatch($integration);
                $this->info("  ✓ Job dispatched successfully");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to dispatch: {$e->getMessage()}");
            }
            
            $this->newLine();
        }

        $this->info('Waiting for jobs to process...');
        sleep(5);
        
        $this->info('Check storage/logs/laravel.log for sync results');
        $this->info('Done!');
        
        return 0;
    }
}
