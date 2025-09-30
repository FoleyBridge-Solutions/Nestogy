<?php

namespace App\Domains\Financial\Controllers;

use App\Models\TaxCalculation;
use App\Models\ServiceTaxRate;
use App\Models\TaxProfile;
use App\Models\TaxJurisdiction;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Tax Reporting Controller
 * 
 * Provides comprehensive tax reporting capabilities including
 * tax summaries, compliance reports, jurisdiction analysis, and performance metrics.
 */
class TaxReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display tax reporting dashboard
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        
        // Get summary statistics
        $stats = $this->getTaxSummaryStats($companyId, $dateRange);
        
        // Get recent tax calculations
        $recentCalculations = TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['calculable'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get jurisdiction breakdown
        $jurisdictionBreakdown = $this->getJurisdictionBreakdown($companyId, $dateRange);
        
        // Get engine performance metrics
        $engineMetrics = $this->getEnginePerformanceMetrics($companyId, $dateRange);
        
        return view('reports.tax.index', compact(
            'stats',
            'recentCalculations',
            'jurisdictionBreakdown',
            'engineMetrics',
            'dateRange'
        ));
    }

    /**
     * Display detailed tax summary report
     */
    public function summary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        $groupBy = $request->input('group_by', 'month'); // month, week, day
        
        // Get tax calculations grouped by time period
        $taxData = $this->getTaxDataGrouped($companyId, $dateRange, $groupBy);
        
        // Get tax breakdown by type
        $taxTypeBreakdown = $this->getTaxTypeBreakdown($companyId, $dateRange);
        
        // Get top jurisdictions by tax amount
        $topJurisdictions = $this->getTopJurisdictions($companyId, $dateRange);
        
        // Get invoice vs quote tax comparison
        $invoiceQuoteComparison = $this->getInvoiceQuoteComparison($companyId, $dateRange);
        
        return view('reports.tax.summary', compact(
            'taxData',
            'taxTypeBreakdown',
            'topJurisdictions',
            'invoiceQuoteComparison',
            'dateRange',
            'groupBy'
        ));
    }

    /**
     * Display jurisdiction-specific tax report
     */
    public function jurisdictions(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        $jurisdictionId = $request->input('jurisdiction_id');
        
        // Get all jurisdictions for the company
        $jurisdictions = TaxJurisdiction::where('company_id', $companyId)
            ->withCount(['taxRates' => function ($query) {
                $query->active();
            }])
            ->orderBy('state')
            ->orderBy('name')
            ->get();
        
        // Get detailed jurisdiction data
        $jurisdictionData = [];
        if ($jurisdictionId) {
            $selectedJurisdiction = $jurisdictions->find($jurisdictionId);
            $jurisdictionData = $this->getJurisdictionDetailedData($companyId, $jurisdictionId, $dateRange);
        }
        
        // Get jurisdiction summary for all jurisdictions
        $jurisdictionSummary = $this->getJurisdictionSummary($companyId, $dateRange);
        
        return view('reports.tax.jurisdictions', compact(
            'jurisdictions',
            'jurisdictionData',
            'jurisdictionSummary',
            'dateRange',
            'jurisdictionId'
        ));
    }

    /**
     * Display tax compliance report
     */
    public function compliance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        
        // Get tax compliance data
        $complianceData = $this->getComplianceData($companyId, $dateRange);
        
        // Get exemption usage
        $exemptionUsage = $this->getExemptionUsage($companyId, $dateRange);
        
        // Get calculation errors and issues
        $calculationIssues = $this->getCalculationIssues($companyId, $dateRange);
        
        // Get rate changes during period
        $rateChanges = $this->getRateChanges($companyId, $dateRange);
        
        return view('reports.tax.compliance', compact(
            'complianceData',
            'exemptionUsage',
            'calculationIssues',
            'rateChanges',
            'dateRange'
        ));
    }

    /**
     * Display tax performance metrics
     */
    public function performance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        
        // Get calculation performance metrics
        $performanceMetrics = $this->getDetailedPerformanceMetrics($companyId, $dateRange);
        
        // Get error rate analysis
        $errorAnalysis = $this->getErrorAnalysis($companyId, $dateRange);
        
        // Get cache performance
        $cacheMetrics = $this->getCachePerformanceMetrics($companyId);
        
        // Get engine comparison
        $engineComparison = $this->getEngineComparison($companyId, $dateRange);
        
        return view('reports.tax.performance', compact(
            'performanceMetrics',
            'errorAnalysis',
            'cacheMetrics',
            'engineComparison',
            'dateRange'
        ));
    }

    /**
     * Export tax report data
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'summary');
        $format = $request->input('format', 'csv');
        $companyId = auth()->user()->company_id;
        $dateRange = $this->getDateRange($request);
        
        switch ($type) {
            case 'summary':
                return $this->exportSummaryReport($companyId, $dateRange, $format);
            case 'jurisdiction':
                return $this->exportJurisdictionReport($companyId, $dateRange, $format);
            case 'compliance':
                return $this->exportComplianceReport($companyId, $dateRange, $format);
            case 'performance':
                return $this->exportPerformanceReport($companyId, $dateRange, $format);
            default:
                abort(400, 'Invalid report type');
        }
    }

    /**
     * Get API data for charts and widgets
     */
    public function apiData(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $type = $request->input('type');
        $dateRange = $this->getDateRange($request);
        
        switch ($type) {
            case 'tax_trend':
                return response()->json($this->getTaxTrendData($companyId, $dateRange));
            case 'jurisdiction_breakdown':
                return response()->json($this->getJurisdictionBreakdown($companyId, $dateRange));
            case 'engine_performance':
                return response()->json($this->getEnginePerformanceMetrics($companyId, $dateRange));
            case 'error_rates':
                return response()->json($this->getErrorRateData($companyId, $dateRange));
            default:
                return response()->json(['error' => 'Invalid data type'], 400);
        }
    }

    // Private helper methods

    private function getDateRange(Request $request): array
    {
        $period = $request->input('period', '30_days');
        
        switch ($period) {
            case '7_days':
                return [
                    'start' => now()->subDays(7)->startOfDay(),
                    'end' => now()->endOfDay(),
                    'label' => 'Last 7 days'
                ];
            case '30_days':
                return [
                    'start' => now()->subDays(30)->startOfDay(),
                    'end' => now()->endOfDay(),
                    'label' => 'Last 30 days'
                ];
            case '90_days':
                return [
                    'start' => now()->subDays(90)->startOfDay(),
                    'end' => now()->endOfDay(),
                    'label' => 'Last 90 days'
                ];
            case 'current_month':
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth(),
                    'label' => 'Current month'
                ];
            case 'last_month':
                return [
                    'start' => now()->subMonth()->startOfMonth(),
                    'end' => now()->subMonth()->endOfMonth(),
                    'label' => 'Last month'
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($request->input('start_date', now()->subDays(30)))->startOfDay(),
                    'end' => Carbon::parse($request->input('end_date', now()))->endOfDay(),
                    'label' => 'Custom range'
                ];
            default:
                return [
                    'start' => now()->subDays(30)->startOfDay(),
                    'end' => now()->endOfDay(),
                    'label' => 'Last 30 days'
                ];
        }
    }

    private function getTaxSummaryStats(int $companyId, array $dateRange): array
    {
        $calculations = TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                COUNT(*) as total_calculations,
                SUM(total_tax_amount) as total_tax_calculated,
                AVG(calculation_time_ms) as avg_calculation_time,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as successful_calculations,
                SUM(CASE WHEN status = \'error\' THEN 1 ELSE 0 END) as failed_calculations
            ')
            ->first();

        $successRate = $calculations->total_calculations > 0 
            ? ($calculations->successful_calculations / $calculations->total_calculations) * 100 
            : 0;

        return [
            'total_calculations' => $calculations->total_calculations ?? 0,
            'total_tax_calculated' => $calculations->total_tax_calculated ?? 0,
            'avg_calculation_time' => $calculations->avg_calculation_time ?? 0,
            'success_rate' => round($successRate, 2),
            'failed_calculations' => $calculations->failed_calculations ?? 0,
        ];
    }

    private function getJurisdictionBreakdown(int $companyId, array $dateRange): array
    {
        return DB::table('tax_calculations')
            ->join('tax_jurisdictions', 'tax_calculations.jurisdiction_data->id', '=', 'tax_jurisdictions.id')
            ->where('tax_calculations.company_id', $companyId)
            ->whereBetween('tax_calculations.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('tax_jurisdictions.id', 'tax_jurisdictions.name', 'tax_jurisdictions.state')
            ->selectRaw('
                tax_jurisdictions.name,
                tax_jurisdictions.state,
                COUNT(*) as calculation_count,
                SUM(tax_calculations.total_tax_amount) as total_tax_amount
            ')
            ->orderBy('total_tax_amount', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getEnginePerformanceMetrics(int $companyId, array $dateRange): array
    {
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('engine_used')
            ->selectRaw('
                engine_used,
                COUNT(*) as usage_count,
                AVG(calculation_time_ms) as avg_time,
                MIN(calculation_time_ms) as min_time,
                MAX(calculation_time_ms) as max_time,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count
            ')
            ->get()
            ->map(function ($engine) {
                $engine->success_rate = $engine->usage_count > 0 
                    ? ($engine->success_count / $engine->usage_count) * 100 
                    : 0;
                return $engine;
            })
            ->toArray();
    }

    private function getTaxDataGrouped(int $companyId, array $dateRange, string $groupBy): array
    {
        $dateFormat = match($groupBy) {
            'day' => 'YYYY-MM-DD',
            'week' => 'YYYY-WW',
            'month' => 'YYYY-MM',
            default => 'YYYY-MM'
        };

        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->groupByRaw("TO_CHAR(created_at, '{$dateFormat}')")
            ->selectRaw("
                TO_CHAR(created_at, '{$dateFormat}') as period,
                COUNT(*) as calculation_count,
                SUM(total_tax_amount) as total_tax,
                AVG(total_tax_amount) as avg_tax,
                SUM(base_amount) as total_base
            ")
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    private function getTaxTypeBreakdown(int $companyId, array $dateRange): array
    {
        // This would need to be adjusted based on how tax breakdown is stored
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->groupBy('engine_type')
            ->selectRaw('
                engine_type as tax_type,
                COUNT(*) as calculation_count,
                SUM(total_tax_amount) as total_amount
            ')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }

    private function getTopJurisdictions(int $companyId, array $dateRange): array
    {
        // Implementation would depend on how jurisdiction data is stored
        return [];
    }

    private function getInvoiceQuoteComparison(int $companyId, array $dateRange): array
    {
        $invoiceTaxes = TaxCalculation::where('company_id', $companyId)
            ->where('calculable_type', 'App\Models\Invoice')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as count,
                SUM(total_tax_amount) as total_tax,
                AVG(total_tax_amount) as avg_tax
            ')
            ->first();

        $quoteTaxes = TaxCalculation::where('company_id', $companyId)
            ->where('calculable_type', 'App\Models\Quote')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as count,
                SUM(total_tax_amount) as total_tax,
                AVG(total_tax_amount) as avg_tax
            ')
            ->first();

        return [
            'invoices' => $invoiceTaxes ? $invoiceTaxes->toArray() : ['count' => 0, 'total_tax' => 0, 'avg_tax' => 0],
            'quotes' => $quoteTaxes ? $quoteTaxes->toArray() : ['count' => 0, 'total_tax' => 0, 'avg_tax' => 0],
        ];
    }

    private function getComplianceData(int $companyId, array $dateRange): array
    {
        return [
            'total_tax_profiles' => TaxProfile::where('company_id', $companyId)->count(),
            'active_tax_profiles' => TaxProfile::where('company_id', $companyId)->where('is_active', true)->count(),
            'total_tax_rates' => ServiceTaxRate::where('company_id', $companyId)->count(),
            'active_tax_rates' => ServiceTaxRate::where('company_id', $companyId)->active()->count(),
            'jurisdictions' => TaxJurisdiction::where('company_id', $companyId)->count(),
        ];
    }

    private function getExemptionUsage(int $companyId, array $dateRange): array
    {
        // Implementation would depend on exemption tracking system
        return [];
    }

    private function getCalculationIssues(int $companyId, array $dateRange): array
    {
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'error')
            ->selectRaw('
                created_at::date as error_date,
                COUNT(*) as error_count,
                error_message
            ')
            ->groupBy('error_date', 'error_message')
            ->orderBy('error_date', 'desc')
            ->get()
            ->toArray();
    }

    private function getRateChanges(int $companyId, array $dateRange): array
    {
        return ServiceTaxRate::where('company_id', $companyId)
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->with(['jurisdiction'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
    }

    private function getDetailedPerformanceMetrics(int $companyId, array $dateRange): array
    {
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                COUNT(*) as total_calculations,
                AVG(calculation_time_ms) as avg_time,
                MIN(calculation_time_ms) as min_time,
                MAX(calculation_time_ms) as max_time,
                STDDEV(calculation_time_ms) as stddev_time,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY calculation_time_ms) as median_time,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY calculation_time_ms) as p95_time
            ')
            ->first()
            ->toArray();
    }

    private function getErrorAnalysis(int $companyId, array $dateRange): array
    {
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'error')
            ->groupBy('error_message')
            ->selectRaw('
                error_message,
                COUNT(*) as occurrence_count,
                MIN(created_at)::date as first_occurrence,
                MAX(created_at)::date as last_occurrence
            ')
            ->orderBy('occurrence_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getCachePerformanceMetrics(int $companyId): array
    {
        // Get cache statistics from the tax engine
        return Cache::get("tax_performance_stats_{$companyId}", [
            'cache_hit_rate' => 0,
            'avg_cache_lookup_time' => 0,
            'profiles_cached' => 0,
            'rates_cached' => 0,
        ]);
    }

    private function getEngineComparison(int $companyId, array $dateRange): array
    {
        return $this->getEnginePerformanceMetrics($companyId, $dateRange);
    }

    // Export methods would be implemented here
    private function exportSummaryReport(int $companyId, array $dateRange, string $format)
    {
        // Implementation for CSV/Excel export
        return response()->download('/path/to/export');
    }

    private function exportJurisdictionReport(int $companyId, array $dateRange, string $format)
    {
        // Implementation for jurisdiction report export
        return response()->download('/path/to/export');
    }

    private function exportComplianceReport(int $companyId, array $dateRange, string $format)
    {
        // Implementation for compliance report export
        return response()->download('/path/to/export');
    }

    private function exportPerformanceReport(int $companyId, array $dateRange, string $format)
    {
        // Implementation for performance report export
        return response()->download('/path/to/export');
    }

    private function getTaxTrendData(int $companyId, array $dateRange): array
    {
        return $this->getTaxDataGrouped($companyId, $dateRange, 'day');
    }

    private function getErrorRateData(int $companyId, array $dateRange): array
    {
        return TaxCalculation::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupByRaw('created_at::date')
            ->selectRaw('
                created_at::date as date,
                COUNT(*) as total_calculations,
                SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as error_count
            ')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->error_rate = $item->total_calculations > 0 
                    ? ($item->error_count / $item->total_calculations) * 100 
                    : 0;
                return $item;
            })
            ->toArray();
    }

    private function getJurisdictionDetailedData(int $companyId, int $jurisdictionId, array $dateRange): array
    {
        // Implementation for detailed jurisdiction analysis
        return [];
    }

    private function getJurisdictionSummary(int $companyId, array $dateRange): array
    {
        // Implementation for jurisdiction summary data
        return [];
    }
}