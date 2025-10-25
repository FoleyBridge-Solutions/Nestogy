<?php

namespace App\Livewire\Clients;

use App\Livewire\BaseIndexComponent;
use App\Domains\Client\Models\ClientCredential;
use Illuminate\Database\Eloquent\Builder;

class ClientCredentialIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'service_name', 'username', 'url'];
    }

    protected function getColumns(): array
    {
        return [
            'service_name' => [
                'label' => 'Service',
                'sortable' => true,
                'filterable' => false,
            ],
            'username' => [
                'label' => 'Username',
                'sortable' => true,
                'filterable' => false,
            ],
            'url' => [
                'label' => 'URL/Endpoint',
                'sortable' => true,
                'filterable' => false,
            ],
            'is_shared' => [
                'label' => 'Shared',
                'sortable' => true,
                'filterable' => true,
                'type' => 'badge',
                'component' => 'client.credentials.cells.status',
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date',
            ],
            'updated_at' => [
                'label' => 'Updated',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        $baseQuery = ClientCredential::where('client_id', $clientId)->where('company_id', $this->companyId);

        $total = $baseQuery->clone()->count();
        $shared = $baseQuery->clone()->where('is_shared', true)->count();
        $unshared = $total - $shared;

        return [
            ['label' => 'Total Credentials', 'value' => $total, 'icon' => 'key', 'iconBg' => 'bg-green-500'],
            ['label' => 'Shared', 'value' => $shared, 'icon' => 'share', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Private', 'value' => $unshared, 'icon' => 'lock-closed', 'iconBg' => 'bg-amber-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Credentials',
            'message' => 'No credentials found. Add credentials for services, accounts, and systems to keep them organized and secure.',
            'icon' => 'lock-closed',
            'action' => [
                'label' => 'Add Credential',
                'href' => route('clients.credentials.create'),
            ],
            'actionLabel' => 'Add Credential',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        return ClientCredential::where('client_id', $clientId);
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('clients.credentials.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('clients.credentials.edit', $item->id), 'icon' => 'pencil'],
            ['label' => 'Delete', 'wire:click' => 'deleteItem('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger'],
        ];
    }

    protected function getBulkActions()
    {
        return [
            ['label' => 'Delete', 'method' => 'bulkDelete', 'variant' => 'danger', 'confirm' => 'Are you sure you want to delete selected items?'],
        ];
    }
}
