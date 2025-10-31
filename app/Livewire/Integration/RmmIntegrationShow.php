<?php

namespace App\Livewire\Integration;

use App\Domains\Client\Models\Client;
use App\Domains\Integration\Models\RmmClientMapping;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class RmmIntegrationShow extends Component
{
    public RmmIntegration $integration;
    public $actionResult = null;
    public $actionType = null;

    // Client mapping properties
    public $showClientMappingModal = false;
    public $nestogyClients = [];
    public $rmmClients = [];
    public $selectedNestogyClientId = null;
    public $selectedRmmClientId = null;
    public $loadingClients = false;
    public $mappings = [];

    public function mount(RmmIntegration $integration)
    {
        $this->integration = $integration;
        $this->loadMappings();
    }

    public function testConnection()
    {
        try {
            $result = $this->integration->testConnection();
            
            $this->actionResult = $result['message'];
            $this->actionType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $this->dispatch('connection-tested');
            }
        } catch (\Exception $e) {
            $this->actionResult = 'Failed to test connection: ' . $e->getMessage();
            $this->actionType = 'error';
        }
    }

    public function syncAgents()
    {
        try {
            \App\Jobs\SyncRmmAgents::dispatch($this->integration);
            
            $this->actionResult = 'Agents sync job queued successfully';
            $this->actionType = 'success';
            
            $this->dispatch('agents-sync-queued');
        } catch (\Exception $e) {
            $this->actionResult = 'Failed to trigger sync: ' . $e->getMessage();
            $this->actionType = 'error';
        }
    }

    public function syncAlerts()
    {
        try {
            \App\Jobs\SyncRmmAlerts::dispatch($this->integration);
            
            $this->actionResult = 'Alerts sync job queued successfully';
            $this->actionType = 'success';
            
            $this->dispatch('alerts-sync-queued');
        } catch (\Exception $e) {
            $this->actionResult = 'Failed to trigger sync: ' . $e->getMessage();
            $this->actionType = 'error';
        }
    }

    public function clearResult()
    {
        $this->actionResult = null;
        $this->actionType = null;
    }

    // ===========================================
    // CLIENT MAPPING METHODS
    // ===========================================

    public function openClientMappingModal()
    {
        $this->showClientMappingModal = true;
        $this->loadNestogyClients();
        $this->loadRmmClients();
    }

    public function closeClientMappingModal()
    {
        $this->showClientMappingModal = false;
        $this->selectedNestogyClientId = null;
        $this->selectedRmmClientId = null;
    }

    public function loadMappings()
    {
        $this->mappings = RmmClientMapping::where('integration_id', $this->integration->id)
            ->where('company_id', auth()->user()->company_id)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($mapping) {
                return [
                    'id' => $mapping->id,
                    'client_name' => $mapping->client->name ?? 'Unknown',
                    'rmm_client_name' => $mapping->rmm_client_name,
                    'is_active' => $mapping->is_active,
                    'last_sync_at' => $mapping->last_sync_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public function loadNestogyClients()
    {
        $this->loadingClients = true;

        try {
            $clients = Client::where('company_id', auth()->user()->company_id)
                ->with(['rmmClientMappings' => function ($query) {
                    $query->where('integration_id', $this->integration->id);
                }])
                ->orderBy('name')
                ->get();

            $this->nestogyClients = $clients->map(function ($client) {
                $mapping = $client->rmmClientMappings->first();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'display_name' => $client->company_name ?: $client->name,
                    'is_mapped' => $mapping !== null,
                    'rmm_client_name' => $mapping?->rmm_client_name,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to load Nestogy clients: '.$e->getMessage());
            $this->actionResult = 'Failed to load clients: '.$e->getMessage();
            $this->actionType = 'error';
        } finally {
            $this->loadingClients = false;
        }
    }

    public function loadRmmClients()
    {
        $this->loadingClients = true;

        try {
            $serviceFactory = app(RmmServiceFactory::class);
            $rmmService = $serviceFactory->make($this->integration);
            $result = $rmmService->getClients();

            if ($result['success'] ?? false) {
                $rmmClients = $result['data'] ?? [];

                // Get already mapped RMM client IDs
                $mappedRmmClientIds = RmmClientMapping::where('integration_id', $this->integration->id)
                    ->where('company_id', auth()->user()->company_id)
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
                $this->actionResult = 'Failed to fetch RMM clients: '.($result['message'] ?? 'Unknown error');
                $this->actionType = 'error';
            }
        } catch (\Exception $e) {
            $this->rmmClients = [];
            Log::error('Error fetching RMM clients: '.$e->getMessage());
            $this->actionResult = 'Failed to fetch RMM clients: '.$e->getMessage();
            $this->actionType = 'error';
        } finally {
            $this->loadingClients = false;
        }
    }

    public function createMapping()
    {
        if (! $this->selectedNestogyClientId || ! $this->selectedRmmClientId) {
            $this->actionResult = 'Please select both a Nestogy client and an RMM client';
            $this->actionType = 'error';
            return;
        }

        try {
            // Find the RMM client name
            $rmmClient = collect($this->rmmClients)->firstWhere('id', $this->selectedRmmClientId);

            if (! $rmmClient) {
                throw new \Exception('RMM client not found');
            }

            // Create or update mapping
            RmmClientMapping::createOrUpdateMapping([
                'company_id' => auth()->user()->company_id,
                'client_id' => $this->selectedNestogyClientId,
                'integration_id' => $this->integration->id,
                'rmm_client_id' => (string) $this->selectedRmmClientId,
                'rmm_client_name' => $rmmClient['name'],
                'is_active' => true,
            ]);

            $this->actionResult = 'Client mapping created successfully';
            $this->actionType = 'success';

            // Reload data
            $this->loadMappings();
            $this->loadNestogyClients();
            $this->loadRmmClients();

            // Clear selections
            $this->selectedNestogyClientId = null;
            $this->selectedRmmClientId = null;
        } catch (\Exception $e) {
            $this->actionResult = 'Failed to create mapping: '.$e->getMessage();
            $this->actionType = 'error';
            Log::error('Failed to create client mapping', [
                'error' => $e->getMessage(),
                'integration_id' => $this->integration->id,
            ]);
        }
    }

    public function removeMapping($mappingId)
    {
        try {
            $mapping = RmmClientMapping::where('id', $mappingId)
                ->where('integration_id', $this->integration->id)
                ->where('company_id', auth()->user()->company_id)
                ->first();

            if (! $mapping) {
                throw new \Exception('Mapping not found or access denied');
            }

            $mapping->delete();

            $this->actionResult = 'Client mapping removed successfully';
            $this->actionType = 'success';

            // Reload data
            $this->loadMappings();
            
            if ($this->showClientMappingModal) {
                $this->loadNestogyClients();
                $this->loadRmmClients();
            }
        } catch (\Exception $e) {
            $this->actionResult = 'Failed to remove mapping: '.$e->getMessage();
            $this->actionType = 'error';
            Log::error('Failed to remove client mapping', [
                'error' => $e->getMessage(),
                'mapping_id' => $mappingId,
            ]);
        }
    }

    public function render()
    {
        $syncStatus = $this->integration->getSyncStatus();
        
        $recentStats = [
            'agents_synced' => $this->integration->total_agents ?? 0,
            'last_sync' => $this->integration->last_sync_at,
        ];

        return view('livewire.integration.rmm-integration-show', [
            'syncStatus' => $syncStatus,
            'recentStats' => $recentStats,
        ]);
    }
}
