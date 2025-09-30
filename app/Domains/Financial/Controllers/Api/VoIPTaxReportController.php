<?php

namespace App\Domains\Financial\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Financial\Services\VoIPTaxReportingService;
use App\Models\TaxJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

/**
 * VoIP Tax Report Controller
 * 
 * RESTful API endpoints for VoIP tax reporting and analytics.
 */
class VoIPTaxReportController extends Controller
{
    protected VoIPTaxReportingService $reportingService;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            $companyId = Auth::user()->company_id ?? 1;
            $this->reportingService = new VoIPTaxReportingService($companyId);
            return $next($request);
        });
    }

    /**
     * Generate tax summary report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function taxSummary(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'client_id' => 'nullable|integer|exists:clients,id',
                'service_type' => 'nullable|string|in:local,long_distance,international,voip_fixed,voip_nomadic,data,equipment',
                'jurisdiction_id' => 'nullable|integer|exists:tax_jurisdictions,id',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            // Validate date range (max 1 year)
            if ($startDate->diffInDays($endDate) > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range cannot exceed 365 days',
                ], 400);
            }

            $filters = array_filter([
                'client_id' => $validated['client_id'] ?? null,
                'service_type' => $validated['service_type'] ?? null,
                'jurisdiction_id' => $validated['jurisdiction_id'] ?? null,
            ]);

            $report = $this->reportingService->generateTaxSummaryReport($startDate, $endDate, $filters);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Tax summary report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Report generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate jurisdiction-specific report.
     *
     * @param Request $request
     * @param int $jurisdictionId
     * @return JsonResponse
     */
    public function jurisdictionReport(Request $request, int $jurisdictionId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            // Verify jurisdiction exists and belongs to user's company
            $jurisdiction = TaxJurisdiction::where('id', $jurisdictionId)
                ->where('company_id', Auth::user()->company_id ?? 1)
                ->first();

            if (!$jurisdiction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jurisdiction not found or access denied',
                ], 404);
            }

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->reportingService->generateJurisdictionReport($jurisdictionId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Jurisdiction report generation failed', [
                'jurisdiction_id' => $jurisdictionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Jurisdiction report generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate service type analysis report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function serviceTypeAnalysis(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->reportingService->generateServiceTypeAnalysis($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Service type analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service type analysis failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate exemption usage report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exemptionReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'exemption_type' => 'nullable|string|in:nonprofit,government,reseller,manufacturing,agriculture,export',
                'client_id' => 'nullable|integer|exists:clients,id',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->reportingService->generateExemptionReport($startDate, $endDate);

            // Apply additional filters if specified
            if (isset($validated['exemption_type'])) {
                $report['by_exemption_type'] = array_filter($report['by_exemption_type'], function ($item) use ($validated) {
                    return $item['exemption_type'] === $validated['exemption_type'];
                });
            }

            if (isset($validated['client_id'])) {
                $report['by_client'] = array_filter($report['by_client'], function ($item) use ($validated) {
                    return $item['client_id'] === $validated['client_id'];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Exemption report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exemption report generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate tax rate effectiveness report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rateEffectiveness(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->reportingService->generateRateEffectivenessReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Rate effectiveness report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rate effectiveness report generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get dashboard data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'nullable|string|in:today,week,month,quarter,year',
                'start_date' => 'nullable|date|before_or_equal:end_date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            // Determine date range
            if (isset($validated['start_date']) && isset($validated['end_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['end_date']);
            } else {
                $period = $validated['period'] ?? 'month';
                [$startDate, $endDate] = $this->getDateRangeForPeriod($period);
            }

            $dashboardData = $this->reportingService->generateDashboardData($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Dashboard data generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dashboard data generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export report data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:tax_summary,jurisdiction,service_type,exemption,rate_effectiveness',
                'format' => 'nullable|string|in:json,csv,xlsx',
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'jurisdiction_id' => 'nullable|integer|exists:tax_jurisdictions,id',
                'filters' => 'nullable|array',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $format = $validated['format'] ?? 'json';
            $filters = $validated['filters'] ?? [];

            // Generate the appropriate report
            $reportData = match ($validated['report_type']) {
                'tax_summary' => $this->reportingService->generateTaxSummaryReport($startDate, $endDate, $filters),
                'jurisdiction' => $this->reportingService->generateJurisdictionReport($validated['jurisdiction_id'], $startDate, $endDate),
                'service_type' => $this->reportingService->generateServiceTypeAnalysis($startDate, $endDate),
                'exemption' => $this->reportingService->generateExemptionReport($startDate, $endDate),
                'rate_effectiveness' => $this->reportingService->generateRateEffectivenessReport($startDate, $endDate),
                default => throw new \InvalidArgumentException('Invalid report type'),
            };

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $reportData,
                ]);
            }

            // For CSV/Excel export, we would typically generate a download
            // For now, return the data with export metadata
            return response()->json([
                'success' => true,
                'message' => 'Export prepared successfully',
                'data' => $reportData,
                'export_info' => [
                    'format' => $format,
                    'filename' => "voip_tax_report_{$validated['report_type']}_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.{$format}",
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Report export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Report export failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get available jurisdictions for reporting.
     *
     * @return JsonResponse
     */
    public function availableJurisdictions(): JsonResponse
    {
        try {
            $companyId = Auth::user()->company_id ?? 1;
            
            $jurisdictions = TaxJurisdiction::where('company_id', $companyId)
                ->active()
                ->select(['id', 'name', 'jurisdiction_type', 'authority_name'])
                ->orderBy('jurisdiction_type')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $jurisdictions->groupBy('jurisdiction_type'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch available jurisdictions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jurisdictions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get report parameters and metadata.
     *
     * @return JsonResponse
     */
    public function reportMetadata(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'available_reports' => [
                    'tax_summary' => [
                        'name' => 'Tax Summary Report',
                        'description' => 'Comprehensive overview of tax collections and breakdowns',
                        'filters' => ['client_id', 'service_type', 'jurisdiction_id'],
                    ],
                    'jurisdiction' => [
                        'name' => 'Jurisdiction Report',
                        'description' => 'Detailed report for a specific tax jurisdiction',
                        'required_params' => ['jurisdiction_id'],
                    ],
                    'service_type' => [
                        'name' => 'Service Type Analysis',
                        'description' => 'Analysis of tax performance by VoIP service type',
                    ],
                    'exemption' => [
                        'name' => 'Exemption Usage Report',
                        'description' => 'Report on tax exemption usage and effectiveness',
                        'filters' => ['exemption_type', 'client_id'],
                    ],
                    'rate_effectiveness' => [
                        'name' => 'Rate Effectiveness Report',
                        'description' => 'Analysis of tax rate utilization and recommendations',
                    ],
                ],
                'service_types' => [
                    'local' => 'Local Service',
                    'long_distance' => 'Long Distance',
                    'international' => 'International',
                    'voip_fixed' => 'VoIP Fixed',
                    'voip_nomadic' => 'VoIP Nomadic',
                    'data' => 'Data Services',
                    'equipment' => 'Equipment',
                ],
                'exemption_types' => [
                    'nonprofit' => 'Non-profit Organization',
                    'government' => 'Government Entity',
                    'reseller' => 'Reseller',
                    'manufacturing' => 'Manufacturing',
                    'agriculture' => 'Agriculture',
                    'export' => 'Export',
                ],
                'export_formats' => ['json', 'csv', 'xlsx'],
                'date_presets' => [
                    'today' => 'Today',
                    'week' => 'This Week',
                    'month' => 'This Month',
                    'quarter' => 'This Quarter',
                    'year' => 'This Year',
                ],
            ],
        ]);
    }

    /**
     * Get date range for a given period.
     *
     * @param string $period
     * @return array
     */
    protected function getDateRangeForPeriod(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}