<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Client\Models\Client;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class AssetIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $type = '';
    public $status = '';
    public $clientId = '';
    public $assignedTo = '';
    public $locationId = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;
    
    public $selectedAssets = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'status' => ['except' => ''],
        'clientId' => ['except' => ''],
        'assignedTo' => ['except' => ''],
        'locationId' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25]
    ];

    public function mount()
    {
        // Get client from session if available
        $selectedClient = app(\App\Services\NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            // Extract the ID if it's an object, otherwise use the value directly
            $this->clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
        }
    }

    public function updatingSearch()
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

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedAssets = $this->getAssets()->pluck('id')->toArray();
        } else {
            $this->selectedAssets = [];
        }
    }

    public function bulkUpdateStatus($status)
    {
        $count = count($this->selectedAssets);
        
        Asset::whereIn('id', $this->selectedAssets)
            ->where('company_id', Auth::user()->company_id)
            ->update(['status' => $status]);

        $this->selectedAssets = [];
        $this->selectAll = false;
        
        session()->flash('message', "$count assets have been updated to $status status.");
    }

    public function archiveAsset($assetId)
    {
        $asset = Asset::where('id', $assetId)
            ->where('company_id', Auth::user()->company_id)
            ->first();
            
        if ($asset) {
            $asset->update(['archived_at' => now()]);
            session()->flash('message', "Asset '{$asset->name}' has been archived.");
        }
    }

    public function getAssets()
    {
        return Asset::query()
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                      ->orWhere('asset_tag', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%')
                      ->orWhere('manufacturer', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->clientId, function ($query) {
                $query->where('client_id', $this->clientId);
            })
            ->when($this->assignedTo, function ($query) {
                $query->where('assigned_to', $this->assignedTo);
            })
            ->when($this->locationId, function ($query) {
                $query->where('location_id', $this->locationId);
            })
            ->with(['client', 'assignedTo', 'location'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        $assets = $this->getAssets();
        
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
            
        $users = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
            
        $locations = \App\Models\Location::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('livewire.assets.asset-index', [
            'assets' => $assets,
            'clients' => $clients,
            'users' => $users,
            'locations' => $locations,
            'types' => ['Computer', 'Laptop', 'Server', 'Printer', 'Mobile', 'Network', 'Software', 'Other'],
            'statuses' => ['Active', 'Inactive', 'In Repair', 'Disposed', 'Lost', 'Stolen']
        ]);
    }
}