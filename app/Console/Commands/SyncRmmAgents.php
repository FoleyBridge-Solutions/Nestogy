<?php

namespace App\Console\Commands;

use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Models\Client;
use Illuminate\Console\Command;

class SyncRmmAgents extends Command
{
    protected $signature = 'rmm:sync-agents {--client=} {--integration=}';

    protected $description = 'Sync RMM agents to specific client';

    public function handle()
    {
        $clientName = $this->option('client') ?? 'BurkhartPeterson';
        $integrationId = $this->option('integration') ?? 1;

        // Find the integration and client
        $integration = RmmIntegration::find($integrationId);
        $client = Client::where('company_id', $integration->company_id)
            ->where('name', $clientName)
            ->first();

        if (! $integration || ! $client) {
            $this->error('Integration or client not found');

            return 1;
        }

        $this->info("Syncing agents to client: {$client->name}");

        // Get agents from RMM
        $factory = new RmmServiceFactory;
        $rmmService = $factory->make($integration);
        $result = $rmmService->getAgents(['limit' => 50]);

        if (! $result['success']) {
            $this->error('Failed to get agents from RMM');

            return 1;
        }

        $count = 0;
        foreach ($result['data'] as $agent) {
            // Debug: show agent structure for first agent
            if ($count === 0) {
                $this->info('Agent data keys: '.implode(', ', array_keys($agent)));
            }

            $agentId = $agent['id'] ?? $agent['agent_id'] ?? $agent['pk'] ?? 'unknown';
            $hostname = $agent['hostname'] ?? $agent['name'] ?? 'Unknown Device';

            // Create device mapping
            $mapping = DeviceMapping::updateOrCreate(
                [
                    'integration_id' => $integration->id,
                    'rmm_device_id' => $agentId,
                ],
                [
                    'client_id' => $client->id,
                    'device_name' => $hostname,
                    'sync_data' => $agent,
                    'last_updated' => now(),
                    'is_active' => true,
                ]
            );
            $count++;
            $this->line("Mapped: {$hostname} (ID: {$agentId})");
        }

        $this->info("Created {$count} device mappings for {$client->name}");

        return 0;
    }
}
