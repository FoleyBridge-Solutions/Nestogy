<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\User;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Project\Models\Project;
use App\Domains\Asset\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    protected $companyId;
    protected $userId;
    
    public function __construct()
    {
        $this->companyId = auth()->user()->company_id ?? null;
        $this->userId = auth()->id();
    }
    
    /**
     * Get available report categories
     */
    public function getCategories()
    {
        return [
            'financial' => [
                'name' => 'Financial Reports',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'description' => 'Revenue, billing, profitability, and expense reports',
                'count' => 16
            ],
            'operational' => [
                'name' => 'Operational Reports',
                'icon' => 'cog',
                'color' => 'blue',
                'description' => 'Service delivery, tickets, and productivity reports',
                'count' => 12
            ],
            'client' => [
                'name' => 'Client Reports',
                'icon' => 'users',
                'color' => 'purple',
                'description' => 'Client engagement, growth, and satisfaction reports',
                'count' => 10
            ],
            'resource' => [
                'name' => 'Resource Reports',
                'icon' => 'user-group',
                'color' => 'indigo',
                'description' => 'Staff performance, utilization, and scheduling reports',
                'count' => 10
            ],
            'project' => [
                'name' => 'Project Reports',
                'icon' => 'folder',
                'color' => 'yellow',
                'description' => 'Project status, performance, and ROI reports',
                'count' => 10
            ],
            'asset' => [
                'name' => 'Asset Reports',
                'icon' => 'computer-desktop',
                'color' => 'gray',
                'description' => 'Asset inventory, maintenance, and financial reports',
                'count' => 8
            ],
            'executive' => [
                'name' => 'Executive Reports',
                'icon' => 'chart-pie',
                'color' => 'red',
                'description' => 'KPI dashboards and strategic reports',
                'count' => 8
            ],
            'compliance' => [
                'name' => 'Compliance Reports',
                'icon' => 'shield-check',
                'color' => 'orange',
                'description' => 'Audit, security, and compliance reports',
                'count' => 5
            ]
        ];
    }
    
    /**
     * Get reports for a specific category
     */
    public function getReportsByCategory($category)
    {
        $reports = [
            'financial' => [
                ['id' => 'revenue-summary', 'name' => 'Revenue Summary', 'description' => 'Total revenue by period, client, service type'],
                ['id' => 'recurring-revenue', 'name' => 'Recurring Revenue (MRR/ARR)', 'description' => 'Monthly and annual recurring revenue trends'],
                ['id' => 'invoice-aging', 'name' => 'Invoice Aging', 'description' => 'Outstanding invoices by age brackets'],
                ['id' => 'payment-history', 'name' => 'Payment History', 'description' => 'All payments received with methods and dates'],
                ['id' => 'collections', 'name' => 'Collections Report', 'description' => 'Overdue accounts and collection efforts'],
                ['id' => 'profit-loss', 'name' => 'Profit & Loss Statement', 'description' => 'Standard P&L by period'],
                ['id' => 'client-profitability', 'name' => 'Client Profitability', 'description' => 'Revenue vs costs per client'],
                ['id' => 'cash-flow', 'name' => 'Cash Flow Statement', 'description' => 'Operating, investing, financing activities'],
                ['id' => 'expense-summary', 'name' => 'Expense Summary', 'description' => 'Expenses by category, department, project'],
            ],
            'operational' => [
                ['id' => 'sla-compliance', 'name' => 'SLA Compliance', 'description' => 'Performance against service level agreements'],
                ['id' => 'response-time', 'name' => 'First Response Time', 'description' => 'Ticket response time metrics'],
                ['id' => 'resolution-time', 'name' => 'Resolution Time', 'description' => 'Average time to resolve by priority'],
                ['id' => 'ticket-volume', 'name' => 'Ticket Volume', 'description' => 'Tickets by status, priority, category'],
                ['id' => 'ticket-aging', 'name' => 'Ticket Aging', 'description' => 'Open tickets by age'],
                ['id' => 'technician-performance', 'name' => 'Technician Performance', 'description' => 'Tickets handled and resolution rates'],
                ['id' => 'utilization', 'name' => 'Utilization Report', 'description' => 'Billable vs non-billable hours'],
                ['id' => 'backlog', 'name' => 'Backlog Report', 'description' => 'Work in queue and estimated completion'],
            ],
            'client' => [
                ['id' => 'client-activity', 'name' => 'Client Activity', 'description' => 'All interactions, tickets, and projects'],
                ['id' => 'client-health', 'name' => 'Client Health Score', 'description' => 'Risk indicators and engagement metrics'],
                ['id' => 'service-usage', 'name' => 'Service Usage', 'description' => 'Services consumed per client'],
                ['id' => 'account-growth', 'name' => 'Account Growth', 'description' => 'Revenue growth by client'],
                ['id' => 'client-retention', 'name' => 'Client Retention', 'description' => 'Churn analysis and trends'],
                ['id' => 'nps-report', 'name' => 'Net Promoter Score', 'description' => 'Client satisfaction trends'],
            ],
            'resource' => [
                ['id' => 'staff-utilization', 'name' => 'Staff Utilization', 'description' => 'Individual and team utilization rates'],
                ['id' => 'performance-scorecard', 'name' => 'Performance Scorecard', 'description' => 'KPIs per employee'],
                ['id' => 'time-tracking', 'name' => 'Time Tracking', 'description' => 'Detailed time entries by person/project'],
                ['id' => 'resource-allocation', 'name' => 'Resource Allocation', 'description' => 'Who is working on what'],
                ['id' => 'availability', 'name' => 'Availability Report', 'description' => 'Current and future availability'],
                ['id' => 'skills-matrix', 'name' => 'Skills Matrix', 'description' => 'Skills vs demand analysis'],
            ],
            'project' => [
                ['id' => 'project-status', 'name' => 'Project Status Dashboard', 'description' => 'Overall health of all projects'],
                ['id' => 'milestone-report', 'name' => 'Milestone Report', 'description' => 'Upcoming and overdue milestones'],
                ['id' => 'project-timeline', 'name' => 'Project Timeline', 'description' => 'Gantt chart view of projects'],
                ['id' => 'project-burnrate', 'name' => 'Project Burn Rate', 'description' => 'Budget consumption rate'],
                ['id' => 'project-variance', 'name' => 'Project Variance', 'description' => 'Schedule and cost variances'],
                ['id' => 'project-roi', 'name' => 'Project ROI', 'description' => 'Return on investment analysis'],
            ],
            'asset' => [
                ['id' => 'asset-inventory', 'name' => 'Asset Inventory', 'description' => 'Complete asset listing'],
                ['id' => 'asset-lifecycle', 'name' => 'Asset Lifecycle', 'description' => 'Age, depreciation, replacement schedule'],
                ['id' => 'asset-assignment', 'name' => 'Asset Assignment', 'description' => 'Who has what equipment'],
                ['id' => 'maintenance-report', 'name' => 'Maintenance Report', 'description' => 'Maintenance history and schedules'],
                ['id' => 'warranty-expiration', 'name' => 'Warranty Expiration', 'description' => 'Upcoming warranty expirations'],
                ['id' => 'asset-depreciation', 'name' => 'Asset Depreciation', 'description' => 'Book value and depreciation'],
            ],
            'executive' => [
                ['id' => 'executive-dashboard', 'name' => 'Executive Dashboard', 'description' => 'High-level KPIs and trends'],
                ['id' => 'department-scorecard', 'name' => 'Department Scorecard', 'description' => 'Performance by department'],
                ['id' => 'revenue-forecast', 'name' => 'Revenue Forecast', 'description' => 'Projected revenue by period'],
                ['id' => 'growth-projection', 'name' => 'Growth Projection', 'description' => 'Business growth scenarios'],
            ],
            'compliance' => [
                ['id' => 'audit-trail', 'name' => 'Audit Trail', 'description' => 'System changes and user actions'],
                ['id' => 'compliance-status', 'name' => 'Compliance Status', 'description' => 'Regulatory compliance tracking'],
                ['id' => 'security-incidents', 'name' => 'Security Incidents', 'description' => 'Security events and responses'],
                ['id' => 'data-access', 'name' => 'Data Access Report', 'description' => 'Who accessed what data'],
            ]
        ];
        
        return $reports[$category] ?? [];
    }
    
    /**
     * Generate a specific report
     */
    public function generateReport($reportId, $params = [])
    {
        $method = str_replace('-', '', ucwords($reportId, '-'));
        $method = 'generate' . $method . 'Report';
        
        if (method_exists($this, $method)) {
            return $this->$method($params);
        }
        
        throw new \Exception("Report generator not found: {$reportId}");
    }
    
    /**
     * Revenue Summary Report
     */
    protected function generateRevenueSummaryReport($params)
    {
        $startDate = Carbon::parse($params['start_date'] ?? now()->subMonths(12));
        $endDate = Carbon::parse($params['end_date'] ?? now());
        
        // Monthly revenue trend
        $monthlyRevenue = Invoice::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial'])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('SUM(total) as revenue')
            ->selectRaw('COUNT(*) as invoice_count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Revenue by client
        $clientRevenue = Invoice::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial'])
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->selectRaw('clients.name as client_name')
            ->selectRaw('SUM(invoices.total) as revenue')
            ->selectRaw('COUNT(invoices.id) as invoice_count')
            ->groupBy('clients.id', 'clients.name')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();
        
        // Revenue by service type
        $serviceRevenue = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $this->companyId)
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->whereIn('invoices.status', ['paid', 'partial'])
            ->selectRaw('invoice_items.description as service')
            ->selectRaw('SUM(invoice_items.total) as revenue')
            ->groupBy('invoice_items.description')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();
        
        // Summary metrics
        $totalRevenue = $monthlyRevenue->sum('revenue');
        $avgMonthlyRevenue = $monthlyRevenue->avg('revenue');
        $totalInvoices = $monthlyRevenue->sum('invoice_count');
        $avgInvoiceValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;
        
        return [
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'avg_monthly_revenue' => $avgMonthlyRevenue,
                'total_invoices' => $totalInvoices,
                'avg_invoice_value' => $avgInvoiceValue,
            ],
            'monthly_trend' => $monthlyRevenue,
            'top_clients' => $clientRevenue,
            'top_services' => $serviceRevenue,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ];
    }
    
    /**
     * Invoice Aging Report
     */
    protected function generateInvoiceAgingReport($params)
    {
        $asOfDate = Carbon::parse($params['as_of_date'] ?? now());
        
        $aging = Invoice::where('company_id', $this->companyId)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->with('client')
            ->get()
            ->map(function ($invoice) use ($asOfDate) {
                $daysOld = $asOfDate->diffInDays($invoice->due_date, false);
                
                if ($daysOld >= 0) {
                    $bracket = 'Current';
                } elseif ($daysOld >= -30) {
                    $bracket = '1-30 days';
                } elseif ($daysOld >= -60) {
                    $bracket = '31-60 days';
                } elseif ($daysOld >= -90) {
                    $bracket = '61-90 days';
                } elseif ($daysOld >= -120) {
                    $bracket = '91-120 days';
                } else {
                    $bracket = '120+ days';
                }
                
                return [
                    'invoice_number' => $invoice->number,
                    'client' => $invoice->client->name,
                    'invoice_date' => $invoice->created_at->format('Y-m-d'),
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'days_overdue' => abs($daysOld),
                    'amount' => $invoice->total,
                    'balance' => $invoice->balance,
                    'bracket' => $bracket
                ];
            });
        
        // Group by aging bracket
        $summary = $aging->groupBy('bracket')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('balance')
            ];
        });
        
        return [
            'summary' => $summary,
            'details' => $aging,
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'total_outstanding' => $aging->sum('balance')
        ];
    }
    
    /**
     * Ticket Volume Report
     */
    protected function generateTicketVolumeReport($params)
    {
        $startDate = Carbon::parse($params['start_date'] ?? now()->subMonths(3));
        $endDate = Carbon::parse($params['end_date'] ?? now());
        
        // Tickets by status
        $byStatus = Ticket::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        // Tickets by priority
        $byPriority = Ticket::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();
        
        // Daily ticket creation
        $dailyVolume = Ticket::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Top categories
        $byCategory = Ticket::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => $byCategory,
            'daily_volume' => $dailyVolume,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'total_tickets' => $byStatus->sum('count')
        ];
    }
    
    /**
     * Client Activity Report
     */
    protected function generateClientActivityReport($params)
    {
        $clientId = $params['client_id'] ?? null;
        $startDate = Carbon::parse($params['start_date'] ?? now()->subMonths(6));
        $endDate = Carbon::parse($params['end_date'] ?? now());
        
        $query = Client::where('company_id', $this->companyId);
        
        if ($clientId) {
            $query->where('id', $clientId);
        }
        
        $clients = $query->with([
            'tickets' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            },
            'invoices' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            },
            'projects' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])->get();
        
        $activity = $clients->map(function ($client) {
            return [
                'client_name' => $client->name,
                'tickets' => [
                    'total' => $client->tickets->count(),
                    'open' => $client->tickets->whereIn('status', ['open', 'in-progress'])->count(),
                    'closed' => $client->tickets->where('status', 'closed')->count()
                ],
                'invoices' => [
                    'total' => $client->invoices->count(),
                    'paid' => $client->invoices->where('status', 'paid')->count(),
                    'outstanding' => $client->invoices->whereIn('status', ['sent', 'partial', 'overdue'])->count(),
                    'total_revenue' => $client->invoices->where('status', 'paid')->sum('total')
                ],
                'projects' => [
                    'total' => $client->projects->count(),
                    'active' => $client->projects->where('status', 'active')->count(),
                    'completed' => $client->projects->where('status', 'completed')->count()
                ],
                'last_activity' => $client->updated_at->format('Y-m-d H:i:s')
            ];
        });
        
        return [
            'clients' => $activity,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'summary' => [
                'total_clients' => $activity->count(),
                'total_tickets' => $activity->sum('tickets.total'),
                'total_revenue' => $activity->sum('invoices.total_revenue'),
                'total_projects' => $activity->sum('projects.total')
            ]
        ];
    }
    
    /**
     * Staff Utilization Report
     */
    protected function generateStaffUtilizationReport($params)
    {
        $startDate = Carbon::parse($params['start_date'] ?? now()->startOfMonth());
        $endDate = Carbon::parse($params['end_date'] ?? now()->endOfMonth());
        
        $staff = User::where('company_id', $this->companyId)
            ->where('role', '!=', 'client')
            ->get();
        
        $utilization = $staff->map(function ($user) use ($startDate, $endDate) {
            // Get time entries (this assumes a time_entries table exists)
            $timeEntries = DB::table('ticket_time_entries')
                ->where('user_id', $user->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            
            $totalHours = $timeEntries->sum('duration') / 60; // Convert minutes to hours
            $billableHours = $timeEntries->where('billable', true)->sum('duration') / 60;
            $nonBillableHours = $totalHours - $billableHours;
            
            // Assuming 8 hours per day, 22 working days per month
            $availableHours = $startDate->diffInWeekdays($endDate) * 8;
            $utilizationRate = $availableHours > 0 ? ($billableHours / $availableHours) * 100 : 0;
            
            return [
                'staff_name' => $user->name,
                'total_hours' => round($totalHours, 2),
                'billable_hours' => round($billableHours, 2),
                'non_billable_hours' => round($nonBillableHours, 2),
                'available_hours' => $availableHours,
                'utilization_rate' => round($utilizationRate, 1),
                'tickets_handled' => $user->tickets()->whereBetween('created_at', [$startDate, $endDate])->count()
            ];
        });
        
        return [
            'staff' => $utilization,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'summary' => [
                'avg_utilization' => round($utilization->avg('utilization_rate'), 1),
                'total_billable_hours' => $utilization->sum('billable_hours'),
                'total_non_billable_hours' => $utilization->sum('non_billable_hours')
            ]
        ];
    }
    
    /**
     * Export report to PDF
     */
    public function exportToPdf($reportId, $data, $params = [])
    {
        $viewName = 'reports.pdf.' . str_replace('-', '_', $reportId);
        
        if (!view()->exists($viewName)) {
            $viewName = 'reports.pdf.default';
        }
        
        $pdf = PDF::loadView($viewName, [
            'data' => $data,
            'params' => $params,
            'reportTitle' => $this->getReportTitle($reportId),
            'generatedAt' => now(),
            'generatedBy' => auth()->user()->name
        ]);
        
        return $pdf->download($reportId . '-' . now()->format('Y-m-d') . '.pdf');
    }
    
    /**
     * Export report to Excel
     */
    public function exportToExcel($reportId, $data, $params = [])
    {
        $exportClass = 'App\\Exports\\' . str_replace('-', '', ucwords($reportId, '-')) . 'Export';
        
        if (!class_exists($exportClass)) {
            $exportClass = 'App\\Exports\\GenericReportExport';
        }
        
        return Excel::download(new $exportClass($data, $params), $reportId . '-' . now()->format('Y-m-d') . '.xlsx');
    }
    
    /**
     * Get report title
     */
    protected function getReportTitle($reportId)
    {
        $titles = [
            'revenue-summary' => 'Revenue Summary Report',
            'invoice-aging' => 'Invoice Aging Report',
            'ticket-volume' => 'Ticket Volume Report',
            'client-activity' => 'Client Activity Report',
            'staff-utilization' => 'Staff Utilization Report',
            // Add more titles as needed
        ];
        
        return $titles[$reportId] ?? ucwords(str_replace('-', ' ', $reportId));
    }
    
    /**
     * Get frequently used reports for dashboard
     */
    public function getFrequentReports($limit = 5)
    {
        // This would typically query a report_logs table
        // For now, return common reports
        return [
            ['id' => 'revenue-summary', 'name' => 'Revenue Summary', 'category' => 'financial'],
            ['id' => 'invoice-aging', 'name' => 'Invoice Aging', 'category' => 'financial'],
            ['id' => 'ticket-volume', 'name' => 'Ticket Volume', 'category' => 'operational'],
            ['id' => 'staff-utilization', 'name' => 'Staff Utilization', 'category' => 'resource'],
            ['id' => 'client-activity', 'name' => 'Client Activity', 'category' => 'client']
        ];
    }
    
    /**
     * Get scheduled reports for user
     */
    public function getScheduledReports()
    {
        // This would query a scheduled_reports table
        // For now, return empty array
        return [];
    }
}