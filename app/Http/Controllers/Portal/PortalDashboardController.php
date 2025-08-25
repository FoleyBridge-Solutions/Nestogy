<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Contract\Models\Contract;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Asset\Models\Asset;
use App\Domains\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Portal Dashboard Controller
 * 
 * Provides the main dashboard view for client portal users with
 * comprehensive metrics, summaries, and service status information.
 */
class PortalDashboardController extends Controller
{
    /**
     * Display the portal dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $client = $user->client;
        
        // Cache dashboard data for 5 minutes
        $cacheKey = "portal_dashboard_{$user->id}";
        $dashboardData = Cache::remember($cacheKey, 300, function () use ($client, $user) {
            return [
                'metrics' => $this->getDashboardMetrics($client, $user),
                'tickets' => $this->getTicketSummary($client, $user),
                'invoices' => $this->getInvoiceSummary($client, $user),
                'assets' => $this->getAssetOverview($client, $user),
                'services' => $this->getServiceStatus($client),
                'projects' => $this->getProjectSummary($client, $user),
                'contracts' => $this->getContractSummary($client, $user),
                'announcements' => $this->getAnnouncements($client),
                'recent_activity' => $this->getRecentActivity($client, $user)
            ];
        });
        
        return view('portal.dashboard.index', array_merge($dashboardData, [
            'user' => $user,
            'client' => $client
        ]));
    }
    
    /**
     * Get dashboard metrics
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getDashboardMetrics(Client $client, $user): array
    {
        $metrics = [];
        
        // Open tickets
        if ($user->can_view_tickets) {
            $metrics['open_tickets'] = Ticket::where('client_id', $client->id)
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count();
            
            $metrics['tickets_this_month'] = Ticket::where('client_id', $client->id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();
        }
        
        // Outstanding invoices
        if ($user->canAccessFinancials()) {
            $metrics['outstanding_invoices'] = Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->count();
            
            $metrics['total_outstanding'] = Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->sum('balance');
        }
        
        // Active assets
        if ($user->can_view_assets) {
            $metrics['total_assets'] = Asset::where('client_id', $client->id)
                ->where('status', 'active')
                ->count();
            
            $metrics['assets_requiring_attention'] = Asset::where('client_id', $client->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('warranty_expires_at', '<=', Carbon::now()->addDays(30))
                        ->orWhere('requires_maintenance', true);
                })
                ->count();
        }
        
        // Active projects
        if ($user->can_view_projects) {
            $metrics['active_projects'] = Project::where('client_id', $client->id)
                ->whereIn('status', ['planning', 'in_progress'])
                ->count();
            
            $metrics['projects_completion_rate'] = $this->calculateProjectCompletionRate($client);
        }
        
        // Service uptime (last 30 days)
        $metrics['service_uptime'] = $this->calculateServiceUptime($client);
        
        // Average response time
        $metrics['avg_response_time'] = $this->calculateAverageResponseTime($client);
        
        return $metrics;
    }
    
    /**
     * Get ticket summary
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getTicketSummary(Client $client, $user): array
    {
        if (!$user->can_view_tickets) {
            return ['accessible' => false];
        }
        
        $tickets = Ticket::where('client_id', $client->id)
            ->with(['assignedTo', 'priority'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $summary = [
            'accessible' => true,
            'recent' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority->name ?? 'Normal',
                    'created_at' => $ticket->created_at,
                    'last_updated' => $ticket->updated_at,
                    'assigned_to' => $ticket->assignedTo->name ?? 'Unassigned'
                ];
            }),
            'by_status' => Ticket::where('client_id', $client->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_priority' => Ticket::where('client_id', $client->id)
                ->join('ticket_priorities', 'tickets.priority_id', '=', 'ticket_priorities.id')
                ->select('ticket_priorities.name', DB::raw('count(*) as count'))
                ->groupBy('ticket_priorities.name')
                ->pluck('count', 'name'),
            'sla_compliance' => $this->calculateSlaCompliance($client)
        ];
        
        return $summary;
    }
    
    /**
     * Get invoice summary
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getInvoiceSummary(Client $client, $user): array
    {
        if (!$user->canAccessFinancials()) {
            return ['accessible' => false];
        }
        
        $invoices = Invoice::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $summary = [
            'accessible' => true,
            'recent' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'total' => $invoice->total,
                    'balance' => $invoice->balance,
                    'status' => $invoice->status,
                    'is_overdue' => $invoice->isOverdue()
                ];
            }),
            'total_outstanding' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->sum('balance'),
            'total_this_year' => Invoice::where('client_id', $client->id)
                ->whereYear('invoice_date', Carbon::now()->year)
                ->sum('total'),
            'payment_history' => $this->getPaymentHistory($client),
            'aging_summary' => $this->getAgingSummary($client)
        ];
        
        return $summary;
    }
    
    /**
     * Get asset overview
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getAssetOverview(Client $client, $user): array
    {
        if (!$user->can_view_assets) {
            return ['accessible' => false];
        }
        
        $assets = Asset::where('client_id', $client->id)
            ->with(['assetType', 'location'])
            ->get();
        
        $overview = [
            'accessible' => true,
            'total_count' => $assets->count(),
            'by_type' => $assets->groupBy('assetType.name')->map->count(),
            'by_status' => $assets->groupBy('status')->map->count(),
            'by_location' => $assets->groupBy('location.name')->map->count(),
            'warranty_expiring' => Asset::where('client_id', $client->id)
                ->whereBetween('warranty_expires_at', [Carbon::now(), Carbon::now()->addDays(90)])
                ->count(),
            'requiring_maintenance' => Asset::where('client_id', $client->id)
                ->where('requires_maintenance', true)
                ->count(),
            'recent_additions' => Asset::where('client_id', $client->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'asset_tag', 'serial_number', 'status', 'created_at'])
        ];
        
        return $overview;
    }
    
    /**
     * Get service status
     *
     * @param Client $client
     * @return array
     */
    protected function getServiceStatus(Client $client): array
    {
        $services = [];
        
        // Get active contracts to determine services
        $contracts = Contract::where('client_id', $client->id)
            ->where('status', 'active')
            ->get();
        
        foreach ($contracts as $contract) {
            $serviceTypes = $contract->service_types ?? [];
            
            foreach ($serviceTypes as $service) {
                $services[] = [
                    'name' => $service['name'] ?? 'Unknown Service',
                    'status' => $this->checkServiceStatus($client, $service),
                    'uptime' => $this->getServiceUptime($client, $service),
                    'last_incident' => $this->getLastIncident($client, $service),
                    'next_maintenance' => $this->getNextMaintenance($client, $service)
                ];
            }
        }
        
        // Add general monitoring status
        $services[] = [
            'name' => 'Network Monitoring',
            'status' => 'operational',
            'uptime' => 99.98,
            'last_incident' => null,
            'next_maintenance' => null
        ];
        
        $services[] = [
            'name' => 'Help Desk',
            'status' => 'operational',
            'uptime' => 100,
            'last_incident' => null,
            'next_maintenance' => null
        ];
        
        return [
            'services' => $services,
            'overall_status' => $this->calculateOverallStatus($services),
            'incidents_last_30_days' => $this->getRecentIncidents($client, 30)
        ];
    }
    
    /**
     * Get project summary
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getProjectSummary(Client $client, $user): array
    {
        if (!$user->can_view_projects) {
            return ['accessible' => false];
        }
        
        $projects = Project::where('client_id', $client->id)
            ->with(['manager', 'tasks'])
            ->get();
        
        $summary = [
            'accessible' => true,
            'active' => $projects->whereIn('status', ['planning', 'in_progress'])->values(),
            'completed_this_year' => $projects->where('status', 'completed')
                ->where('completed_at', '>=', Carbon::now()->startOfYear())
                ->count(),
            'total_budget' => $projects->sum('budget'),
            'total_spent' => $projects->sum('actual_cost'),
            'on_track' => $projects->where('health_status', 'on_track')->count(),
            'at_risk' => $projects->where('health_status', 'at_risk')->count(),
            'delayed' => $projects->where('health_status', 'delayed')->count(),
            'average_completion_time' => $this->calculateAverageCompletionTime($projects)
        ];
        
        return $summary;
    }
    
    /**
     * Get contract summary
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return array
     */
    protected function getContractSummary(Client $client, $user): array
    {
        if (!$user->canAccessFinancials() && !$user->isAdmin()) {
            return ['accessible' => false];
        }
        
        $contracts = Contract::where('client_id', $client->id)->get();
        
        $summary = [
            'accessible' => true,
            'active' => $contracts->where('status', 'active')->values(),
            'expiring_soon' => $contracts->filter(function ($contract) {
                return $contract->status === 'active' && 
                       $contract->end_date <= Carbon::now()->addDays(90);
            })->values(),
            'total_value' => $contracts->where('status', 'active')->sum('value'),
            'auto_renewing' => $contracts->where('auto_renew', true)->count(),
            'next_renewal' => $contracts->where('status', 'active')
                ->sortBy('end_date')
                ->first(),
            'sla_compliance' => $this->getContractSlaCompliance($contracts)
        ];
        
        return $summary;
    }
    
    /**
     * Get announcements for the client
     *
     * @param Client $client
     * @return \Illuminate\Support\Collection
     */
    protected function getAnnouncements(Client $client)
    {
        // This would fetch from an announcements table
        // For now, returning sample data
        return collect([
            [
                'id' => 1,
                'title' => 'Scheduled Maintenance',
                'message' => 'System maintenance scheduled for this weekend.',
                'type' => 'info',
                'created_at' => Carbon::now()->subDays(2)
            ]
        ]);
    }
    
    /**
     * Get recent activity
     *
     * @param Client $client
     * @param \App\Models\ClientPortalUser $user
     * @return \Illuminate\Support\Collection
     */
    protected function getRecentActivity(Client $client, $user)
    {
        $activities = collect();
        
        // Recent tickets
        if ($user->can_view_tickets) {
            $recentTickets = Ticket::where('client_id', $client->id)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'type' => 'ticket',
                        'message' => "Ticket #{$ticket->ticket_number} - {$ticket->subject}",
                        'status' => $ticket->status,
                        'created_at' => $ticket->created_at
                    ];
                });
            $activities = $activities->merge($recentTickets);
        }
        
        // Recent invoices
        if ($user->canAccessFinancials()) {
            $recentInvoices = Invoice::where('client_id', $client->id)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'type' => 'invoice',
                        'message' => "Invoice #{$invoice->invoice_number} - \${$invoice->total}",
                        'status' => $invoice->status,
                        'created_at' => $invoice->created_at
                    ];
                });
            $activities = $activities->merge($recentInvoices);
        }
        
        // Recent project updates
        if ($user->can_view_projects) {
            $recentProjects = Project::where('client_id', $client->id)
                ->orderBy('updated_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($project) {
                    return [
                        'type' => 'project',
                        'message' => "Project: {$project->name}",
                        'status' => $project->status,
                        'created_at' => $project->updated_at
                    ];
                });
            $activities = $activities->merge($recentProjects);
        }
        
        return $activities->sortByDesc('created_at')->take(10);
    }
    
    /**
     * Calculate service uptime percentage
     *
     * @param Client $client
     * @return float
     */
    protected function calculateServiceUptime(Client $client): float
    {
        // This would calculate from actual monitoring data
        // Returning sample data
        return 99.95;
    }
    
    /**
     * Calculate average response time in minutes
     *
     * @param Client $client
     * @return float
     */
    protected function calculateAverageResponseTime(Client $client): float
    {
        $avgMinutes = Ticket::where('client_id', $client->id)
            ->whereNotNull('first_response_at')
            ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes');
        
        return round($avgMinutes ?? 0, 2);
    }
    
    /**
     * Calculate project completion rate
     *
     * @param Client $client
     * @return float
     */
    protected function calculateProjectCompletionRate(Client $client): float
    {
        $total = Project::where('client_id', $client->id)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        if ($total === 0) {
            return 100;
        }
        
        $completed = Project::where('client_id', $client->id)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'completed')
            ->count();
        
        return round(($completed / $total) * 100, 2);
    }
    
    /**
     * Calculate SLA compliance percentage
     *
     * @param Client $client
     * @return float
     */
    protected function calculateSlaCompliance(Client $client): float
    {
        // Check tickets against SLA requirements
        $totalTickets = Ticket::where('client_id', $client->id)
            ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->count();
        
        if ($totalTickets === 0) {
            return 100;
        }
        
        // This would check against actual SLA requirements
        // Simplified implementation
        $slaMetTickets = Ticket::where('client_id', $client->id)
            ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->where('sla_met', true)
            ->count();
        
        return round(($slaMetTickets / $totalTickets) * 100, 2);
    }
    
    /**
     * Get payment history
     *
     * @param Client $client
     * @return array
     */
    protected function getPaymentHistory(Client $client): array
    {
        // Last 12 months of payments
        $payments = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $payments[] = [
                'month' => $month->format('M Y'),
                'amount' => Invoice::where('client_id', $client->id)
                    ->whereMonth('paid_at', $month->month)
                    ->whereYear('paid_at', $month->year)
                    ->sum('total')
            ];
        }
        
        return $payments;
    }
    
    /**
     * Get aging summary for invoices
     *
     * @param Client $client
     * @return array
     */
    protected function getAgingSummary(Client $client): array
    {
        $now = Carbon::now();
        
        return [
            'current' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->where('due_date', '>=', $now)
                ->sum('balance'),
            '1-30' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->whereBetween('due_date', [$now->copy()->subDays(30), $now])
                ->sum('balance'),
            '31-60' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->whereBetween('due_date', [$now->copy()->subDays(60), $now->copy()->subDays(31)])
                ->sum('balance'),
            '61-90' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->whereBetween('due_date', [$now->copy()->subDays(90), $now->copy()->subDays(61)])
                ->sum('balance'),
            'over_90' => Invoice::where('client_id', $client->id)
                ->where('status', 'sent')
                ->where('due_date', '<', $now->copy()->subDays(90))
                ->sum('balance')
        ];
    }
    
    /**
     * Check service status
     *
     * @param Client $client
     * @param array $service
     * @return string
     */
    protected function checkServiceStatus(Client $client, array $service): string
    {
        // This would check actual service monitoring
        // Returning sample status
        return 'operational';
    }
    
    /**
     * Get service uptime
     *
     * @param Client $client
     * @param array $service
     * @return float
     */
    protected function getServiceUptime(Client $client, array $service): float
    {
        // This would calculate from monitoring data
        return 99.9;
    }
    
    /**
     * Get last incident for service
     *
     * @param Client $client
     * @param array $service
     * @return array|null
     */
    protected function getLastIncident(Client $client, array $service): ?array
    {
        // This would fetch from incidents table
        return null;
    }
    
    /**
     * Get next maintenance window
     *
     * @param Client $client
     * @param array $service
     * @return \Carbon\Carbon|null
     */
    protected function getNextMaintenance(Client $client, array $service): ?Carbon
    {
        // This would fetch from maintenance schedule
        return null;
    }
    
    /**
     * Calculate overall service status
     *
     * @param array $services
     * @return string
     */
    protected function calculateOverallStatus(array $services): string
    {
        $statuses = array_column($services, 'status');
        
        if (in_array('down', $statuses)) {
            return 'critical';
        }
        
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        
        return 'operational';
    }
    
    /**
     * Get recent incidents
     *
     * @param Client $client
     * @param int $days
     * @return int
     */
    protected function getRecentIncidents(Client $client, int $days): int
    {
        // This would count from incidents table
        return 0;
    }
    
    /**
     * Calculate average project completion time
     *
     * @param \Illuminate\Support\Collection $projects
     * @return float Days
     */
    protected function calculateAverageCompletionTime($projects): float
    {
        $completed = $projects->where('status', 'completed')
            ->filter(function ($project) {
                return $project->completed_at && $project->created_at;
            });
        
        if ($completed->isEmpty()) {
            return 0;
        }
        
        $totalDays = $completed->sum(function ($project) {
            return $project->created_at->diffInDays($project->completed_at);
        });
        
        return round($totalDays / $completed->count(), 1);
    }
    
    /**
     * Get contract SLA compliance
     *
     * @param \Illuminate\Support\Collection $contracts
     * @return float
     */
    protected function getContractSlaCompliance($contracts): float
    {
        $activeContracts = $contracts->where('status', 'active');
        
        if ($activeContracts->isEmpty()) {
            return 100;
        }
        
        $totalCompliance = $activeContracts->sum(function ($contract) {
            // This would check actual SLA metrics
            return $contract->sla_compliance_rate ?? 100;
        });
        
        return round($totalCompliance / $activeContracts->count(), 2);
    }
}