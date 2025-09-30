<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbArticleFeedback;
use App\Domains\Knowledge\Models\KbArticleView;
use App\Domains\Knowledge\Models\KbCategory;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Knowledge Base Service
 *
 * Core service for managing knowledge base operations, search, and ticket deflection
 */
class KnowledgeBaseService
{
    protected ArticleSearchService $searchService;

    public function __construct(ArticleSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Create a new KB article
     */
    public function createArticle(array $data): KbArticle
    {
        DB::beginTransaction();
        try {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = KbArticle::generateSlug($data['title']);
            }

            // Auto-generate excerpt if not provided
            if (empty($data['excerpt']) && ! empty($data['content'])) {
                $data['excerpt'] = str()->limit(strip_tags($data['content']), 160);
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = KbArticle::STATUS_DRAFT;
            }

            // Set published_at if publishing
            if ($data['status'] === KbArticle::STATUS_PUBLISHED && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $article = KbArticle::create($data);

            // Handle related articles
            if (! empty($data['related_article_ids'])) {
                $article->relatedArticles()->sync($data['related_article_ids']);
            }

            // Handle client restrictions
            if (! empty($data['client_ids'])) {
                $article->clients()->sync($data['client_ids']);
            }

            // Clear cache
            $this->clearCache();

            DB::commit();

            return $article->fresh(['category', 'author']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create KB article', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing KB article
     */
    public function updateArticle(KbArticle $article, array $data): KbArticle
    {
        DB::beginTransaction();
        try {
            // Track if content changed for versioning
            $contentChanged = isset($data['content']) && $data['content'] !== $article->content;

            // Update published_at if changing to published
            if (isset($data['status']) &&
                $data['status'] === KbArticle::STATUS_PUBLISHED &&
                $article->status !== KbArticle::STATUS_PUBLISHED) {
                $data['published_at'] = now();
            }

            $article->update($data);

            // Handle related articles
            if (isset($data['related_article_ids'])) {
                $article->relatedArticles()->sync($data['related_article_ids']);
            }

            // Handle client restrictions
            if (isset($data['client_ids'])) {
                $article->clients()->sync($data['client_ids']);
            }

            // Clear cache
            $this->clearCache();

            DB::commit();

            return $article->fresh(['category', 'author']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update KB article', [
                'error' => $e->getMessage(),
                'article_id' => $article->id,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Get suggested articles for ticket deflection
     */
    public function getSuggestedArticles(string $query, int $limit = 5): Collection
    {
        return Cache::remember(
            'kb_suggestions:'.md5($query).":{$limit}",
            300, // 5 minutes cache
            function () use ($query, $limit) {
                return $this->searchService->search($query, [
                    'limit' => $limit,
                    'visibility' => [KbArticle::VISIBILITY_PUBLIC, KbArticle::VISIBILITY_CLIENT],
                    'status' => KbArticle::STATUS_PUBLISHED,
                ]);
            }
        );
    }

    /**
     * Convert a resolved ticket to a KB article
     */
    public function createArticleFromTicket(Ticket $ticket, array $additionalData = []): KbArticle
    {
        $data = array_merge([
            'company_id' => $ticket->company_id,
            'author_id' => auth()->id(),
            'title' => $ticket->subject,
            'content' => $this->formatTicketContentForArticle($ticket),
            'status' => KbArticle::STATUS_DRAFT,
            'visibility' => KbArticle::VISIBILITY_INTERNAL,
            'tags' => $this->extractTagsFromTicket($ticket),
            'metadata' => [
                'source' => 'ticket',
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ],
        ], $additionalData);

        return $this->createArticle($data);
    }

    /**
     * Track article view and check for ticket deflection
     */
    public function trackView(
        KbArticle $article,
        ?string $searchQuery = null,
        ?int $userId = null,
        ?int $contactId = null
    ): KbArticleView {
        return KbArticleView::track(
            $article->id,
            $userId,
            $contactId,
            $searchQuery,
            ['company_id' => $article->company_id]
        );
    }

    /**
     * Submit feedback for an article
     */
    public function submitFeedback(
        KbArticle $article,
        bool $isHelpful,
        ?string $feedbackText = null,
        ?int $userId = null,
        ?int $contactId = null
    ): KbArticleFeedback {
        $feedback = KbArticleFeedback::submit(
            $article->id,
            $isHelpful,
            $feedbackText,
            $userId,
            $contactId
        );

        // Update deflection rate if this prevented a ticket
        if ($isHelpful) {
            $this->updateDeflectionRate($article);
        }

        return $feedback;
    }

    /**
     * Get popular articles
     */
    public function getPopularArticles(int $limit = 10, ?int $categoryId = null): Collection
    {
        return Cache::remember(
            "kb_popular:{$limit}:{$categoryId}",
            3600, // 1 hour cache
            function () use ($limit, $categoryId) {
                $query = KbArticle::published()
                    ->with(['category', 'author'])
                    ->popular($limit);

                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }

                return $query->get();
            }
        );
    }

    /**
     * Get recent articles
     */
    public function getRecentArticles(int $limit = 10, ?int $categoryId = null): Collection
    {
        return Cache::remember(
            "kb_recent:{$limit}:{$categoryId}",
            900, // 15 minutes cache
            function () use ($limit, $categoryId) {
                $query = KbArticle::published()
                    ->with(['category', 'author'])
                    ->recent($limit);

                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }

                return $query->get();
            }
        );
    }

    /**
     * Get article categories tree
     */
    public function getCategoriesTree(int $companyId): Collection
    {
        return Cache::remember(
            "kb_categories_tree:{$companyId}",
            3600, // 1 hour cache
            function () use ($companyId) {
                return KbCategory::where('company_id', $companyId)
                    ->active()
                    ->root()
                    ->with(['children' => function ($query) {
                        $query->active()->orderBy('sort_order');
                    }])
                    ->withCount('publishedArticles')
                    ->orderBy('sort_order')
                    ->get();
            }
        );
    }

    /**
     * Calculate and update deflection rate for an article
     */
    public function updateDeflectionRate(KbArticle $article): void
    {
        $totalViews = $article->views()->count();
        $viewsThatLedToTickets = $article->views()->ledToTicket()->count();

        if ($totalViews > 0) {
            $deflectionRate = (($totalViews - $viewsThatLedToTickets) / $totalViews) * 100;
            $article->update(['deflection_rate' => round($deflectionRate, 2)]);
        }
    }

    /**
     * Get deflection metrics for a date range
     */
    public function getDeflectionMetrics(
        int $companyId,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $views = KbArticleView::where('company_id', $companyId)
            ->dateRange($startDate, $endDate)
            ->get();

        $totalViews = $views->count();
        $viewsWithSearch = $views->whereNotNull('search_query')->count();
        $viewsThatLedToTickets = $views->where('led_to_ticket', true)->count();
        $deflectedTickets = $viewsWithSearch - $viewsThatLedToTickets;

        return [
            'total_views' => $totalViews,
            'views_with_search' => $viewsWithSearch,
            'tickets_created' => $viewsThatLedToTickets,
            'tickets_deflected' => max(0, $deflectedTickets),
            'deflection_rate' => $viewsWithSearch > 0
                ? round(($deflectedTickets / $viewsWithSearch) * 100, 2)
                : 0,
            'top_deflecting_articles' => $this->getTopDeflectingArticles($companyId, 5),
        ];
    }

    /**
     * Get top deflecting articles
     */
    protected function getTopDeflectingArticles(int $companyId, int $limit = 5): Collection
    {
        return KbArticle::where('company_id', $companyId)
            ->published()
            ->orderBy('deflection_rate', 'desc')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get(['id', 'title', 'deflection_rate', 'views_count']);
    }

    /**
     * Format ticket content for KB article
     */
    protected function formatTicketContentForArticle(Ticket $ticket): string
    {
        $content = "<h2>Issue Description</h2>\n";
        $content .= '<p>'.nl2br(e($ticket->description))."</p>\n\n";

        $content .= "<h2>Resolution</h2>\n";
        $content .= '<p>'.nl2br(e($ticket->resolution ?? 'Resolution details to be added.'))."</p>\n\n";

        if ($ticket->notes) {
            $content .= "<h2>Additional Notes</h2>\n";
            $content .= '<p>'.nl2br(e($ticket->notes))."</p>\n";
        }

        return $content;
    }

    /**
     * Extract tags from ticket
     */
    protected function extractTagsFromTicket(Ticket $ticket): array
    {
        $tags = [];

        // Add category as tag
        if ($ticket->category) {
            $tags[] = str()->slug($ticket->category);
        }

        // Add priority as tag
        if ($ticket->priority) {
            $tags[] = 'priority-'.$ticket->priority;
        }

        // Extract keywords from subject and description
        $text = $ticket->subject.' '.$ticket->description;
        $keywords = $this->extractKeywords($text, 5);
        $tags = array_merge($tags, $keywords);

        return array_unique($tags);
    }

    /**
     * Simple keyword extraction
     */
    protected function extractKeywords(string $text, int $limit = 5): array
    {
        // Remove HTML tags and special characters
        $text = strip_tags($text);
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);

        // Convert to lowercase and split into words
        $words = str_word_count(strtolower($text), 1);

        // Remove common stop words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'shall'];
        $words = array_diff($words, $stopWords);

        // Count word frequency
        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        // Return top keywords
        return array_slice(array_keys($wordCounts), 0, $limit);
    }

    /**
     * Clear knowledge base cache
     */
    protected function clearCache(): void
    {
        Cache::tags(['knowledge_base'])->flush();

        // Also clear specific cache keys
        Cache::forget('kb_popular:*');
        Cache::forget('kb_recent:*');
        Cache::forget('kb_categories_tree:*');
        Cache::forget('kb_suggestions:*');
    }
}
