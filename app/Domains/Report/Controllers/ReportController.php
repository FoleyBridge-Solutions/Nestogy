<?php

namespace App\Domains\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    /**
     * Display the reports dashboard
     */
    public function index()
    {
        $categories = $this->reportService->getCategories();
        $frequentReports = $this->reportService->getFrequentReports(6);
        $scheduledReports = $this->reportService->getScheduledReports();
        
        // Get quick stats for dashboard
        $stats = [
            'reports_generated_today' => Cache::remember('reports_today_' . auth()->id(), 300, function () {
                return 12; // This would query report logs
            }),
            'scheduled_reports' => count($scheduledReports),
            'saved_reports' => 8, // This would query saved reports
            'shared_reports' => 3  // This would query shared reports
        ];
        
        return view('reports.index', compact('categories', 'frequentReports', 'scheduledReports', 'stats'));
    }
    
    /**
     * Display reports for a specific category
     */
    public function category($category)
    {
        $categories = $this->reportService->getCategories();
        
        if (!isset($categories[$category])) {
            abort(404, 'Report category not found');
        }
        
        $categoryInfo = $categories[$category];
        $reports = $this->reportService->getReportsByCategory($category);
        
        return view('reports.category', compact('category', 'categoryInfo', 'reports'));
    }
    
    /**
     * Display the report builder/generator form
     */
    public function builder($reportId)
    {
        // Get report metadata
        $reportInfo = $this->getReportInfo($reportId);
        
        if (!$reportInfo) {
            abort(404, 'Report not found');
        }
        
        // Get available filters for this report
        $filters = $this->getReportFilters($reportId);
        
        return view('reports.builder', compact('reportId', 'reportInfo', 'filters'));
    }
    
    /**
     * Generate and display a report
     */
    public function generate(Request $request, $reportId)
    {
        try {
            // Validate request parameters
            $params = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'client_id' => 'nullable|exists:clients,id',
                'user_id' => 'nullable|exists:users,id',
                'project_id' => 'nullable|exists:projects,id',
                'status' => 'nullable|string',
                'category' => 'nullable|string',
                'group_by' => 'nullable|string',
                'format' => 'nullable|in:html,pdf,excel,csv,json'
            ]);
            
            // Generate the report
            $data = $this->reportService->generateReport($reportId, $params);
            
            // Handle different output formats
            $format = $request->get('format', 'html');
            
            switch ($format) {
                case 'pdf':
                    return $this->reportService->exportToPdf($reportId, $data, $params);
                    
                case 'excel':
                    return $this->reportService->exportToExcel($reportId, $data, $params);
                    
                case 'csv':
                    return $this->exportToCsv($reportId, $data);
                    
                case 'json':
                    return response()->json($data);
                    
                default:
                    $reportInfo = $this->getReportInfo($reportId);
                    return view('reports.view', compact('reportId', 'reportInfo', 'data', 'params'));
            }
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating report: ' . $e->getMessage());
        }
    }
    
    /**
     * Financial reports page
     */
    public function financial(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('financial');
        $categoryInfo = $this->reportService->getCategories()['financial'];
        
        return view('reports.financial', compact('reports', 'categoryInfo'));
    }
    
    /**
     * Ticket reports page
     */
    public function tickets(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('operational');
        $categoryInfo = $this->reportService->getCategories()['operational'];
        
        // Filter to just ticket-related reports
        $reports = array_filter($reports, function ($report) {
            return strpos($report['id'], 'ticket') !== false || 
                   strpos($report['id'], 'sla') !== false ||
                   strpos($report['id'], 'response') !== false ||
                   strpos($report['id'], 'resolution') !== false;
        });
        
        return view('reports.tickets', compact('reports', 'categoryInfo'));
    }
    
    /**
     * Asset reports page
     */
    public function assets(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('asset');
        $categoryInfo = $this->reportService->getCategories()['asset'];
        
        return view('reports.assets', compact('reports', 'categoryInfo'));
    }
    
    /**
     * Client reports page
     */
    public function clients(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('client');
        $categoryInfo = $this->reportService->getCategories()['client'];
        
        return view('reports.clients', compact('reports', 'categoryInfo'));
    }
    
    /**
     * Project reports page
     */
    public function projects(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('project');
        $categoryInfo = $this->reportService->getCategories()['project'];
        
        return view('reports.projects', compact('reports', 'categoryInfo'));
    }
    
    /**
     * User/resource reports page
     */
    public function users(Request $request)
    {
        $reports = $this->reportService->getReportsByCategory('resource');
        $categoryInfo = $this->reportService->getCategories()['resource'];
        
        return view('reports.users', compact('reports', 'categoryInfo'));
    }
    
    /**
     * Save a report configuration
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'required|array',
            'schedule' => 'nullable|string'
        ]);
        
        // Save to database (would need a saved_reports table)
        // For now, just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Report saved successfully'
        ]);
    }
    
    /**
     * Schedule a report
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,excel,csv',
            'parameters' => 'required|array'
        ]);
        
        // Save to scheduled_reports table
        // Set up cron job or queue job
        
        return response()->json([
            'success' => true,
            'message' => 'Report scheduled successfully'
        ]);
    }
    
    /**
     * Get report information
     */
    protected function getReportInfo($reportId)
    {
        $allReports = [];
        $categories = $this->reportService->getCategories();
        
        foreach (array_keys($categories) as $category) {
            $reports = $this->reportService->getReportsByCategory($category);
            foreach ($reports as $report) {
                if ($report['id'] === $reportId) {
                    $report['category'] = $category;
                    return $report;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get available filters for a report
     */
    protected function getReportFilters($reportId)
    {
        $filters = [
            'revenue-summary' => [
                'date_range' => true,
                'client' => true,
                'service_type' => true
            ],
            'invoice-aging' => [
                'as_of_date' => true,
                'client' => true,
                'min_amount' => true
            ],
            'ticket-volume' => [
                'date_range' => true,
                'status' => true,
                'priority' => true,
                'category' => true,
                'technician' => true
            ],
            'client-activity' => [
                'date_range' => true,
                'client' => true,
                'include_inactive' => true
            ],
            'staff-utilization' => [
                'date_range' => true,
                'department' => true,
                'user' => true,
                'minimum_hours' => true
            ]
        ];
        
        return $filters[$reportId] ?? ['date_range' => true];
    }
    
    /**
     * Export report to CSV
     */
    protected function exportToCsv($reportId, $data)
    {
        $filename = $reportId . '-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Convert data to CSV format
            if (isset($data['details']) && is_array($data['details'])) {
                // Write headers
                if (count($data['details']) > 0) {
                    fputcsv($file, array_keys((array)$data['details'][0]));
                }
                
                // Write data
                foreach ($data['details'] as $row) {
                    fputcsv($file, (array)$row);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}