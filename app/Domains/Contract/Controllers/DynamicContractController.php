<?php

namespace App\Domains\Contract\Controllers;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTypeDefinition;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Contract\Services\DynamicContractFormBuilder;
use App\Domains\Contract\Services\DynamicContractNavigationService;
use App\Domains\Contract\Services\DynamicContractViewBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * DynamicContractController
 *
 * Universal controller that handles all contract types dynamically.
 * Replaces hardcoded controllers with configuration-driven approach.
 */
class DynamicContractController extends Controller
{
    protected $formBuilder;

    protected $viewBuilder;

    protected $navigationService;

    protected $contractService;

    public function __construct(
        DynamicContractFormBuilder $formBuilder,
        DynamicContractViewBuilder $viewBuilder,
        DynamicContractNavigationService $navigationService,
        ContractService $contractService
    ) {
        $this->formBuilder = $formBuilder;
        $this->viewBuilder = $viewBuilder;
        $this->navigationService = $navigationService;
        $this->contractService = $contractService;

        $this->middleware('auth');
    }

    /**
     * Display a listing of contracts for the given type
     */
    public function index(Request $request, string $contractType)
    {
        $this->authorize('viewAny', Contract::class);

        // Validate contract type
        $typeDefinition = $this->getContractTypeDefinition($contractType);

        // Get view configuration
        $viewConfig = $this->viewBuilder->buildIndexView($contractType);

        // Get filters from request
        $filters = $request->only([
            'status', 'client_id', 'start_date_from', 'start_date_to',
            'end_date_from', 'end_date_to', 'search',
        ]);
        $filters['contract_type'] = $contractType;

        // Get contracts
        $perPage = $request->get('per_page', $viewConfig['pagination']['per_page']);
        $contracts = $this->contractService->getContracts($filters, $perPage);

        // Get breadcrumbs
        $breadcrumbs = $this->navigationService->getBreadcrumbs($contractType, 'index');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'contracts' => $contracts,
                    'view_config' => $viewConfig,
                ],
            ]);
        }

        return view('contracts.dynamic.index', compact(
            'contracts', 'contractType', 'typeDefinition', 'viewConfig', 'breadcrumbs'
        ));
    }

    /**
     * Show the form for creating a new contract
     */
    public function create(Request $request, string $contractType)
    {
        $this->authorize('create', Contract::class);

        // Validate contract type
        $typeDefinition = $this->getContractTypeDefinition($contractType);

        // Check permissions
        if (! $typeDefinition->hasCreatePermission(Auth::user())) {
            abort(403, 'You do not have permission to create this contract type');
        }

        // Build form configuration
        $formConfig = $this->formBuilder->buildCreateForm($contractType);

        // Get breadcrumbs
        $breadcrumbs = $this->navigationService->getBreadcrumbs($contractType, 'create');

        // Handle pre-selected quote or client
        $quoteId = $request->get('quote_id');
        $clientId = $request->get('client_id');
        $preselected = compact('quoteId', 'clientId');

        return view('contracts.dynamic.create', compact(
            'contractType', 'typeDefinition', 'formConfig', 'breadcrumbs', 'preselected'
        ));
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request, string $contractType)
    {
        $this->authorize('create', Contract::class);

        // Validate contract type
        $typeDefinition = $this->getContractTypeDefinition($contractType);

        try {
            // Validate form data
            $formConfig = $this->formBuilder->buildCreateForm($contractType);
            $validationResult = $this->formBuilder->validateFormData($contractType, $request->all());

            if (! $validationResult['valid']) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validationResult['errors'],
                    ], 422);
                }

                return back()->withInput()->withErrors($validationResult['errors']);
            }

            // Prepare contract data
            $data = $request->all();
            $data['contract_type'] = $contractType;
            $data['company_id'] = Auth::user()->company_id;
            $data['created_by'] = Auth::id();

            // Apply default values from type definition
            $defaultValues = $typeDefinition->getDefaultValues();
            foreach ($defaultValues as $field => $value) {
                if (! isset($data[$field]) || $data[$field] === '') {
                    $data[$field] = $value;
                }
            }

            // Create contract
            $contract = $this->contractService->createContract($data);

            Log::info('Dynamic contract created', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'contract_number' => $contract->contract_number,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract created successfully',
                    'data' => [
                        'contract' => $contract->load(['client']),
                        'redirect_url' => route("contracts.{$contractType}.show", $contract),
                    ],
                ], 201);
            }

            return redirect()
                ->route("contracts.{$contractType}.show", $contract)
                ->with('success', "Contract {$contract->contract_number} created successfully");

        } catch (\Exception $e) {
            Log::error('Dynamic contract creation failed', [
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create contract: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to create contract: '.$e->getMessage());
        }
    }

    /**
     * Display the specified contract
     */
    public function show(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('view', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        // Build view configuration
        $viewConfig = $this->viewBuilder->buildDetailView($contract);

        // Get breadcrumbs
        $breadcrumbs = $this->navigationService->getBreadcrumbs($contractType, 'show', $contract);

        // Get action buttons
        $actions = $this->navigationService->getActionButtons($contract);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'contract' => $contract,
                    'view_config' => $viewConfig,
                ],
            ]);
        }

        return view('contracts.dynamic.show', compact(
            'contract', 'contractType', 'viewConfig', 'breadcrumbs', 'actions'
        ));
    }

    /**
     * Show the form for editing the specified contract
     */
    public function edit(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('update', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        // Check if contract can be edited
        if (! $contract->canBeEdited()) {
            return back()->with('error', 'This contract cannot be edited in its current state');
        }

        // Build form configuration
        $formConfig = $this->formBuilder->buildEditForm($contract);

        // Get breadcrumbs
        $breadcrumbs = $this->navigationService->getBreadcrumbs($contractType, 'edit', $contract);

        return view('contracts.dynamic.edit', compact(
            'contract', 'contractType', 'formConfig', 'breadcrumbs'
        ));
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('update', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        try {
            // Validate form data
            $validationResult = $this->formBuilder->validateFormData($contractType, $request->all());

            if (! $validationResult['valid']) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validationResult['errors'],
                    ], 422);
                }

                return back()->withInput()->withErrors($validationResult['errors']);
            }

            // Update contract
            $data = $request->all();
            unset($data['contract_type']); // Don't allow changing contract type

            $contract = $this->contractService->updateContract($contract, $data);

            Log::info('Dynamic contract updated', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'contract_number' => $contract->contract_number,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract updated successfully',
                    'data' => [
                        'contract' => $contract->load(['client']),
                    ],
                ]);
            }

            return redirect()
                ->route("contracts.{$contractType}.show", $contract)
                ->with('success', "Contract {$contract->contract_number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Dynamic contract update failed', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contract: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to update contract: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified contract
     */
    public function destroy(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('delete', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        try {
            $contractNumber = $contract->contract_number;
            $this->contractService->deleteContract($contract);

            Log::warning('Dynamic contract deleted', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'contract_number' => $contractNumber,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract deleted successfully',
                ]);
            }

            return redirect()
                ->route("contracts.{$contractType}.index")
                ->with('success', "Contract {$contractNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Dynamic contract deletion failed', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete contract: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to delete contract: '.$e->getMessage());
        }
    }

    /**
     * Update contract status
     */
    public function updateStatus(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('update', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        try {
            // This would integrate with the status transition service
            $newStatus = $request->status;
            $reason = $request->reason;
            $effectiveDate = $request->effective_date ? \Carbon\Carbon::parse($request->effective_date) : null;

            // For now, simple status update
            $contract->update(['status' => $newStatus]);

            Log::info('Dynamic contract status updated', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'old_status' => $contract->getOriginal('status'),
                'new_status' => $newStatus,
                'reason' => $reason,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Contract status updated to {$newStatus}",
                    'data' => [
                        'contract' => $contract->fresh()->load(['client']),
                    ],
                ]);
            }

            return redirect()
                ->route("contracts.{$contractType}.show", $contract)
                ->with('success', "Contract status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Dynamic contract status update failed', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contract status: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to update contract status: '.$e->getMessage());
        }
    }

    /**
     * Generate contract PDF
     */
    public function pdf(Request $request, string $contractType, Contract $contract)
    {
        $this->authorize('view', $contract);

        // Validate contract type matches
        if ($contract->contract_type !== $contractType) {
            abort(404, 'Contract not found for this type');
        }

        try {
            // This would integrate with the contract generation service
            // For now, return a placeholder response

            Log::info('Dynamic contract PDF requested', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'user_id' => Auth::id(),
            ]);

            // Placeholder - would generate actual PDF
            return response()->json([
                'success' => true,
                'message' => 'PDF generation is not yet implemented',
                'download_url' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Dynamic contract PDF generation failed', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Get contract type definition
     */
    protected function getContractTypeDefinition(string $contractType): ContractTypeDefinition
    {
        $typeDefinition = ContractTypeDefinition::where('company_id', Auth::user()->company_id)
            ->where('slug', $contractType)
            ->where('is_active', true)
            ->first();

        if (! $typeDefinition) {
            abort(404, "Contract type '{$contractType}' not found or inactive");
        }

        return $typeDefinition;
    }
}
