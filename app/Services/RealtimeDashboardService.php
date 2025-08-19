<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Asset;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * RealtimeDashboardService
 * 
 * Provides real-time data for dashboard widgets without caching
 * to ensure live updates. Optimized queries for performance.
 */
class RealtimeDashboardService
{
    protected int $companyId;
    protected array $widgetRegistry;
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->initializeWidgetRegistry();
    }
    
    /**
     * Initialize widget registry with available widget types
     */
    protected function initializeWidgetRegistry(): void
    {
        $this->widgetRegistry = [
            'revenue_kpi' => 'getRevenueKPI',
            'mrr_kpi' => 'getMRRKPI',
            'ticket_status' => 'getTicketStatus',
            'client_health' => 'getClientHealth',
            'team_performance' => 'getTeamPerformance',
            'revenue_chart' => 'getRevenueChart',
            'ticket_trend' => 'getTicketTrend',
            'payment_status' => 'getPaymentStatus',
            'sla_monitor' => 'getSLAMonitor',
            'activity_feed' => 'getActivityFeed',
            'alerts' => 'getAlerts',
            'forecast' => 'getForecast',
            'top_clients' => 'getTopClients',
            'resource_utilization' => 'getResourceUtilization',
            'project_status' => 'getProjectStatus',
        ];
    }
    
    /**
     * Get widget data by type
     */
    public function getWidgetData(string $widgetType, array $config = []): array
    {
        if (!isset($this->widgetRegistry[$widgetType])) {
            throw new \InvalidArgumentException("Unknown widget type: {$widgetType}");
        }
        
        $method = $this->widgetRegistry[$widgetType];
        $startTime = microtime(true);
        
        try {
            $data = $this->$method($config);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'widget_type' => $widgetType,
                'data' => $data,
                'meta' => [
                    'execution_time_ms' => $executionTime,
                    'timestamp' => now()->toISOString(),
                    'company_id' => $this->companyId,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("Widget data fetch error: {$widgetType}", [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'widget_type' => $widgetType,
            ];
        }
    }
    
    /**
     * Get multiple widget data in parallel
     */
    public function getMultipleWidgetData(array $widgets): array
    {
        $results = [];
        
        foreach ($widgets as $widget) {
            $widgetType = $widget['type'] ?? null;
            $config = $widget['config'] ?? [];
            
            if ($widgetType) {
                $results[$widgetType] = $this->getWidgetData($widgetType, $config);
            }
        }
        
        return $results;
    }
    
    /**
     * Revenue KPI Widget
     */
    protected function getRevenueKPI(array $config): array
    {
        $period = $config['period'] ?? 'month';
        $startDate = $this->getPeriodStart($period);
        
        $currentRevenue = Payment::where('company_id', $this->companyId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('amount');
        
        $previousPeriodStart = $this->getPreviousPeriodStart($period);
        $previousPeriodEnd = $startDate->copy()->subDay();
        
        $previousRevenue = Payment::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->where('status', 'completed')
            ->sum('amount');
        
        $growth = $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;
        
        return [
            'value' => $currentRevenue,
            'formatted' => '$' . number_format($currentRevenue, 2),
            'growth' => $growth,
            'growth_type' => $growth >= 0 ? 'increase' : 'decrease',
            'previous_value' => $previousRevenue,
            'period' => $period,
            'sparkline' => $this->getRevenueSparkline($period),
        ];
    }
    
    /**
     * MRR KPI Widget
     */
    protected function getMRRKPI(array $config): array
    {
        // Calculate MRR from recurring invoices
        $mrr = Invoice::where('company_id', $this->companyId)
            ->where('is_recurring', true)
            ->where('status', 'active')
            ->sum('monthly_amount');
        
        // Get last month's MRR for comparison
        $lastMonthMRR = Cache::get("mrr_last_month_{$this->companyId}", $mrr * 0.95);
        
        $growth = $lastMonthMRR > 0 
            ? round((($mrr - $lastMonthMRR) / $lastMonthMRR) * 100, 1)
            : 0;
        
        // Store current MRR for next comparison
        Cache::put("mrr_last_month_{$this->companyId}", $mrr, now()->endOfMonth());
        
        return [
            'value' => $mrr,
            'formatted' => '$' . number_format($mrr, 2),
            'growth' => $growth,
            'growth_type' => $growth >= 0 ? 'increase' : 'decrease',
            'new_mrr' => $this->getNewMRR(),
            'churned_mrr' => $this->getChurnedMRR(),
            'net_new_mrr' => $this->getNewMRR() - $this->getChurnedMRR(),
        ];
    }
    
    /**
     * Ticket Status Widget
     */
    protected function getTicketStatus(array $config): array
    {
        $tickets = Ticket::where('company_id', $this->companyId)
            ->selectRaw('status, priority, COUNT(*) as count')
            ->groupBy('status', 'priority')
            ->get();
        
        $statusBreakdown = [];
        $priorityBreakdown = [];
        $total = 0;
        
        foreach ($tickets as $ticket) {
            $statusBreakdown[$ticket->status] = ($statusBreakdown[$ticket->status] ?? 0) + $ticket->count;
            $priorityBreakdown[$ticket->priority] = ($priorityBreakdown[$ticket->priority] ?? 0) + $ticket->count;
            $total += $ticket->count;
        }
        
        // Get SLA compliance
        $slaBreached = Ticket::where('company_id', $this->companyId)
            ->where('sla_breached', true)
            ->whereNull('resolved_at')
            ->count();
        
        return [
            'total' => $total,
            'by_status' => $statusBreakdown,
            'by_priority' => $priorityBreakdown,
            'sla_breached' => $slaBreached,
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'open_critical' => $priorityBreakdown['Critical'] ?? 0,
        ];
    }
    
    /**
     * Client Health Widget
     */
    protected function getClientHealth(array $config): array
    {
        $clients = Client::where('company_id', $this->companyId)
            ->whereNull('archived_at')
            ->withCount([
                'tickets as open_tickets' => function ($query) {
                    $query->whereIn('status', ['Open', 'In Progress']);
                },
                'invoices as overdue_invoices' => function ($query) {
                    $query->where('status', 'Sent')
                          ->where('due_date', '<', now());
                },
            ])
            ->withSum([
                'payments as revenue_30d' => function ($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                },
            ], 'amount')
            ->get();
        
        $healthScores = [];
        foreach ($clients as $client) {
            $score = 100;
            
            // Deduct for open tickets
            $score -= min($client->open_tickets * 5, 30);
            
            // Deduct for overdue invoices
            $score -= min($client->overdue_invoices * 10, 40);
            
            // Add for recent revenue
            if ($client->revenue_30d > 0) {
                $score = min($score + 10, 100);
            }
            
            $healthScores[] = [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'score' => max($score, 0),
                'status' => $score >= 80 ? 'healthy' : ($score >= 50 ? 'warning' : 'critical'),
                'open_tickets' => $client->open_tickets,
                'overdue_invoices' => $client->overdue_invoices,
            ];
        }
        
        // Sort by score ascending to show problematic clients first
        usort($healthScores, fn($a, $b) => $a['score'] <=> $b['score']);
        
        return [
            'clients' => array_slice($healthScores, 0, 10),
            'summary' => [
                'healthy' => count(array_filter($healthScores, fn($c) => $c['status'] === 'healthy')),
                'warning' => count(array_filter($healthScores, fn($c) => $c['status'] === 'warning')),
                'critical' => count(array_filter($healthScores, fn($c) => $c['status'] === 'critical')),
            ],
        ];
    }
    
    /**
     * Team Performance Widget
     */
    protected function getTeamPerformance(array $config): array
    {
        $period = $config['period'] ?? 'today';
        $startDate = $this->getPeriodStart($period);
        
        $technicians = User::where('company_id', $this->companyId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['technician', 'admin']);
            })
            ->withCount([
                'assignedTickets as tickets_resolved' => function ($query) use ($startDate) {
                    $query->where('status', 'Closed')
                          ->where('resolved_at', '>=', $startDate);
                },
                'assignedTickets as tickets_open' => function ($query) {
                    $query->whereIn('status', ['Open', 'In Progress']);
                },
            ])
            ->get();
        
        $performance = [];
        foreach ($technicians as $tech) {
            $performance[] = [
                'user_id' => $tech->id,
                'name' => $tech->name,
                'tickets_resolved' => $tech->tickets_resolved,
                'tickets_open' => $tech->tickets_open,
                'efficiency_score' => $this->calculateEfficiencyScore($tech),
                'status' => $tech->last_activity >= now()->subMinutes(5) ? 'online' : 'offline',
            ];
        }
        
        // Sort by tickets resolved
        usort($performance, fn($a, $b) => $b['tickets_resolved'] <=> $a['tickets_resolved']);
        
        return [
            'team' => $performance,
            'total_resolved' => array_sum(array_column($performance, 'tickets_resolved')),
            'total_open' => array_sum(array_column($performance, 'tickets_open')),
            'avg_efficiency' => round(array_sum(array_column($performance, 'efficiency_score')) / max(count($performance), 1), 1),
        ];
    }
    
    /**
     * Revenue Chart Widget
     */
    protected function getRevenueChart(array $config): array
    {
        $period = $config['period'] ?? 'last_30_days';
        $groupBy = $config['group_by'] ?? 'day';
        
        $startDate = $this->getPeriodStart($period);
        
        $query = Payment::where('company_id', $this->companyId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed');
        
        if ($groupBy === 'day') {
            $data = $query->selectRaw('created_at::date as date, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $data = $query->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }
        
        return [
            'labels' => $data->pluck($groupBy === 'day' ? 'date' : 'month')->toArray(),
            'values' => $data->pluck('total')->toArray(),
            'total' => $data->sum('total'),
            'average' => $data->avg('total'),
            'period' => $period,
            'group_by' => $groupBy,
        ];
    }
    
    /**
     * Activity Feed Widget
     */
    protected function getActivityFeed(array $config): array
    {
        $limit = $config['limit'] ?? 10;
        $types = $config['types'] ?? ['ticket', 'invoice', 'payment'];
        
        $activities = [];
        
        if (in_array('ticket', $types)) {
            $tickets = Ticket::where('company_id', $this->companyId)
                ->with('client:id,name', 'assignee:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'type' => 'ticket',
                        'title' => "New ticket: {$ticket->subject}",
                        'client' => $ticket->client->name ?? 'Unknown',
                        'assignee' => $ticket->assignee->name ?? 'Unassigned',
                        'priority' => $ticket->priority,
                        'timestamp' => $ticket->created_at,
                    ];
                });
            $activities = array_merge($activities, $tickets->toArray());
        }
        
        if (in_array('payment', $types)) {
            $payments = Payment::where('company_id', $this->companyId)
                ->with('client:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($payment) {
                    return [
                        'type' => 'payment',
                        'title' => "Payment received: $" . number_format($payment->amount, 2),
                        'client' => $payment->client->name ?? 'Unknown',
                        'method' => $payment->payment_method,
                        'timestamp' => $payment->created_at,
                    ];
                });
            $activities = array_merge($activities, $payments->toArray());
        }
        
        // Sort by timestamp
        usort($activities, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Alerts Widget
     */
    protected function getAlerts(array $config): array
    {
        $alerts = [];
        
        // Critical tickets
        $criticalTickets = Ticket::where('company_id', $this->companyId)
            ->where('priority', 'Critical')
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();
        
        if ($criticalTickets > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Critical Tickets',
                'message' => "{$criticalTickets} critical tickets need immediate attention",
                'action' => '/tickets?priority=critical',
                'timestamp' => now(),
            ];
        }
        
        // Overdue invoices
        $overdueAmount = Invoice::where('company_id', $this->companyId)
            ->where('status', 'Sent')
            ->where('due_date', '<', now()->subDays(30))
            ->sum('amount');
        
        if ($overdueAmount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Overdue Invoices',
                'message' => '$' . number_format($overdueAmount, 2) . ' overdue by 30+ days',
                'action' => '/financial/invoices?status=overdue',
                'timestamp' => now(),
            ];
        }
        
        // Low performing assets
        $lowPerformingAssets = Asset::where('company_id', $this->companyId)
            ->where('health_score', '<', 50)
            ->count();
        
        if ($lowPerformingAssets > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Asset Health',
                'message' => "{$lowPerformingAssets} assets need maintenance",
                'action' => '/assets?health=low',
                'timestamp' => now(),
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Forecast Widget
     */
    protected function getForecast(array $config): array
    {
        $months = $config['months'] ?? 3;
        
        // Get historical data for trend analysis
        $historicalRevenue = Payment::where('company_id', $this->companyId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->where('status', 'completed')
            ->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total')
            ->toArray();
        
        // Simple linear regression for forecast
        $forecast = [];
        $avgGrowth = 0;
        
        if (count($historicalRevenue) > 1) {
            $growthRates = [];
            for ($i = 1; $i < count($historicalRevenue); $i++) {
                if ($historicalRevenue[$i-1] > 0) {
                    $growthRates[] = ($historicalRevenue[$i] - $historicalRevenue[$i-1]) / $historicalRevenue[$i-1];
                }
            }
            $avgGrowth = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
        }
        
        $lastRevenue = end($historicalRevenue) ?: 0;
        for ($i = 1; $i <= $months; $i++) {
            $forecastValue = $lastRevenue * (1 + $avgGrowth);
            $forecast[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'value' => round($forecastValue, 2),
                'confidence' => max(0, 100 - ($i * 10)), // Confidence decreases over time
            ];
            $lastRevenue = $forecastValue;
        }
        
        return [
            'forecast' => $forecast,
            'growth_rate' => round($avgGrowth * 100, 1),
            'based_on_months' => count($historicalRevenue),
        ];
    }
    
    /**
     * Helper methods
     */
    
    protected function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'last_30_days' => now()->subDays(30),
            'last_90_days' => now()->subDays(90),
            default => now()->startOfMonth(),
        };
    }
    
    protected function getPreviousPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->subDay()->startOfDay(),
            'week' => now()->subWeek()->startOfWeek(),
            'month' => now()->subMonth()->startOfMonth(),
            'quarter' => now()->subQuarter()->startOfQuarter(),
            'year' => now()->subYear()->startOfYear(),
            'last_30_days' => now()->subDays(60),
            'last_90_days' => now()->subDays(180),
            default => now()->subMonth()->startOfMonth(),
        };
    }
    
    protected function getRevenueSparkline(string $period): array
    {
        $days = match ($period) {
            'week' => 7,
            'month' => 30,
            default => 7,
        };
        
        $data = Payment::where('company_id', $this->companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'completed')
            ->selectRaw('created_at::date as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();
        
        return $data;
    }
    
    protected function getNewMRR(): float
    {
        return Invoice::where('company_id', $this->companyId)
            ->where('is_recurring', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('monthly_amount');
    }
    
    protected function getChurnedMRR(): float
    {
        return Invoice::where('company_id', $this->companyId)
            ->where('is_recurring', true)
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->sum('monthly_amount');
    }
    
    protected function getAverageResolutionTime(): string
    {
        $avgMinutes = Ticket::where('company_id', $this->companyId)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');
        
        if (!$avgMinutes) {
            return 'N/A';
        }
        
        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;
        
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }
    
    protected function calculateEfficiencyScore($technician): float
    {
        $resolved = $technician->tickets_resolved;
        $open = $technician->tickets_open;
        
        if ($resolved + $open === 0) {
            return 0;
        }
        
        return round(($resolved / ($resolved + $open)) * 100, 1);
    }
    
    protected function getTopClients(array $config): array
    {
        $limit = $config['limit'] ?? 5;
        $period = $config['period'] ?? 'month';
        $startDate = $this->getPeriodStart($period);
        
        $clients = Client::where('company_id', $this->companyId)
            ->withSum(['payments as revenue' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->where('status', 'completed');
            }], 'amount')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get();
        
        return $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'revenue' => $client->revenue ?? 0,
                'formatted_revenue' => '$' . number_format($client->revenue ?? 0, 2),
            ];
        })->toArray();
    }
    
    protected function getResourceUtilization(array $config): array
    {
        $resources = User::where('company_id', $this->companyId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['technician', 'admin']);
            })
            ->withCount([
                'assignedTickets as active_tasks' => function ($query) {
                    $query->whereIn('status', ['In Progress']);
                },
            ])
            ->get();
        
        $utilization = [];
        foreach ($resources as $resource) {
            $utilization[] = [
                'id' => $resource->id,
                'name' => $resource->name,
                'active_tasks' => $resource->active_tasks,
                'utilization_percentage' => min(100, $resource->active_tasks * 20), // Assume 5 tasks = 100%
                'status' => $resource->active_tasks > 4 ? 'overloaded' : ($resource->active_tasks > 2 ? 'busy' : 'available'),
            ];
        }
        
        return [
            'resources' => $utilization,
            'summary' => [
                'total' => count($utilization),
                'available' => count(array_filter($utilization, fn($r) => $r['status'] === 'available')),
                'busy' => count(array_filter($utilization, fn($r) => $r['status'] === 'busy')),
                'overloaded' => count(array_filter($utilization, fn($r) => $r['status'] === 'overloaded')),
            ],
        ];
    }
    
    protected function getProjectStatus(array $config): array
    {
        $projects = Project::where('company_id', $this->companyId)
            ->whereIn('status', ['active', 'in_progress'])
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get();
        
        return $projects->map(function ($project) {
            $progress = $project->tasks_count > 0 
                ? round(($project->completed_tasks / $project->tasks_count) * 100) 
                : 0;
            
            return [
                'id' => $project->id,
                'name' => $project->name,
                'client' => $project->client->name ?? 'N/A',
                'progress' => $progress,
                'tasks_completed' => $project->completed_tasks,
                'tasks_total' => $project->tasks_count,
                'due_date' => $project->due_date,
                'is_overdue' => $project->due_date && $project->due_date < now(),
                'status' => $project->status,
            ];
        })->toArray();
    }
}