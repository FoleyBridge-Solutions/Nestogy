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
use App\Domains\Core\Services\DashboardCacheService;
use Illuminate\Support\Facades\DB;

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
        
        // Get data based on period - optimized to use bulk queries
        switch ($this->period) {
            case 'month':
                // Get all data in bulk queries instead of loops
                $startDate = Carbon::now()->subDays(29);
                $endDate = Carbon::now();
                $lastYearStart = $startDate->copy()->subYear();
                $lastYearEnd = $endDate->copy()->subYear();
                
                // Bulk fetch all revenue data
                $currentRevenues = $this->getBulkDailyRevenue($startDate, $endDate, $companyId);
                $lastYearRevenues = $this->getBulkDailyRevenue($lastYearStart, $lastYearEnd, $companyId);
                $currentInvoices = $this->getBulkDailyInvoices($startDate, $endDate, $companyId);
                $currentPayments = $this->getBulkDailyPayments($startDate, $endDate, $companyId);
                
                // Build the data array
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $dateStr = $date->toDateString();
                    $lastYearDateStr = $date->copy()->subYear()->toDateString();
                    
                    $data[] = [
                        'date' => $dateStr,
                        'revenue' => $currentRevenues[$dateStr] ?? 0,
                        'lastYear' => $lastYearRevenues[$lastYearDateStr] ?? 0,
                        'invoices' => $currentInvoices[$dateStr] ?? 0,
                        'payments' => $currentPayments[$dateStr] ?? 0,
                    ];
                }
                break;
                
            case 'quarter':
                // Get all data in bulk for 3 months
                $months = [];
                for ($i = 2; $i >= 0; $i--) {
                    $months[] = Carbon::now()->subMonths($i);
                }
                
                $currentRevenues = $this->getBulkMonthlyRevenue($months, $companyId);
                $lastYearMonths = array_map(fn($m) => $m->copy()->subYear(), $months);
                $lastYearRevenues = $this->getBulkMonthlyRevenue($lastYearMonths, $companyId);
                $currentInvoices = $this->getBulkMonthlyInvoices($months, $companyId);
                $currentPayments = $this->getBulkMonthlyPayments($months, $companyId);
                
                foreach ($months as $month) {
                    $monthKey = $month->format('Y-m');
                    $lastYearKey = $month->copy()->subYear()->format('Y-m');
                    
                    $data[] = [
                        'date' => $monthKey,
                        'revenue' => $currentRevenues[$monthKey] ?? 0,
                        'lastYear' => $lastYearRevenues[$lastYearKey] ?? 0,
                        'invoices' => $currentInvoices[$monthKey] ?? 0,
                        'payments' => $currentPayments[$monthKey] ?? 0,
                    ];
                }
                break;
                
            case 'year':
                // Get all data in bulk for 12 months
                $months = [];
                for ($i = 11; $i >= 0; $i--) {
                    $months[] = Carbon::now()->subMonths($i);
                }
                
                $currentRevenues = $this->getBulkMonthlyRevenue($months, $companyId);
                $lastYearMonths = array_map(fn($m) => $m->copy()->subYear(), $months);
                $lastYearRevenues = $this->getBulkMonthlyRevenue($lastYearMonths, $companyId);
                $currentInvoices = $this->getBulkMonthlyInvoices($months, $companyId);
                $currentPayments = $this->getBulkMonthlyPayments($months, $companyId);
                
                foreach ($months as $month) {
                    $monthKey = $month->format('Y-m');
                    $lastYearKey = $month->copy()->subYear()->format('Y-m');
                    
                    $data[] = [
                        'date' => $monthKey,
                        'revenue' => $currentRevenues[$monthKey] ?? 0,
                        'lastYear' => $lastYearRevenues[$lastYearKey] ?? 0,
                        'invoices' => $currentInvoices[$monthKey] ?? 0,
                        'payments' => $currentPayments[$monthKey] ?? 0,
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
            ->whereDate('payment_date', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getDayInvoices($date, $companyId)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereDate('date', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getDayPayments($date, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereDate('payment_date', $date);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->count();
    }
    
    protected function getMonthRevenue($month, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereYear('payment_date', $month->year)
            ->whereMonth('payment_date', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getMonthInvoices($month, $companyId)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->sum('amount');
    }
    
    protected function getMonthPayments($month, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereYear('payment_date', $month->year)
            ->whereMonth('payment_date', $month->month);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->count();
    }
    
    // Bulk query methods for performance optimization
    protected function getBulkDailyRevenue($startDate, $endDate, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->groupBy('date');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('total', 'date')->toArray();
    }
    
    protected function getBulkDailyInvoices($startDate, $endDate, $companyId)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('total', 'date')->toArray();
    }
    
    protected function getBulkDailyPayments($startDate, $endDate, $companyId)
    {
        $query = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->selectRaw('DATE(payment_date) as date, COUNT(*) as count')
            ->groupBy('date');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('count', 'date')->toArray();
    }
    
    protected function getBulkMonthlyRevenue($months, $companyId)
    {
        $monthConditions = [];
        foreach ($months as $month) {
            $monthConditions[] = "(YEAR(payment_date) = {$month->year} AND MONTH(payment_date) = {$month->month})";
        }
        
        $query = Payment::where('company_id', $companyId)
            ->whereRaw('(' . implode(' OR ', $monthConditions) . ')')
            ->selectRaw("CONCAT(YEAR(payment_date), '-', LPAD(MONTH(payment_date), 2, '0')) as month, SUM(amount) as total")
            ->groupBy('month');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('total', 'month')->toArray();
    }
    
    protected function getBulkMonthlyInvoices($months, $companyId)
    {
        $monthConditions = [];
        foreach ($months as $month) {
            $monthConditions[] = "(YEAR(date) = {$month->year} AND MONTH(date) = {$month->month})";
        }
        
        $query = Invoice::where('company_id', $companyId)
            ->whereRaw('(' . implode(' OR ', $monthConditions) . ')')
            ->selectRaw("CONCAT(YEAR(date), '-', LPAD(MONTH(date), 2, '0')) as month, SUM(amount) as total")
            ->groupBy('month');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('total', 'month')->toArray();
    }
    
    protected function getBulkMonthlyPayments($months, $companyId)
    {
        $monthConditions = [];
        foreach ($months as $month) {
            $monthConditions[] = "(YEAR(payment_date) = {$month->year} AND MONTH(payment_date) = {$month->month})";
        }
        
        $query = Payment::where('company_id', $companyId)
            ->whereRaw('(' . implode(' OR ', $monthConditions) . ')')
            ->selectRaw("CONCAT(YEAR(payment_date), '-', LPAD(MONTH(payment_date), 2, '0')) as month, COUNT(*) as count")
            ->groupBy('month');
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        return $query->pluck('count', 'month')->toArray();
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