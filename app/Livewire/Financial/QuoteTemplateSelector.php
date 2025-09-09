<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\QuoteTemplate;
use Illuminate\Support\Facades\Auth;

class QuoteTemplateSelector extends Component
{
    public $templates = [];
    public $filteredTemplates = [];
    public $selectedTemplate = null;
    public $searchQuery = '';
    public $selectedCategory = '';
    public $categories = [];
    public $showPreview = false;
    public $previewTemplate = null;

    protected $listeners = [
        'refreshTemplates' => 'loadTemplates'
    ];

    public function mount($selectedTemplate = null)
    {
        $this->selectedTemplate = $selectedTemplate;
        $this->loadTemplates();
        $this->loadCategories();
        $this->filterTemplates();
    }

    protected function loadTemplates()
    {
        $this->templates = QuoteTemplate::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->with('category')
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'category' => $template->category->name ?? 'Uncategorized',
                    'category_id' => $template->category_id,
                    'usage_count' => $template->usage_count ?? 0,
                    'items_count' => $template->items ? count($template->items) : 0,
                    'discount_type' => $template->discount_type ?? 'fixed',
                    'discount_amount' => $template->discount_amount ?? 0,
                    'scope' => $template->scope,
                    'note' => $template->note,
                    'terms_conditions' => $template->terms_conditions,
                    'items' => $template->items ?? [],
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at
                ];
            })
            ->toArray();
    }

    protected function loadCategories()
    {
        $this->categories = QuoteTemplate::where('company_id', auth()->user()->company_id)
            ->whereNotNull('category_id')
            ->with('category')
            ->get()
            ->pluck('category.name', 'category_id')
            ->unique()
            ->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->filterTemplates();
    }

    public function updatedSelectedCategory()
    {
        $this->filterTemplates();
    }

    protected function filterTemplates()
    {
        $templates = collect($this->templates);

        // Filter by search query
        if (!empty($this->searchQuery)) {
            $templates = $templates->filter(function ($template) {
                return str_contains(strtolower($template['name']), strtolower($this->searchQuery)) ||
                       str_contains(strtolower($template['description'] ?? ''), strtolower($this->searchQuery)) ||
                       str_contains(strtolower($template['category']), strtolower($this->searchQuery));
            });
        }

        // Filter by category
        if (!empty($this->selectedCategory)) {
            $templates = $templates->filter(function ($template) {
                return $template['category_id'] == $this->selectedCategory;
            });
        }

        $this->filteredTemplates = $templates->values()->toArray();
    }

    public function selectTemplate($templateId)
    {
        $template = collect($this->templates)->firstWhere('id', $templateId);
        
        if ($template) {
            $this->selectedTemplate = $template;
            
            // Increment usage count
            QuoteTemplate::where('id', $templateId)->increment('usage_count');
            
            // Dispatch event to parent component
            $this->dispatch('templateSelected', $template);
        }
    }

    public function clearSelection()
    {
        $this->selectedTemplate = null;
        $this->dispatch('templateSelected', null);
    }

    public function showPreview($templateId)
    {
        $template = collect($this->templates)->firstWhere('id', $templateId);
        if ($template) {
            $this->previewTemplate = $template;
            $this->showPreview = true;
        }
    }

    public function hidePreview()
    {
        $this->showPreview = false;
        $this->previewTemplate = null;
    }

    public function clearFilters()
    {
        $this->searchQuery = '';
        $this->selectedCategory = '';
        $this->filterTemplates();
    }

    // === COMPUTED PROPERTIES ===
    public function getHasTemplatesProperty()
    {
        return count($this->filteredTemplates) > 0;
    }

    public function getHasSelectedProperty()
    {
        return $this->selectedTemplate !== null;
    }

    public function getPopularTemplatesProperty()
    {
        return collect($this->templates)
            ->sortByDesc('usage_count')
            ->take(3)
            ->values()
            ->toArray();
    }

    public function getRecentTemplatesProperty()
    {
        return collect($this->templates)
            ->sortByDesc('updated_at')
            ->take(3)
            ->values()
            ->toArray();
    }

    public function getCategorizedTemplatesProperty()
    {
        return collect($this->filteredTemplates)
            ->groupBy('category')
            ->map(function ($templates, $category) {
                return [
                    'name' => $category,
                    'templates' => $templates->values()->toArray(),
                    'count' => $templates->count()
                ];
            })
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.financial.quote-template-selector');
    }
}