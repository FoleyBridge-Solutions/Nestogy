<?php

namespace App\Livewire\Dashboard\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentTracking extends Component
{
    public array $data = [];

    public bool $loading = true;

    public string $period = 'month';

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-paymenttracking')]
    public function loadData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        $startDate = match ($this->period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            default => Carbon::now()->startOfMonth()
        };

        $payments = DB::table('payments')
            ->where('payments.company_id', $companyId)
            ->where('payments.payment_date', '>=', $startDate)
            ->whereIn('payments.status', ['completed', 'partial_refund'])
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->select('payments.id', 'payments.amount', 'payments.payment_date', 'payments.status', 'clients.name as client_name', 'payments.payment_method')
            ->orderByDesc('payments.payment_date')
            ->limit(10)
            ->get();

        $totalCollected = DB::table('payments')
            ->where('payments.company_id', $companyId)
            ->where('payments.payment_date', '>=', $startDate)
            ->whereIn('payments.status', ['completed', 'partial_refund'])
            ->sum('payments.amount');

        $this->data = [
            'items' => $payments->map(fn($p) => [
                'id' => $p->id,
                'client' => $p->client_name,
                'description' => 'Payment from ' . $p->client_name,
                'amount' => (float)$p->amount,
                'date' => Carbon::parse($p->payment_date)->format('M d, Y'),
                'method' => $p->payment_method,
                'status' => $p->status,
            ])->toArray(),
            'stats' => [
                [
                    'label' => 'Total Collected',
                    'value' => (float)$totalCollected,
                    'type' => 'currency',
                    'icon' => 'currency-dollar',
                ],
                [
                    'label' => 'Payment Count',
                    'value' => $payments->count(),
                    'type' => 'number',
                    'icon' => 'document-text',
                ],
            ],
        ];

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.payment-tracking');
    }
}
