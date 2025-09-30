<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Core\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ContractTemplateService
 *
 * Handles contract template operations following Nestogy's BaseService pattern
 */
class ContractTemplateService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = ContractTemplate::class;
        $this->defaultEagerLoad = ['creator', 'updater', 'clauses'];
        $this->searchableFields = ['name', 'description', 'template_type', 'category'];
    }

    /**
     * Override to add clause count when finding by ID
     */
    public function findByIdOrFail($id): ContractTemplate
    {
        return $this->buildBaseQuery()
            ->with($this->defaultEagerLoad)
            ->withCount(['clauses'])
            ->findOrFail($id);
    }

    protected function buildBaseQuery(): Builder
    {
        $companyId = auth()->check() ? auth()->user()->company_id : 1;

        return ContractTemplate::where('company_id', $companyId);
    }

    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['template_type'])) {
            $query->where('template_type', $filters['template_type']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['is_default'])) {
            $query->where('is_default', $filters['is_default'] === 'true' || $filters['is_default'] === true);
        }

        return $query;
    }

    protected function applySorting($query, array $filters)
    {
        $sortField = $filters['sort'] ?? $this->defaultSortField;
        $sortDirection = $filters['direction'] ?? $this->defaultSortDirection;

        return $query->orderBy($sortField, $sortDirection);
    }

    /**
     * Create a new contract template
     */
    public function create(array $data): ContractTemplate
    {
        $data['company_id'] = auth()->check() ? auth()->user()->company_id : 1;
        $data['created_by'] = Auth::id();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (empty($data['version'])) {
            $data['version'] = '1.0';
        }

        return ContractTemplate::create($data);
    }

    /**
     * Update an existing contract template
     */
    public function updateTemplate(ContractTemplate $template, array $data): ContractTemplate
    {
        $data['updated_by'] = Auth::id();

        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $template->update($data);

        return $template->fresh();
    }

    /**
     * Delete a contract template (soft delete)
     */
    public function deleteTemplate(ContractTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Get templates by type
     */
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildBaseQuery()
            ->where('template_type', $type)
            ->with($this->defaultEagerLoad)
            ->get();
    }

    /**
     * Get active templates
     */
    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildBaseQuery()
            ->active()
            ->with($this->defaultEagerLoad)
            ->get();
    }

    /**
     * Get active template summaries with minimal columns for list views
     */
    public function getActiveSummaries(): \Illuminate\Support\Collection
    {
        return $this->buildBaseQuery()
            ->active()
            ->select(['id', 'name', 'description', 'category', 'billing_model', 'usage_count'])
            ->orderBy('name')
            ->get();
    }

    // TODO: Planned enhancement for TemplateSelection scalability
    // Add server-side filtering method to replace client-side filtering
    /**
     * Get active template summaries with server-side filtering and pagination
     *
     * @param  array  $filters  ['category' => string, 'billing_model' => string, 'search' => string]
     * @param  int  $page
     * @param  int  $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    // public function getActiveSummariesPaginated(array $filters = [], int $page = 1, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    // {
    //     $query = $this->buildBaseQuery()
    //         ->active()
    //         ->select(['id', 'name', 'description', 'category', 'billing_model', 'usage_count']);
    //
    //     // Apply filters server-side
    //     $query = $this->applyFilters($query, $filters);
    //
    //     return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    // }

    /**
     * Get default templates
     */
    public function getDefault(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildBaseQuery()
            ->default()
            ->with($this->defaultEagerLoad)
            ->get();
    }

    /**
     * Create a new version of a template
     */
    public function createVersion(ContractTemplate $template, array $changes = []): ContractTemplate
    {
        return $template->createVersion($changes);
    }

    /**
     * Toggle template default status
     */
    public function toggleDefault(ContractTemplate $template): ContractTemplate
    {
        // If making this template default, unset other defaults of the same type
        if (! $template->is_default) {
            ContractTemplate::where('company_id', $template->company_id)
                ->where('template_type', $template->template_type)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update(['is_default' => ! $template->is_default]);

        return $template->fresh();
    }

    /**
     * Duplicate a template
     */
    public function duplicate(ContractTemplate $template, array $overrides = []): ContractTemplate
    {
        $data = $template->toArray();

        // Remove fields that shouldn't be duplicated
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['archived_at']);

        // Apply overrides
        $data = array_merge($data, $overrides);

        // Ensure unique name and slug
        $data['name'] = ($data['name'] ?? $template->name).' (Copy)';
        $data['slug'] = Str::slug($data['name']);
        $data['is_default'] = false;
        $data['usage_count'] = 0;
        $data['last_used_at'] = null;

        return $this->create($data);
    }

    /**
     * Get template statistics
     */
    public function getTemplateStatistics(ContractTemplate $template): array
    {
        return $template->getStatistics();
    }

    /**
     * Validate template content
     */
    public function validateTemplate(ContractTemplate $template): array
    {
        return $template->validateContent();
    }

    /**
     * Get available template types for the current company
     */
    public function getAvailableTypes(): array
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;

        return ContractTemplate::getAvailableTypes($companyId);
    }

    /**
     * Get available template categories for the current company
     */
    public function getAvailableCategories(): array
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;

        return ContractTemplate::getAvailableCategories($companyId);
    }

    /**
     * Get available template statuses for the current company
     */
    public function getAvailableStatuses(): array
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;

        return ContractTemplate::getAvailableStatuses($companyId);
    }

    /**
     * Get available billing models for the current company
     */
    public function getAvailableBillingModels(): array
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;

        return ContractTemplate::getAvailableBillingModels($companyId);
    }
}
