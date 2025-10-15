<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $paymentMethodFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $sortBy = 'payment_date';

    public $sortDirection = 'desc';

    public $selected = [];

    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'paymentMethodFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortBy' => ['except' => 'payment_date'],
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

    public function updatingPaymentMethodFilter()
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
    public function payments()
    {
        $user = Auth::user();
        $query = Payment::with(['client', 'applications.applicable'])
            ->where('company_id', $user->company_id);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        if ($this->dateFrom) {
            $query->where('payment_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('payment_date', '<=', $this->dateTo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payment_reference', 'like', "%{$this->search}%")
                    ->orWhere('gateway_transaction_id', 'like', "%{$this->search}%")
                    ->orWhereHas('applications.applicable', function ($applicable) {
                        $applicable->where('number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('client', function ($client) {
                        $client->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->sortBy === 'client') {
            $query->leftJoin('clients', 'payments.client_id', '=', 'clients.id')
                ->orderBy('clients.name', $this->sortDirection)
                ->select('payments.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function totals()
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
            $this->selected = $this->payments->pluck('id')->map(fn($id) => (string) $id)->toArray();
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

    public function getStatusColorProperty()
    {
        return [
            'pending' => 'amber',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'zinc',
        ];
    }

    public function render()
    {
        return view('livewire.financial.payment-index');
    }
}
