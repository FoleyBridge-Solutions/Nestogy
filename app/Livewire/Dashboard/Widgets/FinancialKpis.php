<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Models\Recurring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

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

        $kpiData = $this->fetchCachedKpiData($companyId);
        $trends = $this->calculateAllTrends($companyId, $kpiData);

        $this->kpis = $this->buildKpiArray($kpiData, $trends);
        $this->loading = false;
    }

    protected function fetchCachedKpiData(int $companyId): array
    {
        $cacheKey = "financial_kpis_{$companyId}_".Carbon::now()->format('Y-m-d-H');

        return cache()->remember($cacheKey, 60, function () use ($companyId) {
            $now = Carbon::now();

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
                $companyId,
            ]);

            $clientStats = DB::selectOne("
                SELECT 
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_clients,
                    COUNT(CASE WHEN created_at < ? THEN 1 END) as clients_start_of_month
                FROM clients
                WHERE company_id = ? AND deleted_at IS NULL
            ", [
                $now->copy()->startOfMonth(),
                $companyId,
            ]);

            $churnedThisMonth = DB::table('clients')
                ->where('company_id', $companyId)
                ->whereNotNull('deleted_at')
                ->whereMonth('deleted_at', $now->month)
                ->whereYear('deleted_at', $now->year)
                ->count();

            $recurringItems = Recurring::where('company_id', $companyId)
                ->where('status', true)
                ->select('amount', 'frequency')
                ->get();

            $mrr = $recurringItems->reduce(function ($total, $item) {
                return $total + match (strtolower($item->frequency)) {
                    'monthly' => $item->amount,
                    'quarterly' => $item->amount / 3,
                    'yearly', 'annual' => $item->amount / 12,
                    'weekly' => $item->amount * 4.33,
                    'daily' => $item->amount * 30,
                    default => 0
                };
            }, 0);

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
                'churned_this_month' => $churnedThisMonth,
            ];
        });
    }

    protected function calculateAllTrends(int $companyId, array $kpiData): array
    {
        [$monthlyRevenue, $previousRevenue] = $this->calculateMonthlyRevenue($companyId);

        $churnRate = $this->calculateChurnRate($kpiData);
        $collectionRate = $this->calculateCollectionRate($kpiData);

        $lastMonthMRR = $this->calculateLastMonthMRR($companyId);
        $lastMonthOutstanding = $this->calculateLastMonthOutstanding($companyId);
        $lastMonthAvgInvoice = $this->calculateLastMonthAvgInvoice($companyId);
        $lastMonthChurnRate = $this->calculateLastMonthChurnRate($companyId);
        $lastMonthCollectionRate = $this->calculateLastMonthCollectionRate($companyId);

        $newClientsThisMonth = Client::where('company_id', $companyId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        return [
            'monthly_revenue' => $monthlyRevenue,
            'previous_revenue' => $previousRevenue,
            'churn_rate' => $churnRate,
            'collection_rate' => $collectionRate,
            'new_clients' => $newClientsThisMonth,
            'mrr_trend' => $this->calculateTrend($kpiData['mrr'], $lastMonthMRR),
            'revenue_trend' => $this->calculateTrend($monthlyRevenue, $previousRevenue),
            'outstanding_trend' => $this->calculateTrend($kpiData['outstanding'], $lastMonthOutstanding),
            'avg_invoice_trend' => $this->calculateTrend($kpiData['avg_invoice'], $lastMonthAvgInvoice),
            'churn_trend' => $this->calculateTrend($churnRate, $lastMonthChurnRate),
            'collection_trend' => $this->calculateTrend($collectionRate, $lastMonthCollectionRate),
        ];
    }

    protected function buildKpiArray(array $kpiData, array $trends): array
    {
        return [
            $this->buildKpiItem('MRR', $kpiData['mrr'], 'currency', 'chart-bar', 'green', $trends['mrr_trend']),
            $this->buildKpiItem('ARR', $kpiData['arr'], 'currency', 'arrow-trending-up', 'blue', $trends['mrr_trend']),
            $this->buildKpiItemWithPrevious('Monthly Revenue', $trends['monthly_revenue'], 'currency', 'currency-dollar', 'purple', $trends['revenue_trend'], $trends['previous_revenue']),
            $this->buildKpiItem('Outstanding', $kpiData['outstanding'], 'currency', 'clock', 'orange', $trends['outstanding_trend'], true),
            $this->buildKpiItem('Avg Invoice', $kpiData['avg_invoice'], 'currency', 'document-text', 'indigo', $trends['avg_invoice_trend']),
            $this->buildClientKpiItem($kpiData['active_clients'], $trends['new_clients']),
            $this->buildKpiItem('Churn Rate', $trends['churn_rate'], 'percentage', 'arrow-trending-down', 'red', $trends['churn_trend'], true),
            $this->buildKpiItem('Collection Rate', round($trends['collection_rate'], 1), 'percentage', 'check-circle', 'green', $trends['collection_trend']),
        ];
    }

    protected function buildKpiItem(string $label, float $value, string $format, string $icon, string $color, float $trend, bool $invertTrend = false): array
    {
        $trendDirection = $invertTrend ? ($trend <= 0 ? 'down' : 'up') : ($trend >= 0 ? 'up' : 'down');
        $trendValue = ($trend >= 0 ? '+' : '').$trend.'%';

        return [
            'label' => $label,
            'value' => $value,
            'format' => $format,
            'icon' => $icon,
            'color' => $color,
            'trend' => $trendDirection,
            'trendValue' => $trendValue,
        ];
    }

    protected function buildKpiItemWithPrevious(string $label, float $value, string $format, string $icon, string $color, float $trend, float $previousValue): array
    {
        $kpi = $this->buildKpiItem($label, $value, $format, $icon, $color, $trend);
        $kpi['previousValue'] = $previousValue;

        return $kpi;
    }

    protected function buildClientKpiItem(int $activeClients, int $newClients): array
    {
        return [
            'label' => 'Active Clients',
            'value' => $activeClients,
            'format' => 'number',
            'icon' => 'user-group',
            'color' => 'teal',
            'trend' => $newClients > 0 ? 'up' : 'stable',
            'trendValue' => $newClients > 0 ? '+'.$newClients : '0',
        ];
    }

    protected function calculateChurnRate(array $kpiData): float
    {
        return $kpiData['clients_start_of_month'] > 0 ?
            round(($kpiData['churned_this_month'] / $kpiData['clients_start_of_month']) * 100, 1) : 0;
    }

    protected function calculateCollectionRate(array $kpiData): float
    {
        return $kpiData['total_invoiced'] > 0 ?
            ($kpiData['total_collected'] / $kpiData['total_invoiced']) * 100 : 0;
    }

    protected function calculateLastMonthOutstanding(int $companyId): float
    {
        return Invoice::where('company_id', $companyId)
            ->whereIn('status', ['Sent', 'Viewed', 'Partial'])
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('amount') ?? 0;
    }

    protected function calculateLastMonthAvgInvoice(int $companyId): float
    {
        return Invoice::where('company_id', $companyId)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->avg('amount') ?? 0;
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
            return $total + match (strtolower($item->frequency)) {
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
        $cacheKey = "monthly_revenue_{$companyId}_".Carbon::now()->format('Y-m');

        return cache()->remember($cacheKey, 300, function () use ($companyId) {
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
                    $companyId,
                ]);

                return [(float) $revenues->current_revenue, (float) $revenues->previous_revenue];
            } else {
                // Use a single query for accrual method
                $revenues = DB::selectOne('
                    SELECT 
                        SUM(CASE WHEN date >= ? AND date <= ? THEN amount ELSE 0 END) as current_revenue,
                        SUM(CASE WHEN date >= ? AND date <= ? THEN amount ELSE 0 END) as previous_revenue
                    FROM invoices
                    WHERE company_id = ? AND status = ? AND archived_at IS NULL
                ', [
                    $currentStart, $currentEnd,
                    $previousStart, $previousEnd,
                    $companyId,
                    Invoice::STATUS_PAID,
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
