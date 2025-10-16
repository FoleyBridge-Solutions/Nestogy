<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuoteIndex extends BaseIndexComponent
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
            'quote_number',
            'title',
            'notes',
            'client.name',
        ];
    }

    protected function getColumns(): array
    {
        return [
            'quote_number' => [
                'label' => 'Quote #',
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
            'amount' => [
                'label' => 'Amount',
                'sortable' => true,
                'filterable' => false,
                'type' => 'currency',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'financial.quotes.cells.status',
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'filterable' => false,
                'type' => 'date',
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
                'label' => 'Sent',
                'value' => $stats['sent'],
            ],
            [
                'label' => 'Accepted',
                'value' => $stats['accepted'],
            ],
            [
                'label' => 'Rejected',
                'value' => $stats['rejected'],
            ],
            [
                'label' => 'Total Value (Accepted)',
                'value' => number_format($stats['total_value'], 2),
                'prefix' => '$',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'document-text',
            'title' => 'No quotes found',
            'message' => 'Create your first quote to get started',
            'action' => null,
            'actionLabel' => 'Create Quote',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $query = Quote::with(['client', 'category']);

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
        $baseQuery = Quote::where('company_id', $user->company_id);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
            'accepted' => (clone $baseQuery)->where('status', 'accepted')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total_value' => (clone $baseQuery)->where('status', 'accepted')->sum('amount'),
        ];
    }

    public function getRowActions($quote)
    {
        $actions = [
            [
                'href' => route('financial.quotes.show', $quote),
                'icon' => 'eye',
                'variant' => 'ghost',
                'title' => 'View',
            ],
        ];

        if ($quote->status === 'draft') {
            $actions[] = [
                'href' => route('financial.quotes.edit', $quote),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'title' => 'Edit',
            ];
        }

        return $actions;
    }
}
