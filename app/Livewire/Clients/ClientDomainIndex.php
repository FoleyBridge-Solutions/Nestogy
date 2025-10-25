<?php

namespace App\Livewire\Clients;

use App\Livewire\BaseIndexComponent;
use App\Domains\Client\Models\ClientDomain;
use Illuminate\Database\Eloquent\Builder;

class ClientDomainIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'expires_at', 'direction' => 'asc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'domain_name', 'registrar', 'dns_provider'];
    }

    protected function getColumns(): array
    {
        return [
            'domain' => [
                'label' => 'Domain Name',
                'sortable' => true,
                'filterable' => false,
            ],
            'registrar' => [
                'label' => 'Registrar',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'godaddy' => 'GoDaddy',
                    'namecheap' => 'Namecheap',
                    'cloudflare' => 'Cloudflare',
                    'other' => 'Other',
                ],
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'pending' => 'Pending',
                    'suspended' => 'Suspended',
                ],
                'component' => 'client.domains.cells.status',
            ],
            'expiry_date' => [
                'label' => 'Expiration Date',
                'sortable' => true,
                'type' => 'date',
                'component' => 'client.domains.cells.expiration',
            ],
            'registration_date' => [
                'label' => 'Registration Date',
                'sortable' => true,
                'type' => 'date',
            ],
            'auto_renew' => [
                'label' => 'Auto Renewal',
                'sortable' => true,
                'filterable' => true,
                'type' => 'badge',
                'component' => 'client.domains.cells.auto-renewal',
            ],
        ];
    }

    protected function getStats(): array
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        $baseQuery = ClientDomain::where('client_id', $clientId)->where('company_id', $this->companyId);

        $active = $baseQuery->clone()->where('status', 'active')->count();
        $expiringSoon = $baseQuery->clone()->where('expiry_date', '<=', now()->addDays(30))->where('expiry_date', '>', now())->count();
        $expired = $baseQuery->clone()->where('status', 'expired')->count();
        $pending = $baseQuery->clone()->where('status', 'pending')->count();

        return [
            ['label' => 'Active Domains', 'value' => $active, 'icon' => 'globe-alt', 'iconBg' => 'bg-green-500'],
            ['label' => 'Expiring Soon', 'value' => $expiringSoon, 'icon' => 'calendar', 'iconBg' => 'bg-amber-500'],
            ['label' => 'Expired', 'value' => $expired, 'icon' => 'x-circle', 'iconBg' => 'bg-red-500'],
            ['label' => 'Pending', 'value' => $pending, 'icon' => 'clock', 'iconBg' => 'bg-blue-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Domains',
            'message' => 'No domains found. Add your first domain to track registrations, renewals, and DNS records.',
            'icon' => 'globe-alt',
            'action' => [
                'label' => 'Add Domain',
                'href' => route('clients.domains.create'),
            ],
            'actionLabel' => 'Add Domain',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        return ClientDomain::where('client_id', $clientId);
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('clients.domains.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('clients.domains.edit', $item->id), 'icon' => 'pencil'],
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
