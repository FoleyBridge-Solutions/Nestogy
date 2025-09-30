<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\RmmClientMapping;
use App\Models\RmmIntegration;
use App\Services\TacticalRmmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TacticalRmmIntegration extends Component
{
    // Integration properties
    public $enabled = false;

    public $apiUrl = '';

    public $apiKey = '';

    public $integrationName = 'Tactical RMM Integration';

    public $showApiKey = false;

    public $integrationSaved = false;

    public $integration = null;

    // Status properties
    public $testing = false;

    public $saving = false;

    public $syncing = false;

    public $connectionStatus = null;

    public $connectionMessage = '';

    // Client mapping modal properties
    public $clientMappingModal = false;

    public $nestogyClients = [];

    public $rmmClients = [];

    public $selectedNestogyClientId = null;

    public $selectedRmmClientId = null;

    public $loadingNestogyClients = false;

    public $loadingRmmClients = false;

    public $creatingMapping = false;

    public $removingMapping = false;

    public function mount()
    {
        $this->loadExistingIntegration();
    }

    public function loadExistingIntegration()
    {
        try {
            $this->integration = RmmIntegration::where('company_id', Auth::user()->company_id)
                ->where('rmm_type', 'TRMM')
                ->first();

            if ($this->integration) {
                $this->enabled = $this->integration->is_active;
                $this->integrationName = $this->integration->name;
                $this->integrationSaved = true;
                // Don't expose actual credentials
                $this->apiUrl = '';
                $this->apiKey = '';
            }
        } catch (\Exception $e) {
            Log::error('Failed to load existing integration: '.$e->getMessage());
        }
    }

    public function toggleIntegration()
    {
        if (! $this->enabled && $this->integrationSaved) {
            // Disable the integration
            if ($this->integration) {
                $this->integration->update(['is_active' => false]);
            }
        }
    }

    public function testConnection()
    {
        $this->testing = true;
        $this->connectionStatus = null;
        $this->connectionMessage = '';

        try {
            if ($this->integrationSaved && empty($this->apiUrl) && empty($this->apiKey)) {
                // Test with saved credentials
                if (! $this->integration) {
                    throw new \Exception('No saved integration found');
                }

                $service = new TacticalRmmService($this->integration);
                $result = $service->testConnection();
            } else {
                // Test with new credentials
                if (empty($this->apiUrl) || empty($this->apiKey)) {
                    $this->connectionStatus = 'error';
                    $this->connectionMessage = 'Please enter both API URL and API Key';
                    $this->testing = false;

                    return;
                }

                // Create temporary service instance
                $tempIntegration = new RmmIntegration([
                    'api_url' => $this->apiUrl,
                    'api_key' => Crypt::encryptString($this->apiKey),
                    'rmm_type' => 'TRMM',
                ]);

                $service = new TacticalRmmService($tempIntegration);
                $result = $service->testConnection();
            }

            if ($result['success']) {
                $this->connectionStatus = 'success';
                $this->connectionMessage = $result['message'] ?? 'Connection successful!';
            } else {
                $this->connectionStatus = 'error';
                $this->connectionMessage = $result['message'] ?? 'Connection failed';
            }
        } catch (\Exception $e) {
            $this->connectionStatus = 'error';
            $this->connectionMessage = 'Failed to test connection: '.$e->getMessage();
        } finally {
            $this->testing = false;
        }
    }

    public function saveIntegration()
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            $this->connectionStatus = 'error';
            $this->connectionMessage = 'Please enter both API URL and API Key';

            return;
        }

        $this->saving = true;

        try {
            $data = [
                'company_id' => Auth::user()->company_id,
                'rmm_type' => 'TRMM',
                'name' => $this->integrationName,
                'api_url' => $this->apiUrl,
                'api_key' => Crypt::encryptString($this->apiKey),
                'is_active' => $this->enabled,
            ];

            if ($this->integration) {
                $this->integration->update($data);
            } else {
                $this->integration = RmmIntegration::create($data);
            }

            $this->integrationSaved = true;
            $this->connectionStatus = 'success';
            $this->connectionMessage = 'Integration saved successfully!';

            // Clear credentials from view
            $this->apiUrl = '';
            $this->apiKey = '';

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'RMM Integration saved successfully!',
            ]);
        } catch (\Exception $e) {
            $this->connectionStatus = 'error';
            $this->connectionMessage = 'Failed to save integration: '.$e->getMessage();
        } finally {
            $this->saving = false;
        }
    }

    public function syncAgents()
    {
        if (! $this->integrationSaved || ! $this->integration) {
            return;
        }

        $this->syncing = true;

        try {
            // Dispatch sync job
            \App\Jobs\SyncTacticalRmmAgents::dispatch($this->integration);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Agent sync job queued successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to sync agents: '.$e->getMessage(),
            ]);
        } finally {
            $this->syncing = false;
        }
    }

    public function syncAlerts()
    {
        if (! $this->integrationSaved || ! $this->integration) {
            return;
        }

        $this->syncing = true;

        try {
            // Dispatch sync job
            \App\Jobs\SyncTacticalRmmAlerts::dispatch($this->integration);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Alert sync job queued successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to sync alerts: '.$e->getMessage(),
            ]);
        } finally {
            $this->syncing = false;
        }
    }

    public function openClientMappingModal()
    {
        $this->clientMappingModal = true;
        $this->loadNestogyClients();
        $this->loadRmmClients();
    }

    public function closeClientMappingModal()
    {
        $this->clientMappingModal = false;
        $this->selectedNestogyClientId = null;
        $this->selectedRmmClientId = null;
    }

    public function loadNestogyClients()
    {
        $this->loadingNestogyClients = true;

        try {
            $clients = Client::where('company_id', Auth::user()->company_id)
                ->with('rmmClientMappings')
                ->orderBy('name')
                ->get();

            $this->nestogyClients = $clients->map(function ($client) {
                $mapping = $client->rmmClientMappings->first();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'display_name' => $client->company_name ?: $client->name,
                    'existing_mapping' => $mapping ? [
                        'rmm_client_id' => $mapping->rmm_client_id,
                        'rmm_client_name' => $mapping->rmm_client_name,
                    ] : null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to load Nestogy clients: '.$e->getMessage());
        } finally {
            $this->loadingNestogyClients = false;
        }
    }

    public function loadRmmClients()
    {
        $this->loadingRmmClients = true;

        try {
            if (! $this->integration) {
                $this->rmmClients = [];

                return;
            }

            $service = new TacticalRmmService($this->integration);
            $result = $service->getClients();

            if ($result['success']) {
                $rmmClients = $result['clients'] ?? [];

                // Check which RMM clients are already mapped
                $mappedRmmClientIds = RmmClientMapping::where('company_id', Auth::user()->company_id)
                    ->pluck('rmm_client_id')
                    ->toArray();

                $this->rmmClients = collect($rmmClients)->map(function ($client) use ($mappedRmmClientIds) {
                    return [
                        'id' => $client['id'],
                        'name' => $client['name'],
                        'is_mapped' => in_array((string) $client['id'], $mappedRmmClientIds),
                    ];
                })->toArray();
            } else {
                $this->rmmClients = [];
                Log::error('Failed to fetch RMM clients: '.($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->rmmClients = [];
            Log::error('Error fetching RMM clients: '.$e->getMessage());
        } finally {
            $this->loadingRmmClients = false;
        }
    }

    public function selectNestogyClient($clientId)
    {
        $this->selectedNestogyClientId = $clientId;
        $this->selectedRmmClientId = null;
    }

    public function selectRmmClient($clientId)
    {
        $this->selectedRmmClientId = $clientId;
    }

    public function createMapping()
    {
        if (! $this->selectedNestogyClientId || ! $this->selectedRmmClientId) {
            return;
        }

        $this->creatingMapping = true;

        try {
            // Find the RMM client name
            $rmmClient = collect($this->rmmClients)->firstWhere('id', $this->selectedRmmClientId);

            if (! $rmmClient) {
                throw new \Exception('RMM client not found');
            }

            // Delete any existing mapping for this client
            RmmClientMapping::where('client_id', $this->selectedNestogyClientId)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            // Create new mapping
            RmmClientMapping::create([
                'client_id' => $this->selectedNestogyClientId,
                'company_id' => Auth::user()->company_id,
                'rmm_integration_id' => $this->integration->id,
                'rmm_client_id' => (string) $this->selectedRmmClientId,
                'rmm_client_name' => $rmmClient['name'],
            ]);

            // Refresh the client lists
            $this->loadNestogyClients();
            $this->loadRmmClients();

            // Clear selections
            $this->selectedNestogyClientId = null;
            $this->selectedRmmClientId = null;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Client mapping created successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to create mapping: '.$e->getMessage(),
            ]);
        } finally {
            $this->creatingMapping = false;
        }
    }

    public function removeMapping()
    {
        if (! $this->selectedNestogyClientId) {
            return;
        }

        $this->removingMapping = true;

        try {
            RmmClientMapping::where('client_id', $this->selectedNestogyClientId)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            // Refresh the client lists
            $this->loadNestogyClients();
            $this->loadRmmClients();

            // Clear selections
            $this->selectedNestogyClientId = null;
            $this->selectedRmmClientId = null;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Client mapping removed successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to remove mapping: '.$e->getMessage(),
            ]);
        } finally {
            $this->removingMapping = false;
        }
    }

    public function getSelectedNestogyClient()
    {
        return collect($this->nestogyClients)->firstWhere('id', $this->selectedNestogyClientId);
    }

    public function getSelectedRmmClient()
    {
        return collect($this->rmmClients)->firstWhere('id', $this->selectedRmmClientId);
    }

    public function render()
    {
        return view('livewire.tactical-rmm-integration');
    }
}
