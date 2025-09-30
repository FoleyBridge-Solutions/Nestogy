<?php

namespace App\Domains\Report\Controllers\Report;

use App\Domains\Report\Services\DashboardService;
use App\Domains\Report\Services\ExecutiveReportService;
use App\Domains\Report\Services\WidgetService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Executive Dashboard Controller
 *
 * High-level dashboards for executives and management
 */
class ExecutiveDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected ExecutiveReportService $executiveService,
        protected WidgetService $widgetService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:reports_executive')->except(['widget']);
    }

    /**
     * Display executive dashboard
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        // Default to current month if no date range specified
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->endOfMonth();

        $dashboardData = $this->dashboardService->getExecutiveDashboard(
            $companyId,
            $startDate,
            $endDate
        );

        // Get comparison period (previous month/period)
        $comparisonStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $comparisonEnd = $startDate->copy()->subDay();

        $comparisonData = $this->dashboardService->getExecutiveDashboard(
            $companyId,
            $comparisonStart,
            $comparisonEnd
        );

        return view('reports.executive.dashboard', compact(
            'dashboardData',
            'comparisonData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display service dashboard
     */
    public function service(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->endOfMonth();

        $serviceData = $this->dashboardService->getServiceDashboard(
            $companyId,
            $startDate,
            $endDate
        );

        return view('reports.executive.service', compact(
            'serviceData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display financial dashboard
     */
    public function financial(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->endOfMonth();

        $financialData = $this->dashboardService->getFinancialDashboard(
            $companyId,
            $startDate,
            $endDate
        );

        return view('reports.executive.financial', compact(
            'financialData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display client health scorecard
     */
    public function clientHealth(): View
    {
        $companyId = auth()->user()->company_id;

        $healthScorecard = $this->executiveService->generateClientHealthScorecard($companyId);

        return view('reports.executive.client-health', compact('healthScorecard'));
    }

    /**
     * Display client-specific dashboard
     */
    public function clientDashboard(Request $request, int $clientId): View
    {
        $client = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->findOrFail($clientId);

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->endOfMonth();

        $clientData = $this->dashboardService->getClientDashboard(
            $client,
            $startDate,
            $endDate
        );

        return view('reports.executive.client-dashboard', compact(
            'client',
            'clientData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get widget data (AJAX endpoint)
     */
    public function widget(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:kpi,chart,table,gauge,stat_list,progress,alerts',
            'config' => 'required|array',
        ]);

        try {
            $widgetData = match ($request->input('type')) {
                'kpi' => $this->widgetService->getKPIWidget($request->input('config')),
                'chart' => $this->widgetService->getChartWidget($request->input('config')),
                'table' => $this->widgetService->getTableWidget($request->input('config')),
                'gauge' => $this->widgetService->getGaugeWidget($request->input('config')),
                'stat_list' => $this->widgetService->getStatListWidget($request->input('config')),
                'progress' => $this->widgetService->getProgressWidget($request->input('config')),
                'alerts' => $this->widgetService->getAlertWidget($request->input('config')),
            };

            return response()->json([
                'success' => true,
                'data' => $widgetData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard data (AJAX endpoint)
     */
    public function data(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        $dashboardType = $request->input('type', 'executive');

        try {
            $data = match ($dashboardType) {
                'executive' => $this->dashboardService->getExecutiveDashboard($companyId, $startDate, $endDate),
                'service' => $this->dashboardService->getServiceDashboard($companyId, $startDate, $endDate),
                'financial' => $this->dashboardService->getFinancialDashboard($companyId, $startDate, $endDate),
                default => throw new \InvalidArgumentException("Unknown dashboard type: {$dashboardType}"),
            };

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export dashboard as PDF
     */
    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        $dashboardType = $request->input('type', 'executive');
        $format = $request->input('format', 'pdf');

        try {
            $data = match ($dashboardType) {
                'executive' => $this->dashboardService->getExecutiveDashboard($companyId, $startDate, $endDate),
                'service' => $this->dashboardService->getServiceDashboard($companyId, $startDate, $endDate),
                'financial' => $this->dashboardService->getFinancialDashboard($companyId, $startDate, $endDate),
                default => throw new \InvalidArgumentException("Unknown dashboard type: {$dashboardType}"),
            };

            $filename = "dashboard-{$dashboardType}-".$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d');

            if ($format === 'pdf') {
                $pdf = app('dompdf.wrapper')->loadView("reports.executive.pdf.{$dashboardType}", compact('data', 'startDate', 'endDate'));

                return $pdf->download("{$filename}.pdf");
            } elseif ($format === 'excel') {
                // Implementation for Excel export would go here
                return response()->json(['error' => 'Excel export not yet implemented'], 501);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time metrics (AJAX endpoint for auto-refresh)
     */
    public function realtime(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        try {
            // Get real-time metrics (shorter cache times)
            $metrics = [
                'active_tickets' => \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                    ->whereNull('resolved_at')
                    ->count(),
                'sla_breaches' => \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                    ->where('sla_breached', true)
                    ->whereNull('resolved_at')
                    ->count(),
                'today_revenue' => \App\Domains\Financial\Models\Payment::where('company_id', $companyId)
                    ->whereDate('payment_date', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'online_technicians' => \App\Models\User::where('company_id', $companyId)
                    ->where('last_activity', '>=', now()->subMinutes(5))
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'technician');
                    })
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'updated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
