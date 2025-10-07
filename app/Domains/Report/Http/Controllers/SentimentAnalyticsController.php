<?php

namespace App\Domains\Report\Http\Controllers;

use App\Domains\Core\Services\DashboardDataService;
use App\Domains\Financial\Services\TaxEngine\SentimentAnalysisService;
use App\Domains\Ticket\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TicketReply;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sentiment Analytics Dashboard Controller
 *
 * Provides comprehensive sentiment analysis reporting and visualization
 * for tickets and customer interactions. Includes trends, client health scores,
 * and actionable insights for customer satisfaction management.
 */
class SentimentAnalyticsController extends Controller
{
    private const COLOR_RED = '#ef4444';

    /**
     * Display sentiment analytics dashboard
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        // Get date range from request or default to last 30 days
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : now()->subDays(30);
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : now();

        // Get comprehensive sentiment data
        $dashboardService = new DashboardDataService($companyId);
        $sentimentService = new SentimentAnalysisService($companyId);

        $data = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'label' => $startDate->format('M j').' - '.$endDate->format('M j, Y'),
            ],
            'overview_metrics' => $this->getOverviewMetrics($companyId, $startDate, $endDate),
            'sentiment_trends' => $this->getSentimentTrends($companyId, $startDate, $endDate),
            'client_sentiment_health' => $this->getClientSentimentHealth($companyId, $startDate, $endDate),
            'negative_tickets_requiring_attention' => $this->getNegativeTicketsRequiringAttention($companyId),
            'sentiment_by_category' => $this->getSentimentByCategory($companyId, $startDate, $endDate),
            'sentiment_by_priority' => $this->getSentimentByPriority($companyId, $startDate, $endDate),
            'team_performance' => $this->getTeamSentimentPerformance($companyId, $startDate, $endDate),
            'resolution_time_vs_sentiment' => $this->getResolutionTimeVsSentiment($companyId, $startDate, $endDate),
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('reports.sentiment-analytics', compact('data'));
    }

    /**
     * Get overview metrics for sentiment dashboard
     */
    private function getOverviewMetrics(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Total analyzed interactions
        $totalTickets = Ticket::where('company_id', $companyId)
            ->whereNotNull('sentiment_analyzed_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalReplies = TicketReply::where('company_id', $companyId)
            ->whereNotNull('sentiment_analyzed_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalInteractions = $totalTickets + $totalReplies;

        // Sentiment distribution
        $sentimentStats = DB::select('
            SELECT 
                sentiment_label,
                COUNT(*) as count,
                AVG(sentiment_score) as avg_score,
                AVG(sentiment_confidence) as avg_confidence
            FROM (
                SELECT sentiment_label, sentiment_score, sentiment_confidence
                FROM tickets 
                WHERE company_id = ? 
                AND sentiment_analyzed_at IS NOT NULL
                AND created_at BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT sentiment_label, sentiment_score, sentiment_confidence
                FROM ticket_replies 
                WHERE company_id = ? 
                AND sentiment_analyzed_at IS NOT NULL
                AND created_at BETWEEN ? AND ?
            ) as combined_sentiment
            WHERE sentiment_label IS NOT NULL
            GROUP BY sentiment_label
        ', [$companyId, $startDate, $endDate, $companyId, $startDate, $endDate]);

        $sentimentCounts = collect($sentimentStats)->keyBy('sentiment_label');

        // Calculate overall averages
        $totalCount = $sentimentCounts->sum('count');
        $overallAvgScore = $totalCount > 0
            ? $sentimentCounts->sum(function ($stat) {
                return $stat->avg_score * $stat->count;
            }) / $totalCount
            : 0;

        // Count high-confidence negative tickets
        $negativeTicketsNeedingAttention = Ticket::where('company_id', $companyId)
            ->whereIn('sentiment_label', ['NEGATIVE', 'WEAK_NEGATIVE'])
            ->where('sentiment_confidence', '>', 0.6)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        // Previous period comparison
        $previousPeriodDays = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($previousPeriodDays);
        $previousEnd = $startDate->copy()->subDay();

        $previousTotal = Ticket::where('company_id', $companyId)
            ->whereNotNull('sentiment_analyzed_at')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count() +
            TicketReply::where('company_id', $companyId)
                ->whereNotNull('sentiment_analyzed_at')
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count();

        return [
            'total_interactions' => [
                'value' => $totalInteractions,
                'previous' => $previousTotal,
                'change' => $previousTotal > 0 ? round((($totalInteractions - $previousTotal) / $previousTotal) * 100, 1) : 0,
            ],
            'overall_sentiment_score' => [
                'value' => round($overallAvgScore, 2),
                'label' => $this->getSentimentLabel($overallAvgScore),
                'color' => $this->getSentimentColor($overallAvgScore),
            ],
            'positive_rate' => [
                'value' => $totalCount > 0 ? round((
                    ($sentimentCounts->get('POSITIVE')->count ?? 0) +
                    ($sentimentCounts->get('WEAK_POSITIVE')->count ?? 0)
                ) / $totalCount * 100, 1) : 0,
            ],
            'negative_rate' => [
                'value' => $totalCount > 0 ? round((
                    ($sentimentCounts->get('NEGATIVE')->count ?? 0) +
                    ($sentimentCounts->get('WEAK_NEGATIVE')->count ?? 0)
                ) / $totalCount * 100, 1) : 0,
            ],
            'neutral_rate' => [
                'value' => $totalCount > 0 ? round(($sentimentCounts->get('NEUTRAL')->count ?? 0) / $totalCount * 100, 1) : 0,
            ],
            'tickets_needing_attention' => [
                'value' => $negativeTicketsNeedingAttention,
                'color' => $negativeTicketsNeedingAttention > 0 ? self::COLOR_RED : '#10b981',
            ],
            'sentiment_distribution' => [
                'positive' => $sentimentCounts->get('POSITIVE')->count ?? 0,
                'weak_positive' => $sentimentCounts->get('WEAK_POSITIVE')->count ?? 0,
                'neutral' => $sentimentCounts->get('NEUTRAL')->count ?? 0,
                'weak_negative' => $sentimentCounts->get('WEAK_NEGATIVE')->count ?? 0,
                'negative' => $sentimentCounts->get('NEGATIVE')->count ?? 0,
            ],
        ];
    }

    /**
     * Get sentiment trends over time
     */
    private function getSentimentTrends(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $groupBy = $days > 30 ? 'week' : 'day';

        if ($groupBy === 'week') {
            $period = \Carbon\CarbonPeriod::create($startDate->copy()->startOfWeek(), '1 week', $endDate);
            $dateFormat = 'Y-\WW';
        } else {
            $period = \Carbon\CarbonPeriod::create($startDate, '1 day', $endDate);
            $dateFormat = 'Y-m-d';
        }

        $trends = [];
        foreach ($period as $date) {
            if ($groupBy === 'week') {
                $periodStart = $date->copy()->startOfWeek();
                $periodEnd = $date->copy()->endOfWeek();
                $label = 'Week of '.$date->format('M j');
            } else {
                $periodStart = $date->copy()->startOfDay();
                $periodEnd = $date->copy()->endOfDay();
                $label = $date->format('M j');
            }

            $stats = DB::select("
                SELECT 
                    AVG(sentiment_score) as avg_score,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN sentiment_label IN ('POSITIVE', 'WEAK_POSITIVE') THEN 1 ELSE 0 END) as positive_count,
                    SUM(CASE WHEN sentiment_label = 'NEUTRAL' THEN 1 ELSE 0 END) as neutral_count,
                    SUM(CASE WHEN sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') THEN 1 ELSE 0 END) as negative_count
                FROM (
                    SELECT sentiment_label, sentiment_score
                    FROM tickets 
                    WHERE company_id = ? 
                    AND sentiment_analyzed_at IS NOT NULL
                    AND created_at BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT sentiment_label, sentiment_score
                    FROM ticket_replies 
                    WHERE company_id = ? 
                    AND sentiment_analyzed_at IS NOT NULL
                    AND created_at BETWEEN ? AND ?
                ) as combined_sentiment
                WHERE sentiment_label IS NOT NULL
            ", [$companyId, $periodStart, $periodEnd, $companyId, $periodStart, $periodEnd]);

            $stat = $stats[0] ?? null;

            $trends[] = [
                'date' => $date->format($dateFormat),
                'label' => $label,
                'avg_sentiment_score' => $stat ? round($stat->avg_score, 2) : 0,
                'total_interactions' => $stat ? $stat->total_count : 0,
                'positive_count' => $stat ? $stat->positive_count : 0,
                'neutral_count' => $stat ? $stat->neutral_count : 0,
                'negative_count' => $stat ? $stat->negative_count : 0,
                'positive_rate' => $stat && $stat->total_count > 0 ? round(($stat->positive_count / $stat->total_count) * 100, 1) : 0,
                'negative_rate' => $stat && $stat->total_count > 0 ? round(($stat->negative_count / $stat->total_count) * 100, 1) : 0,
            ];
        }

        return $trends;
    }

    /**
     * Get client sentiment health scores
     */
    private function getClientSentimentHealth(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $clientHealth = DB::select("
            SELECT 
                c.id,
                c.name,
                COUNT(*) as total_interactions,
                AVG(combined.sentiment_score) as avg_sentiment_score,
                SUM(CASE WHEN combined.sentiment_label IN ('POSITIVE', 'WEAK_POSITIVE') THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN combined.sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') THEN 1 ELSE 0 END) as negative_count,
                SUM(CASE WHEN combined.sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') AND combined.sentiment_confidence > 0.6 THEN 1 ELSE 0 END) as high_confidence_negative
            FROM clients c
            JOIN (
                SELECT client_id, sentiment_label, sentiment_score, sentiment_confidence
                FROM tickets 
                WHERE company_id = ? 
                AND sentiment_analyzed_at IS NOT NULL
                AND created_at BETWEEN ? AND ?
                AND client_id IS NOT NULL
            ) as combined ON c.id = combined.client_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.name
            HAVING total_interactions >= 3
            ORDER BY avg_sentiment_score ASC, high_confidence_negative DESC
            LIMIT 20
        ", [$companyId, $startDate, $endDate, $companyId]);

        return collect($clientHealth)->map(function ($client) {
            $healthScore = $this->calculateClientHealthScore(
                $client->avg_sentiment_score,
                $client->positive_count,
                $client->negative_count,
                $client->high_confidence_negative,
                $client->total_interactions
            );

            return [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'total_interactions' => $client->total_interactions,
                'avg_sentiment_score' => round($client->avg_sentiment_score, 2),
                'positive_count' => $client->positive_count,
                'negative_count' => $client->negative_count,
                'high_confidence_negative' => $client->high_confidence_negative,
                'health_score' => $healthScore['score'],
                'health_label' => $healthScore['label'],
                'health_color' => $healthScore['color'],
                'risk_level' => $healthScore['risk_level'],
            ];
        })->toArray();
    }

    /**
     * Calculate client health score based on sentiment metrics
     */
    private function calculateClientHealthScore(float $avgScore, int $positive, int $negative, int $highConfidenceNegative, int $total): array
    {
        $score = 50; // Start with neutral score

        // Adjust based on average sentiment score
        $score += ($avgScore * 25); // -25 to +25 adjustment

        // Adjust based on positive/negative ratio
        if ($total > 0) {
            $positiveRate = $positive / $total;
            $negativeRate = $negative / $total;
            $score += ($positiveRate * 15) - ($negativeRate * 15);
        }

        // Penalty for high-confidence negative interactions
        $score -= ($highConfidenceNegative * 10);

        // Ensure score is between 0 and 100
        $score = max(0, min(100, $score));

        // Determine label, color, and risk level
        if ($score >= 80) {
            return [
                'score' => round($score, 1),
                'label' => 'Excellent',
                'color' => '#10b981',
                'risk_level' => 'Low',
            ];
        } elseif ($score >= 60) {
            return [
                'score' => round($score, 1),
                'label' => 'Good',
                'color' => '#84cc16',
                'risk_level' => 'Low',
            ];
        } elseif ($score >= 40) {
            return [
                'score' => round($score, 1),
                'label' => 'Fair',
                'color' => '#f59e0b',
                'risk_level' => 'Medium',
            ];
        } elseif ($score >= 20) {
            return [
                'score' => round($score, 1),
                'label' => 'Poor',
                'color' => '#f97316',
                'risk_level' => 'High',
            ];
        } else {
            return [
                'score' => round($score, 1),
                'label' => 'Critical',
                'color' => self::COLOR_RED,
                'risk_level' => 'Critical',
            ];
        }
    }

    /**
     * Get tickets requiring immediate attention
     */
    private function getNegativeTicketsRequiringAttention(int $companyId): array
    {
        return Ticket::where('company_id', $companyId)
            ->whereIn('sentiment_label', ['NEGATIVE', 'WEAK_NEGATIVE'])
            ->where('sentiment_confidence', '>', 0.6)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with(['client', 'assignee'])
            ->orderBy('sentiment_score', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'client_name' => $ticket->client->name ?? 'Unknown',
                    'assignee_name' => $ticket->assignee->name ?? 'Unassigned',
                    'sentiment_score' => $ticket->sentiment_score,
                    'sentiment_label' => $ticket->sentiment_label,
                    'sentiment_confidence' => $ticket->sentiment_confidence,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at->format('M j, Y g:i A'),
                    'sentiment_color' => $ticket->getSentimentColor(),
                    'sentiment_icon' => $ticket->getSentimentIcon(),
                ];
            })
            ->toArray();
    }

    /**
     * Get sentiment by ticket category
     */
    private function getSentimentByCategory(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::select("
            SELECT 
                category,
                COUNT(*) as total_count,
                AVG(sentiment_score) as avg_sentiment_score,
                SUM(CASE WHEN sentiment_label IN ('POSITIVE', 'WEAK_POSITIVE') THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') THEN 1 ELSE 0 END) as negative_count
            FROM tickets
            WHERE company_id = ?
            AND sentiment_analyzed_at IS NOT NULL
            AND created_at BETWEEN ? AND ?
            AND category IS NOT NULL
            GROUP BY category
            ORDER BY avg_sentiment_score ASC
        ", [$companyId, $startDate, $endDate]);
    }

    /**
     * Get sentiment by ticket priority
     */
    private function getSentimentByPriority(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::select("
            SELECT 
                priority,
                COUNT(*) as total_count,
                AVG(sentiment_score) as avg_sentiment_score,
                SUM(CASE WHEN sentiment_label IN ('POSITIVE', 'WEAK_POSITIVE') THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') THEN 1 ELSE 0 END) as negative_count
            FROM tickets
            WHERE company_id = ?
            AND sentiment_analyzed_at IS NOT NULL
            AND created_at BETWEEN ? AND ?
            GROUP BY priority
            ORDER BY 
                CASE priority 
                    WHEN 'Critical' THEN 1
                    WHEN 'High' THEN 2  
                    WHEN 'Medium' THEN 3
                    WHEN 'Low' THEN 4
                END
        ", [$companyId, $startDate, $endDate]);
    }

    /**
     * Get team sentiment performance
     */
    private function getTeamSentimentPerformance(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::select("
            SELECT 
                u.id,
                u.name,
                COUNT(DISTINCT t.id) as tickets_handled,
                COUNT(tr.id) as replies_sent,
                AVG(t.sentiment_score) as avg_ticket_sentiment,
                AVG(tr.sentiment_score) as avg_reply_sentiment,
                SUM(CASE WHEN t.sentiment_label IN ('POSITIVE', 'WEAK_POSITIVE') THEN 1 ELSE 0 END) as positive_tickets,
                SUM(CASE WHEN t.sentiment_label IN ('NEGATIVE', 'WEAK_NEGATIVE') THEN 1 ELSE 0 END) as negative_tickets
            FROM users u
            LEFT JOIN tickets t ON u.id = t.assigned_to 
                AND t.company_id = ? 
                AND t.sentiment_analyzed_at IS NOT NULL
                AND t.created_at BETWEEN ? AND ?
            LEFT JOIN ticket_replies tr ON u.id = tr.replied_by 
                AND tr.company_id = ? 
                AND tr.sentiment_analyzed_at IS NOT NULL
                AND tr.created_at BETWEEN ? AND ?
            WHERE u.company_id = ?
            AND u.status = 'active'
            GROUP BY u.id, u.name
            HAVING tickets_handled > 0 OR replies_sent > 0
            ORDER BY avg_ticket_sentiment DESC
        ", [$companyId, $startDate, $endDate, $companyId, $startDate, $endDate, $companyId]);
    }

    /**
     * Get resolution time vs sentiment correlation
     */
    private function getResolutionTimeVsSentiment(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::select('
            SELECT 
                t.sentiment_label,
                COUNT(*) as ticket_count,
                AVG(t.sentiment_score) as avg_sentiment_score,
                AVG(EXTRACT(EPOCH FROM (t.closed_at - t.created_at))/3600) as avg_resolution_hours
            FROM tickets t
            WHERE t.company_id = ?
            AND t.sentiment_analyzed_at IS NOT NULL
            AND t.closed_at IS NOT NULL
            AND t.created_at BETWEEN ? AND ?
            GROUP BY t.sentiment_label
            ORDER BY avg_sentiment_score DESC
        ', [$companyId, $startDate, $endDate]);
    }

    /**
     * Helper methods for sentiment labels and colors
     */
    private function getSentimentLabel(float $score): string
    {
        if ($score > 0.5) {
            return 'Very Positive';
        }
        if ($score > 0.1) {
            return 'Positive';
        }
        if ($score > -0.1) {
            return 'Neutral';
        }
        if ($score > -0.5) {
            return 'Negative';
        }

        return 'Very Negative';
    }

    private function getSentimentColor(float $score): string
    {
        if ($score > 0.5) {
            return '#10b981';
        }
        if ($score > 0.1) {
            return '#84cc16';
        }
        if ($score > -0.1) {
            return '#64748b';
        }
        if ($score > -0.5) {
            return '#f97316';
        }

        return self::COLOR_RED;
    }
}
