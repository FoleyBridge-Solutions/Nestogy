<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return [
            'field' => 'payment_date',
            'direction' => 'desc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'payment_reference',
            'gateway_transaction_id',
            'client.name',
        ];
    }

    protected function getColumns(): array
    {
        return [
            'payment_reference' => [
                'label' => 'Reference',
                'sortable' => true,
                'filterable' => false,
                'component' => 'financial.payments.cells.reference',
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => false,
                'component' => 'financial.payments.cells.client',
            ],
            'amount' => [
                'label' => 'Amount',
                'sortable' => true,
                'filterable' => false,
                'type' => 'currency',
            ],
            'application_status' => [
                'label' => 'Application Status',
                'sortable' => false,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'financial.payments.cells.application-status',
            ],
            'payment_method' => [
                'label' => 'Payment Method',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'financial.payments.cells.payment-method',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'financial.payments.cells.status',
            ],
            'payment_date' => [
                'label' => 'Date',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $totals = $this->calculateTotals();

        return [
            [
                'label' => 'Total Revenue',
                'value' => number_format($totals['total_revenue'], 2),
                'prefix' => '$',
                'icon' => 'currency-dollar',
                'iconBg' => 'bg-green-500',
                'valueClass' => 'text-green-600',
            ],
            [
                'label' => 'This Month',
                'value' => number_format($totals['this_month'], 2),
                'prefix' => '$',
                'icon' => 'chart-bar',
                'iconBg' => 'bg-blue-500',
            ],
            [
                'label' => 'Pending',
                'value' => $totals['pending_count'],
                'icon' => 'clock',
                'iconBg' => 'bg-amber-500',
            ],
            [
                'label' => 'Failed',
                'value' => $totals['failed_count'],
                'icon' => 'x-circle',
                'iconBg' => 'bg-red-500',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'currency-dollar',
            'title' => 'No payments found',
            'message' => 'Get started by creating a new payment.',
            'action' => route('financial.payments.create'),
            'actionLabel' => 'Add Payment',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        $query = Payment::with(['client', 'applications.applicable']);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        return $query;
    }

    protected function calculateTotals()
    {
        $user = Auth::user();
        $baseQuery = Payment::where('company_id', $user->company_id);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            'total_revenue' => (clone $baseQuery)->where('status', 'completed')->sum('amount'),
            'this_month' => (clone $baseQuery)
                ->where('status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_count' => (clone $baseQuery)->where('status', 'failed')->count(),
        ];
    }

    public function getRowActions($payment)
    {
        $actions = [
            [
                'href' => route('financial.payments.show', $payment),
                'icon' => 'eye',
                'variant' => 'ghost',
                'title' => 'View',
            ],
        ];

        if (in_array($payment->status, ['pending', 'failed'])) {
            $actions[] = [
                'href' => route('financial.payments.edit', $payment),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'title' => 'Edit',
            ];

            $actions[] = [
                'wire:click' => "deletePayment({$payment->id})",
                'wire:confirm' => 'Are you sure you want to delete this payment? This action cannot be undone.',
                'icon' => 'trash',
                'variant' => 'ghost',
                'class' => 'text-red-600 hover:text-red-700',
                'title' => 'Delete',
            ];
        }

        return $actions;
    }

    public function deletePayment($paymentId)
    {
        $payment = Payment::where('company_id', Auth::user()->company_id)
            ->findOrFail($paymentId);

        if (in_array($payment->status, ['pending', 'failed'])) {
            $payment->delete();

            $this->dispatch('payment-deleted');
            Flux::toast('Payment deleted successfully.', variant: 'danger');
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getItems()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function bulkDelete()
    {
        $count = Payment::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->whereIn('status', ['pending', 'failed'])
            ->delete();

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} payment(s) deleted successfully.", variant: 'danger');
        $this->dispatch('payment-deleted');
    }
}
