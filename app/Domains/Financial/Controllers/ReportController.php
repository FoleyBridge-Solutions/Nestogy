<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function revenue(Request $request): View
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);

        $revenueData = Invoice::where('status', 'paid')
            ->where('paid_date', '>=', $startDate)
            ->select(
                DB::raw('DATE(paid_date) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $totalRevenue = $revenueData->sum('revenue');
        $avgInvoiceValue = $revenueData->avg('revenue');
        $growthRate = $this->calculateGrowthRate($period);

        $topClients = Invoice::where('status', 'paid')
            ->where('paid_date', '>=', $startDate)
            ->select('client_id', DB::raw('SUM(total) as total_revenue'))
            ->groupBy('client_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->with('client')
            ->get();

        return view('financial.reports.revenue', compact(
            'revenueData',
            'totalRevenue',
            'avgInvoiceValue',
            'growthRate',
            'topClients',
            'period'
        ));
    }

    public function profitLoss(Request $request): View
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);

        $revenue = Invoice::where('status', 'paid')
            ->where('paid_date', '>=', $startDate)
            ->sum('total');

        $expenses = Expense::where('date', '>=', $startDate)
            ->where('status', 'approved')
            ->sum('amount');

        $grossProfit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        $expensesByCategory = Expense::where('date', '>=', $startDate)
            ->where('status', 'approved')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        $monthlyPL = $this->getMonthlyProfitLoss($startDate);

        return view('financial.reports.profit-loss', compact(
            'revenue',
            'expenses',
            'grossProfit',
            'profitMargin',
            'expensesByCategory',
            'monthlyPL',
            'period'
        ));
    }

    public function cashFlow(Request $request): View
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);

        $cashInflows = Payment::where('payment_date', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('amount');

        $cashOutflows = Expense::where('date', '>=', $startDate)
            ->where('status', 'paid')
            ->sum('amount');

        $netCashFlow = $cashInflows - $cashOutflows;

        $dailyCashFlow = $this->getDailyCashFlow($startDate);
        $projectedCashFlow = $this->projectCashFlow();

        return view('financial.reports.cash-flow', compact(
            'cashInflows',
            'cashOutflows',
            'netCashFlow',
            'dailyCashFlow',
            'projectedCashFlow',
            'period'
        ));
    }

    public function aging(Request $request): View
    {
        $agingBuckets = [
            'current' => ['min' => 0, 'max' => 0],
            '1-30' => ['min' => 1, 'max' => 30],
            '31-60' => ['min' => 31, 'max' => 60],
            '61-90' => ['min' => 61, 'max' => 90],
            'over_90' => ['min' => 91, 'max' => null],
        ];

        $agingData = [];
        foreach ($agingBuckets as $bucket => $range) {
            $query = Invoice::where('status', '!=', 'paid');

            if ($range['min'] === 0) {
                $query->where('due_date', '>=', Carbon::now());
            } elseif ($range['max'] === null) {
                $query->where('due_date', '<', Carbon::now()->subDays($range['min']));
            } else {
                $query->whereBetween('due_date', [
                    Carbon::now()->subDays($range['max']),
                    Carbon::now()->subDays($range['min']),
                ]);
            }

            $agingData[$bucket] = [
                'count' => $query->count(),
                'total' => $query->sum('balance_due'),
                'invoices' => $query->with('client')->get(),
            ];
        }

        $totalOutstanding = collect($agingData)->sum('total');
        $avgDaysOutstanding = $this->calculateAvgDaysOutstanding();

        return view('financial.reports.aging', compact(
            'agingData',
            'totalOutstanding',
            'avgDaysOutstanding'
        ));
    }

    public function tax(Request $request): View
    {
        $year = $request->get('year', Carbon::now()->year);
        $quarter = $request->get('quarter', Carbon::now()->quarter);

        $taxData = Invoice::whereYear('invoice_date', $year)
            ->where('status', 'paid')
            ->select(
                DB::raw('QUARTER(invoice_date) as quarter'),
                DB::raw('SUM(tax_amount) as tax_collected'),
                DB::raw('SUM(total) as gross_revenue'),
                DB::raw('SUM(total - tax_amount) as net_revenue')
            )
            ->groupBy('quarter')
            ->get();

        $totalTaxCollected = $taxData->sum('tax_collected');
        $taxByJurisdiction = $this->getTaxByJurisdiction($year);
        $taxFilings = $this->getTaxFilings($year);

        return view('financial.reports.tax', compact(
            'taxData',
            'totalTaxCollected',
            'taxByJurisdiction',
            'taxFilings',
            'year',
            'quarter'
        ));
    }

    private function getStartDate($period): Carbon
    {
        return match ($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth()
        };
    }

    private function calculateGrowthRate($period): float
    {
        // TODO: Implement growth rate calculation
        return 12.5;
    }

    private function getMonthlyProfitLoss($startDate): array
    {
        // TODO: Implement monthly P&L data
        return [];
    }

    private function getDailyCashFlow($startDate): array
    {
        // TODO: Implement daily cash flow data
        return [];
    }

    private function projectCashFlow(): array
    {
        // TODO: Implement cash flow projection
        return [];
    }

    private function calculateAvgDaysOutstanding(): float
    {
        // TODO: Calculate average days outstanding
        return 45.2;
    }

    private function getTaxByJurisdiction($year): array
    {
        // TODO: Get tax data by jurisdiction
        return [];
    }

    private function getTaxFilings($year): array
    {
        // TODO: Get tax filing records
        return [];
    }
}
