<?php

namespace App\Domains\Asset\Controllers;

use App\Domains\Integration\Services\AssetSyncService;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Asset Remote Control Controller
 *
 * Provides comprehensive remote management capabilities for assets
 * through their connected RMM systems. Eliminates the need to access
 * RMM interfaces directly.
 */
class AssetRemoteController extends Controller
{
    protected AssetSyncService $syncService;

    public function __construct(AssetSyncService $syncService)
    {
        $this->syncService = $syncService;
        $this->middleware('auth');
    }

    /**
     * Get real-time device status and performance metrics.
     */
    public function getStatus(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('view', $asset);

            $result = $this->syncService->getDeviceStatus($asset);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'last_updated' => $result['last_updated'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get asset status', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve device status',
            ], 500);
        }
    }

    /**
     * Execute remote command on device.
     */
    public function executeCommand(Request $request, Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            $validator = Validator::make($request->all(), [
                'command' => 'required|string|max:1000',
                'shell' => 'sometimes|string|in:cmd,powershell,bash',
                'timeout' => 'sometimes|integer|min:1|max:300',
                'run_as_system' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid command parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $options = [
                'shell' => $request->get('shell', 'cmd'),
                'timeout' => $request->get('timeout', 30),
                'run_as_system' => $request->get('run_as_system', false),
            ];

            // Log the command execution attempt
            Log::info('Remote command execution requested', [
                'asset_id' => $asset->id,
                'command' => $request->input('command'),
                'user_id' => auth()->id(),
                'options' => $options,
            ]);

            $result = $this->syncService->executeRemoteCommand(
                $asset,
                $request->input('command'),
                $options
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Remote command execution failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Command execution failed',
            ], 500);
        }
    }

    /**
     * Manage Windows services on device.
     */
    public function manageService(Request $request, Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            $validator = Validator::make($request->all(), [
                'service_name' => 'required|string|max:100',
                'action' => 'required|string|in:start,stop,restart',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid service parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Log the service management attempt
            Log::info('Service management requested', [
                'asset_id' => $asset->id,
                'service' => $request->input('service_name'),
                'action' => $request->input('action'),
                'user_id' => auth()->id(),
            ]);

            $result = $this->syncService->manageService(
                $asset,
                $request->input('service_name'),
                $request->input('action')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Service management failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service management failed',
            ], 500);
        }
    }

    /**
     * Install Windows updates on device.
     */
    public function installUpdates(Request $request, Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            $validator = Validator::make($request->all(), [
                'update_ids' => 'sometimes|array',
                'update_ids.*' => 'string',
                'install_all' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid update parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateIds = $request->get('install_all', false)
                ? []
                : $request->get('update_ids', []);

            // Log the update installation attempt
            Log::info('Windows updates installation requested', [
                'asset_id' => $asset->id,
                'update_count' => count($updateIds),
                'install_all' => $request->get('install_all', false),
                'user_id' => auth()->id(),
            ]);

            $result = $this->syncService->installWindowsUpdates($asset, $updateIds);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Windows updates installation failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Updates installation failed',
            ], 500);
        }
    }

    /**
     * Reboot device.
     */
    public function reboot(Request $request, Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            $validator = Validator::make($request->all(), [
                'force' => 'sometimes|boolean',
                'delay' => 'sometimes|integer|min:0|max:300',
                'message' => 'sometimes|string|max:200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reboot parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $options = [
                'force' => $request->get('force', false),
                'delay' => $request->get('delay', 10),
                'message' => $request->get('message', 'Reboot initiated from Nestogy'),
            ];

            // Log the reboot attempt
            Log::warning('Device reboot requested', [
                'asset_id' => $asset->id,
                'options' => $options,
                'user_id' => auth()->id(),
            ]);

            $result = $this->syncService->rebootDevice($asset, $options);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Device reboot failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device reboot failed',
            ], 500);
        }
    }

    /**
     * Get device processes.
     */
    public function getProcesses(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('view', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->getDeviceProcesses($mapping->rmm_device_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get device processes', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve processes',
            ], 500);
        }
    }

    /**
     * Kill a process on device.
     */
    public function killProcess(Request $request, Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            $validator = Validator::make($request->all(), [
                'process_id' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid process ID',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            // Log the process kill attempt
            Log::warning('Process termination requested', [
                'asset_id' => $asset->id,
                'process_id' => $request->input('process_id'),
                'user_id' => auth()->id(),
            ]);

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->killProcess(
                $mapping->rmm_device_id,
                $request->input('process_id')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Process termination failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Process termination failed',
            ], 500);
        }
    }

    /**
     * Get device services.
     */
    public function getServices(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('view', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->getAgentServices($mapping->rmm_device_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get device services', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
            ], 500);
        }
    }

    /**
     * Get Windows updates status.
     */
    public function getUpdates(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('view', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->getDeviceUpdates($mapping->rmm_device_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get device updates', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve updates',
            ], 500);
        }
    }

    /**
     * Scan for Windows updates.
     */
    public function scanUpdates(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            // Log the update scan attempt
            Log::info('Windows update scan requested', [
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
            ]);

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->scanForUpdates($mapping->rmm_device_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Windows update scan failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update scan failed',
            ], 500);
        }
    }

    /**
     * Get comprehensive device inventory.
     */
    public function getInventory(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('view', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $result = $rmmService->getFullDeviceInventory($mapping->rmm_device_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get device inventory', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory',
            ], 500);
        }
    }

    /**
     * Sync asset data from RMM.
     */
    public function sync(Asset $asset): JsonResponse
    {
        try {
            $this->authorize('update', $asset);

            // Get device mapping to access RMM
            $mapping = $asset->deviceMappings()->first();

            if (! $mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No RMM mapping found for this asset',
                ], 400);
            }

            // Use the sync service to update the asset
            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)
                ->create($mapping->integration);

            $agentResponse = $rmmService->getAgent($mapping->rmm_device_id);

            if (! $agentResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get device data from RMM',
                ], 400);
            }

            $updatedAsset = $this->syncService->syncSingleAsset(
                $mapping->integration,
                $rmmService,
                $agentResponse['data']
            );

            return response()->json([
                'success' => true,
                'message' => 'Asset synchronized successfully',
                'data' => $updatedAsset,
            ]);

        } catch (\Exception $e) {
            Log::error('Asset sync failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Asset synchronization failed',
            ], 500);
        }
    }

    /**
     * Show remote management dashboard for asset.
     */
    public function dashboard(Asset $asset)
    {
        $this->authorize('view', $asset);

        return view('domains.asset.remote.dashboard', compact('asset'));
    }
}
