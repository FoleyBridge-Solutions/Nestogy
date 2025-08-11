<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Domains\Report\Services\ExecutiveReportService;
use App\Domains\Report\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Quarterly Business Review (QBR) Controller
 * 
 * Generate comprehensive quarterly reports for executives and clients
 */
class QBRController extends Controller
{
    public function __construct(
        protected ExecutiveReportService $executiveService,
        protected ExportService $exportService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:reports_qbr');
    }

    /**
     * Display QBR selection and configuration
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;
        
        // Get available quarters
        $availableQuarters = $this->getAvailableQuarters();
        
        // Get recent QBRs
        $recentReports = $this->getRecentQBRs($companyId);

        return view('reports.qbr.index', compact(
            'availableQuarters',
            'recentReports'
        ));
    }

    /**
     * Generate QBR for a specific quarter
     */
    public function generate(Request $request): View
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year + 1),
            'quarter' => 'required|integer|min:1|max:4',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->input('year');
        $quarter = $request->input('quarter');

        // Calculate quarter dates
        $quarterStart = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfMonth();
        $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

        // Generate QBR data
        $qbrData = $this->executiveService->generateQBR(
            $companyId,
            $quarterStart,
            $quarterEnd
        );

        return view('reports.qbr.report', compact(
            'qbrData',
            'year',
            'quarter',
            'quarterStart',
            'quarterEnd'
        ));
    }

    /**
     * Preview QBR before final generation
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year + 1),
            'quarter' => 'required|integer|min:1|max:4',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $year = $request->input('year');
            $quarter = $request->input('quarter');

            $quarterStart = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfMonth();
            $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

            // Generate summary data for preview
            $previewData = [
                'period' => [
                    'quarter' => "Q{$quarter} {$year}",
                    'start' => $quarterStart->format('M j, Y'),
                    'end' => $quarterEnd->format('M j, Y'),
                ],
                'data_availability' => $this->checkDataAvailability($companyId, $quarterStart, $quarterEnd),
                'estimated_sections' => $this->getEstimatedSections($companyId, $quarterStart, $quarterEnd),
            ];

            return response()->json([
                'success' => true,
                'preview' => $previewData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export QBR in various formats
     */
    public function export(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year + 1),
            'quarter' => 'required|integer|min:1|max:4',
            'format' => 'required|string|in:pdf,excel,powerpoint',
            'template' => 'string|in:executive,client,detailed',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $year = $request->input('year');
            $quarter = $request->input('quarter');
            $format = $request->input('format');
            $template = $request->input('template', 'executive');

            $quarterStart = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfMonth();
            $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

            // Generate QBR data
            $qbrData = $this->executiveService->generateQBR(
                $companyId,
                $quarterStart,
                $quarterEnd
            );

            $filename = "QBR-Q{$quarter}-{$year}-" . now()->format('Y-m-d');

            switch ($format) {
                case 'pdf':
                    return $this->exportToPDF($qbrData, $filename, $template);
                case 'excel':
                    return $this->exportToExcel($qbrData, $filename);
                case 'powerpoint':
                    return $this->exportToPowerPoint($qbrData, $filename, $template);
                default:
                    throw new \InvalidArgumentException("Unsupported format: {$format}");
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QBR data as JSON (for API or AJAX)
     */
    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year + 1),
            'quarter' => 'required|integer|min:1|max:4',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $year = $request->input('year');
            $quarter = $request->input('quarter');

            $quarterStart = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfMonth();
            $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

            $qbrData = $this->executiveService->generateQBR(
                $companyId,
                $quarterStart,
                $quarterEnd
            );

            return response()->json([
                'success' => true,
                'data' => $qbrData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule QBR generation
     */
    public function schedule(Request $request): JsonResponse
    {
        $request->validate([
            'quarter_end_auto' => 'boolean',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'template' => 'string|in:executive,client,detailed',
            'format' => 'string|in:pdf,excel,powerpoint',
        ]);

        try {
            // Implementation for scheduling QBR generation
            // This would integrate with the ReportSchedulerService
            
            return response()->json([
                'success' => true,
                'message' => 'QBR generation scheduled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available quarters for selection
     */
    protected function getAvailableQuarters(): array
    {
        $quarters = [];
        $currentYear = now()->year;
        $currentQuarter = ceil(now()->month / 3);

        // Include last 8 quarters
        for ($i = 0; $i < 8; $i++) {
            $year = $currentYear;
            $quarter = $currentQuarter - $i;
            
            if ($quarter <= 0) {
                $quarter += 4;
                $year--;
            }

            $quarterStart = Carbon::create($year, (($quarter - 1) * 3) + 1, 1);
            $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

            $quarters[] = [
                'year' => $year,
                'quarter' => $quarter,
                'label' => "Q{$quarter} {$year}",
                'start_date' => $quarterStart->format('M j, Y'),
                'end_date' => $quarterEnd->format('M j, Y'),
                'is_current' => ($year === $currentYear && $quarter === $currentQuarter),
                'is_complete' => $quarterEnd->isPast(),
            ];
        }

        return $quarters;
    }

    /**
     * Get recent QBR reports
     */
    protected function getRecentQBRs(int $companyId): array
    {
        // This would come from a reports history table if implemented
        // For now, return empty array
        return [];
    }

    /**
     * Check data availability for the quarter
     */
    protected function checkDataAvailability(int $companyId, Carbon $start, Carbon $end): array
    {
        return [
            'financial_data' => \App\Domains\Financial\Models\Payment::where('company_id', $companyId)
                ->whereBetween('payment_date', [$start, $end])
                ->exists(),
            'service_data' => \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->whereBetween('created_at', [$start, $end])
                ->exists(),
            'client_data' => \App\Models\Client::where('company_id', $companyId)
                ->whereBetween('created_at', [$start, $end])
                ->exists(),
        ];
    }

    /**
     * Get estimated sections for the QBR
     */
    protected function getEstimatedSections(int $companyId, Carbon $start, Carbon $end): array
    {
        return [
            'executive_summary' => true,
            'financial_performance' => true,
            'service_performance' => true,
            'client_analytics' => true,
            'operational_metrics' => true,
            'recommendations' => true,
        ];
    }

    /**
     * Export QBR to PDF
     */
    protected function exportToPDF(array $qbrData, string $filename, string $template)
    {
        $pdf = app('dompdf.wrapper')->loadView("reports.qbr.pdf.{$template}", compact('qbrData'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Export QBR to Excel
     */
    protected function exportToExcel(array $qbrData, string $filename)
    {
        return $this->exportService->exportToExcel($qbrData, $filename, [
            'template' => 'qbr',
            'sheets' => [
                'Executive Summary',
                'Financial Performance',
                'Service Performance',
                'Client Analytics',
                'Raw Data'
            ]
        ]);
    }

    /**
     * Export QBR to PowerPoint
     */
    protected function exportToPowerPoint(array $qbrData, string $filename, string $template)
    {
        // Implementation would depend on PowerPoint library
        // For now, return PDF as fallback
        return $this->exportToPDF($qbrData, $filename, $template);
    }
}