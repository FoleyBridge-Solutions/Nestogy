<?php

namespace App\Domains\Knowledge\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuoteTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Document Template API Controller
 *
 * Handles API requests for document templates used by the enhanced
 * quote/invoice builder components.
 */
class DocumentTemplateController extends Controller
{
    /**
     * Get list of templates
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = QuoteTemplate::where('company_id', $user->company_id)
            ->where('is_active', true);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('scope', 'like', "%{$search}%");
            });
        }

        // Sort by usage or name
        $sortBy = $request->get('sort_by', 'recent');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'usage':
                $query->orderBy('usage_count', 'desc');
                break;
            default:
                $query->orderBy('updated_at', 'desc');
        }

        $templates = $query->limit(50)->get()->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'scope' => $template->scope,
                'note' => $template->note,
                'terms_conditions' => $template->terms_conditions,
                'discount_type' => $template->discount_type,
                'discount_amount' => $template->discount_amount,
                'items' => $template->items ?? [],
                'usage_count' => $template->usage_count ?? 0,
                'is_favorite' => $template->is_favorite ?? false,
                'type' => $template->type,
                'category_id' => $template->category_id,
                'updated_at' => $template->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Store a new template
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'nullable|string|max:500',
            'note' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.rate' => 'required_with:items|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'type' => 'required|in:quote,invoice',
        ]);

        try {
            $template = QuoteTemplate::create([
                'name' => $request->input('name'),
                'scope' => $request->input('scope'),
                'note' => $request->input('note'),
                'terms_conditions' => $request->input('terms_conditions'),
                'discount_type' => $request->input('discount_type', 'fixed'),
                'discount_amount' => $request->input('discount_amount', 0),
                'items' => $request->input('items', []),
                'type' => $request->input('type'),
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'is_active' => true,
                'usage_count' => 0,
                'is_favorite' => false,
            ]);

            Log::info('Document template created', [
                'template_id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template saved successfully',
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => $template->scope,
                    'note' => $template->note,
                    'terms_conditions' => $template->terms_conditions,
                    'discount_type' => $template->discount_type,
                    'discount_amount' => $template->discount_amount,
                    'items' => $template->items,
                    'type' => $template->type,
                    'usage_count' => $template->usage_count,
                    'is_favorite' => $template->is_favorite,
                    'updated_at' => $template->updated_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Document template creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save template',
            ], 500);
        }
    }

    /**
     * Toggle template favorite status
     */
    public function toggleFavorite(Request $request, QuoteTemplate $template)
    {
        $this->authorize('update', $template);

        try {
            $template->update([
                'is_favorite' => ! $template->is_favorite,
            ]);

            // Increment usage count
            $template->increment('usage_count');

            Log::info('Template favorite toggled', [
                'template_id' => $template->id,
                'is_favorite' => $template->is_favorite,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'is_favorite' => $template->is_favorite,
                'message' => $template->is_favorite ? 'Added to favorites' : 'Removed from favorites',
            ]);

        } catch (\Exception $e) {
            Log::error('Template favorite toggle failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update favorite status',
            ], 500);
        }
    }
}
