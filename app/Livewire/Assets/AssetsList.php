<?php

namespace App\Livewire\Assets;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetsExport;

class AssetsList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $clientId = '';
    public $type = '';
    public $status = '';
    public $locationId = '';
    public $showDropdown = false;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'clientId' => ['except' => ''],
        'type' => ['except' => ''],
        'status' => ['except' => ''],
        'locationId' => ['except' => ''],
    ];
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedClientId()
    {
        $this->resetPage();
    }
    
    public function updatedType()
    {
        $this->resetPage();
    }
    
    public function updatedStatus()
    {
        $this->resetPage();
    }
    
    public function updatedLocationId()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->clientId = '';
        $this->type = '';
        $this->status = '';
        $this->locationId = '';
        $this->resetPage();
    }
    
    public function exportToExcel()
    {
        $assets = $this->getFilteredQuery()->get();
        
        return Excel::download(new AssetsExport($assets), 'assets_' . now()->format('Y-m-d_His') . '.xlsx');
    }
    
    public function deleteAsset($assetId)
    {
        $asset = Asset::find($assetId);
        
        if ($asset && auth()->user()->can('delete', $asset)) {
            $asset->delete();
            session()->flash('success', 'Asset deleted successfully.');
        } else {
            session()->flash('error', 'Unable to delete asset.');
        }
    }
    
    protected function getFilteredQuery()
    {
        return Asset::query()
            ->with(['client', 'location'])
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('serial', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%')
                      ->orWhere('make', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->orderBy('created_at', 'desc');
    }
    
    public function render()
    {
        $assets = $this->getFilteredQuery()->paginate(25);
        
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();
            
        $locations = Location::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();
        
        return view('livewire.assets.assets-list', [
            'assets' => $assets,
            'clients' => $clients,
            'locations' => $locations,
            'types' => Asset::TYPES ?? ['Hardware', 'Software', 'Network', 'Other'],
            'statuses' => Asset::STATUSES ?? ['Active', 'Inactive', 'Maintenance', 'Retired'],
        ]);
    }
}