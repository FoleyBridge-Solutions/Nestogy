<?php

namespace App\Domains\Report\Services;

use App\Domains\Financial\Models\Payment;
use App\Domains\Knowledge\Models\KbArticleView;
use App\Domains\Knowledge\Services\TicketDeflectionService;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Service
 *
 * Dynamic dashboard generation with configurable widgets
 */
class DashboardService
{
    protected WidgetService $widgetService;

    protected TicketDeflectionService $deflectionService;

    public function __construct(
        WidgetService $widgetService,
        TicketDeflectionService $deflectionService
    ) {
        $this->widgetService = $widgetService;
        $this->deflectionService = $deflectionService;
    }

    /**
     * Generate executive dashboard data
     */
    public function getExecutiveDashboard(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "executive_dashboard:{$companyId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($companyId, $startDate, $endDate) {
            return [
                'financial_metrics' => $this->getFinancialMetrics($companyId, $startDate, $endDate),
                'service_metrics' => $this->getServiceMetrics($companyId, $startDate, $endDate),
                'client_metrics' => $this->getClientMetrics($companyId, $startDate, $endDate),
                'knowledge_base_metrics' => $this->getKnowledgeBaseMetrics($companyId, $startDate, $endDate),
                'trend_data' => $this->getTrendData($companyId, $startDate, $endDate),
                'alerts' => $this->getExecutiveAlerts($companyId),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'days' => $startDate->diffInDays($endDate),
                ],
            ];
        });
    }

    /**
     * Generate service dashboard data
     */
    public function getServiceDashboard(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "service_dashboard:{$companyId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($companyId, $startDate, $endDate) {
            return [
                'ticket_metrics' => $this->getDetailedTicketMetrics($companyId, $startDate, $endDate),
                'sla_performance' => $this->getSLAPerformance($companyId, $startDate, $endDate),
                'technician_performance' => $this->getTechnicianPerformance($companyId, $startDate, $endDate),
                'queue_analytics' => $this->getQueueAnalytics($companyId),
                'response_times' => $this->getResponseTimeAnalytics($companyId, $startDate, $endDate),
                'satisfaction_scores' => $this->getSatisfactionMetrics($companyId, $startDate, $endDate),
            ];
        });
    }

    /**
     * Generate financial dashboard data
     */
    public function getFinancialDashboard(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "financial_dashboard:{$companyId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($companyId, $startDate, $endDate) {
            return [
                'revenue_metrics' => $this->getRevenueMetrics($companyId, $startDate, $endDate),
                'ar_aging' => $this->getARAgingReport($companyId),
                'payment_trends' => $this->getPaymentTrends($companyId, $startDate, $endDate),
                'client_profitability' => $this->getClientProfitability($companyId, $startDate, $endDate),
                'recurring_revenue' => $this->getRecurringRevenueMetrics($companyId, $startDate, $endDate),
                'budget_vs_actual' => $this->getBudgetComparison($companyId, $startDate, $endDate),
            ];
        });
    }

    /**
     * Generate client-specific dashboard
     */
    public function getClientDashboard(
        Client $client,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "client_dashboard:{$client->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($client, $startDate, $endDate) {
            return [
                'service_summary' => $this->getClientServiceSummary($client, $startDate, $endDate),
                'ticket_analytics' => $this->getClientTicketAnalytics($client, $startDate, $endDate),
                'financial_summary' => $this->getClientFinancialSummary($client, $startDate, $endDate),
                'usage_metrics' => $this->getClientUsageMetrics($client, $startDate, $endDate),
                'satisfaction_metrics' => $this->getClientSatisfactionMetrics($client, $startDate, $endDate),
                'health_score' => $this->calculateClientHealthScore($client),
            ];
        });
    }

    /**
     * Get financial metrics for executive dashboard
     */
    protected function getFinancialMetrics(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $previousPeriod = $startDate->copy()->subDays($startDate->diffInDays($endDate));

        // Current period revenue
        $currentRevenue = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        // Previous period revenue
        $previousRevenue = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$previousPeriod, $startDate->copy()->subDay()])
            ->where('status', 'completed')
            ->sum('amount');

        // MRR calculation
        $mrr = $this->calculateMRR($companyId);
        $previousMrr = $this->calculateMRR($companyId, $startDate->copy()->subMonth());

        // Outstanding invoices
        $outstandingAmount = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('status', 'sent')
            ->sum('total');

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'revenue_growth' => $previousRevenue > 0
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
                : 0,
            'mrr' => $mrr,
            'mrr_growth' => $previousMrr > 0
                ? (($mrr - $previousMrr) / $previousMrr) * 100
                : 0,
            'outstanding_amount' => $outstandingAmount,
            'average_deal_size' => $this->calculateAverageDealSize($companyId, $startDate, $endDate),
            'payment_collection_rate' => $this->calculateCollectionRate($companyId, $startDate, $endDate),
        ];
    }

    /**
     * Get service metrics for executive dashboard
     */
    protected function getServiceMetrics(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalTickets = $tickets->count();
        $resolvedTickets = $tickets->whereNotNull('resolved_at')->count();

        $avgResolutionTime = DB::table('tickets')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/3600) as avg_hours')
            ->value('avg_hours');

        $slaBreaches = $tickets->where('sla_breached', true)->count();

        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'resolution_rate' => $totalTickets > 0 ? ($resolvedTickets / $totalTickets) * 100 : 0,
            'avg_resolution_time_hours' => round($avgResolutionTime ?? 0, 2),
            'sla_compliance' => $totalTickets > 0
                ? (($totalTickets - $slaBreaches) / $totalTickets) * 100
                : 100,
            'first_response_time' => $this->calculateFirstResponseTime($companyId, $startDate, $endDate),
            'customer_satisfaction' => $this->calculateSatisfactionScore($companyId, $startDate, $endDate),
        ];
    }

    /**
     * Get client metrics for executive dashboard
     */
    protected function getClientMetrics(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $totalClients = Client::where('company_id', $companyId)->count();

        $newClients = Client::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $churned = Client::where('company_id', $companyId)
            ->where('status', 'inactive')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $clientsWithTickets = Client::where('company_id', $companyId)
            ->whereHas('tickets', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();

        return [
            'total_clients' => $totalClients,
            'new_clients' => $newClients,
            'churned_clients' => $churned,
            'churn_rate' => $totalClients > 0 ? ($churned / $totalClients) * 100 : 0,
            'clients_with_tickets' => $clientsWithTickets,
            'ticket_penetration' => $totalClients > 0 ? ($clientsWithTickets / $totalClients) * 100 : 0,
            'avg_client_value' => $this->calculateAverageClientValue($companyId),
        ];
    }

    /**
     * Get knowledge base metrics
     */
    protected function getKnowledgeBaseMetrics(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $deflectionMetrics = $this->deflectionService->getDeflectionMetrics(
            $companyId,
            $startDate,
            $endDate
        );

        $kbViews = KbArticleView::where('company_id', $companyId)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->count();

        $topArticles = KbArticleView::where('company_id', $companyId)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->select('article_id', DB::raw('COUNT(*) as views'))
            ->with('article:id,title')
            ->groupBy('article_id')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_views' => $kbViews,
            'deflection_rate' => $deflectionMetrics['deflection_rate'],
            'tickets_deflected' => $deflectionMetrics['tickets_deflected'],
            'estimated_savings' => $deflectionMetrics['tickets_deflected'] * 25, // $25 per ticket
            'top_articles' => $topArticles,
            'search_success_rate' => $this->calculateSearchSuccessRate($companyId, $startDate, $endDate),
        ];
    }

    /**
     * Get trend data for charts - Optimized to use single queries instead of loop
     */
    protected function getTrendData(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Get all revenue data in a single query
        $revenueByDate = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Get all ticket counts in a single query
        $ticketsByDate = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Build the arrays with all dates
        $days = [];
        $revenueData = [];
        $ticketData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $days[] = $current->format('M j');

            // Use pre-fetched data instead of queries
            $revenueData[] = (float) ($revenueByDate[$dateKey] ?? 0);
            $ticketData[] = (int) ($ticketsByDate[$dateKey] ?? 0);

            $current->addDay();
        }

        return [
            'labels' => $days,
            'revenue' => $revenueData,
            'tickets' => $ticketData,
        ];
    }

    /**
     * Get executive alerts
     */
    protected function getExecutiveAlerts(int $companyId): array
    {
        $alerts = [];

        // High priority open tickets
        $highPriorityTickets = Ticket::where('company_id', $companyId)
            ->where('priority', 'high')
            ->whereNull('resolved_at')
            ->count();

        if ($highPriorityTickets > 5) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Priority Tickets',
                'message' => "{$highPriorityTickets} high priority tickets are open",
                'action_url' => route('tickets.index', ['priority' => 'high']),
            ];
        }

        // SLA breaches
        $slaBreaches = Ticket::where('company_id', $companyId)
            ->where('sla_breached', true)
            ->whereNull('resolved_at')
            ->count();

        if ($slaBreaches > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'SLA Breaches',
                'message' => "{$slaBreaches} tickets have breached SLA",
                'action_url' => route('tickets.index', ['sla_breached' => true]),
            ];
        }

        // Overdue invoices
        $overdueInvoices = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Overdue Invoices',
                'message' => "{$overdueInvoices} invoices are overdue",
                'action_url' => route('invoices.index', ['status' => 'overdue']),
            ];
        }

        return $alerts;
    }

    /**
     * Calculate MRR (Monthly Recurring Revenue)
     */
    protected function calculateMRR(int $companyId, ?Carbon $date = null): float
    {
        $date = $date ?? now();

        return DB::table('client_recurring_invoices')
            ->join('clients', 'client_recurring_invoices.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->where('client_recurring_invoices.status', 'active')
            ->where('client_recurring_invoices.next_invoice_date', '>', $date)
            ->sum('client_recurring_invoices.amount');
    }

    /**
     * Calculate average deal size
     */
    protected function calculateAverageDealSize(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        return Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->avg('amount') ?? 0;
    }

    /**
     * Calculate collection rate
     */
    protected function calculateCollectionRate(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        $invoiced = DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $collected = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        return $invoiced > 0 ? ($collected / $invoiced) * 100 : 0;
    }

    /**
     * Calculate first response time
     */
    protected function calculateFirstResponseTime(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        return DB::table('tickets')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (first_response_at - created_at))/60) as avg_minutes')
            ->value('avg_minutes') ?? 0;
    }

    /**
     * Calculate satisfaction score
     */
    protected function calculateSatisfactionScore(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        return DB::table('ticket_feedback')
            ->join('tickets', 'ticket_feedback.ticket_id', '=', 'tickets.id')
            ->where('tickets.company_id', $companyId)
            ->whereBetween('tickets.created_at', [$startDate, $endDate])
            ->avg('ticket_feedback.rating') ?? 0;
    }

    /**
     * Calculate average client value
     */
    protected function calculateAverageClientValue(int $companyId): float
    {
        return DB::table('payments')
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->where('payments.status', 'completed')
            ->where('payments.payment_date', '>=', now()->subYear())
            ->groupBy('clients.id')
            ->selectRaw('SUM(payments.amount) as client_total')
            ->avg('client_total') ?? 0;
    }

    /**
     * Calculate search success rate for KB
     */
    protected function calculateSearchSuccessRate(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        $searchViews = KbArticleView::where('company_id', $companyId)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('search_query')
            ->count();

        $successfulSearches = KbArticleView::where('company_id', $companyId)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('search_query')
            ->where('time_spent_seconds', '>', 30) // Spent more than 30 seconds = success
            ->count();

        return $searchViews > 0 ? ($successfulSearches / $searchViews) * 100 : 0;
    }

    // Additional helper methods for other dashboard types would be implemented here
    // getDetailedTicketMetrics, getSLAPerformance, etc.
}
