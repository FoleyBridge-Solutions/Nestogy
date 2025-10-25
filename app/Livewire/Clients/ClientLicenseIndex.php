<?php

namespace App\Livewire\Clients;

use App\Livewire\BaseIndexComponent;
use App\Domains\Client\Models\ClientLicense;
use Illuminate\Database\Eloquent\Builder;

class ClientLicenseIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'expiry_date', 'direction' => 'asc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'vendor', 'license_key', 'license_type'];
    }

    protected function getColumns(): array
    {
        return [
            'software_name' => [
                'label' => 'License Name',
                'sortable' => true,
                'filterable' => false,
            ],
            'version' => [
                'label' => 'Version',
                'sortable' => true,
                'filterable' => false,
            ],
            'seats' => [
                'label' => 'Seats',
                'sortable' => true,
                'filterable' => false,
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                ],
                'component' => 'client.licenses.cells.status',
            ],
            'expiry_date' => [
                'label' => 'Expiry Date',
                'sortable' => true,
                'type' => 'date',
                'component' => 'client.licenses.cells.expiry',
            ],
            'purchase_date' => [
                'label' => 'Purchase Date',
                'sortable' => true,
                'type' => 'date',
            ],
            'cost' => [
                'label' => 'Cost',
                'sortable' => true,
                'type' => 'currency',
                'prefix' => '$',
            ],
        ];
    }

    protected function getStats(): array
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        $baseQuery = ClientLicense::where('client_id', $clientId)->where('company_id', $this->companyId);

        $active = $baseQuery->clone()->where('status', 'active')->count();
        $expiringSoon = $baseQuery->clone()->where('status', '!=', 'expired')->where('expiry_date', '<=', now()->addDays(90))->where('expiry_date', '>', now())->count();
        $expired = $baseQuery->clone()->where('status', 'expired')->count();
        $totalCost = $baseQuery->clone()->sum('cost');

        return [
            ['label' => 'Active Licenses', 'value' => $active, 'icon' => 'receipt', 'iconBg' => 'bg-green-500'],
            ['label' => 'Expiring Soon', 'value' => $expiringSoon, 'icon' => 'calendar', 'iconBg' => 'bg-amber-500'],
            ['label' => 'Expired', 'value' => $expired, 'icon' => 'x-circle', 'iconBg' => 'bg-red-500'],
            ['label' => 'Total Investment', 'value' => $totalCost, 'prefix' => '$', 'icon' => 'currency-dollar', 'iconBg' => 'bg-blue-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Licenses',
            'message' => 'No licenses found. Add software and service licenses to track expiration dates, renewal costs, and compliance.',
            'icon' => 'document-check',
            'action' => [
                'label' => 'Add License',
                'href' => route('clients.licenses.create'),
            ],
            'actionLabel' => 'Add License',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        return ClientLicense::where('client_id', $clientId);
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('clients.licenses.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('clients.licenses.edit', $item->id), 'icon' => 'pencil'],
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
