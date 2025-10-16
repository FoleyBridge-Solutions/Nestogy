<?php

namespace App\Livewire\Financial;

use App\Domains\Client\Models\Client;
use App\Models\PaymentApplication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class StatementIndex extends Component
{
    use WithPagination;

    public $client_id = '';

    public $start_date = '';

    public $end_date = '';

    public $view_mode = 'combined';

    public function mount()
    {
        $this->start_date = now()->subMonths(3)->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function updatedClientId()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function updatedViewMode()
    {
        $this->resetPage();
    }

    public function getClientsProperty()
    {
        return Client::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();
    }

    public function getStatementDataProperty()
    {
        $companyId = Auth::user()->company_id;

        $query = PaymentApplication::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['payment.client', 'applicable'])
            ->whereHas('payment')
            ->whereBetween('applied_date', [$this->start_date, $this->end_date]);

        if ($this->client_id) {
            $query->whereHas('payment', function ($q) {
                $q->where('client_id', $this->client_id);
            });
        }

        return $query->orderBy('applied_date', 'desc')->paginate(50);
    }

    public function render()
    {
        return view('livewire.financial.statement-index', [
            'applications' => $this->statementData,
            'clients' => $this->clients,
        ]);
    }
}
