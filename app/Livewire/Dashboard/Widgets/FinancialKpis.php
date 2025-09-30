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
use App\Domains\Core\Services\DashboardCacheService;

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
        
        // Use cache for frequently accessed data
        $cacheKey = "financial_kpis_{$companyId}_" . Carbon::now()->format('Y-m-d-H');
        
        $kpiData = cache()->remember($cacheKey, 60, function() use ($companyId) {
            $now = Carbon::now();
            
            // Batch all invoice queries into one with conditional aggregates
            $invoiceStats = DB::selectOne("
                SELECT 
                    SUM(CASE WHEN status IN ('Sent', 'Viewed', 'Partial') THEN amount ELSE 0 END) as outstanding,
                    AVG(CASE WHEN date >= ? AND date <= ? THEN amount END) as avg_invoice,
                    SUM(CASE WHEN date >= ? AND date <= ? THEN amount ELSE 0 END) as total_invoiced
                FROM invoices
                WHERE company_id = ? AND archived_at IS NULL
            ", [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $companyId
            ]);
            
            // Batch client queries
            $clientStats = DB::selectOne("
                SELECT 
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_clients,
                    COUNT(CASE WHEN created_at < ? THEN 1 END) as clients_start_of_month
                FROM clients
                WHERE company_id = ? AND deleted_at IS NULL
            ", [
                $now->copy()->startOfMonth(),
                $companyId
            ]);
            
            // Get churned clients count
            $churnedThisMonth = DB::table('clients')
                ->where('company_id', $companyId)
                ->whereNotNull('deleted_at')
                ->whereMonth('deleted_at', $now->month)
                ->whereYear('deleted_at', $now->year)
                ->count();
            
            // Calculate MRR from recurring items
            $recurringItems = Recurring::where('company_id', $companyId)
                ->where('status', true)
                ->select('amount', 'frequency')
                ->get();
            
            $mrr = $recurringItems->reduce(function ($total, $item) {
                return $total + match(strtolower($item->frequency)) {
                    'monthly' => $item->amount,
                    'quarterly' => $item->amount / 3,
                    'yearly', 'annual' => $item->amount / 12,
                    'weekly' => $item->amount * 4.33,
                    'daily' => $item->amount * 30,
                    default => 0
                };
            }, 0);
            
            // Get payment stats
            $totalCollected = Payment::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereNotNull('payment_date')
                ->whereMonth('payment_date', $now->month)
                ->whereYear('payment_date', $now->year)
                ->sum('amount') ?? 0;
            
            return [
                'mrr' => $mrr,
                'arr' => $mrr * 12,
                'outstanding' => $invoiceStats->outstanding ?? 0,
                'avg_invoice' => $invoiceStats->avg_invoice ?? 0,
                'total_invoiced' => $invoiceStats->total_invoiced ?? 0,
                'total_collected' => $totalCollected,
                'active_clients' => $clientStats->active_clients ?? 0,
                'clients_start_of_month' => $clientStats->clients_start_of_month ?? 0,
                'churned_this_month' => $churnedThisMonth
            ];
        });
        
        // Calculate metrics from cached data
        $mrr = $kpiData['mrr'];
        $arr = $kpiData['arr'];
        $outstanding = $kpiData['outstanding'];
        $avgInvoice = $kpiData['avg_invoice'];
        $activeClients = $kpiData['active_clients'];
        
        $churnRate = $kpiData['clients_start_of_month'] > 0 ? 
            round(($kpiData['churned_this_month'] / $kpiData['clients_start_of_month']) * 100, 1) : 0;
        
        $collectionRate = $kpiData['total_invoiced'] > 0 ? 
            ($kpiData['total_collected'] / $kpiData['total_invoiced']) * 100 : 0;
        
        // Get revenue with optimized queries
        [$monthlyRevenue, $previousRevenue] = $this->calculateMonthlyRevenue($companyId);
        
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
        // Cache monthly revenue calculations
        $cacheKey = "monthly_revenue_{$companyId}_" . Carbon::now()->format('Y-m');
        
        return cache()->remember($cacheKey, 300, function() use ($companyId) {
            $method = $this->getRevenueRecognitionMethod();
            $now = Carbon::now();

            $currentStart = $now->copy()->startOfMonth();
            $currentEnd = $now->copy()->endOfMonth();
            $previousStart = $now->copy()->subMonth()->startOfMonth();
            $previousEnd = $now->copy()->subMonth()->endOfMonth();

            if ($method === 'cash') {
                // Use a single query with conditional aggregates
                $revenues = DB::selectOne("
                    SELECT 
                        SUM(CASE WHEN payment_date >= ? AND payment_date <= ? THEN amount ELSE 0 END) as current_revenue,
                        SUM(CASE WHEN payment_date >= ? AND payment_date <= ? THEN amount ELSE 0 END) as previous_revenue
                    FROM payments
                    WHERE company_id = ? AND status = 'completed' AND payment_date IS NOT NULL
                ", [
                    $currentStart, $currentEnd,
                    $previousStart, $previousEnd,
                    $companyId
                ]);
                
                return [(float) $revenues->current_revenue, (float) $revenues->previous_revenue];
            } else {
                // Use a single query for accrual method
                $revenues = DB::selectOne("
                    SELECT 
                        SUM(CASE WHEN date >= ? AND date <= ? THEN amount ELSE 0 END) as current_revenue,
                        SUM(CASE WHEN date >= ? AND date <= ? THEN amount ELSE 0 END) as previous_revenue
                    FROM invoices
                    WHERE company_id = ? AND status = ? AND archived_at IS NULL
                ", [
                    $currentStart, $currentEnd,
                    $previousStart, $previousEnd,
                    $companyId,
                    Invoice::STATUS_PAID
                ]);
                
                return [(float) $revenues->current_revenue, (float) $revenues->previous_revenue];
            }
        });
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
