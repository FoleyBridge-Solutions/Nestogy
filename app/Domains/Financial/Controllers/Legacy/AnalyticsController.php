<?php

namespace App\Domains\Financial\Controllers\Legacy;

use App\Domains\Core\Services\DashboardDataService;
use App\Domains\Financial\Services\FinancialAnalyticsService;
use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Models\FinancialReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * AnalyticsController
 *
 * Handles all financial analytics and dashboard endpoints with comprehensive
 * data aggregation, real-time KPIs, and export capabilities.
 */
class AnalyticsController extends Controller
{
    protected FinancialAnalyticsService $analyticsService;

    protected DashboardDataService $dashboardService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->analyticsService = new FinancialAnalyticsService(Auth::user()->company_id);
            $this->dashboardService = new DashboardDataService(Auth::user()->company_id);

            return $next($request);
        });
    }

    /**
     * Get executive dashboard data
     */
    public function executiveDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'boolean',
        ]);

        if ($request->boolean('refresh')) {
            $this->dashboardService->invalidateCache(['executive']);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $data = $this->dashboardService->getExecutiveDashboardData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get revenue analytics dashboard data
     */
    public function revenueDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'boolean',
        ]);

        if ($request->boolean('refresh')) {
            $this->dashboardService->invalidateCache(['revenue']);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $data = $this->dashboardService->getRevenueAnalyticsDashboardData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get customer analytics dashboard data
     */
    public function customerDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'boolean',
        ]);

        if ($request->boolean('refresh')) {
            $this->dashboardService->invalidateCache(['customer']);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $data = $this->dashboardService->getCustomerAnalyticsDashboardData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get operations dashboard data
     */
    public function operationsDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'boolean',
        ]);

        if ($request->boolean('refresh')) {
            $this->dashboardService->invalidateCache(['operations']);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $data = $this->dashboardService->getOperationsDashboardData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get forecasting dashboard data
     */
    public function forecastingDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'refresh' => 'boolean',
        ]);

        if ($request->boolean('refresh')) {
            $this->dashboardService->invalidateCache(['forecasting']);
        }

        $data = $this->dashboardService->getForecastingDashboardData();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get real-time KPI values
     */
    public function realtimeKPIs(Request $request): JsonResponse
    {
        $request->validate([
            'kpis' => 'required|array',
            'kpis.*' => 'string|in:total_revenue,mrr,new_customers,churn_rate,cash_balance,outstanding_receivables,quote_conversion_rate,avg_deal_size',
        ]);

        $kpis = $this->dashboardService->getRealtimeKPIs($request->kpis);

        return response()->json([
            'success' => true,
            'data' => $kpis,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get specific widget data
     */
    public function widgetData(Request $request, int $widgetId): JsonResponse
    {
        $widget = DashboardWidget::where('company_id', Auth::user()->company_id)
            ->where('id', $widgetId)
            ->first();

        if (! $widget) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found',
            ], 404);
        }

        if (! $widget->canBeViewedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $data = $this->dashboardService->getWidgetData($widget);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR)
     */
    public function calculateMRR(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->date ? Carbon::parse($request->date) : null;
        $mrrData = $this->analyticsService->calculateMRR($date);

        return response()->json([
            'success' => true,
            'data' => $mrrData,
        ]);
    }

    /**
     * Calculate Annual Recurring Revenue (ARR)
     */
    public function calculateARR(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->date ? Carbon::parse($request->date) : null;
        $arrData = $this->analyticsService->calculateARR($date);

        return response()->json([
            'success' => true,
            'data' => $arrData,
        ]);
    }

    /**
     * Calculate Customer Lifetime Value (CLV)
     */
    public function calculateCLV(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'nullable|integer|exists:clients,id',
        ]);

        $clvData = $this->analyticsService->calculateCustomerLifetimeValue($request->client_id);

        return response()->json([
            'success' => true,
            'data' => $clvData,
        ]);
    }

    /**
     * Analyze quote-to-cash conversion
     */
    public function quoteToCashAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $analysis = $this->analyticsService->analyzeQuoteToCash($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Analyze service profitability
     */
    public function serviceProfitability(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $profitability = $this->analyticsService->analyzeServiceProfitability($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $profitability,
        ]);
    }

    /**
     * Generate cash flow projections
     */
    public function cashFlowProjections(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'model' => 'nullable|string|in:linear,seasonal,ml_based,manual',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $model = $request->model ?? 'linear';

        $projections = $this->analyticsService->generateCashFlowProjections($startDate, $endDate, $model);

        return response()->json([
            'success' => true,
            'data' => $projections,
        ]);
    }

    /**
     * Analyze tax compliance
     */
    public function taxCompliance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $compliance = $this->analyticsService->analyzeTaxCompliance($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $compliance,
        ]);
    }

    /**
     * Analyze credit note and refund impact
     */
    public function creditRefundImpact(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $impact = $this->analyticsService->analyzeCreditRefundImpact($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $impact,
        ]);
    }

    /**
     * Calculate financial health score
     */
    public function financialHealthScore(): JsonResponse
    {
        $healthScore = $this->analyticsService->calculateFinancialHealthScore();

        return response()->json([
            'success' => true,
            'data' => $healthScore,
        ]);
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'dashboard_type' => 'required|string|in:executive,revenue,customer,operations,forecasting',
            'format' => 'required|string|in:pdf,excel,csv,json',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $exportData = $this->dashboardService->exportDashboardData(
            $request->dashboard_type,
            $request->input('format'),
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'download_url' => null, // Would generate actual download URL
        ]);
    }

    /**
     * Invalidate dashboard cache
     */
    public function invalidateCache(Request $request): JsonResponse
    {
        $request->validate([
            'types' => 'nullable|array',
            'types.*' => 'string|in:executive,revenue,customer,operations,forecasting',
            'widget_id' => 'nullable|integer|exists:dashboard_widgets,id',
        ]);

        $this->dashboardService->invalidateCache($request->types ?? [], $request->widget_id);

        return response()->json([
            'success' => true,
            'message' => 'Cache invalidated successfully',
        ]);
    }

    /**
     * Get available dashboard widgets
     */
    public function availableWidgets(Request $request): JsonResponse
    {
        $request->validate([
            'dashboard_type' => 'nullable|string|in:executive,revenue,customer,operations,forecasting',
        ]);

        $query = DashboardWidget::where('company_id', Auth::user()->company_id)
            ->active()
            ->visible();

        if ($request->dashboard_type) {
            $query->byDashboard($request->dashboard_type);
        }

        $widgets = $query->forUser(Auth::id())
            ->orderByPosition()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $widgets,
        ]);
    }

    /**
     * Update widget position
     */
    public function updateWidgetPosition(Request $request, int $widgetId): JsonResponse
    {
        $request->validate([
            'grid_row' => 'required|integer|min:0',
            'grid_column' => 'required|integer|min:0',
            'grid_width' => 'nullable|integer|min:1|max:12',
            'grid_height' => 'nullable|integer|min:1',
        ]);

        $widget = DashboardWidget::where('company_id', Auth::user()->company_id)
            ->where('id', $widgetId)
            ->first();

        if (! $widget) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found',
            ], 404);
        }

        $widget->updatePosition(
            $request->grid_row,
            $request->grid_column,
            $request->grid_width,
            $request->grid_height
        );

        // Invalidate cache for this widget
        $this->dashboardService->invalidateCache([], $widgetId);

        return response()->json([
            'success' => true,
            'message' => 'Widget position updated successfully',
        ]);
    }

    /**
     * Update widget configuration
     */
    public function updateWidgetConfiguration(Request $request, int $widgetId): JsonResponse
    {
        $request->validate([
            'configuration' => 'required|array',
        ]);

        $widget = DashboardWidget::where('company_id', Auth::user()->company_id)
            ->where('id', $widgetId)
            ->first();

        if (! $widget) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found',
            ], 404);
        }

        $widget->updateConfiguration($request->configuration);

        // Invalidate cache for this widget
        $this->dashboardService->invalidateCache([], $widgetId);

        return response()->json([
            'success' => true,
            'message' => 'Widget configuration updated successfully',
        ]);
    }

    /**
     * Generate financial report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_id' => 'required|integer|exists:financial_reports,id',
        ]);

        $report = FinancialReport::where('company_id', Auth::user()->company_id)
            ->where('id', $request->report_id)
            ->first();

        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        // Queue report generation job (would implement actual job)
        $report->markAsGenerating();

        return response()->json([
            'success' => true,
            'message' => 'Report generation started',
            'report_id' => $report->id,
        ]);
    }
}
