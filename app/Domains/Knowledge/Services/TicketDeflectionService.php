<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbArticleView;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Ticket Deflection Service
 * 
 * Tracks and measures knowledge base ticket deflection effectiveness
 */
class TicketDeflectionService
{
    protected ArticleSearchService $searchService;
    protected KnowledgeBaseService $kbService;

    public function __construct(
        ArticleSearchService $searchService,
        KnowledgeBaseService $kbService
    ) {
        $this->searchService = $searchService;
        $this->kbService = $kbService;
    }

    /**
     * Get suggested articles before ticket creation
     */
    public function getSuggestedArticlesForTicket(string $subject, string $description = ''): array
    {
        $query = $subject . ' ' . $description;
        
        // Get relevant articles
        $suggestedArticles = $this->searchService->search($query, [
            'limit' => 5,
            'visibility' => [KbArticle::VISIBILITY_PUBLIC, KbArticle::VISIBILITY_CLIENT],
            'status' => KbArticle::STATUS_PUBLISHED,
        ]);

        // Track this deflection attempt
        $this->trackDeflectionAttempt($query, $suggestedArticles);

        return [
            'query' => $query,
            'articles' => $suggestedArticles,
            'count' => $suggestedArticles->count(),
            'confidence_score' => $this->calculateConfidenceScore($suggestedArticles),
        ];
    }

    /**
     * Record when a user views a suggested article instead of creating a ticket
     */
    public function recordSuccessfulDeflection(
        int $articleId,
        string $originalQuery,
        ?int $userId = null,
        ?int $contactId = null
    ): void {
        $article = KbArticle::find($articleId);
        if (!$article) {
            return;
        }

        // Track the view
        $view = KbArticleView::track(
            $articleId,
            $userId,
            $contactId,
            $originalQuery,
            [
                'company_id' => $article->company_id,
                'deflection_success' => true,
            ]
        );

        // Update article deflection metrics
        $this->updateArticleDeflectionMetrics($article);

        // Clear related caches
        $this->clearDeflectionCache($article->company_id);
    }

    /**
     * Record when a user creates a ticket despite seeing suggested articles
     */
    public function recordFailedDeflection(
        Ticket $ticket,
        array $suggestedArticleIds = [],
        ?string $originalQuery = null
    ): void {
        // Mark any views of suggested articles as leading to tickets
        if (!empty($suggestedArticleIds)) {
            KbArticleView::whereIn('article_id', $suggestedArticleIds)
                ->where('ip_address', request()->ip())
                ->where('created_at', '>=', now()->subHour()) // Within last hour
                ->update(['led_to_ticket' => true]);
        }

        // Update deflection metrics for those articles
        foreach ($suggestedArticleIds as $articleId) {
            $article = KbArticle::find($articleId);
            if ($article) {
                $this->updateArticleDeflectionMetrics($article);
            }
        }

        // Store metadata on the ticket for analysis
        $ticket->update([
            'metadata' => array_merge($ticket->metadata ?? [], [
                'deflection_attempted' => true,
                'suggested_articles' => $suggestedArticleIds,
                'original_search_query' => $originalQuery,
            ])
        ]);

        // Clear caches
        $this->clearDeflectionCache($ticket->company_id);
    }

    /**
     * Get deflection analytics for a company
     */
    public function getDeflectionAnalytics(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return Cache::remember(
            "deflection_analytics:{$companyId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}",
            900, // 15 minutes cache
            function () use ($companyId, $startDate, $endDate) {
                return $this->calculateDeflectionAnalytics($companyId, $startDate, $endDate);
            }
        );
    }

    /**
     * Get top performing articles for deflection
     */
    public function getTopDeflectingArticles(
        int $companyId,
        int $limit = 10,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $startDate = $startDate ?: now()->subMonth();
        $endDate = $endDate ?: now();

        return Cache::remember(
            "top_deflecting_articles:{$companyId}:{$limit}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}",
            1800, // 30 minutes cache
            function () use ($companyId, $limit, $startDate, $endDate) {
                return $this->calculateTopDeflectingArticles($companyId, $limit, $startDate, $endDate);
            }
        );
    }

    /**
     * Get articles that need improvement (low deflection rates)
     */
    public function getArticlesNeedingImprovement(
        int $companyId,
        int $minViews = 10,
        float $maxDeflectionRate = 50.0
    ): Collection {
        return KbArticle::where('company_id', $companyId)
            ->published()
            ->where('views_count', '>=', $minViews)
            ->where(function ($query) use ($maxDeflectionRate) {
                $query->where('deflection_rate', '<', $maxDeflectionRate)
                      ->orWhereNull('deflection_rate');
            })
            ->with(['category', 'feedback' => function ($query) {
                $query->notHelpful()->latest()->limit(5);
            }])
            ->orderBy('views_count', 'desc')
            ->orderBy('deflection_rate', 'asc')
            ->get();
    }

    /**
     * Calculate potential ticket reduction
     */
    public function calculatePotentialTicketReduction(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $analytics = $this->getDeflectionAnalytics($companyId, $startDate, $endDate);
        
        $totalTickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $currentDeflectionRate = $analytics['overall_deflection_rate'];
        
        // Calculate potential improvements
        $potentialScenarios = [
            'improved_content' => 0.75, // 75% deflection with better articles
            'perfect_scenario' => 0.90,  // 90% deflection (theoretical maximum)
        ];

        $scenarios = [];
        foreach ($potentialScenarios as $scenario => $rate) {
            $deflectedTickets = $analytics['total_deflection_attempts'] * ($rate / 100);
            $remainingTickets = $totalTickets - $deflectedTickets + $analytics['tickets_created_after_viewing'];
            $reduction = $totalTickets - $remainingTickets;
            
            $scenarios[$scenario] = [
                'deflection_rate' => $rate * 100,
                'tickets_deflected' => $deflectedTickets,
                'remaining_tickets' => max(0, $remainingTickets),
                'ticket_reduction' => max(0, $reduction),
                'reduction_percentage' => $totalTickets > 0 ? ($reduction / $totalTickets) * 100 : 0,
            ];
        }

        return [
            'current_state' => [
                'total_tickets' => $totalTickets,
                'deflection_rate' => $currentDeflectionRate,
                'tickets_deflected' => $analytics['successful_deflections'],
            ],
            'potential_improvements' => $scenarios,
            'recommendations' => $this->generateImprovementRecommendations($analytics),
        ];
    }

    /**
     * Track deflection attempt
     */
    protected function trackDeflectionAttempt(string $query, Collection $suggestedArticles): void
    {
        // This could be expanded to store deflection attempts in a separate table
        // For now, we'll rely on article views and ticket metadata
        
        foreach ($suggestedArticles as $article) {
            // Pre-create view record to track deflection attempts
            // Will be updated if user actually views the article
        }
    }

    /**
     * Calculate confidence score for suggestions
     */
    protected function calculateConfidenceScore(Collection $articles): float
    {
        if ($articles->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0;
        $count = 0;

        foreach ($articles as $article) {
            $score = 0;
            
            // Base score from views
            $score += min($article->views_count / 100, 10);
            
            // Helpfulness score
            if ($article->helpful_count + $article->not_helpful_count > 0) {
                $helpfulnessRatio = $article->helpful_count / ($article->helpful_count + $article->not_helpful_count);
                $score += $helpfulnessRatio * 20;
            }
            
            // Deflection rate score
            if ($article->deflection_rate !== null) {
                $score += ($article->deflection_rate / 100) * 30;
            }
            
            // Recency score (newer articles get slight boost)
            $daysSinceUpdate = now()->diffInDays($article->updated_at);
            if ($daysSinceUpdate < 30) {
                $score += (30 - $daysSinceUpdate) / 30 * 5;
            }
            
            $totalScore += $score;
            $count++;
        }

        return min(($totalScore / $count) / 65 * 100, 100); // Normalize to 0-100
    }

    /**
     * Update article deflection metrics
     */
    protected function updateArticleDeflectionMetrics(KbArticle $article): void
    {
        $totalViews = $article->views()->count();
        $viewsWithSearch = $article->views()->whereNotNull('search_query')->count();
        $viewsLedToTickets = $article->views()->ledToTicket()->count();
        
        if ($viewsWithSearch > 0) {
            $deflectionRate = (($viewsWithSearch - $viewsLedToTickets) / $viewsWithSearch) * 100;
            $article->update(['deflection_rate' => round($deflectionRate, 2)]);
        }
    }

    /**
     * Calculate deflection analytics
     */
    protected function calculateDeflectionAnalytics(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Get all KB article views in the period
        $views = KbArticleView::where('company_id', $companyId)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->get();

        $totalViews = $views->count();
        $viewsWithSearch = $views->whereNotNull('search_query')->count();
        $viewsLedToTickets = $views->where('led_to_ticket', true)->count();
        $successfulDeflections = $viewsWithSearch - $viewsLedToTickets;

        // Get tickets created in period
        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalTickets = $tickets->count();
        $ticketsWithDeflectionAttempt = $tickets->whereNotNull('metadata.deflection_attempted')->count();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'days' => $startDate->diffInDays($endDate),
            ],
            'kb_metrics' => [
                'total_article_views' => $totalViews,
                'search_initiated_views' => $viewsWithSearch,
                'unique_articles_viewed' => $views->unique('article_id')->count(),
                'average_time_spent' => $views->avg('time_spent_seconds'),
            ],
            'deflection_metrics' => [
                'total_deflection_attempts' => $viewsWithSearch,
                'successful_deflections' => max(0, $successfulDeflections),
                'tickets_created_after_viewing' => $viewsLedToTickets,
                'overall_deflection_rate' => $viewsWithSearch > 0 
                    ? round(($successfulDeflections / $viewsWithSearch) * 100, 2) 
                    : 0,
            ],
            'ticket_metrics' => [
                'total_tickets_created' => $totalTickets,
                'tickets_with_deflection_attempt' => $ticketsWithDeflectionAttempt,
                'deflection_attempt_rate' => $totalTickets > 0 
                    ? round(($ticketsWithDeflectionAttempt / $totalTickets) * 100, 2) 
                    : 0,
            ],
            'estimated_savings' => [
                'tickets_prevented' => $successfulDeflections,
                'estimated_time_saved_hours' => $successfulDeflections * 0.5, // Assume 30min avg resolution
                'estimated_cost_saved' => $successfulDeflections * 25, // Assume $25 avg cost per ticket
            ],
        ];
    }

    /**
     * Calculate top deflecting articles
     */
    protected function calculateTopDeflectingArticles(
        int $companyId,
        int $limit,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return KbArticle::where('company_id', $companyId)
            ->whereHas('views', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('viewed_at', [$startDate, $endDate]);
            })
            ->withCount([
                'views as period_views' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('viewed_at', [$startDate, $endDate]);
                },
                'views as period_deflections' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('viewed_at', [$startDate, $endDate])
                          ->whereNotNull('search_query')
                          ->where('led_to_ticket', false);
                },
                'views as period_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('viewed_at', [$startDate, $endDate])
                          ->where('led_to_ticket', true);
                },
            ])
            ->with(['category'])
            ->having('period_views', '>', 0)
            ->orderByRaw('period_deflections DESC')
            ->orderByRaw('period_views DESC')
            ->limit($limit)
            ->get()
            ->map(function ($article) {
                $article->period_deflection_rate = $article->period_views > 0 
                    ? round(($article->period_deflections / $article->period_views) * 100, 2)
                    : 0;
                return $article;
            });
    }

    /**
     * Generate improvement recommendations
     */
    protected function generateImprovementRecommendations(array $analytics): array
    {
        $recommendations = [];
        
        $deflectionRate = $analytics['deflection_metrics']['overall_deflection_rate'];
        $attemptRate = $analytics['ticket_metrics']['deflection_attempt_rate'];
        
        if ($deflectionRate < 30) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'content_quality',
                'message' => 'Low deflection rate indicates articles may need content improvement or better SEO.',
                'action' => 'Review low-performing articles and update content based on user feedback.',
            ];
        }
        
        if ($attemptRate < 50) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'visibility',
                'message' => 'Many tickets are created without viewing knowledge base articles.',
                'action' => 'Improve article suggestions in ticket creation flow and enhance search functionality.',
            ];
        }
        
        if ($analytics['kb_metrics']['average_time_spent'] < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'engagement',
                'message' => 'Users spend little time reading articles, suggesting content may not be engaging.',
                'action' => 'Improve article formatting, add visuals, and break content into digestible sections.',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Clear deflection-related caches
     */
    protected function clearDeflectionCache(int $companyId): void
    {
        Cache::forget("deflection_analytics:{$companyId}:*");
        Cache::forget("top_deflecting_articles:{$companyId}:*");
    }
}