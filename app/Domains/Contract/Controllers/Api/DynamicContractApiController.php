<?php

namespace App\Domains\Contract\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Contract\Services\DynamicContractFormBuilder;
use App\Domains\Contract\Services\DynamicContractViewBuilder;
use App\Domains\Contract\Services\DynamicContractRouteService;
use App\Domains\Contract\Models\{
    ContractFormConfiguration,
    ContractViewConfiguration,
    ContractNavigationItem
};
use App\Domains\Contract\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DynamicContractApiController extends Controller
{
    protected DynamicContractFormBuilder $formBuilder;
    protected DynamicContractViewBuilder $viewBuilder;
    protected DynamicContractRouteService $routeService;

    public function __construct(
        DynamicContractFormBuilder $formBuilder,
        DynamicContractViewBuilder $viewBuilder,
        DynamicContractRouteService $routeService
    ) {
        $this->formBuilder = $formBuilder;
        $this->viewBuilder = $viewBuilder;
        $this->routeService = $routeService;
    }

    /**
     * Get paginated list of contracts
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $contractType = $this->getContractTypeFromRequest($request);

        $query = Contract::where('company_id', $companyId);

        if ($contractType) {
            $query->where('type', $contractType);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $perPage = min($request->get('per_page', 20), 100);
        $contracts = $query->with(['client', 'user'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $contracts->items(),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
                'last_page' => $contracts->lastPage(),
            ],
            'contract_type' => $contractType,
        ]);
    }

    /**
     * Store a new contract
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $contractType = $this->getContractTypeFromRequest($request);

        // Get form configuration for validation
        $formConfig = ContractFormConfiguration::where('company_id', $companyId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->first();

        if (!$formConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Form configuration not found for this contract type',
            ], 404);
        }

        // Build and validate form
        $form = $this->formBuilder->buildForm($formConfig);
        $validationRules = $this->formBuilder->getValidationRules($form);

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create contract
            $contractData = $request->all();
            $contractData['company_id'] = $companyId;
            $contractData['user_id'] = Auth::id();
            $contractData['type'] = $contractType;

            $contract = Contract::create($contractData);

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => $contract->load(['client', 'user']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific contract
     */
    public function show(Request $request, $id): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $contract = Contract::where('company_id', $companyId)
            ->where('id', $id)
            ->with(['client', 'user', 'milestones', 'signatures', 'amendments'])
            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    /**
     * Update a contract
     */
    public function update(Request $request, $id): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $contract = Contract::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contractType = $contract->type ?: $this->getContractTypeFromRequest($request);

        // Get form configuration for validation
        $formConfig = ContractFormConfiguration::where('company_id', $companyId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->first();

        if ($formConfig) {
            $form = $this->formBuilder->buildForm($formConfig);
            $validationRules = $this->formBuilder->getValidationRules($form);

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        try {
            $contract->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully',
                'data' => $contract->load(['client', 'user']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a contract
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $contract = Contract::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        try {
            $contract->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:delete,update_status,export',
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'data' => 'array',
        ]);

        $companyId = Auth::user()->company_id;
        $action = $request->action;
        $ids = $request->ids;
        $data = $request->data ?? [];

        $contracts = Contract::where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->get();

        if ($contracts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No contracts found',
            ], 404);
        }

        try {
            switch ($action) {
                case 'delete':
                    $deleted = $contracts->each->delete()->count();
                    return response()->json([
                        'success' => true,
                        'message' => "Deleted {$deleted} contracts",
                    ]);

                case 'update_status':
                    if (!isset($data['status'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Status is required for update_status action',
                        ], 422);
                    }

                    $updated = $contracts->each(function ($contract) use ($data) {
                        $contract->update(['status' => $data['status']]);
                    })->count();

                    return response()->json([
                        'success' => true,
                        'message' => "Updated {$updated} contracts",
                    ]);

                case 'export':
                    // Export logic would go here
                    return response()->json([
                        'success' => true,
                        'message' => 'Export initiated',
                        'export_url' => route('api.contracts.export', ['ids' => implode(',', $ids)]),
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action',
                    ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export contracts
     */
    public function export(Request $request): JsonResponse
    {
        // Export functionality would be implemented here
        return response()->json([
            'success' => true,
            'message' => 'Export feature not implemented yet',
        ]);
    }

    /**
     * Import contracts
     */
    public function import(Request $request): JsonResponse
    {
        // Import functionality would be implemented here
        return response()->json([
            'success' => true,
            'message' => 'Import feature not implemented yet',
        ]);
    }

    /**
     * Get form schema for contract type
     */
    public function schema(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $contractType = $request->get('type');

        if (!$contractType) {
            return response()->json([
                'success' => false,
                'message' => 'Contract type is required',
            ], 422);
        }

        $formConfig = ContractFormConfiguration::where('company_id', $companyId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->first();

        if (!$formConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Schema not found for this contract type',
            ], 404);
        }

        $form = $this->formBuilder->buildForm($formConfig);

        return response()->json([
            'success' => true,
            'data' => [
                'fields' => $form['fields'],
                'validation_rules' => $this->formBuilder->getValidationRules($form),
                'field_groups' => $form['field_groups'] ?? [],
            ],
        ]);
    }

    /**
     * Get configuration for contract type
     */
    public function config(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $contractType = $request->get('type');

        $config = [
            'navigation' => [],
            'forms' => [],
            'views' => [],
        ];

        if ($contractType) {
            // Get specific type configuration
            $config['navigation'] = ContractNavigationItem::where('company_id', $companyId)
                ->where('slug', $contractType)
                ->where('is_active', true)
                ->first();

            $config['forms'] = ContractFormConfiguration::where('company_id', $companyId)
                ->where('contract_type', $contractType)
                ->where('is_active', true)
                ->first();

            $config['views'] = ContractViewConfiguration::where('company_id', $companyId)
                ->where('contract_type', $contractType)
                ->where('is_active', true)
                ->first();
        } else {
            // Get all configurations
            $config['navigation'] = ContractNavigationItem::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $config['forms'] = ContractFormConfiguration::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            $config['views'] = ContractViewConfiguration::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Get available contract types
     */
    public function types(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $types = ContractNavigationItem::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderBy('sort_order')
            ->get(['slug', 'title', 'description', 'icon'])
            ->map(function ($item) {
                return [
                    'type' => $item->slug,
                    'title' => $item->title,
                    'description' => $item->description,
                    'icon' => $item->icon,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Extract contract type from request
     */
    protected function getContractTypeFromRequest(Request $request): ?string
    {
        // Try to get from navigation context first
        $navigationItem = $request->get('dynamic_navigation_item');
        if ($navigationItem && $navigationItem->slug) {
            return $navigationItem->slug;
        }

        // Try to get from request parameters
        if ($request->filled('type')) {
            return $request->type;
        }

        // Try to extract from route
        $routeName = $request->route()->getName();
        if ($routeName && str_contains($routeName, 'contracts.')) {
            $parts = explode('.', $routeName);
            if (count($parts) >= 3 && $parts[0] === 'contracts') {
                return $parts[1];
            }
        }

        return null;
    }
}