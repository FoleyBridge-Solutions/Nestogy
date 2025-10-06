<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Client\Models\Client;
use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Services\NavigationService;
use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialDashboardController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    public function index(Request $request)
    {
        $selectedClient = $this->navigationService->getSelectedClient();

        // Date ranges
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();

        // Build base queries with optional client filtering
        $invoiceQuery = Invoice::query();
        $paymentQuery = Payment::query();
        $expenseQuery = Expense::query();
        $contractQuery = Contract::query();

        if ($selectedClient) {
            $invoiceQuery->where('client_id', $selectedClient->id);
            $paymentQuery->whereHas('invoice', function ($q) use ($selectedClient) {
                $q->where('client_id', $selectedClient->id);
            });
            $expenseQuery->where('client_id', $selectedClient->id);
            $contractQuery->where('client_id', $selectedClient->id);
        }

        // Revenue metrics
        $totalRevenue = clone $invoiceQuery;
        $totalRevenue = $totalRevenue->where('status', 'paid')->sum('amount');

        $monthlyRevenue = clone $invoiceQuery;
        $monthlyRevenue = $monthlyRevenue->where('status', 'paid')
            ->where('paid_at', '>=', $currentMonth)
            ->sum('amount');

        $lastMonthRevenue = clone $invoiceQuery;
        $lastMonthRevenue = $lastMonthRevenue->where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonth, $currentMonth])
            ->sum('amount');

        // Outstanding invoices
        $outstandingInvoices = clone $invoiceQuery;
        $outstandingInvoices = $outstandingInvoices->whereIn('status', ['sent', 'partial'])
            ->sum('balance');

        $overdueInvoices = clone $invoiceQuery;
        $overdueInvoices = $overdueInvoices->where('status', 'overdue')
            ->sum('balance');

        // Expense metrics
        $monthlyExpenses = clone $expenseQuery;
        $monthlyExpenses = $monthlyExpenses->where('date', '>=', $currentMonth)
            ->sum('amount');

        $yearlyExpenses = clone $expenseQuery;
        $yearlyExpenses = $yearlyExpenses->where('date', '>=', $currentYear)
            ->sum('amount');

        // Contract metrics
        $activeContracts = clone $contractQuery;
        $activeContracts = $activeContracts->where('status', 'active')->count();

        $contractMRR = clone $contractQuery;
        $contractMRR = $contractMRR->where('status', 'active')
            ->where('billing_frequency', 'monthly')
            ->sum('amount');

        $contractARR = clone $contractQuery;
        $contractARR = $contractARR->where('status', 'active')
            ->sum(DB::raw("CASE 
                WHEN billing_frequency = 'monthly' THEN amount * 12
                WHEN billing_frequency = 'quarterly' THEN amount * 4
                WHEN billing_frequency = 'semi-annual' THEN amount * 2
                WHEN billing_frequency = 'annual' THEN amount
                ELSE 0
            END"));

        // Recent transactions
        $recentInvoices = clone $invoiceQuery;
        $recentInvoices = $recentInvoices->with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = clone $paymentQuery;
        $recentPayments = $recentPayments->with(['invoice.client'])
            ->latest()
            ->take(5)
            ->get();

        // Revenue trend (last 12 months)
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $monthQuery = clone $invoiceQuery;
            $revenue = $monthQuery->where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount');

            $revenueTrend[] = [
                'month' => $startDate->format('M'),
                'revenue' => $revenue,
            ];
        }

        // Payment methods breakdown
        $paymentMethods = clone $paymentQuery;
        $paymentMethods = $paymentMethods->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Top clients by revenue (if not filtered by client)
        $topClients = [];
        if (! $selectedClient) {
            $topClients = Invoice::where('status', 'paid')
                ->select('client_id', DB::raw('SUM(total) as revenue'))
                ->with('client')
                ->groupBy('client_id')
                ->orderByDesc('revenue')
                ->take(5)
                ->get();
        }

        // Calculate growth percentages
        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        return view('financial.dashboard', compact(
            'selectedClient',
            'totalRevenue',
            'monthlyRevenue',
            'revenueGrowth',
            'outstandingInvoices',
            'overdueInvoices',
            'monthlyExpenses',
            'yearlyExpenses',
            'activeContracts',
            'contractMRR',
            'contractARR',
            'recentInvoices',
            'recentPayments',
            'revenueTrend',
            'paymentMethods',
            'topClients'
        ));
    }
}
