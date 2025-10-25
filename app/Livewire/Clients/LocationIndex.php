<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Location;
use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use Illuminate\Database\Eloquent\Builder;

class LocationIndex extends BaseIndexComponent
{
    public $state = '';

    public $country = '';

    public $primaryOnly = false;

    protected function getDefaultSort(): array
    {
        return ['field' => 'primary', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'address', 'city', 'state', 'zip', 'country'];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'state' => ['except' => ''],
            'country' => ['except' => ''],
            'primaryOnly' => ['except' => false],
            'sortField' => ['except' => 'primary'],
            'sortDirection' => ['except' => 'desc'],
            'perPage' => ['except' => 20],
        ];
    }

    protected function getColumns(): array
    {
        $client = NavigationService::getSelectedClient();

        return [
            'name' => [
                'label' => 'Location',
                'sortable' => true,
                'filterable' => false,
            ],
            'address' => [
                'label' => 'Address',
                'sortable' => true,
                'filterable' => false,
            ],
            'contact.name' => [
                'label' => 'Contact',
                'sortable' => false,
                'filterable' => false,
            ],
            'phone' => [
                'label' => 'Phone',
                'sortable' => true,
                'filterable' => false,
            ],
            'primary' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    '1' => 'Primary',
                    '0' => 'Secondary',
                ],
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        $client = NavigationService::getSelectedClient();

        return [
            'icon' => 'map-pin',
            'title' => 'No Locations',
            'message' => 'Get started by adding your first location for ' . ($client->name ?? 'this client'),
            'action' => route('clients.locations.create', $client),
            'actionLabel' => 'Add First Location',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $client = NavigationService::getSelectedClient();

        if (! $client) {
            return Location::query()->whereRaw('1 = 0');
        }

        return Location::with(['contact:id,name,email,phone,title'])
            ->where('client_id', is_object($client) ? $client->id : $client);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->state) {
            $query->where('state', $this->state);
        }

        if ($this->country) {
            $query->where('country', $this->country);
        }

        if ($this->primaryOnly) {
            $query->where('primary', true);
        }

        return $query;
    }

    protected function applySorting($query)
    {
        $query->orderBy($this->sortField, $this->sortDirection);

        if ($this->sortField !== 'name') {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }

    protected function getRowActions($item)
    {
        $client = NavigationService::getSelectedClient();

        return [
            ['label' => 'View', 'href' => route('clients.locations.show', [$client, $item->id]), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('clients.locations.edit', [$client, $item->id]), 'icon' => 'pencil'],
            ['label' => 'Delete', 'wire:click' => 'confirmDelete('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger', 'wire:confirm' => 'Are you sure you want to delete this location?'],
        ];
    }

    public function confirmDelete($locationId)
    {
        $location = Location::find($locationId);
        $client = NavigationService::getSelectedClient();

        if ($location && $location->client_id === (is_object($client) ? $client->id : $client) && $location->company_id === $this->companyId) {
            $location->delete();
            session()->flash('success', "Location '{$location->name}' has been deleted.");
        }
    }
}
