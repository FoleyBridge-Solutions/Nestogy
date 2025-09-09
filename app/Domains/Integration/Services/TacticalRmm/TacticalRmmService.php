<?php

namespace App\Domains\Integration\Services\TacticalRmm;

use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Tactical RMM Service
 * 
 * Implements RmmServiceInterface for Tactical RMM integration.
 * Provides standardized access to Tactical RMM API functionality.
 */
class TacticalRmmService implements RmmServiceInterface
{
    protected RmmIntegration $integration;
    protected TacticalRmmApiClient $apiClient;
    protected TacticalRmmDataMapper $dataMapper;

    public function __construct(RmmIntegration $integration)
    {
        $this->integration = $integration;
        $this->apiClient = new TacticalRmmApiClient($integration);
        $this->dataMapper = new TacticalRmmDataMapper();
    }

    /**
     * Test connection to the RMM system.
     */
    public function testConnection(): array
    {
        return $this->apiClient->testConnection();
    }

    /**
     * Get all agents/devices from the RMM system.
     */
    public function getAgents(array $filters = []): array
    {
        $response = $this->apiClient->get('/agents/');
        
        if (!$response['success']) {
            return $response;
        }

        $agents = $this->dataMapper->mapAgents($response['data']);
        
        // Apply filters if provided
        if (!empty($filters)) {
            $agents = $this->filterAgents($agents, $filters);
        }

        return [
            'success' => true,
            'data' => $agents,
            'total' => count($agents),
        ];
    }

    /**
     * Get a specific agent by ID.
     */
    public function getAgent(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $agent = $this->dataMapper->mapAgent($response['data']);

        return [
            'success' => true,
            'data' => $agent,
        ];
    }

    /**
     * Get all clients/organizations from the RMM system.
     */
    public function getClients(): array
    {
        // Use the correct endpoint according to TacticalRMM API specification
        $endpoint = '/clients/';
        
        Log::info('TacticalRMM: Fetching clients', [
            'integration_id' => $this->integration->id,
            'endpoint' => $endpoint,
            'api_url' => $this->integration->api_url,
        ]);
        
        $response = $this->apiClient->get($endpoint);
        
        if (!$response['success']) {
            Log::error('TacticalRMM: Failed to fetch clients', [
                'integration_id' => $this->integration->id,
                'endpoint' => $endpoint,
                'error' => $response['message'] ?? 'Unknown error',
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to fetch clients from Tactical RMM: ' . ($response['message'] ?? 'Unknown error'),
                'data' => [],
                'debug_info' => [
                    'endpoint' => $endpoint,
                    'integration_url' => $this->integration->api_url,
                    'has_api_key' => !empty($this->integration->api_key),
                    'response' => $response,
                ]
            ];
        }
        
        // Check if we're getting JSON data (not HTML)
        if ($response['data'] === null || !is_array($response['data'])) {
            Log::error('TacticalRMM: Invalid response data format', [
                'integration_id' => $this->integration->id,
                'endpoint' => $endpoint,
                'data_type' => gettype($response['data']),
                'data_sample' => is_string($response['data']) ? substr($response['data'], 0, 200) : $response['data'],
                'raw_body_sample' => isset($response['raw_body']) ? substr($response['raw_body'], 0, 500) : null,
                'headers' => $response['headers'] ?? [],
            ]);
            
            return [
                'success' => false,
                'message' => 'Invalid response format from Tactical RMM API. Expected JSON array but received: ' . gettype($response['data']),
                'data' => [],
                'debug_info' => [
                    'endpoint' => $endpoint,
                    'response_type' => gettype($response['data']),
                    'integration_url' => $this->integration->api_url,
                ]
            ];
        }
        
        try {
            $clients = $this->dataMapper->mapClients($response['data']);
            
            Log::info('TacticalRMM: Successfully fetched clients', [
                'integration_id' => $this->integration->id,
                'client_count' => count($clients),
                'endpoint' => $endpoint,
            ]);
            
            return [
                'success' => true,
                'data' => $clients,
                'total' => count($clients),
            ];
            
        } catch (\Exception $e) {
            Log::error('TacticalRMM: Error mapping clients', [
                'integration_id' => $this->integration->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'raw_data_sample' => array_slice($response['data'], 0, 2), // First 2 items for debugging
            ]);
            
            return [
                'success' => false,
                'message' => 'Error processing client data from Tactical RMM: ' . $e->getMessage(),
                'data' => [],
                'debug_info' => [
                    'endpoint' => $endpoint,
                    'mapping_error' => $e->getMessage(),
                    'raw_data_count' => count($response['data']),
                ]
            ];
        }
    }

    /**
     * Create a new client/organization in the RMM system.
     */
    public function createClient(array $clientData): array
    {
        // Validate required fields
        if (empty($clientData['name'])) {
            return [
                'success' => false,
                'message' => 'Client name is required'
            ];
        }

        // Prepare data for TacticalRMM API
        $apiData = [
            'name' => $clientData['name'],
            'description' => $clientData['description'] ?? 'Created from Nestogy'
        ];

        $response = $this->apiClient->post('/clients/', $apiData);
        
        if (!$response['success']) {
            return $response;
        }

        // Map the created client data
        $client = $this->dataMapper->mapClient($response['data']);
        
        return [
            'success' => true,
            'data' => $client,
            'id' => $client['id'],
            'name' => $client['name']
        ];
    }

    /**
     * Get all sites for a specific client.
     */
    public function getSites(string $clientId): array
    {
        $response = $this->apiClient->get('/clients/sites/', ['client' => $clientId]);
        
        if (!$response['success']) {
            return $response;
        }

        $sites = $this->dataMapper->mapSites($response['data']);

        return [
            'success' => true,
            'data' => $sites,
        ];
    }

    /**
     * Get alerts from the RMM system.
     */
    public function getAlerts(array $filters = []): array
    {
        $params = [];
        
        // Apply date filters if provided
        if (isset($filters['from_date'])) {
            $params['created_time__gte'] = $filters['from_date'];
        }
        
        if (isset($filters['to_date'])) {
            $params['created_time__lte'] = $filters['to_date'];
        }

        if (isset($filters['severity'])) {
            $params['severity'] = $filters['severity'];
        }

        $response = $this->apiClient->get('/alerts/', $params);
        
        if (!$response['success']) {
            return $response;
        }

        $alerts = $this->dataMapper->mapAlerts($response['data']);

        return [
            'success' => true,
            'data' => $alerts,
            'total' => count($alerts),
        ];
    }

    /**
     * Get a specific alert by ID.
     */
    public function getAlert(string $alertId): array
    {
        $response = $this->apiClient->get("/alerts/{$alertId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $alert = $this->dataMapper->mapAlert($response['data']);

        return [
            'success' => true,
            'data' => $alert,
        ];
    }

    /**
     * Acknowledge/resolve an alert.
     */
    public function updateAlert(string $alertId, string $action, ?string $note = null): array
    {
        $data = ['action' => $action];
        
        if ($note) {
            $data['note'] = $note;
        }

        $response = $this->apiClient->patch("/alerts/{$alertId}/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => "Alert {$action}d successfully",
            ];
        }

        return $response;
    }

    /**
     * Get checks/monitors for an agent.
     */
    public function getAgentChecks(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/checks/");
        
        if (!$response['success']) {
            return $response;
        }

        $checks = $this->dataMapper->mapChecks($response['data']);

        return [
            'success' => true,
            'data' => $checks,
        ];
    }

    /**
     * Run a command on an agent.
     */
    public function runCommand(string $agentId, string $command, array $options = []): array
    {
        $data = [
            'command' => $command,
            'shell' => $options['shell'] ?? 'cmd',
            'timeout' => $options['timeout'] ?? 30,
        ];

        $response = $this->apiClient->post("/agents/{$agentId}/cmd/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => $response['data'],
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Get system information for an agent.
     */
    public function getAgentInfo(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $agentInfo = $this->dataMapper->mapAgentInfo($response['data']);

        return [
            'success' => true,
            'data' => $agentInfo,
        ];
    }

    /**
     * Get installed software for an agent.
     */
    public function getAgentSoftware(string $agentId): array
    {
        $response = $this->apiClient->get("/software/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $software = $this->dataMapper->mapSoftware($response['data']);

        return [
            'success' => true,
            'data' => $software,
        ];
    }

    /**
     * Get services for an agent.
     */
    public function getAgentServices(string $agentId): array
    {
        $response = $this->apiClient->get("/services/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $services = $this->dataMapper->mapServices($response['data']);

        return [
            'success' => true,
            'data' => $services,
        ];
    }

    /**
     * Get event logs for an agent.
     */
    public function getAgentEventLogs(string $agentId, string $logType, int $days = 7): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/eventlog/{$logType}/{$days}/");
        
        if (!$response['success']) {
            return $response;
        }

        $logs = $this->dataMapper->mapEventLogs($response['data']);

        return [
            'success' => true,
            'data' => $logs,
        ];
    }

    /**
     * Create a script and run it on an agent.
     */
    public function runScript(string $agentId, array $scriptData): array
    {
        $response = $this->apiClient->post("/agents/{$agentId}/runscript/", $scriptData);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => $response['data'],
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Get pending actions for an agent.
     */
    public function getPendingActions(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/pendingactions/");
        
        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }

    /**
     * Reboot an agent.
     */
    public function rebootAgent(string $agentId, array $options = []): array
    {
        $data = [
            'force' => $options['force'] ?? false,
            'delay' => $options['delay'] ?? 10,
        ];

        $response = $this->apiClient->post("/agents/{$agentId}/reboot/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Get task/action status.
     */
    public function getTaskStatus(string $taskId): array
    {
        // This would depend on how Tactical RMM handles task status
        // For now, return a placeholder implementation
        return [
            'success' => true,
            'data' => [
                'id' => $taskId,
                'status' => 'pending',
                'result' => null,
            ],
        ];
    }

    /**
     * Sync agents data and return standardized format.
     */
    public function syncAgents(): array
    {
        $response = $this->getAgents();
        
        if (!$response['success']) {
            return $response;
        }

        // Update integration statistics
        $this->integration->updateAgentCount($response['total']);
        $this->integration->updateLastSync();

        return [
            'success' => true,
            'agents' => $response['data'],
            'total' => $response['total'],
        ];
    }

    /**
     * Sync alerts data and return standardized format.
     */
    public function syncAlerts(array $filters = []): array
    {
        // Get alerts from the last sync or last 24 hours if never synced
        if (empty($filters['from_date'])) {
            $lastSync = $this->integration->last_sync_at;
            $filters['from_date'] = $lastSync ? $lastSync->toISOString() : now()->subDay()->toISOString();
        }

        $response = $this->getAlerts($filters);
        
        if (!$response['success']) {
            return $response;
        }

        // Update integration statistics
        $this->integration->updateAlertsCount($response['total']);

        return [
            'success' => true,
            'alerts' => $response['data'],
            'total' => $response['total'],
        ];
    }

    /**
     * Get webhook endpoint URL for this integration.
     */
    public function getWebhookUrl(): string
    {
        return route('api.webhooks.tactical-rmm', ['integration' => $this->integration->id]);
    }

    /**
     * Process webhook payload from the RMM system.
     */
    public function processWebhook(array $payload): array
    {
        try {
            $standardizedData = $this->dataMapper->mapWebhookPayload($payload);
            
            return [
                'success' => true,
                'data' => $standardizedData,
            ];
        } catch (\Exception $e) {
            Log::error('TacticalRMM webhook processing failed', [
                'integration_id' => $this->integration->id,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get API rate limit information.
     */
    public function getRateLimits(): array
    {
        $rateLimitInfo = $this->apiClient->getRateLimitInfo();
        
        return [
            'success' => true,
            'limits' => $rateLimitInfo,
        ];
    }

    /**
     * Get service health/status information.
     */
    public function getServiceHealth(): array
    {
        $response = $this->apiClient->get('/core/version/');
        
        if ($response['success']) {
            return [
                'success' => true,
                'status' => 'healthy',
                'version' => $response['data']['version'] ?? 'Unknown',
            ];
        }

        return [
            'success' => false,
            'status' => 'unhealthy',
            'version' => 'Unknown',
        ];
    }

    /**
     * Filter agents based on criteria.
     */
    protected function filterAgents(array $agents, array $filters): array
    {
        return array_filter($agents, function ($agent) use ($filters) {
            // Filter by online status
            if (isset($filters['online']) && $agent['online'] !== $filters['online']) {
                return false;
            }

            // Filter by client
            if (isset($filters['client']) && $agent['client'] !== $filters['client']) {
                return false;
            }

            // Filter by site
            if (isset($filters['site']) && $agent['site'] !== $filters['site']) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get the API client instance.
     */
    public function getApiClient(): TacticalRmmApiClient
    {
        return $this->apiClient;
    }

    /**
     * Get the data mapper instance.
     */
    public function getDataMapper(): TacticalRmmDataMapper
    {
        return $this->dataMapper;
    }

    /**
     * Get comprehensive device hardware information.
     */
    public function getDeviceHardware(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $hardware = $this->dataMapper->mapHardwareInfo($response['data']);

        return [
            'success' => true,
            'data' => $hardware,
        ];
    }

    /**
     * Get device performance metrics.
     */
    public function getDevicePerformance(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $performance = $this->dataMapper->mapPerformanceMetrics($response['data']);

        return [
            'success' => true,
            'data' => $performance,
        ];
    }

    /**
     * Get device network configuration.
     */
    public function getDeviceNetwork(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $network = $this->dataMapper->mapNetworkInfo($response['data']);

        return [
            'success' => true,
            'data' => $network,
        ];
    }

    /**
     * Get device Windows updates information.
     */
    public function getDeviceUpdates(string $agentId): array
    {
        $response = $this->apiClient->get("/winupdate/{$agentId}/");
        
        if (!$response['success']) {
            return $response;
        }

        $updates = $this->dataMapper->mapWindowsUpdates($response['data']);

        return [
            'success' => true,
            'data' => $updates,
        ];
    }

    /**
     * Install Windows updates on device.
     */
    public function installUpdates(string $agentId, array $updateIds = []): array
    {
        $data = empty($updateIds) ? [] : ['updates' => $updateIds];
        
        $response = $this->apiClient->post("/winupdate/{$agentId}/install/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Windows updates installation initiated',
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Scan for Windows updates on device.
     */
    public function scanForUpdates(string $agentId): array
    {
        $response = $this->apiClient->post("/winupdate/{$agentId}/scan/");
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Windows update scan initiated',
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Get device processes.
     */
    public function getDeviceProcesses(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/processes/");
        
        if (!$response['success']) {
            return $response;
        }

        $processes = $this->dataMapper->mapProcesses($response['data']);

        return [
            'success' => true,
            'data' => $processes,
        ];
    }

    /**
     * Kill a process on device.
     */
    public function killProcess(string $agentId, int $processId): array
    {
        $response = $this->apiClient->delete("/agents/{$agentId}/processes/{$processId}/");
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Process terminated successfully',
            ];
        }

        return $response;
    }

    /**
     * Start a service on device.
     */
    public function startService(string $agentId, string $serviceName): array
    {
        $data = ['action' => 'start'];
        
        $response = $this->apiClient->post("/services/{$agentId}/{$serviceName}/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => "Service '{$serviceName}' started successfully",
            ];
        }

        return $response;
    }

    /**
     * Stop a service on device.
     */
    public function stopService(string $agentId, string $serviceName): array
    {
        $data = ['action' => 'stop'];
        
        $response = $this->apiClient->post("/services/{$agentId}/{$serviceName}/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => "Service '{$serviceName}' stopped successfully",
            ];
        }

        return $response;
    }

    /**
     * Restart a service on device.
     */
    public function restartService(string $agentId, string $serviceName): array
    {
        $data = ['action' => 'restart'];
        
        $response = $this->apiClient->post("/services/{$agentId}/{$serviceName}/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => "Service '{$serviceName}' restarted successfully",
            ];
        }

        return $response;
    }

    /**
     * Get WMI data from device.
     */
    public function getWmiData(string $agentId, string $wmiClass, array $properties = []): array
    {
        $data = [
            'wmi_class' => $wmiClass,
            'properties' => $properties,
        ];

        $response = $this->apiClient->post("/agents/{$agentId}/wmi/", $data);
        
        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }

    /**
     * Wake on LAN for device.
     */
    public function wakeOnLan(string $agentId): array
    {
        $response = $this->apiClient->post("/agents/{$agentId}/wol/");
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Wake on LAN signal sent',
            ];
        }

        return $response;
    }

    /**
     * Shutdown device.
     */
    public function shutdownDevice(string $agentId, array $options = []): array
    {
        $data = [
            'force' => $options['force'] ?? false,
            'delay' => $options['delay'] ?? 10,
        ];

        $response = $this->apiClient->post("/agents/{$agentId}/shutdown/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'task_id' => $response['data']['id'] ?? null,
            ];
        }

        return $response;
    }

    /**
     * Get agent history/activity logs.
     */
    public function getAgentHistory(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/history/");
        
        if (!$response['success']) {
            return $response;
        }

        $history = $this->dataMapper->mapAgentHistory($response['data']);

        return [
            'success' => true,
            'data' => $history,
        ];
    }

    /**
     * Get agent notes.
     */
    public function getAgentNotes(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/notes/");
        
        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }

    /**
     * Add note to agent.
     */
    public function addAgentNote(string $agentId, string $note): array
    {
        $data = ['note' => $note];
        
        $response = $this->apiClient->post("/agents/{$agentId}/notes/", $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Note added successfully',
                'data' => $response['data'],
            ];
        }

        return $response;
    }

    /**
     * Get MeshCentral connection info for remote access.
     */
    public function getMeshCentralInfo(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/meshcentral/");
        
        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }

    /**
     * Initiate MeshCentral connection.
     */
    public function initiateMeshCentral(string $agentId): array
    {
        $response = $this->apiClient->post("/agents/{$agentId}/meshcentral/");
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'MeshCentral connection initiated',
                'data' => $response['data'],
            ];
        }

        return $response;
    }

    /**
     * Recover MeshCentral connection.
     */
    public function recoverMeshCentral(string $agentId): array
    {
        $response = $this->apiClient->post("/agents/{$agentId}/meshcentral/recover/");
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'MeshCentral recovery initiated',
            ];
        }

        return $response;
    }

    /**
     * Ping device to test connectivity.
     */
    public function pingDevice(string $agentId): array
    {
        $response = $this->apiClient->get("/agents/{$agentId}/ping/");
        
        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }

    /**
     * Get comprehensive device inventory (all data combined).
     */
    public function getFullDeviceInventory(string $agentId): array
    {
        $results = [];
        $errors = [];

        // Get basic agent info
        $agentInfo = $this->getAgent($agentId);
        if ($agentInfo['success']) {
            $results['agent'] = $agentInfo['data'];
        } else {
            $errors['agent'] = $agentInfo['error'];
        }

        // Get hardware info
        $hardware = $this->getDeviceHardware($agentId);
        if ($hardware['success']) {
            $results['hardware'] = $hardware['data'];
        } else {
            $errors['hardware'] = $hardware['error'];
        }

        // Get software info
        $software = $this->getAgentSoftware($agentId);
        if ($software['success']) {
            $results['software'] = $software['data'];
        } else {
            $errors['software'] = $software['error'];
        }

        // Get services info
        $services = $this->getAgentServices($agentId);
        if ($services['success']) {
            $results['services'] = $services['data'];
        } else {
            $errors['services'] = $services['error'];
        }

        // Get checks/monitors
        $checks = $this->getAgentChecks($agentId);
        if ($checks['success']) {
            $results['checks'] = $checks['data'];
        } else {
            $errors['checks'] = $checks['error'];
        }

        // Get performance metrics
        $performance = $this->getDevicePerformance($agentId);
        if ($performance['success']) {
            $results['performance'] = $performance['data'];
        } else {
            $errors['performance'] = $performance['error'];
        }

        // Get network info
        $network = $this->getDeviceNetwork($agentId);
        if ($network['success']) {
            $results['network'] = $network['data'];
        } else {
            $errors['network'] = $network['error'];
        }

        return [
            'success' => !empty($results),
            'data' => $results,
            'errors' => $errors,
            'collected_at' => now()->toISOString(),
        ];
    }
}