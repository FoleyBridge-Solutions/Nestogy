<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Services\NavigationService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;

class ClientIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $type = '';
    public $status = '';
    public $showLeads = false;
    public $perPage = 25;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    
    public $selectedClient;
    public $showDeleteModal = false;
    public $showConvertModal = false;
    public $clientToDelete = null;
    public $leadToConvert = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'status' => ['except' => ''],
        'showLeads' => ['except' => false],
        'perPage' => ['except' => 25],
    ];

    public function mount()
    {
        $this->selectedClient = NavigationService::getSelectedClient();
        $this->showLeads = request()->has('lead');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingShowLeads()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function selectClient($clientId)
    {
        $client = Client::find($clientId);
        if ($client && $client->company_id === Auth::user()->company_id) {
            NavigationService::setSelectedClient($clientId);
            $this->selectedClient = $client;
            
            session()->flash('success', "Selected client: {$client->name}");
            
            // Refresh the component
            $this->dispatch('client-selected');
        }
    }

    public function clearSelection()
    {
        NavigationService::clearSelectedClient();
        $this->selectedClient = null;
        session()->flash('info', 'Client selection cleared');
    }

    public function confirmDelete($clientId)
    {
        $this->clientToDelete = $clientId;
        $this->showDeleteModal = true;
    }

    public function deleteClient()
    {
        if ($this->clientToDelete) {
            $client = Client::find($this->clientToDelete);
            if ($client && $client->company_id === Auth::user()->company_id) {
                $clientName = $client->name;
                
                // Clear selection if deleting selected client
                if ($this->selectedClient && $this->selectedClient->id === $client->id) {
                    NavigationService::clearSelectedClient();
                    $this->selectedClient = null;
                }
                
                $client->delete();
                session()->flash('success', "Client '{$clientName}' has been deleted.");
            }
        }
        
        $this->showDeleteModal = false;
        $this->clientToDelete = null;
    }

    public function confirmConvert($leadId)
    {
        $this->leadToConvert = $leadId;
        $this->showConvertModal = true;
    }

    public function convertLead()
    {
        if ($this->leadToConvert) {
            $client = Client::find($this->leadToConvert);
            if ($client && $client->company_id === Auth::user()->company_id && $client->lead) {
                $client->lead = false;
                $client->save();
                
                session()->flash('success', "Lead '{$client->name}' has been converted to a customer.");
            }
        }
        
        $this->showConvertModal = false;
        $this->leadToConvert = null;
    }

    public function exportCsv()
    {
        $filename = $this->showLeads ? 'leads-export.csv' : 'clients-export.csv';
        
        return Excel::download(new ClientsExport($this->showLeads), $filename);
    }

    public function render()
    {
        $user = Auth::user();
        
        $query = Client::with(['primaryContact', 'primaryLocation', 'tags'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', $this->showLeads);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhereHas('primaryContact', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
            });
        }

        // Apply type filter
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // Apply status filter
        if ($this->status) {
            $query->where('is_active', $this->status === 'active');
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $clients = $query->paginate($this->perPage);

        return view('livewire.clients.client-index', [
            'clients' => $clients,
        ]);
    }
}