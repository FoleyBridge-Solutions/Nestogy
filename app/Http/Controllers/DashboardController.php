<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Asset;
use App\Models\User;
use App\Models\Location;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Project;
use App\Services\DashboardDataService;
use App\Services\NavigationService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected DashboardDataService $dashboardService;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && $user->company_id) {
                $this->dashboardService = new DashboardDataService($user->company_id);
            }
            return $next($request);
        });
    }

    /**
     * Display the workflow-centric dashboard (main entry point)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $workflowView = $request->get('view', 'default');
        $selectedClient = NavigationService::getSelectedClient();
        
        // Get user context for role-based dashboard
        $userContext = $this->getUserContext($user);
        
        // Route to appropriate workflow handler
        return $this->handleWorkflowRequest($user, $userContext, $workflowView, $selectedClient, $request);
    }
    
    /**
     * Handle workflow-specific dashboard requests
     */
    private function handleWorkflowRequest($user, $userContext, $workflow, $selectedClient, $request)
    {
        // Set workflow context in navigation service
        NavigationService::setWorkflowContext($workflow);
        
        // Get workflow-specific data based on role and client context
        $workflowData = $this->getWorkflowData($workflow, $userContext, $selectedClient);
        
        // Get role-appropriate KPIs for this workflow
        $kpis = $this->getWorkflowKPIs($workflow, $userContext->role, $selectedClient);
        
        // Get contextual quick actions
        $quickActions = $this->getWorkflowQuickActions($workflow, $userContext->role, $selectedClient);
        
        // Get real-time notifications and alerts
        $alerts = $this->getWorkflowAlerts($workflow, $userContext, $selectedClient);
        $notifications = $this->getSystemNotifications($user);
        
        // Get chart data for this workflow
        $chartData = $this->getWorkflowChartData($workflow, $userContext, $selectedClient);
        
        // Prepare view data
        $viewData = compact(
            'user',
            'userContext', 
            'workflow',
            'selectedClient',
            'workflowData',
            'kpis',
            'quickActions',
            'alerts',
            'notifications',
            'chartData'
        );
        
        // Add workflowView for backward compatibility
        $viewData['workflowView'] = $workflow;
        
        // Add legacy compatibility data
        $viewData = array_merge($viewData, $this->getLegacyCompatibilityData($user->company_id));
        
        return view('dashboard', $viewData);
    }
    
    /**
     * Get user context with role and permissions
     */
    private function getUserContext($user)
    {
        return (object) [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $this->getUserPrimaryRole($user),
            'isAdmin' => $user->isAdmin(),
            'isTech' => $user->isTech(),
            'isAccountant' => $user->isAccountant(),
            'permissions' => $user->getAllPermissions()->pluck('slug')->toArray(),
            'company_id' => $user->company_id
        ];
    }
    
    /**
     * Determine user's primary role
     */
    private function getUserPrimaryRole($user)
    {
        if ($user->isAdmin()) return 'admin';
        if ($user->isTech()) return 'tech';
        if ($user->isAccountant()) return 'accountant';
        return 'user';
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($companyId)
    {
        return [
            'total_clients' => Client::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->count(),
            
            'open_tickets' => Ticket::where('company_id', $companyId)
                ->whereIn('status', ['Open', 'In Progress', 'Waiting'])
                ->count(),
            
            'overdue_invoices' => Invoice::where('company_id', $companyId)
                ->where('status', 'Sent')
                ->where('due_date', '<', now())
                ->count(),
            
            'total_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->count(),
            
            'monthly_revenue' => Invoice::where('company_id', $companyId)
                ->where('status', 'Paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            
            'pending_invoices_amount' => Invoice::where('company_id', $companyId)
                ->whereIn('status', ['Draft', 'Sent'])
                ->sum('amount'),
        ];
    }

    /**
     * Get recent tickets
     */
    private function getRecentTickets($companyId, $limit = 10)
    {
        return Ticket::with(['client', 'contact', 'assignee'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent invoices
     */
    private function getRecentInvoices($companyId, $limit = 10)
    {
        return Invoice::with('client')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming tasks/scheduled tickets
     */
    private function getUpcomingTasks($companyId, $limit = 10)
    {
        return Ticket::with(['client', 'contact'])
            ->where('company_id', $companyId)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get ticket chart data for the last 30 days
     */
    private function getTicketChartData($companyId)
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $tickets->pluck('date')->toArray(),
            'data' => $tickets->pluck('count')->toArray(),
        ];
    }

    /**
     * Get revenue chart data for the last 12 months
     */
    private function getRevenueChartData($companyId)
    {
        $revenue = Invoice::where('company_id', $companyId)
            ->where('status', 'Paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $revenue->map(function ($item) {
                return date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            })->toArray(),
            'data' => $revenue->pluck('total')->toArray(),
        ];
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $type = $request->get('type');

        switch ($type) {
            case 'stats':
                return response()->json($this->getDashboardStats($companyId));
            
            case 'recent_tickets':
                return response()->json($this->getRecentTickets($companyId));
            
            case 'recent_invoices':
                return response()->json($this->getRecentInvoices($companyId));
            
            case 'ticket_chart':
                return response()->json($this->getTicketChartData($companyId));
            
            case 'revenue_chart':
                return response()->json($this->getRevenueChartData($companyId));
            
            default:
                return response()->json(['error' => 'Invalid data type'], 400);
        }
    }

    /**
     * Get notifications for the current user
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        
        try {
            if (DB::getSchemaBuilder()->hasTable('notifications')) {
                $notifications = DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->where('read_at', null)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                
                return response()->json($notifications);
            }
        } catch (\Exception $e) {
            Log::warning('Notifications table not accessible: ' . $e->getMessage());
        }

        return response()->json([]); // Return empty array if table doesn't exist
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, $id)
    {
        $user = Auth::user();
        
        try {
            if (DB::getSchemaBuilder()->hasTable('notifications')) {
                DB::table('notifications')
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->update(['read_at' => now()]);
                
                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            Log::warning('Notifications table not accessible: ' . $e->getMessage());
        }

        return response()->json(['success' => false, 'message' => 'Notifications not available']);
    }

    /**
     * Get real-time dashboard data via AJAX
     */
    public function getRealtimeData(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'all');
        
        if (!$this->dashboardService) {
            return response()->json(['error' => 'Dashboard service not available'], 500);
        }

        try {
            switch ($type) {
                case 'kpis':
                    $kpis = $request->get('kpis', ['total_revenue', 'mrr', 'new_customers', 'churn_rate']);
                    return response()->json($this->dashboardService->getRealtimeKPIs($kpis));
                
                case 'executive':
                    $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->startOfMonth();
                    $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now()->endOfMonth();
                    return response()->json($this->dashboardService->getExecutiveDashboardData($startDate, $endDate));
                
                case 'revenue':
                    $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subMonths(11)->startOfMonth();
                    $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now()->endOfMonth();
                    return response()->json($this->dashboardService->getRevenueAnalyticsDashboardData($startDate, $endDate));
                
                case 'stats':
                    return response()->json($this->getDashboardStats($user->company_id));
                
                case 'recent_activity':
                    return response()->json([
                        'tickets' => $this->getRecentTickets($user->company_id, 5),
                        'invoices' => $this->getRecentInvoices($user->company_id, 5),
                        'tasks' => $this->getUpcomingTasks($user->company_id, 5),
                    ]);
                
                case 'charts':
                    return response()->json([
                        'revenue' => $this->getRevenueChartData($user->company_id),
                        'tickets' => $this->getTicketChartData($user->company_id),
                    ]);
                
                case 'alerts':
                    return response()->json($this->getPerformanceAlerts($user->company_id));
                
                case 'all':
                default:
                    return response()->json([
                        'stats' => $this->getDashboardStats($user->company_id),
                        'kpis' => $this->dashboardService->getRealtimeKPIs(['total_revenue', 'mrr', 'new_customers', 'churn_rate']),
                        'recent_activity' => [
                            'tickets' => $this->getRecentTickets($user->company_id, 5),
                            'invoices' => $this->getRecentInvoices($user->company_id, 5),
                        ],
                        'charts' => [
                            'revenue' => $this->getRevenueChartData($user->company_id),
                            'tickets' => $this->getTicketChartData($user->company_id),
                        ],
                        'alerts' => $this->getPerformanceAlerts($user->company_id),
                        'updated_at' => now()->toISOString(),
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Dashboard realtime data error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Failed to fetch dashboard data'], 500);
        }
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'executive');
        $format = $request->get('format', 'json');
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now()->endOfMonth();

        if (!$this->dashboardService) {
            return response()->json(['error' => 'Dashboard service not available'], 500);
        }

        try {
            $data = $this->dashboardService->exportDashboardData($type, $format, $startDate, $endDate);
            
            $filename = "dashboard-{$type}-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.{$format}";
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => $filename,
                'generated_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get performance alerts
     */
    private function getPerformanceAlerts($companyId)
    {
        $alerts = [];
        
        // Check for overdue invoices
        $overdueInvoices = Invoice::where('company_id', $companyId)
            ->where('status', 'Sent')
            ->where('due_date', '<', now()->subDays(7))
            ->count();
            
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'Overdue Invoices',
                'message' => "{$overdueInvoices} invoices are overdue by more than 7 days",
                'action_url' => route('financial.invoices.index', ['status' => 'overdue']),
                'action_text' => 'Review Invoices',
                'priority' => 'high'
            ];
        }
        
        // Check for open tickets without assignment
        $unassignedTickets = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Open', 'New'])
            ->whereNull('assigned_to')
            ->count();
            
        if ($unassignedTickets > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'ticket',
                'title' => 'Unassigned Tickets',
                'message' => "{$unassignedTickets} tickets need to be assigned",
                'action_url' => route('tickets.index', ['status' => 'unassigned']),
                'action_text' => 'Assign Tickets',
                'priority' => 'medium'
            ];
        }
        
        // Check for expiring assets/licenses (if applicable)
        // This would be expanded based on your specific business logic
        
        return $alerts;
    }

    /**
     * Get system notifications for the user
     */
    private function getSystemNotifications($user)
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('notifications')) {
                return DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->where('read_at', null)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($notification) {
                        return [
                            'id' => $notification->id,
                            'title' => $notification->title ?? 'Notification',
                            'message' => $notification->message,
                            'type' => $notification->type ?? 'info',
                            'created_at' => $notification->created_at,
                            'read_at' => $notification->read_at,
                        ];
                    });
            }
        } catch (\Exception $e) {
            Log::warning('Notifications table not accessible: ' . $e->getMessage());
        }
        
        return collect(); // Return empty collection if table doesn't exist
    }
    
    /**
     * Get workflow-specific data based on role and client context
     */
    private function getWorkflowData($workflow, $userContext, $selectedClient)
    {
        $companyId = $userContext->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        switch ($workflow) {
            case 'urgent':
                return $this->getUrgentWorkflowData($baseQuery, $userContext);
            
            case 'today':
                return $this->getTodayWorkflowData($baseQuery, $userContext);
            
            case 'scheduled':
                return $this->getScheduledWorkflowData($baseQuery, $userContext);
            
            case 'financial':
                return $this->getFinancialWorkflowData($baseQuery, $userContext);
            
            case 'reports':
                return $this->getReportsWorkflowData($baseQuery, $userContext);
            
            default:
                return $this->getDefaultWorkflowData($baseQuery, $userContext);
        }
    }
    
    /**
     * Get urgent workflow data
     */
    private function getUrgentWorkflowData($baseQuery, $userContext)
    {
        $urgentTickets = Ticket::where($baseQuery)
            ->whereIn('priority', ['Critical', 'High'])
            ->whereIn('status', ['Open', 'In Progress'])
            ->with(['client', 'assignee'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->limit(10)
            ->get();
            
        $overdueInvoices = Invoice::where($baseQuery)
            ->where('status', 'Sent')
            ->where('due_date', '<', now())
            ->with('client')
            ->orderBy('due_date')
            ->limit(5)
            ->get();
            
        // Check for tickets that have been open for more than 24 hours (simplified SLA check)
        $slaBreaches = Ticket::where($baseQuery)
            ->whereIn('status', ['Open', 'In Progress'])
            ->where('created_at', '<', now()->subHours(24))
            ->with(['client', 'assignee'])
            ->orderBy('created_at')
            ->limit(5)
            ->get();
            
        return [
            'urgent_tickets' => $urgentTickets,
            'overdue_invoices' => $overdueInvoices,
            'sla_breaches' => $slaBreaches,
            'counts' => [
                'urgent_tickets' => $urgentTickets->count(),
                'overdue_invoices' => $overdueInvoices->count(),
                'sla_breaches' => $slaBreaches->count()
            ]
        ];
    }
    
    /**
     * Get today's workflow data
     */
    private function getTodayWorkflowData($baseQuery, $userContext)
    {
        $today = now()->startOfDay();
        $endOfDay = now()->endOfDay();
        
        $todaysTickets = Ticket::where($baseQuery)
            ->whereBetween('created_at', [$today, $endOfDay])
            ->with(['client', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $scheduledTickets = Ticket::where($baseQuery)
            ->whereBetween('scheduled_at', [$today, $endOfDay])
            ->with(['client', 'assignee'])
            ->orderBy('scheduled_at')
            ->get();
            
        $todaysInvoices = Invoice::where($baseQuery)
            ->whereBetween('created_at', [$today, $endOfDay])
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $myAssignedTickets = [];
        if ($userContext->isTech || $userContext->isAdmin) {
            $myAssignedTickets = Ticket::where($baseQuery)
                ->where('assigned_to', $userContext->id)
                ->whereIn('status', ['Open', 'In Progress'])
                ->with(['client'])
                ->orderBy('priority')
                ->orderBy('created_at')
                ->get();
        }
        
        return [
            'todays_tickets' => $todaysTickets,
            'scheduled_tickets' => $scheduledTickets,
            'todays_invoices' => $todaysInvoices,
            'my_assigned_tickets' => $myAssignedTickets,
            'counts' => [
                'todays_tickets' => $todaysTickets->count(),
                'scheduled_tickets' => $scheduledTickets->count(),
                'todays_invoices' => $todaysInvoices->count(),
                'my_assigned_tickets' => count($myAssignedTickets)
            ]
        ];
    }
    
    /**
     * Get scheduled workflow data
     */
    private function getScheduledWorkflowData($baseQuery, $userContext)
    {
        $upcomingWeek = now()->addWeek();
        
        $scheduledTickets = Ticket::where($baseQuery)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', $upcomingWeek)
            ->with(['client', 'assignee'])
            ->orderBy('scheduled_at')
            ->get();
            
        $recurringTickets = [];
        if (DB::getSchemaBuilder()->hasTable('recurring_tickets')) {
            $recurringTickets = DB::table('recurring_tickets')
                ->where('company_id', $baseQuery['company_id'])
                ->where('is_active', true)
                ->get();
        }
        
        return [
            'scheduled_tickets' => $scheduledTickets,
            'recurring_tickets' => $recurringTickets,
            'calendar_events' => $this->getCalendarEvents($baseQuery, $upcomingWeek),
            'counts' => [
                'scheduled_tickets' => $scheduledTickets->count(),
                'recurring_tickets' => count($recurringTickets)
            ]
        ];
    }
    
    /**
     * Get financial workflow data
     */
    private function getFinancialWorkflowData($baseQuery, $userContext)
    {
        $pendingInvoices = Invoice::where($baseQuery)
            ->where('status', 'Draft')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $recentPayments = Payment::where($baseQuery)
            ->with(['invoice', 'client'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $upcomingInvoices = Invoice::where($baseQuery)
            ->where('status', 'Sent')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addWeek())
            ->with('client')
            ->orderBy('due_date')
            ->get();
        
        return [
            'pending_invoices' => $pendingInvoices,
            'recent_payments' => $recentPayments,
            'upcoming_invoices' => $upcomingInvoices,
            'financial_summary' => $this->getFinancialSummary($baseQuery),
            'counts' => [
                'pending_invoices' => $pendingInvoices->count(),
                'recent_payments' => $recentPayments->count(),
                'upcoming_invoices' => $upcomingInvoices->count()
            ]
        ];
    }
    
    /**
     * Get reports workflow data
     */
    private function getReportsWorkflowData($baseQuery, $userContext)
    {
        return [
            'recent_reports' => [],
            'favorite_reports' => [],
            'report_categories' => [
                'financial' => ['Revenue', 'Invoicing', 'Payments'],
                'operational' => ['Tickets', 'Assets', 'Projects'],
                'client' => ['Client Activity', 'Service Usage']
            ],
            'quick_stats' => $this->getDashboardStats($baseQuery['company_id'])
        ];
    }
    
    /**
     * Get default workflow data for overview
     */
    private function getDefaultWorkflowData($baseQuery, $userContext)
    {
        $stats = $this->getDashboardStats($baseQuery['company_id']);
        
        return [
            'overview_stats' => $stats,
            'recent_tickets' => $this->getRecentTickets($baseQuery['company_id'], 5),
            'recent_invoices' => $this->getRecentInvoices($baseQuery['company_id'], 5),
            'upcoming_tasks' => $this->getUpcomingTasks($baseQuery['company_id'], 5),
            'performance_summary' => $this->getPerformanceSummary($baseQuery['company_id'])
        ];
    }
    
    /**
     * Get role-appropriate KPIs for workflow
     */
    private function getWorkflowKPIs($workflow, $role, $selectedClient)
    {
        $kpis = [];
        
        switch ($role) {
            case 'admin':
                $kpis = $this->getAdminKPIs($workflow, $selectedClient);
                break;
            case 'tech':
                $kpis = $this->getTechKPIs($workflow, $selectedClient);
                break;
            case 'accountant':
                $kpis = $this->getAccountantKPIs($workflow, $selectedClient);
                break;
            default:
                $kpis = $this->getBasicKPIs($workflow, $selectedClient);
        }
        
        return $kpis;
    }
    
    /**
     * Get admin-level KPIs
     */
    private function getAdminKPIs($workflow, $selectedClient)
    {
        $companyId = Auth::user()->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        return [
            'total_revenue' => [
                'label' => 'Total Revenue',
                'value' => Invoice::where($baseQuery)->where('status', 'Paid')->sum('amount'),
                'format' => 'currency',
                'icon' => 'dollar-sign',
                'trend' => 'up'
            ],
            'active_clients' => [
                'label' => 'Active Clients',
                'value' => $selectedClient ? 1 : Client::where(['company_id' => $companyId])->whereNull('archived_at')->count(),
                'format' => 'number',
                'icon' => 'users',
                'trend' => 'stable'
            ],
            'open_tickets' => [
                'label' => 'Open Tickets',
                'value' => Ticket::where($baseQuery)->whereIn('status', ['Open', 'In Progress'])->count(),
                'format' => 'number',
                'icon' => 'ticket',
                'trend' => 'down'
            ],
            'monthly_revenue' => [
                'label' => 'Monthly Revenue',
                'value' => Invoice::where($baseQuery)
                    ->where('status', 'Paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
                'format' => 'currency',
                'icon' => 'trending-up',
                'trend' => 'up'
            ]
        ];
    }
    
    /**
     * Get tech-level KPIs
     */
    private function getTechKPIs($workflow, $selectedClient)
    {
        $userId = Auth::user()->id;
        $companyId = Auth::user()->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        return [
            'my_open_tickets' => [
                'label' => 'My Open Tickets',
                'value' => Ticket::where($baseQuery)->where('assigned_to', $userId)->whereIn('status', ['Open', 'In Progress'])->count(),
                'format' => 'number',
                'icon' => 'user-check',
                'trend' => 'stable'
            ],
            'resolved_today' => [
                'label' => 'Resolved Today',
                'value' => Ticket::where($baseQuery)
                    ->where('assigned_to', $userId)
                    ->where('status', 'Closed')
                    ->whereDate('updated_at', today())
                    ->count(),
                'format' => 'number',
                'icon' => 'check-circle',
                'trend' => 'up'
            ],
            'avg_response_time' => [
                'label' => 'Avg Response Time',
                'value' => '2.5 hrs',
                'format' => 'text',
                'icon' => 'clock',
                'trend' => 'down'
            ],
            'total_assets' => [
                'label' => 'Total Assets',
                'value' => Asset::where($baseQuery)->count(),
                'format' => 'number',
                'icon' => 'server',
                'trend' => 'stable'
            ]
        ];
    }
    
    /**
     * Get accountant-level KPIs
     */
    private function getAccountantKPIs($workflow, $selectedClient)
    {
        $companyId = Auth::user()->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        return [
            'outstanding_invoices' => [
                'label' => 'Outstanding Invoices',
                'value' => Invoice::where($baseQuery)->where('status', 'Sent')->sum('amount'),
                'format' => 'currency',
                'icon' => 'file-invoice',
                'trend' => 'stable'
            ],
            'payments_this_month' => [
                'label' => 'Payments This Month',
                'value' => Payment::where($baseQuery)->whereMonth('created_at', now()->month)->sum('amount'),
                'format' => 'currency',
                'icon' => 'credit-card',
                'trend' => 'up'
            ],
            'overdue_amount' => [
                'label' => 'Overdue Amount',
                'value' => Invoice::where($baseQuery)
                    ->where('status', 'Sent')
                    ->where('due_date', '<', now())
                    ->sum('amount'),
                'format' => 'currency',
                'icon' => 'exclamation-triangle',
                'trend' => 'down'
            ],
            'collection_rate' => [
                'label' => 'Collection Rate',
                'value' => '94.5%',
                'format' => 'percentage',
                'icon' => 'percentage',
                'trend' => 'up'
            ]
        ];
    }
    
    /**
     * Get basic KPIs for standard users
     */
    private function getBasicKPIs($workflow, $selectedClient)
    {
        $companyId = Auth::user()->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        return [
            'open_tickets' => [
                'label' => 'Open Tickets',
                'value' => Ticket::where($baseQuery)->whereIn('status', ['Open', 'In Progress'])->count(),
                'format' => 'number',
                'icon' => 'ticket',
                'trend' => 'stable'
            ],
            'recent_activity' => [
                'label' => 'Recent Activity',
                'value' => Ticket::where($baseQuery)->whereDate('created_at', today())->count(),
                'format' => 'number',
                'icon' => 'activity',
                'trend' => 'up'
            ]
        ];
    }
    
    /**
     * Get contextual quick actions for workflow and role
     */
    private function getWorkflowQuickActions($workflow, $role, $selectedClient)
    {
        $actions = [];
        $clientParam = $selectedClient ? ['client_id' => $selectedClient->id] : [];
        
        switch ($workflow) {
            case 'urgent':
                $actions = [
                    ['label' => 'Create Urgent Ticket', 'route' => 'tickets.create', 'params' => array_merge($clientParam, ['priority' => 'High']), 'icon' => 'plus', 'color' => 'red'],
                    ['label' => 'Review SLA Breaches', 'route' => 'tickets.index', 'params' => ['filter' => 'sla_breach'], 'icon' => 'clock', 'color' => 'orange'],
                    ['label' => 'Send Payment Reminder', 'route' => 'financial.invoices.index', 'params' => ['status' => 'overdue'], 'icon' => 'mail', 'color' => 'yellow']
                ];
                break;
                
            case 'today':
                $actions = [
                    ['label' => 'Create New Ticket', 'route' => 'tickets.create', 'params' => $clientParam, 'icon' => 'plus', 'color' => 'blue'],
                    ['label' => 'Schedule Appointment', 'route' => 'tickets.calendar.index', 'params' => [], 'icon' => 'calendar', 'color' => 'green'],
                    ['label' => 'Create Invoice', 'route' => 'financial.invoices.create', 'params' => $clientParam, 'icon' => 'file-invoice', 'color' => 'purple']
                ];
                break;
                
            case 'financial':
                $actions = [
                    ['label' => 'Create Invoice', 'route' => 'financial.invoices.create', 'params' => $clientParam, 'icon' => 'plus', 'color' => 'green'],
                    ['label' => 'Record Payment', 'route' => 'financial.payments.create', 'params' => [], 'icon' => 'credit-card', 'color' => 'blue'],
                    ['label' => 'Generate Report', 'route' => 'reports.financial.index', 'params' => [], 'icon' => 'chart-bar', 'color' => 'purple']
                ];
                break;
                
            default:
                $actions = [
                    ['label' => 'Create Ticket', 'route' => 'tickets.create', 'params' => $clientParam, 'icon' => 'plus', 'color' => 'blue'],
                    ['label' => 'Add Client', 'route' => 'clients.create', 'params' => [], 'icon' => 'user-plus', 'color' => 'green'],
                    ['label' => 'View Calendar', 'route' => 'tickets.calendar.index', 'params' => [], 'icon' => 'calendar', 'color' => 'purple']
                ];
        }
        
        // Filter actions based on role permissions
        return collect($actions)->filter(function ($action) use ($role) {
            return $this->userCanAccessAction($action['route'], $role);
        })->values()->toArray();
    }
    
    /**
     * Get workflow-specific alerts
     */
    private function getWorkflowAlerts($workflow, $userContext, $selectedClient)
    {
        $alerts = [];
        $companyId = $userContext->company_id;
        $baseQuery = ['company_id' => $companyId];
        
        if ($selectedClient) {
            $baseQuery['client_id'] = $selectedClient->id;
        }
        
        switch ($workflow) {
            case 'urgent':
                // Critical ticket alerts
                $criticalTickets = Ticket::where($baseQuery)
                    ->where('priority', 'Critical')
                    ->whereIn('status', ['Open', 'In Progress'])
                    ->count();
                    
                if ($criticalTickets > 0) {
                    $alerts[] = [
                        'type' => 'error',
                        'title' => 'Critical Tickets',
                        'message' => "{$criticalTickets} critical tickets require immediate attention",
                        'action_url' => route('tickets.index', ['priority' => 'Critical']),
                        'action_text' => 'Review Now'
                    ];
                }
                break;
                
            case 'financial':
                // Overdue invoice alerts
                $overdueAmount = Invoice::where($baseQuery)
                    ->where('status', 'Sent')
                    ->where('due_date', '<', now()->subDays(30))
                    ->sum('amount');
                    
                if ($overdueAmount > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'title' => 'Overdue Invoices',
                        'message' => "$" . number_format($overdueAmount, 2) . " in invoices overdue by 30+ days",
                        'action_url' => route('financial.invoices.index', ['status' => 'overdue']),
                        'action_text' => 'Review Collections'
                    ];
                }
                break;
        }
        
        return $alerts;
    }
    
    /**
     * Get workflow-specific chart data
     */
    private function getWorkflowChartData($workflow, $userContext, $selectedClient)
    {
        $companyId = $userContext->company_id;
        
        switch ($workflow) {
            case 'urgent':
                return [
                    'priority_distribution' => $this->getTicketPriorityChart($companyId, $selectedClient),
                    'sla_performance' => $this->getSLAPerformanceChart($companyId, $selectedClient)
                ];
                
            case 'financial':
                return [
                    'revenue_trend' => $this->getRevenueChartData($companyId),
                    'payment_status' => $this->getPaymentStatusChart($companyId, $selectedClient)
                ];
                
            case 'today':
                return [
                    'daily_activity' => $this->getDailyActivityChart($companyId, $selectedClient),
                    'ticket_trend' => $this->getTicketChartData($companyId)
                ];
                
            default:
                return [
                    'overview' => $this->getTicketChartData($companyId),
                    'revenue' => $this->getRevenueChartData($companyId)
                ];
        }
    }
    
    /**
     * Get legacy compatibility data for existing dashboard views
     */
    private function getLegacyCompatibilityData($companyId)
    {
        return [
            'stats' => $this->getDashboardStats($companyId),
            'recent_tickets' => $this->getRecentTickets($companyId, 5),
            'recent_invoices' => $this->getRecentInvoices($companyId, 5),
            'upcoming_tasks' => $this->getUpcomingTasks($companyId, 5),
            'ticket_chart' => $this->getTicketChartData($companyId),
            'revenue_chart' => $this->getRevenueChartData($companyId)
        ];
    }
    
    /**
     * Helper methods for additional data processing
     */
    
    private function getCalendarEvents($baseQuery, $endDate)
    {
        return Ticket::where($baseQuery)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now(), $endDate])
            ->select(['id', 'subject', 'scheduled_at', 'client_id'])
            ->with('client:id,name')
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->subject,
                    'start' => $ticket->scheduled_at,
                    'client' => $ticket->client->name ?? 'Unknown'
                ];
            });
    }
    
    private function getFinancialSummary($baseQuery)
    {
        return [
            'total_outstanding' => Invoice::where($baseQuery)->where('status', 'Sent')->sum('amount'),
            'monthly_revenue' => Invoice::where($baseQuery)
                ->where('status', 'Paid')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'avg_payment_time' => 15.2, // days - would be calculated from actual data
            'collection_rate' => 94.5 // percentage - would be calculated from actual data
        ];
    }
    
    private function getPerformanceSummary($companyId)
    {
        return [
            'ticket_resolution_time' => 2.8, // hours - would be calculated
            'client_satisfaction' => 4.2, // rating - would be calculated
            'technician_utilization' => 78.5, // percentage - would be calculated
            'revenue_growth' => 12.3 // percentage - would be calculated
        ];
    }
    
    private function userCanAccessAction($route, $role)
    {
        // Simplified permission check - would use proper permission system
        $restrictedRoutes = [
            'accountant' => ['clients.create', 'assets.create'],
            'tech' => ['financial.invoices.create', 'users.create']
        ];
        
        return !in_array($route, $restrictedRoutes[$role] ?? []);
    }
    
    private function getTicketPriorityChart($companyId, $selectedClient)
    {
        $baseQuery = ['company_id' => $companyId];
        if ($selectedClient) $baseQuery['client_id'] = $selectedClient->id;
        
        $priorities = Ticket::where($baseQuery)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
            
        return [
            'labels' => array_keys($priorities),
            'data' => array_values($priorities)
        ];
    }
    
    private function getSLAPerformanceChart($companyId, $selectedClient)
    {
        // Simplified SLA performance data
        return [
            'labels' => ['Met SLA', 'Breached SLA'],
            'data' => [85, 15]
        ];
    }
    
    private function getPaymentStatusChart($companyId, $selectedClient)
    {
        $baseQuery = ['company_id' => $companyId];
        if ($selectedClient) $baseQuery['client_id'] = $selectedClient->id;
        
        $statuses = Invoice::where($baseQuery)
            ->selectRaw('status, SUM(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
            
        return [
            'labels' => array_keys($statuses),
            'data' => array_values($statuses)
        ];
    }
    
    private function getDailyActivityChart($companyId, $selectedClient)
    {
        $baseQuery = ['company_id' => $companyId];
        if ($selectedClient) $baseQuery['client_id'] = $selectedClient->id;
        
        $activities = Ticket::where($baseQuery)
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
            
        // Fill in missing hours
        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = $activities[$i] ?? 0;
        }
        
        return [
            'labels' => array_keys($hourlyData),
            'data' => array_values($hourlyData)
        ];
    }
}