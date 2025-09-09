<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Lazy;
use App\Models\Invoice;
use App\Models\Payment;
use App\Traits\LazyLoadable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Lazy]
class RevenueChart extends Component
{
    use LazyLoadable;
    public ?array $chartData = [];
    public string $period = 'month'; // month, quarter, year
    public bool $loading = true;
    public ?int $clientId = null;
    public bool $showComparison = true;
    
    public function mount(?int $clientId = null)
    {
        $this->clientId = $clientId;
        $this->trackLoadTime('mount');
        // Initialize with empty data structure to prevent JSON parse errors
        $this->chartData = [];
        $this->loading = true;
        $this->loadChartData();
    }
    
    #[On('refresh-revenue-chart')]
    public function loadChartData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        // Cache the chart data for better performance
        $cacheKey = "revenue_chart_{$companyId}_{$this->clientId}_{$this->period}";
        $cacheDuration = 300; // 5 minutes
        
        $this->chartData = Cache::remember($cacheKey, $cacheDuration, function() use ($companyId) {
            return $this->generateChartData($companyId);
        });
        
        $this->loading = false;
    }
    
    protected function generateChartData($companyId)
    {
        $data = [];
        
        // Get data based on period
        switch ($this->period) {
            case 'month':
                // Last 30 days
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $dayRevenue = $this->getDayRevenue($date, $companyId);
                    $lastYearRevenue = $this->getDayRevenue($date->copy()->subYear(), $companyId);
                    
                    $data[] = [
                        'date' => $date->toDateString(),
                        'revenue' => $dayRevenue,
                        'lastYear' => $lastYearRevenue,
                        'invoices' => $this->getDayInvoices($date, $companyId),
                        'payments' => $this->getDayPayments($date, $companyId),
                    ];
                }
                break;
                
            case 'quarter':
                // Last 3 months
                for ($i = 2; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthRevenue = $this->getMonthRevenue($month, $companyId);
                    $lastYearRevenue = $this->getMonthRevenue($month->copy()->subYear(), $companyId);
                    
                    $data[] = [
                        'date' => $month->format('Y-m'),
                        'revenue' => $monthRevenue,
                        'lastYear' => $lastYearRevenue,
                        'invoices' => $this->getMonthInvoices($month, $companyId),
                        'payments' => $this->getMonthPayments($month, $companyId),
                    ];
                }
                break;
                
            case 'year':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthRevenue = $this->getMonthRevenue($month, $companyId);
                    $lastYearRevenue = $this->getMonthRevenue($month->copy()->subYear(), $companyId);
                    
                    $data[] = [
                        'date' => $month->format('Y-m'),
                        'revenue' => $monthRevenue,
                        'lastYear' => $lastYearRevenue,
                        'invoices' => $this->getMonthInvoices($month, $companyId),
                        'payments' => $this->getMonthPayments($month, $companyId),
                    ];
                }
                break;
        }
        
        return $data;
    }
    
    /**
     * Livewire lifecycle hook that runs when the period property is updated
     * This works properly with wire:model.live binding
     */
    public function updatedPeriod($value)
    {
        if (in_array($value, ['month', 'quarter', 'year'])) {
            $this->loadChartData();
        }
    }
    
    public function toggleComparison()
    {
        $this->showComparison = !$this->showComparison;
    }
    
    protected function getDayRevenue($date, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereDate('created_at', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getDayInvoices($date, $companyId)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereDate('created_at', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getDayPayments($date, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereDate('created_at', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->count();
    }
    
    protected function getMonthRevenue($month, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getMonthInvoices($month, $companyId)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getMonthPayments($month, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->count();
    }
    
    public function exportChart()
    {
        $this->dispatch('export-chart', [
            'type' => 'revenue',
            'data' => $this->chartData,
            'period' => $this->period
        ]);
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.revenue-chart');
    }
}