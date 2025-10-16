<?php

namespace App\Livewire\Client;

use App\Livewire\BaseIndexComponent;
use App\Domains\Client\Models\ClientITDocumentation;
use Illuminate\Database\Eloquent\Builder;

class ClientITDocumentationIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'description', 'it_category'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Documentation Name',
                'sortable' => true,
                'filterable' => false,
            ],
            'it_category' => [
                'label' => 'Category',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'runbook' => 'Runbook',
                    'troubleshooting' => 'Troubleshooting',
                    'architecture' => 'Architecture',
                    'configuration' => 'Configuration',
                    'other' => 'Other',
                ],
                'component' => 'client.it-documentation.cells.category',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'active' => 'Active',
                    'draft' => 'Draft',
                    'archived' => 'Archived',
                ],
                'component' => 'client.it-documentation.cells.status',
            ],
            'access_level' => [
                'label' => 'Access Level',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'public' => 'Public',
                    'confidential' => 'Confidential',
                    'restricted' => 'Restricted',
                ],
                'component' => 'client.it-documentation.cells.access-level',
            ],
            'version' => [
                'label' => 'Version',
                'sortable' => true,
                'filterable' => false,
            ],
            'last_reviewed_at' => [
                'label' => 'Last Reviewed',
                'sortable' => true,
                'type' => 'date',
                'component' => 'client.it-documentation.cells.last-reviewed',
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        $baseQuery = ClientITDocumentation::where('client_id', $clientId)->where('company_id', $this->companyId);

        $active = $baseQuery->clone()->where('status', 'active')->count();
        $draft = $baseQuery->clone()->where('status', 'draft')->count();
        $total = $baseQuery->clone()->count();

        return [
            ['label' => 'Total Documentation', 'value' => $total, 'icon' => 'document-text', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Active', 'value' => $active, 'icon' => 'check-circle', 'iconBg' => 'bg-green-500'],
            ['label' => 'Draft', 'value' => $draft, 'icon' => 'pencil', 'iconBg' => 'bg-amber-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No IT Documentation',
            'message' => 'No IT documentation found. Create your first documentation to get started.',
            'icon' => 'document-text',
            'action' => [
                'label' => 'Create Documentation',
                'href' => route('clients.it-documentation.create'),
            ],
            'actionLabel' => 'Create Documentation',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;

        return ClientITDocumentation::where('client_id', $clientId);
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('clients.it-documentation.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('clients.it-documentation.edit', $item->id), 'icon' => 'pencil'],
            ['label' => 'Download', 'href' => route('clients.it-documentation.download', $item->id), 'icon' => 'arrow-down-tray'],
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
