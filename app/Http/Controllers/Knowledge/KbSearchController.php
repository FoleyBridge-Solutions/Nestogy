<?php

namespace App\Http\Controllers\Knowledge;

use App\Http\Controllers\Controller;
use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbCategory;
use App\Domains\Knowledge\Services\ArticleSearchService;
use App\Domains\Knowledge\Services\KnowledgeBaseService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Knowledge Base Search Controller
 * 
 * Search functionality for both internal and client portal use
 */
class KbSearchController extends Controller
{
    public function __construct(
        protected ArticleSearchService $searchService,
        protected KnowledgeBaseService $knowledgeBaseService
    ) {
        // No auth middleware - some endpoints are public for client portal
    }

    /**
     * Main search interface
     */
    public function index(Request $request): View
    {
        $query = $request->input('q', '');
        $categoryId = $request->input('category_id');
        $tags = $request->input('tags', []);
        
        $results = collect();
        $facets = [];
        $totalResults = 0;
        
        if (!empty($query)) {
            $searchResults = $this->searchService->searchWithFacets($query, [
                'category_id' => $categoryId,
                'tags' => is_array($tags) ? $tags : [$tags],
                'visibility' => $this->getVisibilityOptions(),
                'company_id' => $this->getCompanyId(),
                'limit' => 20,
            ]);
            
            $results = $searchResults['results'];
            $facets = $searchResults['facets'];
            $totalResults = $searchResults['total'];
        }

        $categories = $this->getAvailableCategories();
        $popularArticles = $this->knowledgeBaseService->getPopularArticles(5);
        $recentArticles = $this->knowledgeBaseService->getRecentArticles(5);

        return view('knowledge.search.index', compact(
            'query',
            'results',
            'facets',
            'totalResults',
            'categories',
            'popularArticles',
            'recentArticles'
        ));
    }

    /**
     * AJAX search endpoint
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'suggestions' => [],
                'total' => 0,
            ]);
        }

        $results = $this->searchService->search($query, [
            'visibility' => $this->getVisibilityOptions(),
            'company_id' => $this->getCompanyId(),
            'limit' => $request->input('limit', 10),
        ]);

        $suggestions = $this->searchService->getSuggestions($query, 5);

        return response()->json([
            'results' => $results->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'url' => $this->getArticleUrl($article),
                    'category' => $article->category?->name,
                    'views_count' => $article->views_count,
                    'helpfulness_percentage' => $article->helpfulness_percentage,
                    'updated_at' => $article->updated_at?->format('M j, Y'),
                ];
            }),
            'suggestions' => $suggestions,
            'total' => $results->count(),
        ]);
    }

    /**
     * Autocomplete suggestions endpoint
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->getSuggestions($query, 8);
        
        return response()->json($suggestions);
    }

    /**
     * Advanced search interface
     */
    public function advanced(Request $request): View
    {
        $searchParams = [
            'q' => $request->input('q', ''),
            'category_id' => $request->input('category_id'),
            'tags' => $request->input('tags', []),
            'author_id' => $request->input('author_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'min_views' => $request->input('min_views'),
            'sort_by' => $request->input('sort_by', 'relevance'),
        ];

        $results = collect();
        $totalResults = 0;

        if ($request->hasAny(['q', 'category_id', 'tags', 'author_id'])) {
            $results = $this->performAdvancedSearch($searchParams);
            $totalResults = $results->count();
        }

        $categories = $this->getAvailableCategories();
        $authors = $this->getAvailableAuthors();

        return view('knowledge.search.advanced', compact(
            'searchParams',
            'results',
            'totalResults',
            'categories',
            'authors'
        ));
    }

    /**
     * Get ticket deflection suggestions
     */
    public function deflectionSuggestions(Request $request): JsonResponse
    {
        $subject = $request->input('subject', '');
        $description = $request->input('description', '');
        
        if (strlen($subject) < 3) {
            return response()->json([
                'suggestions' => [],
                'count' => 0,
            ]);
        }

        $query = $subject . ' ' . $description;
        
        $suggestions = $this->knowledgeBaseService->getSuggestedArticles($query, 5);

        return response()->json([
            'suggestions' => $suggestions->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'url' => $this->getArticleUrl($article),
                    'category' => $article->category?->name,
                    'views_count' => $article->views_count,
                    'helpfulness_percentage' => $article->helpfulness_percentage,
                    'deflection_rate' => $article->deflection_rate,
                ];
            }),
            'count' => $suggestions->count(),
            'query' => $query,
        ]);
    }

    /**
     * Browse articles by category
     */
    public function category(KbCategory $category, Request $request): View
    {
        $this->authorize('view', $category);

        $articles = KbArticle::where('category_id', $category->id)
            ->published()
            ->where('company_id', $this->getCompanyId())
            ->with(['author'])
            ->orderBy('views_count', 'desc')
            ->orderBy('title')
            ->paginate(20);

        $subcategories = $category->children()
            ->active()
            ->withCount('publishedArticles')
            ->orderBy('sort_order')
            ->get();

        $popularInCategory = $this->knowledgeBaseService->getPopularArticles(5, $category->id);

        return view('knowledge.search.category', compact(
            'category',
            'articles',
            'subcategories',
            'popularInCategory'
        ));
    }

    /**
     * Browse articles by tag
     */
    public function tag(string $tag, Request $request): View
    {
        $articles = KbArticle::published()
            ->where('company_id', $this->getCompanyId())
            ->whereJsonContains('tags', $tag)
            ->with(['category', 'author'])
            ->orderBy('views_count', 'desc')
            ->paginate(20);

        $relatedTags = $this->getRelatedTags($tag, 10);

        return view('knowledge.search.tag', compact('tag', 'articles', 'relatedTags'));
    }

    /**
     * Perform advanced search with multiple criteria
     */
    protected function performAdvancedSearch(array $params): \Illuminate\Support\Collection
    {
        $builder = KbArticle::published()
            ->where('company_id', $this->getCompanyId())
            ->with(['category', 'author']);

        // Text search
        if (!empty($params['q'])) {
            // Use search service for text matching
            $textResults = $this->searchService->search($params['q'], [
                'visibility' => $this->getVisibilityOptions(),
                'company_id' => $this->getCompanyId(),
                'limit' => 1000, // Get all matches for further filtering
            ]);
            
            $articleIds = $textResults->pluck('id');
            $builder->whereIn('id', $articleIds);
        }

        // Category filter
        if (!empty($params['category_id'])) {
            $builder->where('category_id', $params['category_id']);
        }

        // Tag filter
        if (!empty($params['tags'])) {
            $tags = is_array($params['tags']) ? $params['tags'] : [$params['tags']];
            $builder->where(function ($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Author filter
        if (!empty($params['author_id'])) {
            $builder->where('author_id', $params['author_id']);
        }

        // Date range filter
        if (!empty($params['date_from'])) {
            $builder->where('published_at', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $builder->where('published_at', '<=', $params['date_to']);
        }

        // Minimum views filter
        if (!empty($params['min_views'])) {
            $builder->where('views_count', '>=', $params['min_views']);
        }

        // Sorting
        switch ($params['sort_by']) {
            case 'newest':
                $builder->orderBy('published_at', 'desc');
                break;
            case 'oldest':
                $builder->orderBy('published_at', 'asc');
                break;
            case 'most_viewed':
                $builder->orderBy('views_count', 'desc');
                break;
            case 'most_helpful':
                $builder->orderBy('helpful_count', 'desc');
                break;
            case 'title':
                $builder->orderBy('title', 'asc');
                break;
            default: // relevance
                $builder->orderBy('views_count', 'desc');
                break;
        }

        return $builder->get();
    }

    /**
     * Get visibility options based on user context
     */
    protected function getVisibilityOptions(): array
    {
        if (auth()->check()) {
            // Internal users can see public and internal articles
            return [KbArticle::VISIBILITY_PUBLIC, KbArticle::VISIBILITY_INTERNAL];
        } else {
            // Public/client portal users see only public articles
            return [KbArticle::VISIBILITY_PUBLIC];
        }
    }

    /**
     * Get company ID from context
     */
    protected function getCompanyId(): ?int
    {
        if (auth()->check()) {
            return auth()->user()->company_id;
        }
        
        // For client portal, get from session or request
        return session('client_company_id') ?? request()->input('company_id');
    }

    /**
     * Get available categories
     */
    protected function getAvailableCategories(): \Illuminate\Support\Collection
    {
        return KbCategory::where('company_id', $this->getCompanyId())
            ->active()
            ->withCount('publishedArticles')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available authors
     */
    protected function getAvailableAuthors(): \Illuminate\Support\Collection
    {
        return \App\Models\User::whereHas('kbArticles', function ($query) {
                $query->where('company_id', $this->getCompanyId())
                      ->where('status', KbArticle::STATUS_PUBLISHED);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get related tags
     */
    protected function getRelatedTags(string $currentTag, int $limit = 10): array
    {
        $articles = KbArticle::published()
            ->where('company_id', $this->getCompanyId())
            ->whereJsonContains('tags', $currentTag)
            ->whereNotNull('tags')
            ->get();

        $tagCounts = [];
        foreach ($articles as $article) {
            if ($article->tags) {
                foreach ($article->tags as $tag) {
                    if ($tag !== $currentTag) {
                        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($tagCounts);
        return array_slice($tagCounts, 0, $limit);
    }

    /**
     * Get article URL based on context
     */
    protected function getArticleUrl(KbArticle $article): string
    {
        if (auth()->check()) {
            return route('knowledge.articles.show', $article);
        } else {
            return route('portal.knowledge.show', $article);
        }
    }
}