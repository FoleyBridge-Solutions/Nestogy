<?php

namespace App\Domains\Report\Services;

use App\Domains\Financial\Models\Payment;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Executive Report Service
 *
 * Pre-built executive reports and QBR generation
 */
class ExecutiveReportService
{
    protected DashboardService $dashboardService;

    protected WidgetService $widgetService;

    public function __construct(
        DashboardService $dashboardService,
        WidgetService $widgetService
    ) {
        $this->dashboardService = $dashboardService;
        $this->widgetService = $widgetService;
    }

    /**
     * Generate Quarterly Business Review (QBR) report
     */
    public function generateQBR(
        int $companyId,
        Carbon $quarterStart,
        Carbon $quarterEnd
    ): array {
        $cacheKey = "qbr_report:{$companyId}:{$quarterStart->format('Y-m-d')}:{$quarterEnd->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($companyId, $quarterStart, $quarterEnd) {
            return [
                'executive_summary' => $this->generateExecutiveSummary($companyId, $quarterStart, $quarterEnd),
                'financial_performance' => $this->generateFinancialPerformance($companyId, $quarterStart, $quarterEnd),
                'service_performance' => $this->generateServicePerformance($companyId, $quarterStart, $quarterEnd),
                'client_analytics' => $this->generateClientAnalytics($companyId, $quarterStart, $quarterEnd),
                'operational_metrics' => $this->generateOperationalMetrics($companyId, $quarterStart, $quarterEnd),
                'key_achievements' => $this->generateKeyAchievements($companyId, $quarterStart, $quarterEnd),
                'areas_for_improvement' => $this->generateImprovementAreas($companyId, $quarterStart, $quarterEnd),
                'next_quarter_recommendations' => $this->generateRecommendations($companyId),
                'appendix' => $this->generateAppendix($companyId, $quarterStart, $quarterEnd),
                'metadata' => [
                    'generated_at' => now(),
                    'quarter' => $this->getQuarterLabel($quarterStart),
                    'period' => [
                        'start' => $quarterStart,
                        'end' => $quarterEnd,
                        'days' => $quarterStart->diffInDays($quarterEnd),
                    ],
                ],
            ];
        });
    }

    /**
     * Generate monthly executive report
     */
    public function generateMonthlyExecutiveReport(
        int $companyId,
        Carbon $monthStart,
        Carbon $monthEnd
    ): array {
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $monthStart->copy()->subDay();

        return [
            'summary' => $this->generateMonthlySummary($companyId, $monthStart, $monthEnd),
            'kpis' => $this->generateMonthlyKPIs($companyId, $monthStart, $monthEnd),
            'financial_highlights' => $this->generateFinancialHighlights($companyId, $monthStart, $monthEnd),
            'service_highlights' => $this->generateServiceHighlights($companyId, $monthStart, $monthEnd),
            'client_updates' => $this->generateClientUpdates($companyId, $monthStart, $monthEnd),
            'team_performance' => $this->generateTeamPerformance($companyId, $monthStart, $monthEnd),
            'trends_and_insights' => $this->generateTrendsAndInsights($companyId, $monthStart, $monthEnd),
            'action_items' => $this->generateActionItems($companyId),
            'charts' => $this->generateExecutiveCharts($companyId, $monthStart, $monthEnd),
        ];
    }

    /**
     * Generate client health scorecard
     */
    public function generateClientHealthScorecard(int $companyId): array
    {
        $clients = Client::where('company_id', $companyId)
            ->with(['tickets', 'payments', 'contacts'])
            ->get();

        $healthScores = [];
        foreach ($clients as $client) {
            $healthScores[] = $this->calculateClientHealthScore($client);
        }

        // Sort by health score (lowest first for attention)
        usort($healthScores, function ($a, $b) {
            return $a['overall_score'] <=> $b['overall_score'];
        });

        return [
            'clients' => $healthScores,
            'summary' => [
                'total_clients' => count($healthScores),
                'healthy_clients' => count(array_filter($healthScores, fn ($c) => $c['overall_score'] >= 80)),
                'at_risk_clients' => count(array_filter($healthScores, fn ($c) => $c['overall_score'] < 60)),
                'average_score' => round(array_sum(array_column($healthScores, 'overall_score')) / count($healthScores), 2),
            ],
            'recommendations' => $this->generateHealthScoreRecommendations($healthScores),
        ];
    }

    /**
     * Generate service level agreement (SLA) report
     */
    public function generateSLAReport(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $slaMetrics = [
            'first_response' => $this->calculateSLAMetric($tickets, 'first_response_time', 60), // 1 hour SLA
            'resolution_time' => $this->calculateSLAMetric($tickets, 'resolution_time', 480), // 8 hours SLA
            'customer_satisfaction' => $this->calculateSatisfactionSLA($companyId, $startDate, $endDate),
        ];

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'overall_compliance' => $this->calculateOverallSLACompliance($slaMetrics),
            'metrics' => $slaMetrics,
            'by_priority' => $this->calculateSLAByPriority($tickets),
            'by_client' => $this->calculateSLAByClient($tickets),
            'trends' => $this->calculateSLATrends($companyId, $startDate, $endDate),
            'breach_analysis' => $this->analyzeSLABreaches($tickets),
            'recommendations' => $this->generateSLARecommendations($slaMetrics),
        ];
    }

    /**
     * Generate executive summary
     */
    protected function generateExecutiveSummary(int $companyId, Carbon $start, Carbon $end): array
    {
        $dashboard = $this->dashboardService->getExecutiveDashboard($companyId, $start, $end);

        return [
            'revenue_summary' => $this->formatRevenueSummary($dashboard['financial_metrics']),
            'service_summary' => $this->formatServiceSummary($dashboard['service_metrics']),
            'client_summary' => $this->formatClientSummary($dashboard['client_metrics']),
            'key_highlights' => $this->extractKeyHighlights($dashboard),
            'quarter_grade' => $this->calculateQuarterGrade($dashboard),
        ];
    }

    /**
     * Generate financial performance section
     */
    protected function generateFinancialPerformance(int $companyId, Carbon $start, Carbon $end): array
    {
        $currentRevenue = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$start, $end])
            ->where('status', 'completed')
            ->sum('amount');

        $previousPeriod = $start->copy()->subDays($start->diffInDays($end));
        $previousRevenue = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$previousPeriod, $start->copy()->subDay()])
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'revenue' => [
                'current' => $currentRevenue,
                'previous' => $previousRevenue,
                'growth' => $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0,
                'target' => $this->getRevenueTarget($companyId, $start, $end),
            ],
            'mrr' => $this->calculateMRRGrowth($companyId, $start),
            'client_value' => $this->calculateClientValueMetrics($companyId, $start, $end),
            'profitability' => $this->calculateProfitabilityMetrics($companyId, $start, $end),
            'forecasting' => $this->generateRevenueForecasting($companyId),
        ];
    }

    /**
     * Generate service performance section
     */
    protected function generateServicePerformance(int $companyId, Carbon $start, Carbon $end): array
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end]);

        return [
            'ticket_volume' => [
                'total' => $tickets->count(),
                'resolved' => $tickets->whereNotNull('resolved_at')->count(),
                'escalated' => $tickets->where('escalated', true)->count(),
                'sla_breached' => $tickets->where('sla_breached', true)->count(),
            ],
            'response_times' => $this->calculateResponseTimes($companyId, $start, $end),
            'resolution_metrics' => $this->calculateResolutionMetrics($companyId, $start, $end),
            'customer_satisfaction' => $this->calculateSatisfactionMetrics($companyId, $start, $end),
            'technician_performance' => $this->calculateTechnicianMetrics($companyId, $start, $end),
        ];
    }

    /**
     * Calculate client health score
     */
    protected function calculateClientHealthScore(Client $client): array
    {
        $scores = [];

        // Ticket health (40% weight)
        $recentTickets = $client->tickets()->where('created_at', '>=', now()->subMonth())->count();
        $escalatedTickets = $client->tickets()->where('escalated', true)->where('created_at', '>=', now()->subMonth())->count();
        $scores['ticket_health'] = max(0, 100 - ($recentTickets * 5) - ($escalatedTickets * 15));

        // Payment health (30% weight)
        $overdueInvoices = DB::table('invoices')
            ->where('client_id', $client->id)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->count();
        $scores['payment_health'] = max(0, 100 - ($overdueInvoices * 20));

        // Engagement health (20% weight)
        $lastActivity = $client->tickets()->latest()->first()?->created_at ?? $client->updated_at;
        $daysSinceActivity = now()->diffInDays($lastActivity);
        $scores['engagement_health'] = max(0, 100 - ($daysSinceActivity * 2));

        // Growth health (10% weight)
        $currentRevenue = $client->payments()->where('payment_date', '>=', now()->subMonth())->sum('amount');
        $previousRevenue = $client->payments()->whereBetween('payment_date', [now()->subMonths(2), now()->subMonth()])->sum('amount');
        $growthRate = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $scores['growth_health'] = min(100, max(0, 50 + $growthRate));

        $overallScore = ($scores['ticket_health'] * 0.4) +
                       ($scores['payment_health'] * 0.3) +
                       ($scores['engagement_health'] * 0.2) +
                       ($scores['growth_health'] * 0.1);

        return [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'overall_score' => round($overallScore, 2),
            'scores' => $scores,
            'risk_level' => $this->determineRiskLevel($overallScore),
            'recommendations' => $this->generateClientRecommendations($client, $scores),
        ];
    }

    /**
     * Determine risk level based on score
     */
    protected function determineRiskLevel(float $score): string
    {
        if ($score >= 80) {
            return 'low';
        }
        if ($score >= 60) {
            return 'medium';
        }

        return 'high';
    }

    /**
     * Calculate quarter grade
     */
    protected function calculateQuarterGrade(array $dashboard): array
    {
        $scores = [];

        // Revenue performance (30%)
        $revenueGrowth = $dashboard['financial_metrics']['revenue_growth'] ?? 0;
        $scores['revenue'] = min(100, max(0, 50 + $revenueGrowth));

        // Service performance (40%)
        $slaCompliance = $dashboard['service_metrics']['sla_compliance'] ?? 0;
        $scores['service'] = $slaCompliance;

        // Client satisfaction (20%)
        $satisfaction = $dashboard['service_metrics']['customer_satisfaction'] ?? 0;
        $scores['satisfaction'] = $satisfaction * 20; // Convert 5-point scale to 100

        // Growth performance (10%)
        $clientGrowth = $dashboard['client_metrics']['churn_rate'] ?? 0;
        $scores['growth'] = max(0, 100 - $clientGrowth * 2);

        $overallScore = ($scores['revenue'] * 0.3) +
                       ($scores['service'] * 0.4) +
                       ($scores['satisfaction'] * 0.2) +
                       ($scores['growth'] * 0.1);

        return [
            'overall_score' => round($overallScore, 2),
            'letter_grade' => $this->getLetterGrade($overallScore),
            'component_scores' => $scores,
        ];
    }

    /**
     * Get letter grade from score
     */
    protected function getLetterGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A+';
        }
        if ($score >= 85) {
            return 'A';
        }
        if ($score >= 80) {
            return 'A-';
        }
        if ($score >= 75) {
            return 'B+';
        }
        if ($score >= 70) {
            return 'B';
        }
        if ($score >= 65) {
            return 'B-';
        }
        if ($score >= 60) {
            return 'C+';
        }
        if ($score >= 55) {
            return 'C';
        }
        if ($score >= 50) {
            return 'C-';
        }

        return 'F';
    }

    /**
     * Get quarter label
     */
    protected function getQuarterLabel(Carbon $date): string
    {
        $quarter = ceil($date->month / 3);

        return "Q{$quarter} {$date->year}";
    }

    /**
     * Format revenue summary
     */
    protected function formatRevenueSummary(array $metrics): string
    {
        $revenue = number_format($metrics['current_revenue'] ?? 0, 2);
        $growth = round($metrics['revenue_growth'] ?? 0, 2);
        $direction = $growth >= 0 ? 'increased' : 'decreased';

        return "Revenue for this period was \${$revenue}, which {$direction} by {$growth}% compared to the previous period.";
    }

    /**
     * Format service summary
     */
    protected function formatServiceSummary(array $metrics): string
    {
        $compliance = round($metrics['sla_compliance'] ?? 0, 2);
        $avgResolution = round($metrics['avg_resolution_time_hours'] ?? 0, 2);

        return "Service level compliance was {$compliance}% with an average resolution time of {$avgResolution} hours.";
    }

    /**
     * Format client summary
     */
    protected function formatClientSummary(array $metrics): string
    {
        $total = $metrics['total_clients'] ?? 0;
        $new = $metrics['new_clients'] ?? 0;
        $churn = round($metrics['churn_rate'] ?? 0, 2);

        return "We served {$total} clients this period, added {$new} new clients, and maintained a churn rate of {$churn}%.";
    }

    // Additional helper methods would be implemented here...
    // calculateSLAMetric, calculateMRRGrowth, generateRecommendations, etc.
}
