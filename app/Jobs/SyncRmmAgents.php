<?php

namespace App\Jobs;

use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Models\RmmClientMapping;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Models\Asset;
use App\Models\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncRmmAgents implements ShouldQueue
{
    use Queueable;

    protected RmmIntegration $integration;

    /**
     * Create a new job instance.
     */
    public function __construct(RmmIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Execute the job.
     */
    public function handle(RmmServiceFactory $factory): void
    {
        try {
            Log::info('Starting RMM agents sync', [
                'integration_id' => $this->integration->id,
                'company_id' => $this->integration->company_id,
            ]);

            // Create RMM service instance
            $service = $factory->make($this->integration);

            // Sync agents
            $result = $service->syncAgents();

            if (!$result['success']) {
                throw new \Exception('Failed to sync agents: ' . ($result['error'] ?? 'Unknown error'));
            }

            $agents = $result['agents'];
            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;

            Log::info('Processing agents from RMM', [
                'total_agents' => count($agents),
                'agents_sample' => array_slice($agents, 0, 2), // Log first 2 agents for debugging
                'integration_id' => $this->integration->id,
            ]);

            foreach ($agents as $agentData) {
                try {
                    Log::info('About to process agent', [
                        'agent_id' => $agentData['id'] ?? 'unknown',
                        'hostname' => $agentData['hostname'] ?? 'unknown',
                        'integration_id' => $this->integration->id,
                    ]);
                    $this->processAgent($agentData, $syncedCount, $createdCount, $updatedCount);
                    Log::info('Successfully processed agent', [
                        'agent_id' => $agentData['id'] ?? 'unknown',
                        'hostname' => $agentData['hostname'] ?? 'unknown',
                        'integration_id' => $this->integration->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to process agent', [
                        'agent_id' => $agentData['id'] ?? 'unknown',
                        'hostname' => $agentData['hostname'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'integration_id' => $this->integration->id,
                    ]);
                }
            }

            Log::info('RMM agents sync completed', [
                'integration_id' => $this->integration->id,
                'total_processed' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('RMM agents sync failed', [
                'integration_id' => $this->integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single agent and create/update corresponding asset.
     */
    protected function processAgent(array $agentData, int &$syncedCount, int &$createdCount, int &$updatedCount): void
    {
        $syncedCount++;

        // Log raw agent data for debugging
        Log::info('Processing agent with raw data', [
            'agent_id' => $agentData['id'],
            'hostname' => $agentData['hostname'],
            'client_name_from_data' => $agentData['client'] ?? 'NOT_SET',
            'monitoring_type' => $agentData['monitoring_type'] ?? 'NULL',
            'operating_system' => $agentData['operating_system'] ?? 'NULL',
            'raw_data_keys' => array_keys($agentData['raw_data'] ?? []),
            'raw_client_name' => $agentData['raw_data']['client_name'] ?? 'NOT_IN_RAW_DATA',
            'raw_site_name' => $agentData['raw_data']['site_name'] ?? 'NOT_IN_RAW_DATA',
            'integration_id' => $this->integration->id,
        ]);

        // Find or resolve client
        $client = $this->resolveClient($agentData['client']);
        if (!$client) {
            Log::warning('Could not resolve client for agent - skipping asset creation', [
                'agent_id' => $agentData['id'],
                'client_name' => $agentData['client'],
                'integration_id' => $this->integration->id,
                'hostname' => $agentData['hostname'] ?? 'unknown',
                'suggestion' => 'Create a client mapping in settings to resolve this RMM client to a Nestogy client',
            ]);
            return;
        }

        // Additional validation: Ensure resolved client belongs to correct company
        if ($client->company_id !== $this->integration->company_id) {
            Log::error('CRITICAL SECURITY VIOLATION: Resolved client belongs to wrong company', [
                'agent_id' => $agentData['id'],
                'hostname' => $agentData['hostname'] ?? 'unknown',
                'client_name' => $agentData['client'],
                'resolved_client_id' => $client->id,
                'resolved_client_name' => $client->name,
                'client_company_id' => $client->company_id,
                'integration_company_id' => $this->integration->company_id,
                'integration_id' => $this->integration->id,
                'action' => 'Asset creation prevented - data isolation maintained',
            ]);
            return;
        }

        Log::debug('Client validation passed', [
            'agent_id' => $agentData['id'],
            'hostname' => $agentData['hostname'] ?? 'unknown',
            'client_id' => $client->id,
            'client_name' => $client->name,
            'company_id' => $client->company_id,
        ]);


        // Find existing device mapping
        $deviceMapping = DeviceMapping::where([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => $agentData['id'],
        ])->first();

        // Find or create asset
        $asset = null;
        if ($deviceMapping && $deviceMapping->asset_id) {
            $asset = Asset::find($deviceMapping->asset_id);
        }

        if (!$asset) {
            // Create new asset
            
            // Ensure required fields have valid values
            $platform = trim($agentData['platform'] ?? 'unknown');
            $make = $this->mapAssetMake($platform);
            $type = $this->determineAssetTypeFromRmm($agentData);
            
            // Ensure make is never null or empty
            if (empty($make) || $make === '') {
                $make = 'Unknown';
            }
            
            Log::debug('Creating asset with data', [
                'hostname' => $agentData['hostname'],
                'platform' => $platform,
                'mapped_make' => $make,
                'determined_type' => $type,
                'monitoring_type' => $agentData['monitoring_type'] ?? 'null',
                'os' => $agentData['operating_system'] ?? 'null',
            ]);

            // Final safety check before asset creation
            if ($this->integration->company_id !== $client->company_id) {
                Log::error('PREVENTED CROSS-COMPANY ASSET CREATION', [
                    'agent_id' => $agentData['id'],
                    'hostname' => $agentData['hostname'],
                    'integration_company_id' => $this->integration->company_id,
                    'client_company_id' => $client->company_id,
                    'client_id' => $client->id,
                ]);
                return;
            }

            $assetData = [
                'company_id' => $this->integration->company_id, // Ensure this matches integration company
                'client_id' => $client->id,
                'name' => $agentData['hostname'],
                'type' => $type,
                'make' => $make, // Guaranteed to be a non-empty string
                'model' => $agentData['operating_system'] ?? 'Unknown',
                'serial' => $agentData['id'] ?? 'Unknown', // Use RMM agent ID as serial
                'ip' => $agentData['local_ip'] ?? null,
                'mac' => $agentData['mac_address'] ?? null,
                'status' => $agentData['online'] ? 'active' : 'inactive',
                'description' => "Synced from {$this->integration->getRmmTypeLabel()}",
                'notes' => json_encode([
                    'rmm_agent_id' => $agentData['id'],
                    'rmm_platform' => $platform,
                    'rmm_version' => $agentData['version'] ?? null,
                    'rmm_last_seen' => $agentData['last_seen'] ?? null,
                    'rmm_public_ip' => $agentData['public_ip'] ?? null,
                    'rmm_cpu_info' => $agentData['cpu_info'] ?? null,
                    'rmm_total_ram' => $agentData['total_ram'] ?? null,
                    'rmm_timezone' => $agentData['timezone'] ?? null,
                    'rmm_monitoring_type' => $agentData['monitoring_type'] ?? 'workstation',
                    'sync_timestamp' => now()->toISOString(),
                    'integration_id' => $this->integration->id,
                ]),
            ];

            Log::info('Creating asset with validated company scoping', [
                'asset_company_id' => $assetData['company_id'],
                'client_company_id' => $client->company_id,
                'integration_company_id' => $this->integration->company_id,
                'hostname' => $agentData['hostname'],
                'agent_id' => $agentData['id'],
            ]);

            $asset = Asset::create($assetData);

            $createdCount++;
            Log::info('Successfully created new asset from RMM agent', [
                'asset_id' => $asset->id,
                'asset_company_id' => $asset->company_id,
                'asset_client_id' => $asset->client_id,
                'agent_id' => $agentData['id'],
                'hostname' => $agentData['hostname'],
                'integration_id' => $this->integration->id,
                'verification' => 'Company scoping validated',
            ]);
        } else {
            // Update existing asset
            $asset->update([
                'name' => $agentData['hostname'],
                'type' => $this->determineAssetTypeFromRmm($agentData),
                'model' => $agentData['operating_system'] ?? $asset->model,
                'ip' => $agentData['local_ip'],
                'mac' => $agentData['mac_address'],
                'status' => $agentData['online'] ? 'active' : 'inactive',
                'notes' => json_encode(array_merge(
                    json_decode($asset->notes ?? '{}', true),
                    [
                        'rmm_last_seen' => $agentData['last_seen'],
                        'rmm_public_ip' => $agentData['public_ip'],
                        'rmm_version' => $agentData['version'],
                        'rmm_needs_reboot' => $agentData['needs_reboot'] ?? false,
                        'rmm_monitoring_type' => $agentData['monitoring_type'] ?? 'workstation',
                    ]
                )),
                'updated_at' => now(),
            ]);

            $updatedCount++;
            Log::debug('Updated asset from RMM agent', [
                'asset_id' => $asset->id,
                'agent_id' => $agentData['id'],
                'hostname' => $agentData['hostname'],
            ]);
        }

        // Create or update device mapping
        Log::debug('Creating device mapping with data', [
            'integration_id' => $this->integration->id,
            'rmm_device_id' => $agentData['id'],
            'client_id' => $client->id,
            'asset_id' => $asset->id,
            'device_name' => $agentData['hostname'],
            'sync_data_keys' => array_keys($agentData),
            'monitoring_type' => $agentData['monitoring_type'] ?? 'NULL',
        ]);

        DeviceMapping::updateOrCreate([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => $agentData['id'],
        ], [
            'client_id' => $client->id,
            'asset_id' => $asset->id,
            'device_name' => $agentData['hostname'],
            'sync_data' => $agentData,
            'last_updated' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Resolve client from RMM client name using proper mapping system.
     */
    protected function resolveClient(string $clientName): ?Client
    {
        Log::info('Starting client resolution', [
            'rmm_client_name' => $clientName,
            'integration_id' => $this->integration->id,
            'company_id' => $this->integration->company_id,
        ]);

        // Primary: Try to find client using RmmClientMapping
        $client = RmmClientMapping::findClientByRmmId($this->integration->id, $clientName);
        if ($client) {
            // Validate the client belongs to the correct company
            if ($client->company_id !== $this->integration->company_id) {
                Log::error('SECURITY ALERT: RMM client mapping returned client from wrong company', [
                    'rmm_client_name' => $clientName,
                    'expected_company_id' => $this->integration->company_id,
                    'actual_company_id' => $client->company_id,
                    'client_id' => $client->id,
                    'integration_id' => $this->integration->id,
                ]);
                return null;
            }

            Log::info('Client resolved via RMM mapping', [
                'rmm_client_name' => $clientName,
                'resolved_client_id' => $client->id,
                'resolved_client_name' => $client->name,
                'integration_id' => $this->integration->id,
            ]);
            return $client;
        }

        // Secondary: Try exact name matching for backward compatibility
        $client = Client::where('company_id', $this->integration->company_id)
                    ->where(function ($query) use ($clientName) {
                        $query->where('name', $clientName)
                              ->orWhere('company_name', $clientName);
                    })
                    ->first();
        
        if ($client) {
            Log::info('Client resolved via exact name match', [
                'rmm_client_name' => $clientName,
                'resolved_client_id' => $client->id,
                'resolved_client_name' => $client->name,
                'integration_id' => $this->integration->id,
            ]);
            return $client;
        }

        // No fallback to first client - this was the cause of the bug
        Log::warning('Cannot resolve RMM client - no mapping or exact match found', [
            'rmm_client_name' => $clientName,
            'integration_id' => $this->integration->id,
            'company_id' => $this->integration->company_id,
            'available_mappings_count' => RmmClientMapping::where('integration_id', $this->integration->id)
                ->where('is_active', true)
                ->count(),
            'message' => 'Asset creation will be skipped. Please create a client mapping for this RMM client.',
        ]);
        
        return null;
    }

    /**
     * Determine asset type from RMM data by checking multiple possible fields.
     */
    protected function determineAssetTypeFromRmm(array $agentData): string
    {
        // 1. Primary: Check monitoring_type field (this is the authoritative field from Tactical RMM)
        $monitoringType = $agentData['monitoring_type'] ?? null;
        if ($monitoringType === 'server') {
            return 'Server';
        }
        
        // 2. Check raw_data for monitoring_type in case it's nested
        $rawData = $agentData['raw_data'] ?? [];
        if (isset($rawData['monitoring_type']) && $rawData['monitoring_type'] === 'server') {
            return 'Server';
        }
        
        // 3. Check other server indicator fields in raw data
        $serverIndicatorFields = [
            'agent_type', 
            'machine_type',
            'server_type',
            'role',
            'computer_type'
        ];
        
        foreach ($serverIndicatorFields as $field) {
            if (isset($rawData[$field]) && 
                (strtolower($rawData[$field]) === 'server' || strtolower($rawData[$field]) === 'domain_controller')) {
                return 'Server';
            }
        }
        
        // 4. Check operating system for server indicators
        $os = $agentData['operating_system'] ?? '';
        $serverOsPatterns = [
            'windows server',
            'server 2019',
            'server 2022', 
            'server 2025',
            'server 2016',
            'server 2012',
            'server core',
            'domain controller',
            'ubuntu server',
            'centos',
            'rhel',
            'red hat',
            'windows srv',
            'srv '
        ];
        
        $osLower = strtolower($os);
        foreach ($serverOsPatterns as $pattern) {
            if (stripos($osLower, $pattern) !== false) {
                return 'Server';
            }
        }
        
        // 5. Check hostname patterns that typically indicate servers
        $hostname = $agentData['hostname'] ?? '';
        $serverHostnamePatterns = [
            'srv-',
            'server-',
            'dc-',
            'ads-',
            'rds-',
            'sql-',
            'db-',
            'mail-',
            'web-',
            'file-',
            'print-',
            'backup-'
        ];
        
        $hostnameLower = strtolower($hostname);
        foreach ($serverHostnamePatterns as $pattern) {
            if (strpos($hostnameLower, $pattern) === 0) {
                return 'Server';
            }
        }
        
        // 6. Final fallback - default to Desktop for workstations
        return 'Desktop';
    }

    /**
     * Map RMM platform to asset make.
     */
    protected function mapAssetMake(string $platform): string
    {
        $mapping = [
            'windows' => 'Microsoft',
            'linux' => 'Linux',
            'darwin' => 'Apple',
            'macos' => 'Apple',
        ];

        return $mapping[strtolower($platform)] ?? 'Unknown';
    }
}
