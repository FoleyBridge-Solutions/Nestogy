<?php

namespace App\Jobs;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\DeviceMapping;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

/**
 * Sync Device Inventory Job
 * 
 * Synchronizes device information from RMM systems to maintain
 * up-to-date asset inventory and device mappings.
 */
class SyncDeviceInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Integration $integration;
    protected ?string $deviceId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 300;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 180, 360]; // 1m, 3m, 6m
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Integration $integration, ?string $deviceId = null)
    {
        $this->integration = $integration;
        $this->deviceId = $deviceId;
        $this->queue = 'device-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting device inventory sync', [
                'integration_id' => $this->integration->id,
                'provider' => $this->integration->provider,
                'device_id' => $this->deviceId,
                'attempt' => $this->attempts(),
            ]);

            // Check if integration is active
            if (!$this->integration->isActive()) {
                Log::warning('Skipping sync for inactive integration', [
                    'integration_id' => $this->integration->id,
                ]);
                return;
            }

            // Sync specific device or all devices
            if ($this->deviceId) {
                $this->syncSingleDevice();
            } else {
                $this->syncAllDevices();
            }

            // Update integration last sync timestamp
            $this->integration->updateLastSync();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Device inventory sync completed', [
                'integration_id' => $this->integration->id,
                'device_id' => $this->deviceId,
                'processing_time_ms' => $processingTime,
            ]);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Device inventory sync failed', [
                'integration_id' => $this->integration->id,
                'device_id' => $this->deviceId,
                'attempt' => $this->attempts(),
                'processing_time_ms' => $processingTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single device.
     */
    protected function syncSingleDevice(): void
    {
        $deviceData = $this->fetchDeviceData($this->deviceId);
        
        if ($deviceData) {
            $this->updateDeviceMapping($deviceData);
            Log::info('Single device synced successfully', [
                'integration_id' => $this->integration->id,
                'device_id' => $this->deviceId,
            ]);
        }
    }

    /**
     * Sync all devices for the integration.
     */
    protected function syncAllDevices(): void
    {
        $devicesData = $this->fetchAllDevicesData();
        $syncedCount = 0;
        $errorCount = 0;

        foreach ($devicesData as $deviceData) {
            try {
                $this->updateDeviceMapping($deviceData);
                $syncedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::warning('Failed to sync individual device', [
                    'integration_id' => $this->integration->id,
                    'device_data' => $deviceData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('All devices sync completed', [
            'integration_id' => $this->integration->id,
            'synced_count' => $syncedCount,
            'error_count' => $errorCount,
        ]);
    }

    /**
     * Fetch device data from RMM system.
     */
    protected function fetchDeviceData(string $deviceId): ?array
    {
        try {
            switch ($this->integration->provider) {
                case Integration::PROVIDER_CONNECTWISE:
                    return $this->fetchConnectWiseDevice($deviceId);
                case Integration::PROVIDER_DATTO:
                    return $this->fetchDattoDevice($deviceId);
                case Integration::PROVIDER_NINJA:
                    return $this->fetchNinjaDevice($deviceId);
                default:
                    Log::warning('Device sync not supported for provider', [
                        'provider' => $this->integration->provider,
                    ]);
                    return null;
            }
        } catch (RequestException $e) {
            Log::error('API request failed during device fetch', [
                'integration_id' => $this->integration->id,
                'device_id' => $deviceId,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch all devices data from RMM system.
     */
    protected function fetchAllDevicesData(): array
    {
        try {
            switch ($this->integration->provider) {
                case Integration::PROVIDER_CONNECTWISE:
                    return $this->fetchConnectWiseDevices();
                case Integration::PROVIDER_DATTO:
                    return $this->fetchDattoDevices();
                case Integration::PROVIDER_NINJA:
                    return $this->fetchNinjaDevices();
                default:
                    Log::warning('Bulk device sync not supported for provider', [
                        'provider' => $this->integration->provider,
                    ]);
                    return [];
            }
        } catch (RequestException $e) {
            Log::error('API request failed during devices fetch', [
                'integration_id' => $this->integration->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch device from ConnectWise Automate.
     */
    protected function fetchConnectWiseDevice(string $deviceId): ?array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $credentials['api_key'],
        ])->timeout(30)->get("{$endpoint}/computers/{$deviceId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Fetch all devices from ConnectWise Automate.
     */
    protected function fetchConnectWiseDevices(): array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $credentials['api_key'],
        ])->timeout(60)->get("{$endpoint}/computers");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Fetch device from Datto RMM.
     */
    protected function fetchDattoDevice(string $deviceId): ?array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            return null;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $credentials['api_key'],
        ])->timeout(30)->get("{$endpoint}/device/{$deviceId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Fetch all devices from Datto RMM.
     */
    protected function fetchDattoDevices(): array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            return [];
        }

        $response = Http::withHeaders([
            'X-API-Key' => $credentials['api_key'],
        ])->timeout(60)->get("{$endpoint}/devices");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Fetch device from NinjaOne.
     */
    protected function fetchNinjaDevice(string $deviceId): ?array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['bearer_token'])) {
            return null;
        }

        $response = Http::withToken($credentials['bearer_token'])
            ->timeout(30)
            ->get("{$endpoint}/v2/device/{$deviceId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Fetch all devices from NinjaOne.
     */
    protected function fetchNinjaDevices(): array
    {
        $credentials = $this->integration->getCredentials();
        $endpoint = $this->integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['bearer_token'])) {
            return [];
        }

        $response = Http::withToken($credentials['bearer_token'])
            ->timeout(60)
            ->get("{$endpoint}/v2/devices");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Update device mapping with fresh data.
     */
    protected function updateDeviceMapping(array $deviceData): void
    {
        $fieldMappings = $this->integration->field_mappings 
                      ?: Integration::getDefaultFieldMappings($this->integration->provider);

        $deviceId = data_get($deviceData, $fieldMappings['device_id']);
        $deviceName = data_get($deviceData, $fieldMappings['device_name'], 'Unknown Device');
        $clientId = data_get($deviceData, $fieldMappings['client_id']);

        if (!$deviceId) {
            Log::warning('Device ID not found in sync data', [
                'integration_id' => $this->integration->id,
                'device_data' => $deviceData,
            ]);
            return;
        }

        // Resolve client ID
        $internalClientId = $this->resolveClientId($clientId);
        if (!$internalClientId) {
            Log::warning('Could not resolve client ID for device sync', [
                'integration_id' => $this->integration->id,
                'rmm_client_id' => $clientId,
                'device_id' => $deviceId,
            ]);
            return;
        }

        // Update or create device mapping
        $mapping = DeviceMapping::updateOrCreateMapping(
            $this->integration->id,
            $deviceId,
            $internalClientId,
            $deviceName,
            [
                'last_inventory_sync' => now()->toISOString(),
                'api_data' => $deviceData,
            ]
        );

        Log::debug('Device mapping updated', [
            'mapping_id' => $mapping->id,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
        ]);
    }

    /**
     * Resolve RMM client ID to internal client ID.
     */
    protected function resolveClientId(string $rmmClientId): ?int
    {
        // Try as direct ID first
        if (is_numeric($rmmClientId)) {
            $client = \App\Models\Client::forCompany($this->integration->company_id)
                ->where('id', $rmmClientId)
                ->first();
            if ($client) {
                return $client->id;
            }
        }

        // Try matching by name or RMM ID
        $client = \App\Models\Client::forCompany($this->integration->company_id)
            ->where(function ($query) use ($rmmClientId) {
                $query->where('name', $rmmClientId)
                      ->orWhere('company_name', $rmmClientId)
                      ->orWhere('rmm_id', $rmmClientId);
            })
            ->first();

        return $client?->id;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Device inventory sync job failed permanently', [
            'integration_id' => $this->integration->id,
            'device_id' => $this->deviceId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'device-sync',
            'integration:' . $this->integration->id,
            'provider:' . $this->integration->provider,
        ];
    }
}