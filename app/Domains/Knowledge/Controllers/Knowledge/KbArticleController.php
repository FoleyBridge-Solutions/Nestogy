<?php

namespace App\Domains\Knowledge\Controllers\Knowledge;

use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbCategory;
use App\Domains\Knowledge\Services\ArticleSearchService;
use App\Domains\Knowledge\Services\KnowledgeBaseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Knowledge Base Article Controller
 *
 * CRUD operations and management for knowledge base articles
 */
class KbArticleController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBaseService,
        protected ArticleSearchService $searchService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:kb_manage')->except(['show']);
        $this->middleware('permission:kb_view')->only(['show']);
    }

    /**
     * Display a listing of articles
     */
    public function index(Request $request): View
    {
        $query = KbArticle::with(['category', 'author'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('search')) {
            $results = $this->searchService->search($request->search, [
                'company_id' => auth()->user()->company_id,
                'status' => null, // Allow all statuses for management
            ]);
            $articles = $results;
        } else {
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('visibility')) {
                $query->where('visibility', $request->visibility);
            }

            $articles = $query->orderBy('updated_at', 'desc')
                ->paginate(20)
                ->withQueryString();
        }

        $categories = KbCategory::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => KbArticle::where('company_id', auth()->user()->company_id)->count(),
            'published' => KbArticle::where('company_id', auth()->user()->company_id)
                ->where('status', KbArticle::STATUS_PUBLISHED)->count(),
            'draft' => KbArticle::where('company_id', auth()->user()->company_id)
                ->where('status', KbArticle::STATUS_DRAFT)->count(),
        ];

        return view('knowledge.articles.index', compact('articles', 'categories', 'stats'));
    }

    /**
     * Show the form for creating a new article
     */
    public function create(Request $request): View
    {
        $categories = KbCategory::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        // Pre-fill from ticket if provided
        $ticketData = null;
        if ($request->filled('from_ticket')) {
            $ticket = \App\Domains\Ticket\Models\Ticket::where('company_id', auth()->user()->company_id)
                ->findOrFail($request->from_ticket);

            $ticketData = [
                'title' => $ticket->subject,
                'content' => $this->formatTicketForArticle($ticket),
                'tags' => $this->extractTagsFromTicket($ticket),
            ];
        }

        return view('knowledge.articles.create', compact('categories', 'ticketData'));
    }

    /**
     * Store a newly created article
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|exists:kb_categories,id',
            'status' => 'required|in:'.implode(',', [
                KbArticle::STATUS_DRAFT,
                KbArticle::STATUS_PUBLISHED,
                KbArticle::STATUS_UNDER_REVIEW,
            ]),
            'visibility' => 'required|in:'.implode(',', [
                KbArticle::VISIBILITY_PUBLIC,
                KbArticle::VISIBILITY_INTERNAL,
                KbArticle::VISIBILITY_CLIENT,
            ]),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'related_article_ids' => 'nullable|array',
            'related_article_ids.*' => 'exists:kb_articles,id',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $validatedData['company_id'] = auth()->user()->company_id;
        $validatedData['author_id'] = auth()->id();

        try {
            $article = $this->knowledgeBaseService->createArticle($validatedData);

            return redirect()
                ->route('knowledge.articles.show', $article)
                ->with('success', 'Article created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create article: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified article
     */
    public function show(KbArticle $article): View
    {
        $this->authorize('view', $article);

        // Track view
        $this->knowledgeBaseService->trackView(
            $article,
            request()->query('q'), // Search query if came from search
            auth()->id()
        );

        $article->load(['category', 'author', 'relatedArticles' => function ($query) {
            $query->published()->limit(5);
        }]);

        // Get similar articles
        $similarArticles = $this->searchService->findSimilar($article, 5);

        // Get feedback summary
        $feedbackSummary = \App\Domains\Knowledge\Models\KbArticleFeedback::getSummary($article->id);

        return view('knowledge.articles.show', compact('article', 'similarArticles', 'feedbackSummary'));
    }

    /**
     * Show the form for editing the specified article
     */
    public function edit(KbArticle $article): View
    {
        $this->authorize('update', $article);

        $categories = KbCategory::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        $relatedArticles = $article->relatedArticles;
        $assignedClients = $article->clients;

        return view('knowledge.articles.edit', compact('article', 'categories', 'relatedArticles', 'assignedClients'));
    }

    /**
     * Update the specified article
     */
    public function update(Request $request, KbArticle $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|exists:kb_categories,id',
            'status' => 'required|in:'.implode(',', [
                KbArticle::STATUS_DRAFT,
                KbArticle::STATUS_PUBLISHED,
                KbArticle::STATUS_ARCHIVED,
                KbArticle::STATUS_UNDER_REVIEW,
            ]),
            'visibility' => 'required|in:'.implode(',', [
                KbArticle::VISIBILITY_PUBLIC,
                KbArticle::VISIBILITY_INTERNAL,
                KbArticle::VISIBILITY_CLIENT,
            ]),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'related_article_ids' => 'nullable|array',
            'related_article_ids.*' => 'exists:kb_articles,id',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        try {
            $article = $this->knowledgeBaseService->updateArticle($article, $validatedData);

            return redirect()
                ->route('knowledge.articles.show', $article)
                ->with('success', 'Article updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update article: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified article
     */
    public function destroy(KbArticle $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        try {
            $article->delete();

            return redirect()
                ->route('knowledge.articles.index')
                ->with('success', 'Article deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete article: '.$e->getMessage()]);
        }
    }

    /**
     * Submit feedback for an article
     */
    public function feedback(Request $request, KbArticle $article): JsonResponse
    {
        $validatedData = $request->validate([
            'is_helpful' => 'required|boolean',
            'feedback_text' => 'nullable|string|max:1000',
        ]);

        try {
            $feedback = $this->knowledgeBaseService->submitFeedback(
                $article,
                $validatedData['is_helpful'],
                $validatedData['feedback_text'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'feedback' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback.',
            ], 500);
        }
    }

    /**
     * Get article suggestions (AJAX endpoint)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->knowledgeBaseService->getSuggestedArticles($query, 5);

        return response()->json([
            'articles' => $suggestions->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'url' => route('knowledge.articles.show', $article),
                    'category' => $article->category?->name,
                    'views_count' => $article->views_count,
                    'helpfulness_percentage' => $article->helpfulness_percentage,
                ];
            }),
            'count' => $suggestions->count(),
        ]);
    }

    /**
     * Bulk actions on articles
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'action' => 'required|in:publish,unpublish,archive,delete',
            'article_ids' => 'required|array|min:1',
            'article_ids.*' => 'exists:kb_articles,id',
        ]);

        $articles = KbArticle::where('company_id', auth()->user()->company_id)
            ->whereIn('id', $validatedData['article_ids'])
            ->get();

        $count = 0;
        foreach ($articles as $article) {
            if (! $this->authorize('update', $article, false)) {
                continue;
            }

            switch ($validatedData['action']) {
                case 'publish':
                    $article->update([
                        'status' => KbArticle::STATUS_PUBLISHED,
                        'published_at' => $article->published_at ?? now(),
                    ]);
                    break;
                case 'unpublish':
                    $article->update(['status' => KbArticle::STATUS_DRAFT]);
                    break;
                case 'archive':
                    $article->update(['status' => KbArticle::STATUS_ARCHIVED]);
                    break;
                case 'delete':
                    if ($this->authorize('delete', $article, false)) {
                        $article->delete();
                    }
                    break;
            }
            $count++;
        }

        $action = ucfirst($validatedData['action']);

        return back()->with('success', "{$action}ed {$count} articles successfully.");
    }

    /**
     * Format ticket content for article creation
     */
    protected function formatTicketForArticle(\App\Domains\Ticket\Models\Ticket $ticket): string
    {
        $content = "<h2>Issue Description</h2>\n";
        $content .= '<p>'.nl2br(e($ticket->description))."</p>\n\n";

        $content .= "<h2>Resolution</h2>\n";
        $content .= "<p>Add the resolution steps here...</p>\n\n";

        if ($ticket->notes) {
            $content .= "<h2>Additional Notes</h2>\n";
            $content .= '<p>'.nl2br(e($ticket->notes))."</p>\n";
        }

        return $content;
    }

    /**
     * Extract tags from ticket for article creation
     */
    protected function extractTagsFromTicket(\App\Domains\Ticket\Models\Ticket $ticket): array
    {
        $tags = [];

        if ($ticket->category) {
            $tags[] = str()->slug($ticket->category);
        }

        if ($ticket->priority) {
            $tags[] = 'priority-'.$ticket->priority;
        }

        return $tags;
    }
}
