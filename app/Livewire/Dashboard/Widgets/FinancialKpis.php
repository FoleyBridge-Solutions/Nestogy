<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Recurring;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialKpis extends Component
{
    public array $kpis = [];
    public bool $loading = true;
    protected ?string $revenueRecognitionMethod = null;
    
    public function mount()
    {
        $this->loadFinancialKpis();
    }
    
    #[On('refresh-financial-kpis')]
    public function loadFinancialKpis()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        // Monthly Recurring Revenue
        // Calculate MRR based on frequency
        $recurringItems = Recurring::where('company_id', $companyId)
            ->where('status', true)
            ->get();
        
        $mrr = $recurringItems->reduce(function ($total, $item) {
            // Convert to monthly amount based on frequency
            return $total + match(strtolower($item->frequency)) {
                'monthly' => $item->amount,
                'quarterly' => $item->amount / 3,
                'yearly', 'annual' => $item->amount / 12,
                'weekly' => $item->amount * 4.33, // Average weeks per month
                'daily' => $item->amount * 30,
                default => 0
            };
        }, 0);
        
        // Annual Recurring Revenue
        $arr = $mrr * 12;
        
        // Total Revenue This Month
        [$monthlyRevenue, $previousRevenue] = $this->calculateMonthlyRevenue($companyId);

        // Outstanding Invoices
        $outstanding = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['Sent', 'Viewed', 'Partial'])
            ->sum('amount') ?? 0;
        
        // Average Invoice Value
        $avgInvoice = Invoice::where('company_id', $companyId)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->avg('amount') ?? 0;
        
        // Customer Count
        $activeClients = Client::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();
        
        // Calculate Churn Rate from actual client data
        $totalClientsStartOfMonth = Client::where('company_id', $companyId)
            ->where('created_at', '<', Carbon::now()->startOfMonth())
            ->count();
        
        $churnedThisMonth = Client::where('company_id', $companyId)
            ->onlyTrashed()
            ->whereMonth('deleted_at', Carbon::now()->month)
            ->whereYear('deleted_at', Carbon::now()->year)
            ->count();
        
        $churnRate = $totalClientsStartOfMonth > 0 ? 
            round(($churnedThisMonth / $totalClientsStartOfMonth) * 100, 1) : 0;
        
        // Collection Rate
        $totalInvoiced = Invoice::where('company_id', $companyId)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount') ?? 1;
        $totalCollected = Payment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNotNull('payment_date')
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount') ?? 0;
        $collectionRate = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
        
        // Calculate trends by comparing with previous month
        $lastMonthMRR = $this->calculateLastMonthMRR($companyId);
        $mrrTrend = $this->calculateTrend($mrr, $lastMonthMRR);
        
        $revenueTrend = $this->calculateTrend($monthlyRevenue, $previousRevenue);
        
        $lastMonthOutstanding = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['Sent', 'Viewed', 'Partial'])
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('amount') ?? 0;
        $outstandingTrend = $this->calculateTrend($outstanding, $lastMonthOutstanding);
        
        $lastMonthAvgInvoice = Invoice::where('company_id', $companyId)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->avg('amount') ?? 0;
        $avgInvoiceTrend = $this->calculateTrend($avgInvoice, $lastMonthAvgInvoice);
        
        $newClientsThisMonth = Client::where('company_id', $companyId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
        
        $lastMonthChurnRate = $this->calculateLastMonthChurnRate($companyId);
        $churnTrend = $this->calculateTrend($churnRate, $lastMonthChurnRate);
        
        $lastMonthCollectionRate = $this->calculateLastMonthCollectionRate($companyId);
        $collectionTrend = $this->calculateTrend($collectionRate, $lastMonthCollectionRate);
        
        $this->kpis = [
            ['label' => 'MRR', 'value' => $mrr, 'format' => 'currency', 'icon' => 'chart-bar', 'color' => 'green', 
             'trend' => $mrrTrend >= 0 ? 'up' : 'down', 'trendValue' => ($mrrTrend >= 0 ? '+' : '') . $mrrTrend . '%'],
            ['label' => 'ARR', 'value' => $arr, 'format' => 'currency', 'icon' => 'arrow-trending-up', 'color' => 'blue', 
             'trend' => $mrrTrend >= 0 ? 'up' : 'down', 'trendValue' => ($mrrTrend >= 0 ? '+' : '') . $mrrTrend . '%'],
            ['label' => 'Monthly Revenue', 'value' => $monthlyRevenue, 'format' => 'currency', 'icon' => 'currency-dollar', 'color' => 'purple', 
             'trend' => $revenueTrend >= 0 ? 'up' : 'down', 'trendValue' => ($revenueTrend >= 0 ? '+' : '') . $revenueTrend . '%', 'previousValue' => $previousRevenue],
            ['label' => 'Outstanding', 'value' => $outstanding, 'format' => 'currency', 'icon' => 'clock', 'color' => 'orange', 
             'trend' => $outstandingTrend <= 0 ? 'down' : 'up', 'trendValue' => ($outstandingTrend >= 0 ? '+' : '') . $outstandingTrend . '%'],
            ['label' => 'Avg Invoice', 'value' => $avgInvoice, 'format' => 'currency', 'icon' => 'document-text', 'color' => 'indigo', 
             'trend' => $avgInvoiceTrend >= 0 ? 'up' : 'down', 'trendValue' => ($avgInvoiceTrend >= 0 ? '+' : '') . $avgInvoiceTrend . '%'],
            ['label' => 'Active Clients', 'value' => $activeClients, 'format' => 'number', 'icon' => 'user-group', 'color' => 'teal', 
             'trend' => $newClientsThisMonth > 0 ? 'up' : 'stable', 'trendValue' => $newClientsThisMonth > 0 ? '+' . $newClientsThisMonth : '0'],
            ['label' => 'Churn Rate', 'value' => $churnRate, 'format' => 'percentage', 'icon' => 'arrow-trending-down', 'color' => 'red', 
             'trend' => $churnTrend <= 0 ? 'down' : 'up', 'trendValue' => ($churnTrend >= 0 ? '+' : '') . $churnTrend . '%'],
            ['label' => 'Collection Rate', 'value' => round($collectionRate, 1), 'format' => 'percentage', 'icon' => 'check-circle', 'color' => 'green', 
             'trend' => $collectionTrend >= 0 ? 'up' : 'down', 'trendValue' => ($collectionTrend >= 0 ? '+' : '') . $collectionTrend . '%'],
        ];
        
        $this->loading = false;
    }

    protected function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    protected function calculateLastMonthMRR($companyId)
    {
        // Get recurring items that were active last month
        $lastMonth = Carbon::now()->subMonth();
        $recurringItems = Recurring::where('company_id', $companyId)
            ->where('status', true)
            ->where('created_at', '<=', $lastMonth->endOfMonth())
            ->get();
        
        return $recurringItems->reduce(function ($total, $item) {
            return $total + match(strtolower($item->frequency)) {
                'monthly' => $item->amount,
                'quarterly' => $item->amount / 3,
                'yearly', 'annual' => $item->amount / 12,
                'weekly' => $item->amount * 4.33,
                'daily' => $item->amount * 30,
                default => 0
            };
        }, 0);
    }
    
    protected function calculateLastMonthChurnRate($companyId)
    {
        $lastMonth = Carbon::now()->subMonth();
        $totalClientsStartOfLastMonth = Client::where('company_id', $companyId)
            ->where('created_at', '<', $lastMonth->startOfMonth())
            ->count();
        
        $churnedLastMonth = Client::where('company_id', $companyId)
            ->onlyTrashed()
            ->whereMonth('deleted_at', $lastMonth->month)
            ->whereYear('deleted_at', $lastMonth->year)
            ->count();
        
        return $totalClientsStartOfLastMonth > 0 ? 
            round(($churnedLastMonth / $totalClientsStartOfLastMonth) * 100, 1) : 0;
    }
    
    protected function calculateLastMonthCollectionRate($companyId)
    {
        $lastMonth = Carbon::now()->subMonth();
        $totalInvoiced = Invoice::where('company_id', $companyId)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('amount') ?? 1;
            
        $totalCollected = Payment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNotNull('payment_date')
            ->whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->sum('amount') ?? 0;
            
        return $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
    }

    protected function calculateMonthlyRevenue(int $companyId): array
    {
        $method = $this->getRevenueRecognitionMethod();
        $now = Carbon::now();

        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->endOfMonth();
        $previousStart = $now->copy()->subMonth()->startOfMonth();
        $previousEnd = $now->copy()->subMonth()->endOfMonth();

        if ($method === 'cash') {
            $current = Payment::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereNotNull('payment_date')
                ->whereBetween('payment_date', [$currentStart, $currentEnd])
                ->sum('amount');

            $previous = Payment::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereNotNull('payment_date')
                ->whereBetween('payment_date', [$previousStart, $previousEnd])
                ->sum('amount');
        } else {
            $current = Invoice::where('company_id', $companyId)
                ->where('status', Invoice::STATUS_PAID)
                ->whereBetween('date', [$currentStart, $currentEnd])
                ->sum('amount');

            $previous = Invoice::where('company_id', $companyId)
                ->where('status', Invoice::STATUS_PAID)
                ->whereBetween('date', [$previousStart, $previousEnd])
                ->sum('amount');
        }

        return [(float) $current, (float) $previous];
    }

    protected function getRevenueRecognitionMethod(): string
    {
        if ($this->revenueRecognitionMethod !== null) {
            return $this->revenueRecognitionMethod;
        }

        $settings = optional(Auth::user()->company->setting);
        $method = data_get($settings?->revenue_recognition_settings, 'method');

        return $this->revenueRecognitionMethod = in_array($method, ['cash', 'accrual'], true) ? $method : 'accrual';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.financial-kpis');
    }
}
