<?php

namespace App\Domains\Contract\Controllers;

use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractTemplateService;
use App\Domains\Contract\Requests\StoreContractTemplateRequest;
use App\Domains\Contract\Requests\UpdateContractTemplateRequest;
use App\Http\Controllers\BaseResourceController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ContractTemplateController
 * 
 * Handles CRUD operations for contract templates following Nestogy's patterns
 */
class ContractTemplateController extends BaseResourceController
{
    protected function initializeController(): void
    {
        $this->service = app(ContractTemplateService::class);
        $this->resourceName = 'template';
        $this->viewPath = 'settings.contract-templates';
        $this->routePrefix = 'settings.contract-templates';
    }

    protected function getModelClass(): string
    {
        return ContractTemplate::class;
    }

    protected function getAllowedFilters(): array
    {
        return ['search', 'status', 'template_type', 'category', 'is_default', 'sort', 'direction'];
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', ContractTemplate::class);
        
        $filters = array_merge(
            $this->defaultFilters,
            $request->only($this->getAllowedFilters())
        );
        
        $templates = $this->service->getPaginated($filters, $this->perPage);
        
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $templates->items(),
                'pagination' => [
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'per_page' => $templates->perPage(),
                    'total' => $templates->total(),
                ]
            ]);
        }

        $availableTypes = $this->service->getAvailableTypes();
        $availableCategories = $this->service->getAvailableCategories();
        $availableStatuses = $this->service->getAvailableStatuses();

        return view('settings.contract-templates.index', compact(
            'templates',
            'availableTypes',
            'availableCategories', 
            'availableStatuses'
        ));
    }

    public function create(): View
    {
        $this->authorize('create', ContractTemplate::class);

        $availableTypes = $this->service->getAvailableTypes();
        $availableCategories = $this->service->getAvailableCategories();
        $availableStatuses = $this->service->getAvailableStatuses();
        $availableBillingModels = $this->service->getAvailableBillingModels();

        return view('settings.contract-templates.create', compact(
            'availableTypes',
            'availableCategories',
            'availableStatuses',
            'availableBillingModels'
        ));
    }

    public function store(FormRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', $this->getModelClass());
        
        // Create and validate using our specific request class
        $storeRequest = StoreContractTemplateRequest::createFrom($request);
        $storeRequest->setContainer(app());
        $storeRequest->validateResolved();
        
        try {
            $template = $this->service->create($storeRequest->validated());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Contract template created successfully.',
                    'data' => $template,
                ], 201);
            }

            return redirect()
                ->route('settings.contract-templates.show', $template)
                ->with('success', 'Contract template created successfully.');
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create contract template',
                    'error' => $e->getMessage()
                ], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create contract template: ' . $e->getMessage()]);
        }
    }

    public function show($id): View|JsonResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('view', $template);

        if (request()->expectsJson()) {
            return response()->json(['data' => $template]);
        }

        $statistics = $this->service->getTemplateStatistics($template);
        $validationErrors = $this->service->validateTemplate($template);

        return view('settings.contract-templates.show', compact(
            'template',
            'statistics', 
            'validationErrors'
        ));
    }

    public function edit($id): View
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('update', $template);

        $availableTypes = $this->service->getAvailableTypes();
        $availableCategories = $this->service->getAvailableCategories();
        $availableStatuses = $this->service->getAvailableStatuses();
        $availableBillingModels = $this->service->getAvailableBillingModels();

        return view('settings.contract-templates.edit', compact(
            'template',
            'availableTypes',
            'availableCategories',
            'availableStatuses',
            'availableBillingModels'
        ));
    }

    public function update(FormRequest $request, $id): RedirectResponse|JsonResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('update', $template);
        
        try {
            $template = $this->service->updateTemplate($template, $request->validated());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Contract template updated successfully.',
                    'data' => $template,
                ]);
            }

            return redirect()
                ->route('settings.contract-templates.show', $template)
                ->with('success', 'Contract template updated successfully.');
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update contract template',
                    'error' => $e->getMessage()
                ], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update contract template: ' . $e->getMessage()]);
        }
    }

    public function destroy($id): RedirectResponse|JsonResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('delete', $template);

        try {
            $this->service->deleteTemplate($template);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Contract template deleted successfully.',
                ]);
            }

            return redirect()
                ->route('settings.contract-templates.index')
                ->with('success', 'Contract template deleted successfully.');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to delete contract template',
                    'error' => $e->getMessage()
                ], 422);
            }
            
            return back()
                ->withErrors(['error' => 'Failed to delete contract template: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle the default status of a template
     */
    public function toggleDefault($id): RedirectResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('update', $template);

        $template = $this->service->toggleDefault($template);

        $message = $template->is_default 
            ? 'Template set as default successfully.'
            : 'Template removed as default successfully.';

        return redirect()
            ->route('settings.contract-templates.show', $template)
            ->with('success', $message);
    }

    /**
     * Create a new version of a template
     */
    public function createVersion(Request $request, $id): RedirectResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('create', ContractTemplate::class);

        $changes = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);

        $newVersion = $this->service->createVersion($template, $changes);

        return redirect()
            ->route('settings.contract-templates.edit', $newVersion)
            ->with('success', 'New template version created successfully.');
    }

    /**
     * Duplicate a template
     */
    public function duplicate(Request $request, $id): RedirectResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('create', ContractTemplate::class);

        $overrides = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        $duplicate = $this->service->duplicate($template, $overrides);

        return redirect()
            ->route('settings.contract-templates.edit', $duplicate)
            ->with('success', 'Template duplicated successfully.');
    }

    /**
     * Validate template content
     */
    public function validateTemplate($id): JsonResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('view', $template);

        $validationErrors = $this->service->validateTemplate($template);

        return response()->json([
            'valid' => empty($validationErrors),
            'errors' => $validationErrors
        ]);
    }

    /**
     * Get template statistics
     */
    public function statistics($id): JsonResponse
    {
        $template = $this->service->findByIdOrFail($id);
        $this->authorize('view', $template);

        $statistics = $this->service->getTemplateStatistics($template);

        return response()->json($statistics);
    }
}