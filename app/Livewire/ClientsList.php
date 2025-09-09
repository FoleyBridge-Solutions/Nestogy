<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Auth;

class ClientsList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedClient = null;
    public $isLeadsView = false;
    public $switchingClient = false;
    public $selectedClientId = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        $this->isLeadsView = request()->routeIs('clients.leads') || request('lead');
        $this->selectedClient = NavigationService::getSelectedClient();
        $this->selectedClientId = $this->selectedClient?->id;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectClientAndRedirect($clientId)
    {
        $client = Client::find($clientId);
        
        if ($client && $client->company_id === auth()->user()->company_id) {
            // Set client in session using NavigationService
            NavigationService::setSelectedClient($client);
            
            // Update client access timestamp
            $client->accessed_at = now();
            $client->save();
            
            // Redirect to dashboard
            return redirect()->route('dashboard');
        }
    }
    
    public function selectClient($clientId)
    {
        $client = Client::find($clientId);
        
        if ($client && $client->company_id === auth()->user()->company_id) {
            // Set client in session using NavigationService
            NavigationService::setSelectedClient($client);
            
            // Update client access timestamp
            $client->accessed_at = now();
            $client->save();
            
            // Set a property to track the selected row
            $this->selectedClientId = $clientId;
            
            // Dispatch browser event for animation with client ID in detail
            $this->dispatch('client-selected', ['clientId' => $clientId]);
            
            // Note: The actual redirect happens in JavaScript after animation
        }
    }

    public function clearSelection()
    {
        NavigationService::clearSelectedClient();
        $this->selectedClient = null;
        $this->selectedClientId = null;
        $this->dispatch('client-deselected');
    }

    public function deleteClient($clientId)
    {
        $client = Client::where('company_id', Auth::user()->company_id)
            ->find($clientId);
        
        if ($client && Auth::user()->can('delete', $client)) {
            $client->delete();
            $this->dispatch('client-deleted', clientName: $client->name);
        }
    }

    public function render()
    {
        $query = Client::with(['primaryContact', 'primaryLocation'])
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('lead', $this->isLeadsView);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('company_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $clients = $query->orderBy('accessed_at', 'desc')
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.clients-list', [
            'clients' => $clients
        ]);
    }
}
