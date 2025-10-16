<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Models\ClientCredit;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ClientCreditIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $typeFilter = '';

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public $selected = [];

    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
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

    public function updatingTypeFilter()
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
    public function credits()
    {
        $user = Auth::user();
        $query = ClientCredit::with(['client', 'applications'])
            ->where('company_id', $user->company_id);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', "%{$this->search}%")
                    ->orWhereHas('client', function ($client) {
                        $client->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->sortBy === 'client') {
            $query->leftJoin('clients', 'client_credits.client_id', '=', 'clients.id')
                ->orderBy('clients.name', $this->sortDirection)
                ->select('client_credits.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function totals()
    {
        $user = Auth::user();
        $baseQuery = ClientCredit::where('company_id', $user->company_id);

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $baseQuery = $baseQuery->where('client_id', $clientId);
        }

        return [
            'total_amount' => (clone $baseQuery)->where('status', 'active')->sum('amount'),
            'available_amount' => (clone $baseQuery)->where('status', 'active')->sum('available_amount'),
            'active_count' => (clone $baseQuery)->where('status', 'active')->count(),
            'expired_count' => (clone $baseQuery)->where('status', 'expired')->count(),
        ];
    }

    public function voidCredit($creditId)
    {
        $credit = ClientCredit::where('company_id', Auth::user()->company_id)
            ->findOrFail($creditId);

        if ($credit->status === 'active') {
            $creditService = app(\App\Domains\Financial\Services\ClientCreditService::class);
            $creditService->voidCredit($credit, 'Voided by user');

            $this->dispatch('credit-voided');
            Flux::toast('Credit voided successfully.', variant: 'warning');
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->credits->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function bulkVoid()
    {
        $creditService = app(\App\Domains\Financial\Services\ClientCreditService::class);

        $count = 0;
        foreach (ClientCredit::whereIn('id', $this->selected)
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get() as $credit) {
            $creditService->voidCredit($credit, 'Bulk voided by user');
            $count++;
        }

        $this->selected = [];
        $this->selectAll = false;

        Flux::toast("{$count} credit(s) voided successfully.", variant: 'warning');
        $this->dispatch('credit-voided');
    }

    public function getStatusColorProperty()
    {
        return [
            'active' => 'green',
            'depleted' => 'zinc',
            'expired' => 'amber',
            'voided' => 'red',
        ];
    }

    public function render()
    {
        return view('livewire.financial.client-credit-index');
    }
}
