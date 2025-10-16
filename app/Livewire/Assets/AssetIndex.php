<?php

namespace App\Livewire\Assets;

use App\Domains\Client\Models\Client;
use App\Livewire\BaseIndexComponent;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AssetIndex extends BaseIndexComponent
{
    public $type = '';

    public $status = '';

    public $clientId = '';

    public $assignedTo = '';

    public $locationId = '';

    public $selectedAssets = [];

    protected function getDefaultSort(): array
    {
        return [
            'field' => 'created_at',
            'direction' => 'desc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'name',
            'serial_number',
            'asset_tag',
            'model',
            'manufacturer',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'type' => ['except' => ''],
            'status' => ['except' => ''],
            'clientId' => ['except' => ''],
            'assignedTo' => ['except' => ''],
            'locationId' => ['except' => ''],
            'sortField' => ['except' => 'created_at'],
            'sortDirection' => ['except' => 'desc'],
            'perPage' => ['except' => 25],
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Asset::with(['client', 'assignedTo', 'location']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }

        if ($this->assignedTo) {
            $query->where('contact_id', $this->assignedTo);
        }

        if ($this->locationId) {
            $query->where('location_id', $this->locationId);
        }

        return $query;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedAssets = $this->getItems()->pluck('id')->toArray();
            $this->selected = $this->selectedAssets;
        } else {
            $this->selectedAssets = [];
            $this->selected = [];
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

    public function render()
    {
        $assets = $this->getItems();

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
            'statuses' => ['Active', 'Inactive', 'In Repair', 'Disposed', 'Lost', 'Stolen'],
        ]);
    }
}
