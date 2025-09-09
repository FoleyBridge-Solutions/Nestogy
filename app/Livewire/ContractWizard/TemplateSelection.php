<?php

namespace App\Livewire\ContractWizard;

use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractTemplateService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class TemplateSelection extends Component
{
    public array $templates = [];
    public array $filteredTemplates = [];
    public ?array $selectedTemplate = null;
    public string $categoryFilter = '';
    public string $billingModelFilter = '';
    public string $searchQuery = '';

    protected ContractTemplateService $templateService;

    // Dynamic label mappings built from service config
    protected array $categoryLabels = [];
    protected array $billingModelLabels = [];
    
    // Flag to prevent duplicate events during initial hydration
    private bool $isInitializing = true;

    public function mount(ContractTemplateService $templateService, $selectedTemplate = null)
    {
        $this->templateService = $templateService;
        
        // Handle incoming selectedTemplate param from parent
        if ($selectedTemplate && isset($selectedTemplate['id'])) {
            $this->selectedTemplate = $selectedTemplate;
        }
        
        $this->buildLabelMaps();
        $this->loadTemplates();
        $this->filterTemplates();
        
        // If a selectedTemplate was passed with an ID, sync UI state after loading templates
        if ($selectedTemplate && isset($selectedTemplate['id']) && $selectedTemplate['id'] !== null) {
            $template = collect($this->templates)->firstWhere('id', $selectedTemplate['id']);
            if ($template) {
                $this->selectedTemplate = $template;
                // Mark initialization as complete before emitting to allow the event
                $this->isInitializing = false;
                // Emit the resolved template to keep parent state in sync
                $this->dispatch('templateSelected', template: $template)->to(\App\Livewire\ContractWizard::class);
                return; // Exit early to avoid duplicate initialization completion
            }
        }
        
        // Mark initialization as complete
        $this->isInitializing = false;
    }

    private function buildLabelMaps()
    {
        // Build category labels from service config - support associative arrays
        $availableCategories = $this->templateService->getAvailableCategories() ?? [];
        // Guard against empty/null responses
        $availableCategories = is_array($availableCategories) ? $availableCategories : [];
        
        if ($this->isAssociativeArray($availableCategories)) {
            // Use key as slug and value as label for associative arrays
            foreach ($availableCategories as $slug => $label) {
                $this->categoryLabels[$slug] = is_string($label) ? $label : $this->prettifySlug($slug);
            }
        } else {
            // Fallback for numeric arrays - prettify the slug
            foreach ($availableCategories as $category) {
                $this->categoryLabels[$category] = $this->prettifySlug($category);
            }
        }
        
        // Build billing model labels from service config - support associative arrays
        $availableBillingModels = $this->templateService->getAvailableBillingModels() ?? [];
        // Guard against empty/null responses
        $availableBillingModels = is_array($availableBillingModels) ? $availableBillingModels : [];
        
        if ($this->isAssociativeArray($availableBillingModels)) {
            // Use key as slug and value as label for associative arrays
            foreach ($availableBillingModels as $slug => $label) {
                $this->billingModelLabels[$slug] = is_string($label) ? $label : $this->prettifySlug($slug);
            }
        } else {
            // Fallback for numeric arrays - prettify the slug
            foreach ($availableBillingModels as $billingModel) {
                $this->billingModelLabels[$billingModel] = $this->prettifySlug($billingModel);
            }
        }
        
        // Normalize known acronyms after building labels
        $this->normalizeAcronyms();
    }

    private function loadTemplates()
    {
        // Ensure proper company scoping - service already scopes by company via buildBaseQuery()
        // Load only minimal fields for listing performance
        try {
            $templates = $this->templateService->getActiveSummaries();
            $this->templates = $templates
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'category' => $template->category,
                        'billing_model' => $template->billing_model,
                        'usage_count' => $template->usage_count ?? 0,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Fallback to direct model query if service fails
            $this->templates = ContractTemplate::where('company_id', auth()->user()->company_id)
                ->active()
                ->select(['id', 'name', 'description', 'category', 'billing_model', 'usage_count'])
                ->orderBy('name')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'category' => $template->category,
                        'billing_model' => $template->billing_model,
                        'usage_count' => $template->usage_count ?? 0,
                    ];
                })
                ->toArray();
        }
    }

    #[Computed]
    public function availableCategories()
    {
        return collect($this->templates)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function availableBillingModels()
    {
        return collect($this->templates)
            ->pluck('billing_model')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function getCategoryLabel($category)
    {
        return $this->categoryLabels[$category] ?? $this->prettifySlug($category);
    }

    public function getBillingModelLabel($billingModel)
    {
        return $this->billingModelLabels[$billingModel] ?? $this->prettifySlug($billingModel);
    }

    /**
     * Check if array is associative (has string keys)
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Convert slug to pretty label
     */
    private function prettifySlug(string $slug): string
    {
        return ucfirst(str_replace('_', ' ', $slug));
    }
    
    /**
     * Normalize known acronyms in labels
     */
    private function normalizeAcronyms(): void
    {
        $acronyms = [
            'msp' => 'MSP',
            'voip' => 'VoIP',
            'var' => 'VAR',
            'api' => 'API',
            'crm' => 'CRM',
            'erp' => 'ERP',
            'rmm' => 'RMM',
            'psa' => 'PSA',
            'sla' => 'SLA',
            'it' => 'IT'
        ];
        
        // Normalize category labels
        foreach ($this->categoryLabels as $slug => $label) {
            $this->categoryLabels[$slug] = $this->replaceAcronyms($label, $acronyms);
        }
        
        // Normalize billing model labels
        foreach ($this->billingModelLabels as $slug => $label) {
            $this->billingModelLabels[$slug] = $this->replaceAcronyms($label, $acronyms);
        }
    }
    
    /**
     * Replace acronyms in a label string
     */
    private function replaceAcronyms(string $label, array $acronyms): string
    {
        $words = explode(' ', $label);
        
        foreach ($words as &$word) {
            $lowerWord = strtolower($word);
            if (isset($acronyms[$lowerWord])) {
                $word = $acronyms[$lowerWord];
            }
        }
        
        return implode(' ', $words);
    }

    public function updatedCategoryFilter()
    {
        $this->filterTemplates();
    }

    public function updatedBillingModelFilter()
    {
        $this->filterTemplates();
    }

    public function updatedSearchQuery()
    {
        $this->filterTemplates();
    }

    public function filterTemplates()
    {
        $filtered = collect($this->templates);

        // Apply category filter
        if (!empty($this->categoryFilter)) {
            $filtered = $filtered->where('category', $this->categoryFilter);
        }

        // Apply billing model filter
        if (!empty($this->billingModelFilter)) {
            $filtered = $filtered->where('billing_model', $this->billingModelFilter);
        }

        // Apply search query
        if (!empty($this->searchQuery)) {
            $searchTerm = strtolower($this->searchQuery);
            $filtered = $filtered->filter(function ($template) use ($searchTerm) {
                return str_contains(strtolower($template['name']), $searchTerm) ||
                       str_contains(strtolower($template['description'] ?? ''), $searchTerm);
            });
        }

        $this->filteredTemplates = $filtered->values()->toArray();
    }
    
    /**
     * Check if the currently selected template is hidden by active filters
     */
    public function isSelectedTemplateHidden(): bool
    {
        if (!$this->selectedTemplate || $this->selectedTemplate['id'] === null) {
            return false; // Custom template is always visible
        }
        
        $selectedId = $this->selectedTemplate['id'];
        
        // First check if the template exists in the loaded templates at all
        $templateExists = collect($this->templates)->contains('id', $selectedId);
        if (!$templateExists) {
            return false; // Don't warn if template isn't in the list at all
        }
        
        // Only warn if template exists but is filtered out
        return !collect($this->filteredTemplates)->contains('id', $selectedId);
    }


    public function selectTemplateById(int $id)
    {
        // Find template from loaded templates by ID
        $template = collect($this->templates)->firstWhere('id', $id);
        
        if ($template) {
            $this->applySelection($template);
        }
    }

    /**
     * Centralized method to set selection and dispatch event
     */
    private function applySelection(array $template): void
    {
        $this->selectedTemplate = $template;
        
        // Only dispatch if not during initial hydration to prevent duplicate events
        if (!$this->isInitializing) {
            // Target the parent wizard specifically to avoid event collisions
            return $this->dispatch('templateSelected', template: $template)->to(\App\Livewire\ContractWizard::class);
        }
    }

    public function clearFilters()
    {
        $this->searchQuery = '';
        $this->categoryFilter = '';
        $this->billingModelFilter = '';
        $this->filterTemplates();
    }

    public function selectCustomContract()
    {
        $customTemplate = [
            'id' => null,
            'name' => 'Custom Contract',
            'category' => 'custom',
            'billing_model' => null,
            'description' => 'Create a contract from scratch without using a template',
            'usage_count' => 0,
            'variable_fields' => []
        ];

        $this->applySelection($customTemplate);
    }

    // TODO: Server-side filtering enhancement for scalability
    // Currently loads all templates client-side and filters in JavaScript/PHP
    // Future enhancement should implement server-side filtering for better performance
    
    /**
     * Planned enhancement: Load templates with server-side filtering
     * This would replace client-side filtering for better scalability
     * 
     * @param string|null $category
     * @param string|null $billingModel  
     * @param string|null $search
     * @param int $page
     * @param int $perPage
     * @return void
     */
    // private function loadTemplatesFiltered(?string $category = null, ?string $billingModel = null, ?string $search = null, int $page = 1, int $perPage = 20): void
    // {
    //     $filters = [
    //         'category' => $category ?: $this->categoryFilter,
    //         'billing_model' => $billingModel ?: $this->billingModelFilter,
    //         'search' => $search ?: $this->searchQuery,
    //     ];
    //     
    //     // Use enhanced ContractTemplateService method (to be implemented)
    //     $this->templates = $this->templateService->getActiveSummariesPaginated($filters, $page, $perPage);
    //     $this->filteredTemplates = $this->templates; // Server-side filtering eliminates need for client filtering
    // }

    /**
     * Planned enhancement: Wire click handlers for pagination
     * These would be added to the Blade template with wire:click handlers
     */
    // public function nextPage(): void { /* Load next page of filtered results */ }
    // public function previousPage(): void { /* Load previous page of filtered results */ }
    // public function gotoPage(int $page): void { /* Load specific page of filtered results */ }

    public function render()
    {
        return view('livewire.contract-wizard.template-selection');
    }
}