<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use App\Domains\Financial\Models\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

class InvoiceIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return [
            'field' => 'due_date',
            'direction' => 'desc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'number',
            'scope',
            'description',
            'client.name',
        ];
    }

    protected function getColumns(): array
    {
        return [
            'number' => [
                'label' => 'Invoice #',
                'sortable' => true,
                'filterable' => false,
                'component' => 'financial.invoices.cells.number',
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => false,
                'component' => 'financial.invoices.cells.client',
            ],
            'amount' => [
                'label' => 'Amount',
                'sortable' => true,
                'filterable' => true,
                'type' => 'currency',
                'filter_type' => 'numeric_range',
                'prefix' => '$',
                'step' => '0.01',
            ],
            'balance' => [
                'label' => 'Balance',
                'sortable' => false,
                'filterable' => false,
                'component' => 'financial.invoices.cells.balance',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'financial.invoices.cells.status',
            ],
            'date' => [
                'label' => 'Date',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
            ],
            'due_date' => [
                'label' => 'Due Date',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
                'component' => 'financial.invoices.cells.due-date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $totals = $this->calculateTotals();

        return [
            [
                'label' => 'Draft',
                'value' => number_format($totals['draft'], 2),
                'prefix' => '$',
            ],
            [
                'label' => 'Awaiting Payment',
                'value' => number_format($totals['sent'], 2),
                'prefix' => '$',
            ],
            [
                'label' => 'Paid',
                'value' => number_format($totals['paid'], 2),
                'prefix' => '$',
                'valueClass' => 'text-green-600',
            ],
            [
                'label' => 'Overdue',
                'value' => number_format($totals['overdue'], 2),
                'prefix' => '$',
                'valueClass' => 'text-red-600',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'document-text',
            'title' => 'Create your first invoice',
            'message' => 'Start billing your clients by creating an invoice',
            'action' => route('financial.invoices.create'),
            'actionLabel' => 'Create Invoice',
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Invoice::with(['client', 'category', 'activePaymentApplications', 'activeCreditApplications']);

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
        $baseQuery = Invoice::where('company_id', $user->company_id);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            'draft' => (clone $baseQuery)->where('status', 'Draft')->sum('amount'),
            'sent' => (clone $baseQuery)->where('status', 'Sent')->sum('amount'),
            'paid' => (clone $baseQuery)->where('status', 'Paid')->sum('amount'),
            'overdue' => (clone $baseQuery)
                ->where('status', 'Sent')
                ->whereDate('due_date', '<', now())
                ->sum('amount'),
        ];
    }

    public function getBulkActions()
    {
        return [
            [
                'method' => 'bulkDownloadPdf',
                'label' => 'Download PDFs',
                'variant' => 'primary',
                'icon' => 'arrow-down-tray',
            ],
            [
                'method' => 'bulkMarkAsSent',
                'label' => 'Mark as Sent',
                'variant' => 'ghost',
            ],
            [
                'method' => 'bulkCancel',
                'label' => 'Cancel',
                'variant' => 'ghost',
            ],
            [
                'method' => 'bulkDelete',
                'label' => 'Delete',
                'variant' => 'danger',
            ],
        ];
    }

    public function getRowActions($invoice)
    {
        $actions = [
            [
                'href' => route('financial.invoices.show', $invoice),
                'icon' => 'eye',
                'variant' => 'ghost',
                'label' => 'View',
            ],
            [
                'href' => route('financial.invoices.pdf', $invoice),
                'icon' => 'arrow-down-tray',
                'variant' => 'ghost',
                'label' => 'Download PDF',
            ],
        ];

        if ($invoice->status === 'Draft') {
            $actions[] = [
                'href' => route('financial.invoices.edit', $invoice),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'label' => 'Edit',
            ];

            $actions[] = [
                'wire:click' => "markAsSent({$invoice->id})",
                'wire:confirm' => 'Mark this invoice as sent?',
                'icon' => 'paper-airplane',
                'variant' => 'ghost',
                'class' => 'text-blue-600 hover:text-blue-700',
                'label' => 'Mark as Sent',
            ];
        }

        return $actions;
    }

    public function markAsSent($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->findOrFail($invoiceId);

        if ($invoice->status === 'Draft') {
            $invoice->update([
                'status' => 'Sent',
            ]);

            $this->dispatch('invoice-updated');
            Flux::toast('Invoice marked as sent successfully.');
        }
    }

    public function cancelInvoice($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->findOrFail($invoiceId);

        if (in_array($invoice->status, ['Draft', 'Sent'])) {
            $invoice->update([
                'status' => 'Cancelled',
            ]);

            $this->dispatch('invoice-updated');
            Flux::toast('Invoice cancelled successfully.', variant: 'warning');
        }
    }

    public function deleteInvoice($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->findOrFail($invoiceId);

        if ($invoice->status === 'Draft') {
            $invoice->delete();

            $this->dispatch('invoice-deleted');
            Flux::toast('Invoice deleted successfully.', variant: 'danger');
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

    public function bulkMarkAsSent()
    {
        $count = Invoice::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'Draft')
            ->update([
                'status' => 'Sent',
            ]);

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} invoice(s) marked as sent successfully.");
        $this->dispatch('invoice-updated');
    }

    public function bulkCancel()
    {
        $count = Invoice::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->whereIn('status', ['Draft', 'Sent'])
            ->update([
                'status' => 'Cancelled',
            ]);

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} invoice(s) cancelled successfully.", variant: 'warning');
        $this->dispatch('invoice-updated');
    }

    public function bulkDelete()
    {
        $count = Invoice::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'Draft')
            ->delete();

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} invoice(s) deleted successfully.", variant: 'danger');
        $this->dispatch('invoice-deleted');
    }

    public function bulkDownloadPdf()
    {
        if (empty($this->selected)) {
            Flux::toast('Please select at least one invoice to download.', variant: 'warning');

            return;
        }

        $invoices = Invoice::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->get();

        if ($invoices->count() === 1) {
            return redirect()->route('financial.invoices.pdf', $invoices->first());
        }

        $this->dispatch('bulk-download-pdf', invoiceIds: $this->selected);
        Flux::toast("Preparing {$invoices->count()} PDF(s) for download...");
    }
}
