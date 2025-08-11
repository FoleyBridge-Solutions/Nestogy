<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbCategory;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Article Search Service
 * 
 * Advanced search functionality for knowledge base articles with relevance scoring
 */
class ArticleSearchService
{
    /**
     * Search knowledge base articles
     */
    public function search(string $query, array $options = []): Collection
    {
        $defaults = [
            'limit' => 20,
            'visibility' => null,
            'status' => KbArticle::STATUS_PUBLISHED,
            'category_id' => null,
            'tags' => [],
            'company_id' => auth()->user()?->company_id,
            'use_cache' => true,
        ];

        $options = array_merge($defaults, $options);
        
        $cacheKey = $this->generateCacheKey($query, $options);
        
        if ($options['use_cache']) {
            return Cache::remember($cacheKey, 300, function () use ($query, $options) {
                return $this->performSearch($query, $options);
            });
        }
        
        return $this->performSearch($query, $options);
    }

    /**
     * Get search suggestions based on partial query
     */
    public function getSuggestions(string $partialQuery, int $limit = 5): array
    {
        if (strlen($partialQuery) < 2) {
            return [];
        }

        return Cache::remember(
            "kb_suggestions:" . md5($partialQuery) . ":{$limit}",
            600, // 10 minutes cache
            function () use ($partialQuery, $limit) {
                return $this->generateSuggestions($partialQuery, $limit);
            }
        );
    }

    /**
     * Search with facets (categories, tags, etc.)
     */
    public function searchWithFacets(string $query, array $options = []): array
    {
        $results = $this->search($query, $options);
        
        $facets = [
            'categories' => $this->getCategoryFacets($query, $options),
            'tags' => $this->getTagFacets($query, $options),
            'authors' => $this->getAuthorFacets($query, $options),
        ];

        return [
            'results' => $results,
            'facets' => $facets,
            'total' => $results->count(),
            'query' => $query,
        ];
    }

    /**
     * Find similar articles based on content and tags
     */
    public function findSimilar(KbArticle $article, int $limit = 5): Collection
    {
        return Cache::remember(
            "kb_similar:{$article->id}:{$limit}",
            1800, // 30 minutes cache
            function () use ($article, $limit) {
                $similarArticles = collect();
                
                // Find articles with similar tags
                if ($article->tags) {
                    $tagSimilar = $this->findByTags($article->tags, $article->id, $limit);
                    $similarArticles = $similarArticles->merge($tagSimilar);
                }
                
                // Find articles in same category
                if ($article->category_id) {
                    $categorySimilar = $this->findByCategory($article->category_id, $article->id, $limit);
                    $similarArticles = $similarArticles->merge($categorySimilar);
                }
                
                // Find articles with similar content
                $contentSimilar = $this->findByContent($article, $limit);
                $similarArticles = $similarArticles->merge($contentSimilar);
                
                // Remove duplicates and score by similarity
                return $this->rankSimilarArticles($similarArticles, $article)
                    ->take($limit);
            }
        );
    }

    /**
     * Perform the actual search
     */
    protected function performSearch(string $query, array $options): Collection
    {
        // Use Laravel Scout if available and configured
        if ($this->hasScoutConfigured()) {
            return $this->searchWithScout($query, $options);
        }
        
        // Fallback to database full-text search
        return $this->searchWithDatabase($query, $options);
    }

    /**
     * Search using Laravel Scout (if configured)
     */
    protected function searchWithScout(string $query, array $options): Collection
    {
        $search = KbArticle::search($query);
        
        if ($options['company_id']) {
            $search->where('company_id', $options['company_id']);
        }
        
        if ($options['status']) {
            $search->where('status', $options['status']);
        }
        
        if ($options['visibility']) {
            $visibilities = is_array($options['visibility']) 
                ? $options['visibility'] 
                : [$options['visibility']];
            $search->whereIn('visibility', $visibilities);
        }
        
        return $search->take($options['limit'])->get()->load(['category', 'author']);
    }

    /**
     * Search using database queries
     */
    protected function searchWithDatabase(string $query, array $options): Collection
    {
        $builder = KbArticle::query()
            ->with(['category', 'author'])
            ->where('status', $options['status']);

        if ($options['company_id']) {
            $builder->where('company_id', $options['company_id']);
        }

        if ($options['visibility']) {
            $visibilities = is_array($options['visibility']) 
                ? $options['visibility'] 
                : [$options['visibility']];
            $builder->whereIn('visibility', $visibilities);
        }

        if ($options['category_id']) {
            $builder->where('category_id', $options['category_id']);
        }

        // Full-text search if MySQL supports it
        if ($this->supportsFullText()) {
            $builder->whereRaw(
                "MATCH(title, content, excerpt) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$query]
            );
            $builder->selectRaw(
                "*, MATCH(title, content, excerpt) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score",
                [$query]
            );
            $builder->orderBy('relevance_score', 'desc');
        } else {
            // Fallback to LIKE searches
            $terms = explode(' ', $query);
            $builder->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('title', 'LIKE', "%{$term}%")
                      ->orWhere('content', 'LIKE', "%{$term}%")
                      ->orWhere('excerpt', 'LIKE', "%{$term}%");
                }
            });
            
            // Simple relevance scoring
            $builder->selectRaw("
                *, 
                (
                    CASE WHEN title LIKE '%{$query}%' THEN 10 ELSE 0 END +
                    CASE WHEN excerpt LIKE '%{$query}%' THEN 5 ELSE 0 END +
                    CASE WHEN content LIKE '%{$query}%' THEN 1 ELSE 0 END
                ) as relevance_score
            ");
            $builder->orderBy('relevance_score', 'desc');
        }

        // Tag filtering
        if (!empty($options['tags'])) {
            $builder->where(function ($q) use ($options) {
                foreach ($options['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $builder->orderBy('views_count', 'desc')
               ->orderBy('helpful_count', 'desc');

        return $builder->limit($options['limit'])->get();
    }

    /**
     * Generate search suggestions
     */
    protected function generateSuggestions(string $partialQuery, int $limit): array
    {
        $suggestions = [];
        
        // Title-based suggestions
        $titleSuggestions = KbArticle::published()
            ->where('title', 'LIKE', "%{$partialQuery}%")
            ->pluck('title')
            ->take($limit)
            ->toArray();
        
        $suggestions = array_merge($suggestions, $titleSuggestions);
        
        // Tag-based suggestions
        $tagSuggestions = KbArticle::published()
            ->whereJsonContains('tags', $partialQuery)
            ->pluck('tags')
            ->flatten()
            ->filter(function ($tag) use ($partialQuery) {
                return stripos($tag, $partialQuery) !== false;
            })
            ->unique()
            ->take($limit - count($suggestions))
            ->toArray();
        
        $suggestions = array_merge($suggestions, $tagSuggestions);
        
        // Category-based suggestions
        $categorySuggestions = KbCategory::active()
            ->where('name', 'LIKE', "%{$partialQuery}%")
            ->pluck('name')
            ->take($limit - count($suggestions))
            ->toArray();
        
        $suggestions = array_merge($suggestions, $categorySuggestions);
        
        return array_slice(array_unique($suggestions), 0, $limit);
    }

    /**
     * Get category facets for search results
     */
    protected function getCategoryFacets(string $query, array $options): array
    {
        $baseQuery = $this->getBaseSearchQuery($query, $options);
        
        return $baseQuery->join('kb_categories', 'kb_articles.category_id', '=', 'kb_categories.id')
            ->select('kb_categories.id', 'kb_categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('kb_categories.id', 'kb_categories.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get tag facets for search results
     */
    protected function getTagFacets(string $query, array $options): array
    {
        $baseQuery = $this->getBaseSearchQuery($query, $options);
        
        $articles = $baseQuery->whereNotNull('tags')->get();
        $tagCounts = [];
        
        foreach ($articles as $article) {
            if ($article->tags) {
                foreach ($article->tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }
        
        arsort($tagCounts);
        
        return array_slice($tagCounts, 0, 10);
    }

    /**
     * Get author facets for search results
     */
    protected function getAuthorFacets(string $query, array $options): array
    {
        $baseQuery = $this->getBaseSearchQuery($query, $options);
        
        return $baseQuery->join('users', 'kb_articles.author_id', '=', 'users.id')
            ->select('users.id', 'users.name', DB::raw('COUNT(*) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Find articles by tags
     */
    protected function findByTags(array $tags, int $excludeId, int $limit): Collection
    {
        $query = KbArticle::published()
            ->where('id', '!=', $excludeId);
        
        $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
        
        return $query->limit($limit)->get();
    }

    /**
     * Find articles by category
     */
    protected function findByCategory(int $categoryId, int $excludeId, int $limit): Collection
    {
        return KbArticle::published()
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->limit($limit)
            ->get();
    }

    /**
     * Find articles with similar content
     */
    protected function findByContent(KbArticle $article, int $limit): Collection
    {
        // Extract key terms from the article content
        $terms = $this->extractKeyTerms($article->title . ' ' . strip_tags($article->content));
        
        if (empty($terms)) {
            return collect();
        }
        
        $query = KbArticle::published()
            ->where('id', '!=', $article->id);
        
        $query->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere('title', 'LIKE', "%{$term}%")
                  ->orWhere('content', 'LIKE', "%{$term}%");
            }
        });
        
        return $query->limit($limit)->get();
    }

    /**
     * Rank similar articles by similarity score
     */
    protected function rankSimilarArticles(Collection $articles, KbArticle $baseArticle): Collection
    {
        return $articles->unique('id')
            ->map(function ($article) use ($baseArticle) {
                $score = 0;
                
                // Tag similarity
                if ($baseArticle->tags && $article->tags) {
                    $commonTags = array_intersect($baseArticle->tags, $article->tags);
                    $score += count($commonTags) * 3;
                }
                
                // Category similarity
                if ($baseArticle->category_id === $article->category_id) {
                    $score += 5;
                }
                
                // View count similarity (popularity)
                if ($article->views_count > 0) {
                    $score += min($article->views_count / 100, 2);
                }
                
                // Helpfulness similarity
                if ($article->helpful_count > 0) {
                    $score += min($article->helpful_count / 10, 2);
                }
                
                $article->similarity_score = $score;
                return $article;
            })
            ->sortByDesc('similarity_score');
    }

    /**
     * Extract key terms from text
     */
    protected function extractKeyTerms(string $text, int $limit = 5): array
    {
        $text = strtolower(strip_tags($text));
        $words = str_word_count($text, 1);
        
        // Remove common stop words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'shall', 'with', 'from', 'they', 'them', 'their', 'this', 'that', 'these', 'those'];
        $words = array_diff($words, $stopWords);
        
        // Filter words with minimum length
        $words = array_filter($words, function ($word) {
            return strlen($word) >= 3;
        });
        
        // Count frequencies
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        
        return array_slice(array_keys($wordCounts), 0, $limit);
    }

    /**
     * Get base search query builder
     */
    protected function getBaseSearchQuery(string $query, array $options): Builder
    {
        $builder = KbArticle::query()->where('status', $options['status']);

        if ($options['company_id']) {
            $builder->where('company_id', $options['company_id']);
        }

        if ($options['visibility']) {
            $visibilities = is_array($options['visibility']) 
                ? $options['visibility'] 
                : [$options['visibility']];
            $builder->whereIn('visibility', $visibilities);
        }

        return $builder;
    }

    /**
     * Generate cache key for search
     */
    protected function generateCacheKey(string $query, array $options): string
    {
        return 'kb_search:' . md5($query . serialize($options));
    }

    /**
     * Check if Laravel Scout is configured
     */
    protected function hasScoutConfigured(): bool
    {
        return class_exists('\Laravel\Scout\Searchable') && 
               config('scout.driver') !== null &&
               in_array(\Laravel\Scout\Searchable::class, class_uses_recursive(KbArticle::class));
    }

    /**
     * Check if database supports full-text search
     */
    protected function supportsFullText(): bool
    {
        return DB::connection()->getDriverName() === 'mysql' &&
               version_compare(DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION), '5.6.0', '>=');
    }
}