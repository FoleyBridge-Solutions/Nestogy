<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ContractAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ContractAnalyticsController
 * 
 * Handles contract performance analytics, revenue forecasting,
 * and business intelligence dashboard endpoints.
 */
class ContractAnalyticsController extends Controller
{
    protected ContractAnalyticsService $analyticsService;

    public function __construct(ContractAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            
            $analytics = $this->analyticsService->getAnalyticsDashboard($companyId, $filters);
            
            return view('financial.analytics.index', [
                'analytics' => $analytics,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading analytics dashboard', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);
            
            return redirect()->back()->with('error', 'Unable to load analytics dashboard.');
        }
    }

    /**
     * Get revenue analytics data (API endpoint)
     */
    public function revenueAnalytics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            
            $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subYear();
            $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();
            
            $revenueData = $this->analyticsService->getRevenueAnalytics($companyId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $revenueData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching revenue analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch revenue analytics',
            ], 500);
        }
    }

    /**
     * Get performance metrics data (API endpoint)
     */
    public function performanceMetrics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            
            $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subYear();
            $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();
            
            $performanceData = $this->analyticsService->getPerformanceMetrics($companyId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $performanceData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching performance metrics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch performance metrics',
            ], 500);
        }
    }

    /**
     * Get client analytics data (API endpoint)
     */
    public function clientAnalytics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            
            $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subYear();
            $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();
            
            $clientData = $this->analyticsService->getClientAnalytics($companyId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $clientData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching client analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch client analytics',
            ], 500);
        }
    }

    /**
     * Get revenue forecast data (API endpoint)
     */
    public function revenueForecast(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $forecastData = $this->analyticsService->getRevenueForecast($companyId);
            
            return response()->json([
                'success' => true,
                'data' => $forecastData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching revenue forecast', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch revenue forecast',
            ], 500);
        }
    }

    /**
     * Get risk analytics data (API endpoint)
     */
    public function riskAnalytics(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $riskData = $this->analyticsService->getRiskAnalytics($companyId);
            
            return response()->json([
                'success' => true,
                'data' => $riskData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching risk analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch risk analytics',
            ], 500);
        }
    }

    /**
     * Export analytics report
     */
    public function exportReport(Request $request)
    {
        try {
            $request->validate([
                'format' => 'required|in:pdf,excel,csv',
                'report_type' => 'required|in:overview,revenue,performance,client,forecast,risk',
            ]);

            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            $format = $request->get('format');
            $reportType = $request->get('report_type');
            
            // Generate report based on type and format
            $analytics = $this->analyticsService->getAnalyticsDashboard($companyId, $filters);
            
            switch ($format) {
                case 'pdf':
                    return $this->exportToPdf($analytics, $reportType);
                case 'excel':
                    return $this->exportToExcel($analytics, $reportType);
                case 'csv':
                    return $this->exportToCsv($analytics, $reportType);
                default:
                    throw new \InvalidArgumentException('Invalid export format');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting analytics report', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'format' => $request->get('format'),
                'report_type' => $request->get('report_type'),
            ]);
            
            return redirect()->back()->with('error', 'Unable to export report.');
        }
    }

    /**
     * Get contract lifecycle analytics
     */
    public function lifecycleAnalytics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $companyId = auth()->user()->company_id;
            
            $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subYear();
            $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();
            
            $lifecycleData = $this->analyticsService->getContractLifecycleAnalytics($companyId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $lifecycleData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching lifecycle analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch lifecycle analytics',
            ], 500);
        }
    }

    /**
     * Build filters from request parameters
     */
    protected function buildFilters(Request $request): array
    {
        $filters = [];
        
        if ($request->has('start_date') && $request->get('start_date')) {
            $filters['start_date'] = $request->get('start_date');
        }
        
        if ($request->has('end_date') && $request->get('end_date')) {
            $filters['end_date'] = $request->get('end_date');
        }
        
        if ($request->has('contract_type') && $request->get('contract_type')) {
            $filters['contract_type'] = $request->get('contract_type');
        }
        
        if ($request->has('client_id') && $request->get('client_id')) {
            $filters['client_id'] = $request->get('client_id');
        }
        
        if ($request->has('status') && $request->get('status')) {
            $filters['status'] = $request->get('status');
        }
        
        return $filters;
    }

    /**
     * Export analytics to PDF
     */
    protected function exportToPdf($analytics, $reportType)
    {
        // Implementation would use a PDF library like DomPDF
        // For now, return a simple response
        $pdf = app('dompdf.wrapper');
        
        $html = view('financial.analytics.exports.pdf', [
            'analytics' => $analytics,
            'report_type' => $reportType,
            'generated_at' => now(),
        ])->render();
        
        $pdf->loadHTML($html);
        
        return $pdf->download('contract-analytics-' . $reportType . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export analytics to Excel
     */
    protected function exportToExcel($analytics, $reportType)
    {
        // Implementation would use Laravel Excel
        // For now, return CSV format
        return $this->exportToCsv($analytics, $reportType);
    }

    /**
     * Export analytics to CSV
     */
    protected function exportToCsv($analytics, $reportType)
    {
        $filename = 'contract-analytics-' . $reportType . '-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($analytics, $reportType) {
            $file = fopen('php://output', 'w');
            
            // Write CSV headers and data based on report type
            switch ($reportType) {
                case 'overview':
                    fputcsv($file, ['Metric', 'Value']);
                    foreach ($analytics['overview_metrics'] as $key => $value) {
                        fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
                    }
                    break;
                    
                case 'revenue':
                    fputcsv($file, ['Period', 'Revenue']);
                    if (isset($analytics['revenue_analytics']['monthly_breakdown']['data'])) {
                        $labels = $analytics['revenue_analytics']['monthly_breakdown']['labels'];
                        $data = $analytics['revenue_analytics']['monthly_breakdown']['data'];
                        for ($i = 0; $i < count($labels); $i++) {
                            fputcsv($file, [$labels[$i], $data[$i] ?? 0]);
                        }
                    }
                    break;
                    
                default:
                    fputcsv($file, ['Report Type', 'Generated At']);
                    fputcsv($file, [$reportType, now()->toString()]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}