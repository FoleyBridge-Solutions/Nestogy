<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Models\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function invoices()
    {
        $user = Auth::user();
        $query = Invoice::with(['client', 'category'])
            ->where('company_id', $user->company_id);

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply client filter from session if client is selected
        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            // If it's an object, get the ID; if it's already an ID, use it directly
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        // Apply date filters
        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', "%{$this->search}%")
                    ->orWhere('scope', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Apply sorting
        if ($this->sortBy === 'client') {
            $query->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
                ->orderBy('clients.name', $this->sortDirection)
                ->select('invoices.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function totals()
    {
        $user = Auth::user();
        $baseQuery = Invoice::where('company_id', $user->company_id);

        // Apply client filter from session if client is selected
        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            // If it's an object, get the ID; if it's already an ID, use it directly
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

    public function markAsPaid($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->findOrFail($invoiceId);

        if ($invoice->status === 'Sent') {
            $invoice->update([
                'status' => 'Paid',
                'paid_at' => now(),
            ]);

            $this->dispatch('invoice-updated');
            Flux::toast('Invoice marked as paid successfully.');
        }
    }

    public function markAsSent($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->findOrFail($invoiceId);

        if ($invoice->status === 'Draft') {
            $invoice->update([
                'status' => 'Sent',
                'sent_at' => now(),
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
                'cancelled_at' => now(),
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

    public function getStatusColorProperty()
    {
        return [
            'Draft' => 'zinc',
            'Sent' => 'blue',
            'Paid' => 'green',
            'Cancelled' => 'red',
            'Overdue' => 'amber',
        ];
    }

    public function render()
    {
        return view('livewire.financial.invoice-index');
    }
}
