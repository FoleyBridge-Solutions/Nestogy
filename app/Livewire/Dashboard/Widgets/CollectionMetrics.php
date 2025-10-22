<?php

namespace App\Livewire\Dashboard\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class CollectionMetrics extends Component
{
    public array $data = [];

    public bool $loading = true;

    public string $period = 'month';

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-collectionmetrics')]
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

        $totalInvoiced = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.date', '>=', $startDate)
            ->sum('invoices.amount');

        $totalPaid = DB::table('payments')
            ->where('payments.company_id', $companyId)
            ->where('payments.payment_date', '>=', $startDate)
            ->whereIn('payments.status', ['completed', 'partial_refund'])
            ->sum('payments.amount');

        $outstanding = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->whereNotIn('invoices.status', ['Paid', 'Cancelled'])
            ->sum('invoices.amount');

        $collectionRate = $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 1) : 0;

        $this->data = [
            'items' => [],
            'stats' => [
                [
                    'label' => 'Total Outstanding',
                    'value' => (float)$outstanding,
                    'type' => 'currency',
                    'icon' => 'exclamation-circle',
                ],
                [
                    'label' => 'Collection Rate',
                    'value' => $collectionRate,
                    'type' => 'percentage',
                    'icon' => 'chart-bar',
                ],
                [
                    'label' => 'Total Invoiced',
                    'value' => (float)$totalInvoiced,
                    'type' => 'currency',
                    'icon' => 'document-text',
                ],
                [
                    'label' => 'Total Collected',
                    'value' => (float)$totalPaid,
                    'type' => 'currency',
                    'icon' => 'currency-dollar',
                ],
            ],
        ];

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.collection-metrics');
    }
}
