<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use App\Domains\Financial\Models\Expense;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

class ExpenseIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return [
            'field' => 'expense_date',
            'direction' => 'desc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'description',
            'vendor',
            'reference_number',
            'client.name',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'sortField' => ['except' => 'expense_date'],
            'sortDirection' => ['except' => 'desc'],
            'perPage' => ['except' => 25],
        ];
    }

    protected function getColumns(): array
    {
        return [
            'expense_date' => [
                'label' => 'Date',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
            ],
            'vendor' => [
                'label' => 'Vendor',
                'sortable' => true,
                'filterable' => false,
            ],
            'description' => [
                'label' => 'Description',
                'sortable' => false,
                'filterable' => false,
                'component' => 'financial.expenses.cells.description',
            ],
            'category.name' => [
                'label' => 'Category',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'amount' => [
                'label' => 'Amount',
                'sortable' => true,
                'filterable' => true,
                'type' => 'currency',
                'filter_type' => 'numeric_range',
            ],
            'is_billable' => [
                'label' => 'Billable',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    '1' => 'Yes',
                    '0' => 'No',
                ],
                'component' => 'financial.expenses.cells.billable',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'draft' => 'Draft',
                    'pending_approval' => 'Pending Approval',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'paid' => 'Paid',
                    'invoiced' => 'Invoiced',
                ],
                'component' => 'financial.expenses.cells.status',
            ],
        ];
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;
        
        $baseQuery = Expense::where('company_id', $companyId);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            [
                'label' => 'Total',
                'value' => number_format((clone $baseQuery)->sum('amount'), 2),
                'prefix' => '$',
            ],
            [
                'label' => 'Pending Approval',
                'value' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
                'valueClass' => 'text-yellow-600',
            ],
            [
                'label' => 'This Month',
                'value' => number_format((clone $baseQuery)
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'), 2),
                'prefix' => '$',
            ],
            [
                'label' => 'Billable',
                'value' => number_format((clone $baseQuery)
                    ->where('is_billable', true)
                    ->sum('amount'), 2),
                'prefix' => '$',
                'valueClass' => 'text-green-600',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'receipt-percent',
            'title' => 'No expenses recorded',
            'message' => 'Start tracking expenses to manage your business costs',
            'action' => route('financial.expenses.create'),
            'actionLabel' => 'Add Expense',
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Expense::with(['category', 'client', 'submittedBy']);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        return $query;
    }

    public function getBulkActions()
    {
        return [
            [
                'method' => 'bulkApprove',
                'label' => 'Approve',
                'variant' => 'primary',
                'icon' => 'check',
            ],
            [
                'method' => 'bulkReject',
                'label' => 'Reject',
                'variant' => 'danger',
                'icon' => 'x-mark',
            ],
            [
                'method' => 'bulkDelete',
                'label' => 'Delete',
                'variant' => 'danger',
            ],
        ];
    }

    public function getRowActions($expense)
    {
        $actions = [
            [
                'href' => route('financial.expenses.show', $expense),
                'icon' => 'eye',
                'variant' => 'ghost',
                'label' => 'View',
            ],
        ];

        if ($expense->status === 'draft') {
            $actions[] = [
                'href' => route('financial.expenses.edit', $expense),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'label' => 'Edit',
            ];
        }

        if ($expense->canBeApproved()) {
            $actions[] = [
                'wire:click' => "approveExpense({$expense->id})",
                'wire:confirm' => 'Approve this expense?',
                'icon' => 'check',
                'variant' => 'ghost',
                'label' => 'Approve',
            ];
        }

        if ($expense->canBeRejected()) {
            $actions[] = [
                'wire:click' => "rejectExpense({$expense->id})",
                'wire:confirm' => 'Reject this expense?',
                'icon' => 'x-mark',
                'variant' => 'ghost',
                'label' => 'Reject',
            ];
        }

        return $actions;
    }

    public function approveExpense($expenseId)
    {
        $expense = Expense::where('company_id', Auth::user()->company_id)
            ->findOrFail($expenseId);

        if ($expense->approve(Auth::user())) {
            $this->dispatch('expense-updated');
            Flux::toast('Expense approved successfully.');
        } else {
            Flux::toast('Unable to approve this expense.', variant: 'danger');
        }
    }

    public function rejectExpense($expenseId)
    {
        $expense = Expense::where('company_id', Auth::user()->company_id)
            ->findOrFail($expenseId);

        if ($expense->reject(Auth::user(), 'Rejected from index page')) {
            $this->dispatch('expense-updated');
            Flux::toast('Expense rejected.', variant: 'warning');
        } else {
            Flux::toast('Unable to reject this expense.', variant: 'danger');
        }
    }

    public function bulkApprove()
    {
        $count = 0;
        foreach ($this->selected as $expenseId) {
            $expense = Expense::where('company_id', Auth::user()->company_id)
                ->find($expenseId);
            
            if ($expense && $expense->approve(Auth::user())) {
                $count++;
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} expense(s) approved successfully.");
        $this->dispatch('expense-updated');
    }

    public function bulkReject()
    {
        $count = 0;
        foreach ($this->selected as $expenseId) {
            $expense = Expense::where('company_id', Auth::user()->company_id)
                ->find($expenseId);
            
            if ($expense && $expense->reject(Auth::user(), 'Bulk rejected')) {
                $count++;
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} expense(s) rejected.", variant: 'warning');
        $this->dispatch('expense-updated');
    }

    public function bulkDelete()
    {
        $count = Expense::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'draft')
            ->delete();

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} expense(s) deleted successfully.", variant: 'danger');
        $this->dispatch('expense-deleted');
    }
}
