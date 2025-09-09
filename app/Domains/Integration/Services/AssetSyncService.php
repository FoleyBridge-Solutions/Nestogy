<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceInterface;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Models\Asset;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Asset Sync Service
 * 
 * Handles bidirectional synchronization between Nestogy assets and RMM systems.
 * Provides comprehensive device management without needing to access RMM directly.
 */
class AssetSyncService
{
    protected RmmServiceFactory $rmmFactory;
    
    public function __construct(RmmServiceFactory $rmmFactory)
    {
        $this->rmmFactory = $rmmFactory;
    }

    /**
     * Sync all assets for a company from their RMM integrations.
     */
    public function syncAllAssetsForCompany(int $companyId): array
    {
        $integrations = RmmIntegration::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $results = [];
        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($integrations as $integration) {
            $result = $this->syncAssetsFromIntegration($integration);
            $results[$integration->name] = $result;
            $totalSynced += $result['synced_count'];
            $totalErrors += $result['error_count'];
        }

        return [
            'success' => $totalErrors === 0,
            'total_synced' => $totalSynced,
            'total_errors' => $totalErrors,
            'integration_results' => $results,
        ];
    }

    /**
     * Sync assets from a specific RMM integration.
     */
    public function syncAssetsFromIntegration(RmmIntegration $integration): array
    {
        try {
            $rmmService = $this->rmmFactory->make($integration);
            $agentsResponse = $rmmService->getAgents();

            if (!$agentsResponse['success']) {
                Log::error('Failed to get agents from RMM', [
                    'integration_id' => $integration->id,
                    'error' => $agentsResponse['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'synced_count' => 0,
                    'error_count' => 1,
                    'error' => $agentsResponse['error'] ?? 'Failed to fetch agents',
                ];
            }

            $syncedCount = 0;
            $errorCount = 0;

            foreach ($agentsResponse['data'] as $agentData) {
                try {
                    $this->syncSingleAsset($integration, $rmmService, $agentData);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::warning('Failed to sync single asset', [
                        'integration_id' => $integration->id,
                        'agent_id' => $agentData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'success' => $errorCount === 0,
                'synced_count' => $syncedCount,
                'error_count' => $errorCount,
            ];

        } catch (\Exception $e) {
            Log::error('Asset sync failed for integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'synced_count' => 0,
                'error_count' => 1,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync a single asset with comprehensive data collection.
     */
    public function syncSingleAsset(RmmIntegration $integration, RmmServiceInterface $rmmService, array $agentData): Asset
    {
        return DB::transaction(function () use ($integration, $rmmService, $agentData) {
            $agentId = $agentData['id'];
            
            // Get comprehensive device inventory
            $inventoryResult = $rmmService->getFullDeviceInventory($agentId);
            
            if (!$inventoryResult['success']) {
                throw new \Exception("Failed to get device inventory: " . ($inventoryResult['errors']['agent'] ?? 'Unknown error'));
            }
            
            $inventory = $inventoryResult['data'];
            
            // Find or create device mapping
            $mapping = $this->findOrCreateDeviceMapping($integration, $agentData);
            
            // Find or create asset
            $asset = $this->findOrCreateAsset($mapping, $inventory);
            
            // Update asset with comprehensive data
            $this->updateAssetFromInventory($asset, $inventory);
            
            // Update device mapping with latest sync data
            $mapping->updateSyncData([
                'last_full_sync' => now()->toISOString(),
                'inventory_data' => $inventory,
                'sync_source' => 'comprehensive_sync',
            ]);
            
            Log::info('Asset synced successfully', [
                'asset_id' => $asset->id,
                'rmm_agent_id' => $agentId,
                'integration_id' => $integration->id,
            ]);
            
            return $asset;
        });
    }

    /**
     * Push asset changes from Nestogy to RMM system.
     */
    public function pushAssetToRmm(Asset $asset): array
    {
        // Find device mappings for this asset
        $mappings = DeviceMapping::where('asset_id', $asset->id)->get();
        
        if ($mappings->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No RMM mappings found for this asset',
            ];
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($mappings as $mapping) {
            try {
                $integration = $mapping->integration;
                $rmmService = $this->rmmFactory->make($integration);
                
                $result = $this->updateRmmDevice($rmmService, $mapping, $asset);
                $results[$integration->name] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                $results[$mapping->integration->name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $errorCount === 0,
            'updated_integrations' => $successCount,
            'failed_integrations' => $errorCount,
            'results' => $results,
        ];
    }

    /**
     * Execute remote command on device through RMM.
     */
    public function executeRemoteCommand(Asset $asset, string $command, array $options = []): array
    {
        $mapping = DeviceMapping::where('asset_id', $asset->id)->first();
        
        if (!$mapping) {
            return [
                'success' => false,
                'error' => 'No RMM mapping found for this asset',
            ];
        }

        try {
            $rmmService = $this->rmmFactory->create($mapping->integration);
            
            $result = $rmmService->runCommand($mapping->rmm_device_id, $command, $options);
            
            // Log the command execution
            Log::info('Remote command executed', [
                'asset_id' => $asset->id,
                'command' => $command,
                'success' => $result['success'],
                'task_id' => $result['task_id'] ?? null,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Remote command execution failed', [
                'asset_id' => $asset->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Manage Windows services on device.
     */
    public function manageService(Asset $asset, string $serviceName, string $action): array
    {
        $mapping = DeviceMapping::where('asset_id', $asset->id)->first();
        
        if (!$mapping) {
            return [
                'success' => false,
                'error' => 'No RMM mapping found for this asset',
            ];
        }

        try {
            $rmmService = $this->rmmFactory->create($mapping->integration);
            $agentId = $mapping->rmm_device_id;
            
            $result = match ($action) {
                'start' => $rmmService->startService($agentId, $serviceName),
                'stop' => $rmmService->stopService($agentId, $serviceName),
                'restart' => $rmmService->restartService($agentId, $serviceName),
                default => throw new \InvalidArgumentException("Invalid service action: {$action}"),
            };
            
            // Log the service management action
            Log::info('Service management executed', [
                'asset_id' => $asset->id,
                'service' => $serviceName,
                'action' => $action,
                'success' => $result['success'],
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Service management failed', [
                'asset_id' => $asset->id,
                'service' => $serviceName,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Install Windows updates on device.
     */
    public function installWindowsUpdates(Asset $asset, array $updateIds = []): array
    {
        $mapping = DeviceMapping::where('asset_id', $asset->id)->first();
        
        if (!$mapping) {
            return [
                'success' => false,
                'error' => 'No RMM mapping found for this asset',
            ];
        }

        try {
            $rmmService = $this->rmmFactory->create($mapping->integration);
            
            $result = $rmmService->installUpdates($mapping->rmm_device_id, $updateIds);
            
            // Log the update installation
            Log::info('Windows updates installation initiated', [
                'asset_id' => $asset->id,
                'update_count' => count($updateIds),
                'success' => $result['success'],
                'task_id' => $result['task_id'] ?? null,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Windows updates installation failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reboot device through RMM.
     */
    public function rebootDevice(Asset $asset, array $options = []): array
    {
        $mapping = DeviceMapping::where('asset_id', $asset->id)->first();
        
        if (!$mapping) {
            return [
                'success' => false,
                'error' => 'No RMM mapping found for this asset',
            ];
        }

        try {
            $rmmService = $this->rmmFactory->create($mapping->integration);
            
            $result = $rmmService->rebootAgent($mapping->rmm_device_id, $options);
            
            // Log the reboot action
            Log::info('Device reboot initiated', [
                'asset_id' => $asset->id,
                'options' => $options,
                'success' => $result['success'],
                'task_id' => $result['task_id'] ?? null,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Device reboot failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get real-time device status and performance.
     */
    public function getDeviceStatus(Asset $asset): array
    {
        $mapping = DeviceMapping::where('asset_id', $asset->id)->first();
        
        if (!$mapping) {
            return [
                'success' => false,
                'error' => 'No RMM mapping found for this asset',
                'data' => null,
            ];
        }

        try {
            $rmmService = $this->rmmFactory->create($mapping->integration);
            $agentId = $mapping->rmm_device_id;
            
            // Get comprehensive current status
            $status = [];
            
            // Basic agent info
            $agentInfo = $rmmService->getAgent($agentId);
            if ($agentInfo['success']) {
                $status['agent'] = $agentInfo['data'];
            }
            
            // Performance metrics
            $performance = $rmmService->getDevicePerformance($agentId);
            if ($performance['success']) {
                $status['performance'] = $performance['data'];
            }
            
            // Current processes
            $processes = $rmmService->getDeviceProcesses($agentId);
            if ($processes['success']) {
                $status['processes'] = array_slice($processes['data'], 0, 10); // Top 10 processes
            }
            
            // Connectivity test
            $ping = $rmmService->pingDevice($agentId);
            if ($ping['success']) {
                $status['connectivity'] = $ping['data'];
            }
            
            return [
                'success' => true,
                'data' => $status,
                'last_updated' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get device status', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Find or create device mapping.
     */
    protected function findOrCreateDeviceMapping(RmmIntegration $integration, array $agentData): DeviceMapping
    {
        $deviceId = $agentData['id'];
        $deviceName = $agentData['hostname'] ?? 'Unknown Device';
        
        // Try to resolve client
        $clientId = $this->resolveClientId($integration, $agentData);
        
        return DeviceMapping::updateOrCreateMapping(
            $integration->id,
            $deviceId,
            $clientId,
            $deviceName,
            [
                'last_agent_sync' => now()->toISOString(),
                'agent_data' => $agentData,
            ]
        );
    }

    /**
     * Find or create asset based on device mapping.
     */
    protected function findOrCreateAsset(DeviceMapping $mapping, array $inventory): Asset
    {
        if ($mapping->asset_id) {
            return Asset::find($mapping->asset_id);
        }

        $agentData = $inventory['agent'] ?? [];
        
        // Create new asset
        $asset = Asset::create([
            'company_id' => $mapping->integration->company_id,
            'client_id' => $mapping->client_id,
            'name' => $agentData['hostname'] ?? 'Unknown Device',
            'type' => $this->determineAssetType($agentData),
            'description' => 'Auto-created from RMM sync',
            'os' => $agentData['operating_system'] ?? null,
            'ip' => $agentData['local_ip'] ?? null,
            'nat_ip' => $agentData['public_ip'] ?? null,
            'mac' => $agentData['mac_address'] ?? null,
            'status' => $agentData['online'] ? 'Deployed' : 'Unknown',
            'rmm_id' => $agentData['id'],
        ]);

        // Link the mapping to the asset
        $mapping->linkToAsset($asset->id);

        return $asset;
    }

    /**
     * Update asset with comprehensive inventory data.
     */
    protected function updateAssetFromInventory(Asset $asset, array $inventory): void
    {
        $agentData = $inventory['agent'] ?? [];
        $hardwareData = $inventory['hardware'] ?? [];
        $performanceData = $inventory['performance'] ?? [];

        $updateData = [
            'name' => $agentData['hostname'] ?? $asset->name,
            'os' => $agentData['operating_system'] ?? $asset->os,
            'ip' => $agentData['local_ip'] ?? $asset->ip,
            'nat_ip' => $agentData['public_ip'] ?? $asset->nat_ip,
            'mac' => $agentData['mac_address'] ?? $asset->mac,
            'status' => $agentData['online'] ? 'Deployed' : 'Unknown',
        ];

        // Add hardware information if available
        if (!empty($hardwareData['cpu']['model'])) {
            $updateData['description'] = "CPU: {$hardwareData['cpu']['model']}, RAM: " . 
                ($hardwareData['memory']['total_gb'] ?? 'Unknown') . " GB";
        }

        // Update the asset
        $asset->update(array_filter($updateData, fn($value) => $value !== null));
    }

    /**
     * Update device in RMM system with asset changes.
     */
    protected function updateRmmDevice(RmmServiceInterface $rmmService, DeviceMapping $mapping, Asset $asset): array
    {
        // For TacticalRMM, we can update device notes/description
        try {
            $note = "Updated from Nestogy: {$asset->name}";
            if ($asset->description) {
                $note .= " - {$asset->description}";
            }
            
            $result = $rmmService->addAgentNote($mapping->rmm_device_id, $note);
            
            if ($result['success']) {
                $mapping->updateSyncData([
                    'last_push_to_rmm' => now()->toISOString(),
                    'pushed_data' => [
                        'name' => $asset->name,
                        'description' => $asset->description,
                        'status' => $asset->status,
                    ],
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve client ID from agent data.
     */
    protected function resolveClientId(RmmIntegration $integration, array $agentData): int
    {
        $clientName = $agentData['client'] ?? 'Unknown';
        
        // Try to find existing client
        $client = Client::where('company_id', $integration->company_id)
            ->where(function ($query) use ($clientName) {
                $query->where('name', $clientName)
                      ->orWhere('company_name', $clientName);
            })
            ->first();

        if ($client) {
            return $client->id;
        }

        // Create new client if not found
        $client = Client::create([
            'company_id' => $integration->company_id,
            'name' => $clientName,
            'company_name' => $clientName,
            'email' => null,
            'phone' => null,
        ]);

        Log::info('Created new client during RMM sync', [
            'client_id' => $client->id,
            'client_name' => $clientName,
            'integration_id' => $integration->id,
        ]);

        return $client->id;
    }

    /**
     * Determine asset type from agent data.
     */
    protected function determineAssetType(array $agentData): string
    {
        $platform = strtolower($agentData['platform'] ?? '');
        $monitoringType = strtolower($agentData['monitoring_type'] ?? '');

        if (str_contains($platform, 'server') || $monitoringType === 'server') {
            return 'Server';
        }

        if (str_contains($platform, 'laptop') || str_contains($platform, 'mobile')) {
            return 'Laptop';
        }

        return 'Desktop';
    }
}