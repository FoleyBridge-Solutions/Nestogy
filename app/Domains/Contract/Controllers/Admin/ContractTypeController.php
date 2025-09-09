<?php

namespace App\Domains\Contract\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractTypeController extends Controller
{
    protected ContractConfigurationRegistry $configRegistry;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->configRegistry = new ContractConfigurationRegistry(auth()->user()->company_id);
            return $next($request);
        });
    }

    /**
     * Display contract types management interface
     */
    public function index(): View
    {
        $contractTypes = $this->configRegistry->getContractTypes();
        $statistics = $this->getStatistics();
        
        // Add usage statistics to each contract type
        foreach ($contractTypes as &$type) {
            $type['contracts_count'] = Contract::where('company_id', auth()->user()->company_id)
                ->where('contract_type', $type['id'])
                ->count();
        }

        return view('admin.contract-types.index', compact('contractTypes', 'statistics'));
    }

    /**
     * Store a new contract type
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:50',
                'default_billing_model' => 'nullable|string|max:50',
                'default_term_length' => 'nullable|integer|min:1|max:120',
                'requires_signature' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'allows_amendments' => 'boolean',
                'supports_milestones' => 'boolean',
                'auto_renew' => 'boolean',
                'requires_approval' => 'boolean',
                'workflow_stages' => 'nullable|array',
                'notification_settings' => 'nullable|array',
            ]);

            $contractType = $this->configRegistry->createContractType($validated);

            return response()->json([
                'success' => true,
                'message' => 'Contract type created successfully',
                'data' => $contractType
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create contract type', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show contract type details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $contractType = $this->configRegistry->getContractType($id);
            
            if (!$contractType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $contractType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contract type'
            ], 500);
        }
    }

    /**
     * Update contract type
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:50',
                'default_billing_model' => 'nullable|string|max:50',
                'default_term_length' => 'nullable|integer|min:1|max:120',
                'requires_signature' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'allows_amendments' => 'boolean',
                'supports_milestones' => 'boolean',
                'auto_renew' => 'boolean',
                'requires_approval' => 'boolean',
                'workflow_stages' => 'nullable|array',
                'notification_settings' => 'nullable|array',
            ]);

            $contractType = $this->configRegistry->updateContractType($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Contract type updated successfully',
                'data' => $contractType
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update contract type', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contract type status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $this->configRegistry->updateContractType($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Contract type status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Clone contract type
     */
    public function clone(string $id): JsonResponse
    {
        try {
            $originalType = $this->configRegistry->getContractType($id);
            
            if (!$originalType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract type not found'
                ], 404);
            }

            // Create a copy with modified name
            $cloneData = $originalType;
            $cloneData['name'] = $cloneData['name'] . ' (Copy)';
            $cloneData['is_active'] = false; // Make clones inactive by default
            unset($cloneData['id']); // Remove ID to create new record

            $clonedType = $this->configRegistry->createContractType($cloneData);

            return response()->json([
                'success' => true,
                'message' => 'Contract type cloned successfully',
                'data' => $clonedType
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clone contract type', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clone contract type'
            ], 500);
        }
    }

    /**
     * Delete contract type
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Check if any contracts use this type
            $contractCount = Contract::where('company_id', auth()->user()->company_id)
                ->where('contract_type', $id)
                ->count();

            if ($contractCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete contract type. {$contractCount} contracts are using this type."
                ], 400);
            }

            $this->configRegistry->deleteContractType($id);

            return response()->json([
                'success' => true,
                'message' => 'Contract type deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete contract type', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract type'
            ], 500);
        }
    }

    /**
     * Get contract type statistics
     */
    protected function getStatistics(): array
    {
        $companyId = auth()->user()->company_id;
        $contractTypes = $this->configRegistry->getContractTypes();
        
        $totalTypes = count($contractTypes);
        $activeTypes = count(array_filter($contractTypes, fn($type) => $type['is_active'] ?? true));

        $contractsQuery = Contract::where('company_id', $companyId);
        
        $contractsThisMonth = (clone $contractsQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalValue = (clone $contractsQuery)->sum('contract_value') ?? 0;

        return [
            'total_types' => $totalTypes,
            'active_types' => $activeTypes,
            'contracts_this_month' => $contractsThisMonth,
            'total_value' => $totalValue,
        ];
    }

    /**
     * Export contract types configuration
     */
    public function export(): JsonResponse
    {
        try {
            $contractTypes = $this->configRegistry->getContractTypes();
            
            $export = [
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'company_id' => auth()->user()->company_id,
                'contract_types' => $contractTypes
            ];

            return response()->json($export);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export contract types'
            ], 500);
        }
    }

    /**
     * Import contract types configuration
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:json|max:2048'
            ]);

            $content = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !isset($data['contract_types'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid import file format'
                ], 400);
            }

            $imported = 0;
            $errors = [];

            foreach ($data['contract_types'] as $typeData) {
                try {
                    // Remove ID and company-specific data
                    unset($typeData['id']);
                    $typeData['is_active'] = false; // Import as inactive by default
                    
                    $this->configRegistry->createContractType($typeData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to import '{$typeData['name']}': " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$imported} contract types",
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import contract types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contract type usage statistics
     */
    public function usageStats(string $id): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            
            $contracts = Contract::where('company_id', $companyId)
                ->where('contract_type', $id);

            $stats = [
                'total_contracts' => $contracts->count(),
                'active_contracts' => (clone $contracts)->where('status', 'active')->count(),
                'total_value' => $contracts->sum('contract_value'),
                'average_value' => $contracts->avg('contract_value'),
                'created_this_month' => (clone $contracts)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'status_breakdown' => $contracts->groupBy('status')
                    ->map(fn($group) => $group->count())
                    ->toArray(),
                'monthly_trend' => $contracts
                    ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                    ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get()
                    ->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve usage statistics'
            ], 500);
        }
    }
}