<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ContractIndex extends BaseIndexComponent
{
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
            'contract_number',
            'title',
            'description',
            'client.name',
        ];
    }

    protected function getColumns(): array
    {
        return [
            'contract_number' => [
                'label' => 'Contract #',
                'sortable' => true,
                'filterable' => false,
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => false,
            ],
            'title' => [
                'label' => 'Title',
                'sortable' => false,
                'filterable' => false,
            ],
            'contract_type' => [
                'label' => 'Type',
                'sortable' => false,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'contracts.cells.type',
            ],
            'contract_value' => [
                'label' => 'Value',
                'sortable' => true,
                'filterable' => false,
                'type' => 'currency',
            ],
            'start_date' => [
                'label' => 'Start Date',
                'sortable' => true,
                'filterable' => false,
                'type' => 'date',
            ],
            'end_date' => [
                'label' => 'End Date',
                'sortable' => true,
                'filterable' => false,
                'type' => 'date',
                'component' => 'contracts.cells.end-date',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'contracts.cells.status',
            ],
        ];
    }

    protected function getStats(): array
    {
        $stats = $this->calculateStatistics();

        return [
            [
                'label' => 'Draft',
                'value' => $stats['draft'],
            ],
            [
                'label' => 'Active',
                'value' => $stats['active'],
            ],
            [
                'label' => 'Pending Signature',
                'value' => $stats['pending_signature'],
            ],
            [
                'label' => 'Expiring Soon (30 days)',
                'value' => $stats['expiring_soon'],
            ],
            [
                'label' => 'Total Value (Active)',
                'value' => number_format($stats['total_value'], 2),
                'prefix' => '$',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'document-text',
            'title' => 'No contracts found',
            'message' => 'Create your first contract to get started',
            'action' => null,
            'actionLabel' => 'Create Contract',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $query = Contract::with(['client', 'createdBy']);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        return $query;
    }

    protected function calculateStatistics()
    {
        $user = Auth::user();
        $baseQuery = Contract::where('company_id', $user->company_id);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'pending_signature' => (clone $baseQuery)->where('signature_status', 'pending')->count(),
            'expiring_soon' => (clone $baseQuery)
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', now())
                ->whereDate('end_date', '<=', now()->addDays(30))
                ->count(),
            'total_value' => (clone $baseQuery)->where('status', 'active')->sum('contract_value'),
        ];
    }

    public function getRowActions($contract)
    {
        $actions = [
            [
                'href' => route('financial.contracts.show', $contract),
                'icon' => 'eye',
                'variant' => 'ghost',
                'label' => 'View',
            ],
        ];

        if ($contract->status === 'draft') {
            $actions[] = [
                'href' => route('financial.contracts.edit', $contract),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'label' => 'Edit',
            ];
        }

        return $actions;
    }
}
