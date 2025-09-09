<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoiceStatus extends Component
{
    public array $statusData = [];
    public array $agingData = [];
    public bool $loading = true;
    
    public function mount()
    {
        $this->loadInvoiceData();
    }
    
    #[On('refresh-invoice-status')]
    public function loadInvoiceData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        // Get invoice counts by status
        $statusCounts = Invoice::where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('status')
            ->get();
        
        $this->statusData = [
            'draft' => ['count' => 0, 'total' => 0],
            'sent' => ['count' => 0, 'total' => 0],
            'viewed' => ['count' => 0, 'total' => 0],
            'partial' => ['count' => 0, 'total' => 0],
            'paid' => ['count' => 0, 'total' => 0],
            'overdue' => ['count' => 0, 'total' => 0],
        ];
        
        foreach ($statusCounts as $status) {
            $key = strtolower($status->status);
            if (isset($this->statusData[$key])) {
                $this->statusData[$key] = [
                    'count' => $status->count,
                    'total' => $status->total ?? 0
                ];
            }
        }
        
        // Calculate aging - using amount minus total payments as balance
        $now = Carbon::now();
        $this->agingData = [
            'current' => $this->calculateBalanceSum($companyId, ['Sent', 'Viewed', 'Partial'], [['due_date', '>=', $now]]),
            '1-30' => $this->calculateBalanceSum($companyId, ['Sent', 'Viewed', 'Partial'], [
                ['due_date', '<', $now],
                ['due_date', '>=', $now->copy()->subDays(30)]
            ]),
            '31-60' => $this->calculateBalanceSum($companyId, ['Sent', 'Viewed', 'Partial'], [
                ['due_date', '<', $now->copy()->subDays(30)],
                ['due_date', '>=', $now->copy()->subDays(60)]
            ]),
            '61-90' => $this->calculateBalanceSum($companyId, ['Sent', 'Viewed', 'Partial'], [
                ['due_date', '<', $now->copy()->subDays(60)],
                ['due_date', '>=', $now->copy()->subDays(90)]
            ]),
            '90+' => $this->calculateBalanceSum($companyId, ['Sent', 'Viewed', 'Partial'], [
                ['due_date', '<', $now->copy()->subDays(90)]
            ]),
        ];
        
        $this->loading = false;
    }

    /**
     * Calculate the sum of outstanding balances for invoices matching the criteria.
     */
    private function calculateBalanceSum(int $companyId, array $statuses, array $whereConditions): float
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereIn('status', $statuses);
        
        foreach ($whereConditions as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }
        
        return $query->get()->sum(function ($invoice) {
            return $invoice->getBalance();
        });
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.invoice-status');
    }
}
