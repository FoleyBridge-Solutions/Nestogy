<?php

namespace App\Domains\Contract\Controllers;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Models\ContractSignature;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Requests\ContractStatusRequest;
use App\Domains\Contract\Requests\StoreContractRequest;
use App\Domains\Contract\Requests\UpdateContractRequest;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Services\ContractGenerationService;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Security\Services\DigitalSignatureService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ContractController
 *
 * Comprehensive contract management with generation, digital signatures,
 * milestone tracking, amendments, and VoIP-specific contract handling.
 */
class ContractController extends Controller
{
    protected $contractService;

    protected $contractGenerationService;

    protected $signatureService;

    public function __construct(
        ContractService $contractService,
        ContractGenerationService $contractGenerationService,
        ?DigitalSignatureService $signatureService = null
    ) {
        $this->contractService = $contractService;
        $this->contractGenerationService = $contractGenerationService;
        $this->signatureService = $signatureService;

        // Apply middleware
        $this->middleware('auth');
    }

    /**
     * Get contract configuration registry for current company
     */
    protected function getContractConfigRegistry(): ContractConfigurationRegistry
    {
        return new ContractConfigurationRegistry(auth()->user()->company_id);
    }

    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        // Get filters from request
        $filters = $request->only([
            'status', 'contract_type', 'client_id', 'signature_status',
            'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to', 'search',
        ]);
        
        // If no client_id filter specified but session has selected_client_id, apply it
        if (empty($filters['client_id']) && session('selected_client_id')) {
            $filters['client_id'] = session('selected_client_id');
        }

        $perPage = $request->get('per_page', 25);
        $contracts = $this->contractService->getContracts($filters, $perPage);
        
        // Pass client filter to statistics as well
        $statisticsClientId = $filters['client_id'] ?? null;
        $statistics = $this->contractService->getDashboardStatistics($statisticsClientId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'contracts' => $contracts,
                    'statistics' => $statistics,
                ],
            ]);
        }

        // Get clients for filter
        $clients = Client::where('company_id', Auth::user()->company_id)->orderBy('name')->get();

        return view('financial.contracts.index', compact('contracts', 'statistics', 'clients'));
    }

    /**
     * Show the form for creating a new contract
     */
    public function create(Request $request)
    {
        $this->authorize('create', Contract::class);

        $user = Auth::user();

        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Get active and draft contract templates (exclude only archived)
        $templates = ContractTemplate::where('company_id', $user->company_id)
            ->whereIn('status', ['active', 'draft'])
            ->with(['clauses'])
            ->withCount(['contracts'])
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                // Add additional computed fields for the frontend
                $template->clause_count = $template->clauses->count();
                $template->usage_count = $template->contracts_count;

                // Add variable count from variable_fields or extract from clauses
                $template->variable_count = $template->variable_fields ? count($template->variable_fields) : count($template->extractVariables());

                return $template;
            });

        // Handle pre-selected client from session
        $selectedClient = app(\App\Domains\Core\Services\NavigationService::class)->getSelectedClient();

        $contractConfigRegistry = $this->getContractConfigRegistry();
        $contractTypes = $contractConfigRegistry->getContractTypes();
        $availableStatuses = $contractConfigRegistry->getContractStatuses();

        // Billing models for programmable contracts
        $billingModels = [
            'fixed' => 'Fixed Price',
            'per_asset' => 'Per Asset/Device',
            'per_contact' => 'Per Contact/Seat',
            'tiered' => 'Tiered Pricing',
            'hybrid' => 'Hybrid Model',
        ];

        // Template types
        $templateTypes = [
            'service_agreement' => 'Service Agreement',
            'maintenance_contract' => 'Maintenance Contract',
            'support_contract' => 'Support Contract',
            'managed_services' => 'Managed Services',
            'consulting' => 'Consulting Agreement',
            'custom' => 'Custom Contract',
        ];

        return view('financial.contracts.create', compact(
            'clients', 'templates', 'selectedClient', 'contractTypes', 'availableStatuses',
            'billingModels', 'templateTypes'
        ));
    }

    /**
     * Store a newly created contract
     */
    public function store(StoreContractRequest $request)
    {
        try {
            $data = $request->validatedWithComputed();

            // Create contract via wizard
            $contract = $this->contractService->createContract($data);

            Log::info('Contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract created successfully',
                    'data' => [
                        'contract' => $contract->load(['client', 'template']),
                    ],
                ], 201);
            }

            return redirect()
                ->route('financial.contracts.show', $contract->id)
                ->with('success', "Contract {$contract->contract_number} created successfully");

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they're handled properly
            throw $e;
        } catch (\Exception $e) {
            Log::error('Contract creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['password', 'token']),
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
    public function show(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load([
            'client',
            'quote',
            'template',
            'signatures' => function ($query) {
                $query->orderBy('signing_order');
            },
            'contractMilestones' => function ($query) {
                $query->orderBy('sort_order');
            },
            'amendments' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'approvals' => function ($query) {
                $query->orderBy('requested_at', 'desc');
            },
            'invoices' => function ($query) {
                $query->orderBy('date', 'desc');
            },
        ]);

        // Calculate contract metrics
        $metrics = $this->calculateContractMetrics($contract);

        // Get upcoming milestones
        $upcomingMilestones = $contract->contractMilestones()
            ->where('status', '!=', ContractMilestone::STATUS_COMPLETED)
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'contract' => $contract,
                'metrics' => $metrics,
                'upcoming_milestones' => $upcomingMilestones,
            ]);
        }

        return view('financial.contracts.show', compact('contract', 'metrics', 'upcomingMilestones'));
    }

    /**
     * Show the form for editing the specified contract
     */
    public function edit(Contract $contract)
    {
        $this->authorize('update', $contract);

        // Redirect to Livewire component
        return view('financial.contracts.edit', compact('contract'));
    }

    /**
     * Update the specified contract
     */
    public function update(UpdateContractRequest $request, Contract $contract)
    {
        try {
            $data = $request->getUpdatableFields();
            $contract = $this->contractService->updateContract($contract, $data);

            Log::info('Contract updated', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract updated successfully',
                    'data' => [
                        'contract' => $contract->load(['client', 'quote', 'template']),
                    ],
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', "Contract {$contract->contract_number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Contract update failed', [
                'contract_id' => $contract->id,
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
     * Send contract for signature
     */
    public function sendForSignature(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $configRegistry = $this->getContractConfigRegistry();
        $contractStatuses = $configRegistry->getContractStatuses();
        $pendingSignatureStatus = array_search('Pending Signature', array_column($contractStatuses, 'name'));

        if ($contract->status !== $pendingSignatureStatus) {
            return back()->with('error', 'Contract must be ready for signature');
        }

        try {
            $pendingSignatures = $contract->signatures()
                ->where('status', ContractSignature::STATUS_PENDING)
                ->orderBy('signing_order')
                ->get();

            if ($pendingSignatures->isEmpty()) {
                return back()->with('error', 'No pending signatures found');
            }

            $sentCount = 0;
            foreach ($pendingSignatures as $signature) {
                if ($signature->send()) {
                    $sentCount++;
                }
            }

            Log::info('Contract signatures sent', [
                'contract_id' => $contract->id,
                'signatures_sent' => $sentCount,
                'user_id' => Auth::id(),
            ]);

            return back()->with('success', "Contract sent for signature to {$sentCount} recipient(s)");

        } catch (\Exception $e) {
            Log::error('Failed to send contract for signature', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to send contract for signature: '.$e->getMessage());
        }
    }

    /**
     * Process signature webhook from external providers
     */
    public function processSignatureWebhook(Request $request)
    {
        $provider = $request->header('X-Provider', $request->get('provider'));

        try {
            if ($this->signatureService) {
                $result = $this->signatureService->processWebhook($provider, $request->all());

                Log::info('Signature webhook processed', [
                    'provider' => $provider,
                    'result' => $result,
                ]);

                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false, 'message' => 'Signature service not available'], 503);

        } catch (\Exception $e) {
            Log::error('Signature webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update contract status
     */
    public function updateStatus(ContractStatusRequest $request, Contract $contract)
    {
        try {
            $data = $request->getProcessedData();
            $newStatus = $data['status'];
            $reason = $data['reason'] ?? null;
            $effectiveDate = $data['effective_date'] ? Carbon::parse($data['effective_date']) : null;

            // Handle special status transitions using service methods
            $configRegistry = $this->getContractConfigRegistry();
            $contractStatuses = $configRegistry->getContractStatuses();
            $activeStatus = array_search('Active', array_column($contractStatuses, 'name'));
            $terminatedStatus = array_search('Terminated', array_column($contractStatuses, 'name'));
            $suspendedStatus = array_search('Suspended', array_column($contractStatuses, 'name'));

            switch ($newStatus) {
                case $activeStatus:
                    $contract = $this->contractService->activateContract($contract, $effectiveDate);
                    break;

                case $terminatedStatus:
                    $contract = $this->contractService->terminateContract($contract, $reason, $effectiveDate);
                    break;

                case $suspendedStatus:
                    $contract = $this->contractService->suspendContract($contract, $reason);
                    break;

                default:
                    $contract = $this->contractService->updateContract($contract, ['status' => $newStatus]);
                    break;
            }

            Log::info('Contract status updated', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'old_status' => $data['previous_status'],
                'new_status' => $newStatus,
                'reason' => $reason,
                'effective_date' => $effectiveDate,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Contract status updated to {$newStatus}",
                    'data' => [
                        'contract' => $contract->load(['client']),
                    ],
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', "Contract status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Contract status update failed', [
                'contract_id' => $contract->id,
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
     * Create contract amendment
     */
    public function createAmendment(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amendment_type' => 'required|in:pricing_change,term_extension,scope_modification,service_addition,service_removal,sla_modification,payment_terms,compliance_update,general_modification',
            'changes' => 'required|array',
            'reason' => 'required|string|max:1000',
            'effective_date' => 'required|date|after_or_equal:today',
        ]);

        try {
            $amendment = $contract->createAmendment($request->changes, $request->reason);

            $amendment->update([
                'title' => $request->title,
                'description' => $request->description,
                'amendment_type' => $request->amendment_type,
                'effective_date' => $request->effective_date,
            ]);

            Log::info('Contract amendment created', [
                'contract_id' => $contract->id,
                'amendment_id' => $amendment->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract amendment created successfully',
                'amendment' => $amendment,
            ]);

        } catch (\Exception $e) {
            Log::error('Contract amendment creation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create amendment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate contract PDF
     */
    public function generatePdf(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        try {
            $pdfPath = $this->contractGenerationService->generateContractDocument($contract);

            Log::info('Contract PDF generated', [
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
            ]);

            // Get PDF content from S3 and return as download
            $pdfContent = Storage::get($pdfPath);
            $filename = basename($pdfPath);

            Log::info('PDF download requested', [
                'path' => $pdfPath,
                'content_size' => strlen($pdfContent),
                'filename' => $filename,
            ]);

            if (empty($pdfContent)) {
                throw new \Exception('PDF file is empty or could not be retrieved from storage');
            }

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            Log::error('Contract PDF generation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Delete the specified contract
     */
    public function destroy(Request $request, Contract $contract)
    {
        try {
            $contractNumber = $contract->contract_number;
            $this->contractService->deleteContract($contract);

            Log::warning('Contract deleted', [
                'contract_id' => $contract->id,
                'contract_number' => $contractNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract deleted successfully',
                ]);
            }

            return redirect()
                ->route('financial.contracts.index')
                ->with('success', "Contract {$contractNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Contract deletion failed', [
                'contract_id' => $contract->id,
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
     * Get dashboard data
     */
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        try {
            $statistics = $this->contractService->getDashboardStatistics();
            $expiringContracts = $this->contractService->getExpiringContracts(30);
            $contractsDueForRenewal = $this->contractService->getContractsDueForRenewal(60);

            $dashboardData = [
                'statistics' => $statistics,
                'expiring_contracts' => $expiringContracts,
                'contracts_due_for_renewal' => $contractsDueForRenewal,
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $dashboardData,
                ]);
            }

            return view('financial.contracts.dashboard', $dashboardData);

        } catch (\Exception $e) {
            Log::error('Contract dashboard data failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load dashboard data',
                ], 500);
            }

            return back()->with('error', 'Failed to load dashboard data');
        }
    }

    /**
     * Get contracts expiring soon
     */
    public function expiring(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        try {
            $days = $request->get('days', 30);
            $contracts = $this->contractService->getExpiringContracts($days);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'contracts' => $contracts,
                        'days_ahead' => $days,
                    ],
                ]);
            }

            return view('financial.contracts.expiring', compact('contracts', 'days'));

        } catch (\Exception $e) {
            Log::error('Get expiring contracts failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get expiring contracts',
                ], 500);
            }

            return back()->with('error', 'Failed to get expiring contracts');
        }
    }

    /**
     * Search contracts
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        try {
            $query = $request->get('q');
            $limit = $request->get('limit', 25);

            $contracts = $this->contractService->searchContracts($query, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'contracts' => $contracts,
                    'query' => $query,
                    'total' => $contracts->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Contract search failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'query' => $request->get('q'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Activate a contract
     */
    public function activate(Request $request, Contract $contract)
    {
        $this->authorize('activate', $contract);

        try {
            $activationDate = $request->get('activation_date') ?
                Carbon::parse($request->get('activation_date')) : null;

            $contract = $this->contractService->activateContract($contract, $activationDate);

            Log::info('Contract activated', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'activation_date' => $activationDate,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract activated successfully',
                    'data' => [
                        'contract' => $contract->load(['client']),
                    ],
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', 'Contract activated successfully');

        } catch (\Exception $e) {
            Log::error('Contract activation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate contract: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to activate contract: '.$e->getMessage());
        }
    }

    /**
     * Reactivate a suspended contract
     */
    public function reactivate(Request $request, Contract $contract)
    {
        $this->authorize('reactivate', $contract);

        try {
            $contract = $this->contractService->reactivateContract($contract);

            Log::info('Contract reactivated', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract reactivated successfully',
                    'data' => [
                        'contract' => $contract->load(['client']),
                    ],
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', 'Contract reactivated successfully');

        } catch (\Exception $e) {
            Log::error('Contract reactivation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reactivate contract: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to reactivate contract: '.$e->getMessage());
        }
    }

    /**
     * Get contract statistics
     */
    protected function getContractStatistics(int $companyId): array
    {
        $contracts = Contract::where('company_id', $companyId);

        $configRegistry = new ContractConfigurationRegistry($companyId);
        $contractStatuses = $configRegistry->getContractStatuses();
        $signatureStatuses = $configRegistry->getContractSignatureStatuses();

        $activeStatus = array_search('Active', array_column($contractStatuses, 'name'));
        $pendingSignatureStatus = array_search('Pending', array_column($signatureStatuses, 'name'));

        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', $activeStatus)->count(),
            'pending_signature' => $contracts->where('signature_status', $pendingSignatureStatus)->count(),
            'expiring_soon' => $contracts->expiringSoon(30)->count(),
            'total_value' => $contracts->sum('contract_value'),
            'monthly_recurring' => $contracts->active()->sum(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pricing_structure, '$.recurring_monthly'))")),
        ];
    }

    /**
     * Calculate contract metrics
     */
    protected function calculateContractMetrics(Contract $contract): array
    {
        $milestones = $contract->contractMilestones;

        return [
            'total_milestones' => $milestones->count(),
            'completed_milestones' => $milestones->where('status', ContractMilestone::STATUS_COMPLETED)->count(),
            'overdue_milestones' => $milestones->where('planned_completion_date', '<', now())
                ->where('status', '!=', ContractMilestone::STATUS_COMPLETED)->count(),
            'progress_percentage' => $milestones->count() > 0 ?
                ($milestones->where('status', ContractMilestone::STATUS_COMPLETED)->count() / $milestones->count()) * 100 : 0,
            'days_remaining' => $contract->getRemainingDays(),
            'monthly_value' => $contract->getMonthlyRecurringRevenue(),
            'annual_value' => $contract->getAnnualValue(),
            'signatures_pending' => $contract->signatures->where('status', ContractSignature::STATUS_PENDING)->count(),
            'signatures_completed' => $contract->signatures->where('status', ContractSignature::STATUS_SIGNED)->count(),
        ];
    }

    /**
     * Get contract templates
     */
    public function getTemplates(Request $request, string $type = 'all')
    {
        $user = Auth::user();

        // For now, return a simple templates page
        // This can be expanded later to show actual template management
        return view('financial.contracts.templates', [
            'type' => $type,
            'templates' => [], // Placeholder for future template functionality
        ]);
    }

    // ====================================
    // CONTRACT TEMPLATES MANAGEMENT
    // ====================================

    /**
     * Display contract templates listing
     */
    public function templatesIndex(Request $request)
    {
        try {
            $user = Auth::user();

            $query = \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('template_type', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Type filter
            if ($request->filled('type')) {
                $query->where('template_type', $request->get('type'));
            }

            // Billing model filter
            if ($request->filled('billing_model')) {
                $query->where('billing_model', $request->get('billing_model'));
            }

            $templates = $query->with(['creator', 'updater'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Statistics
            $stats = [
                'total' => \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id)->count(),
                'active' => \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id)->where('status', 'active')->count(),
                'draft' => \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id)->where('status', 'draft')->count(),
                'programmable' => \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id)->programmable()->count(),
            ];

            return view('financial.contracts.templates.index', compact('templates', 'stats'));

        } catch (\Exception $e) {
            Log::error('Contract templates listing failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load contract templates');
        }
    }

    /**
     * Show form for creating a new contract template
     */
    public function templatesCreate(Request $request)
    {
        try {
            $templateTypes = [
                'service_agreement' => 'Service Agreement',
                'maintenance' => 'Maintenance Contract',
                'support' => 'Support Contract',
                'msp_contract' => 'MSP Contract',
                'voip_service' => 'VoIP Service',
                'security' => 'Security Services',
                'backup' => 'Backup Services',
                'monitoring' => 'Monitoring Services',
            ];

            $billingModels = [
                'fixed' => 'Fixed Price',
                'per_asset' => 'Per Asset/Device',
                'per_contact' => 'Per Contact/Seat',
                'tiered' => 'Tiered Pricing',
                'hybrid' => 'Hybrid Model',
            ];

            return view('financial.contracts.templates.create', compact('templateTypes', 'billingModels'));

        } catch (\Exception $e) {
            Log::error('Contract template create form failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load template creation form');
        }
    }

    /**
     * Store a new contract template
     */
    public function templatesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_type' => 'required|string',
            'billing_model' => 'required|string',
            'variable_fields' => 'nullable|array',
            'default_values' => 'nullable|array',
            'required_fields' => 'nullable|array',
            'asset_billing_rules' => 'nullable|array',
            'contact_billing_rules' => 'nullable|array',
            'calculation_formulas' => 'nullable|array',
            'automation_settings' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();

            $template = \App\Domains\Contract\Models\ContractTemplate::create([
                'company_id' => $user->company_id,
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'template_type' => $request->get('template_type'),
                'billing_model' => $request->get('billing_model'),
                'variable_fields' => $request->get('variable_fields'),
                'default_values' => $request->get('default_values'),
                'required_fields' => $request->get('required_fields'),
                'asset_billing_rules' => $request->get('asset_billing_rules'),
                'contact_billing_rules' => $request->get('contact_billing_rules'),
                'calculation_formulas' => $request->get('calculation_formulas'),
                'automation_settings' => $request->get('automation_settings'),
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            Log::info('Contract template created', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'user_id' => $user->id,
            ]);

            return redirect()
                ->route('financial.contracts.templates.show', $template)
                ->with('success', 'Contract template created successfully');

        } catch (\Exception $e) {
            Log::error('Contract template creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'input' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create contract template: '.$e->getMessage());
        }
    }

    /**
     * Display the specified contract template
     */
    public function templatesShow(\App\Domains\Contract\Models\ContractTemplate $template)
    {
        try {
            $template->load(['creator', 'updater', 'contracts']);

            return view('financial.contracts.templates.show', compact('template'));

        } catch (\Exception $e) {
            Log::error('Contract template show failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load contract template');
        }
    }

    /**
     * Show form for editing the specified contract template
     */
    public function templatesEdit(\App\Domains\Contract\Models\ContractTemplate $template)
    {
        try {
            $templateTypes = [
                'service_agreement' => 'Service Agreement',
                'maintenance' => 'Maintenance Contract',
                'support' => 'Support Contract',
                'msp_contract' => 'MSP Contract',
                'voip_service' => 'VoIP Service',
                'security' => 'Security Services',
                'backup' => 'Backup Services',
                'monitoring' => 'Monitoring Services',
            ];

            $billingModels = [
                'fixed' => 'Fixed Price',
                'per_asset' => 'Per Asset/Device',
                'per_contact' => 'Per Contact/Seat',
                'tiered' => 'Tiered Pricing',
                'hybrid' => 'Hybrid Model',
            ];

            return view('financial.contracts.templates.edit', compact('template', 'templateTypes', 'billingModels'));

        } catch (\Exception $e) {
            Log::error('Contract template edit form failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load template edit form');
        }
    }

    /**
     * Update the specified contract template
     */
    public function templatesUpdate(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_type' => 'required|string',
            'billing_model' => 'required|string',
            'variable_fields' => 'nullable|array',
            'default_values' => 'nullable|array',
            'required_fields' => 'nullable|array',
            'asset_billing_rules' => 'nullable|array',
            'contact_billing_rules' => 'nullable|array',
            'calculation_formulas' => 'nullable|array',
        ]);

        try {
            $template->update([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'template_type' => $request->get('template_type'),
                'billing_model' => $request->get('billing_model'),
                'variable_fields' => $request->get('variable_fields'),
                'default_values' => $request->get('default_values'),
                'required_fields' => $request->get('required_fields'),
                'asset_billing_rules' => $request->get('asset_billing_rules'),
                'contact_billing_rules' => $request->get('contact_billing_rules'),
                'calculation_formulas' => $request->get('calculation_formulas'),
                'updated_by' => Auth::id(),
            ]);

            Log::info('Contract template updated', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('financial.contracts.templates.show', $template)
                ->with('success', 'Contract template updated successfully');

        } catch (\Exception $e) {
            Log::error('Contract template update failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update contract template: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified contract template
     */
    public function templatesDestroy(\App\Domains\Contract\Models\ContractTemplate $template)
    {
        try {
            $templateName = $template->name;

            // Check if template is in use
            if ($template->contracts()->count() > 0) {
                return back()->with('error', 'Cannot delete template that is used by contracts');
            }

            $template->delete();

            Log::warning('Contract template deleted', [
                'template_id' => $template->id,
                'template_name' => $templateName,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('financial.contracts.templates.index')
                ->with('success', "Contract template '{$templateName}' deleted successfully");

        } catch (\Exception $e) {
            Log::error('Contract template deletion failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete contract template: '.$e->getMessage());
        }
    }

    // ====================================
    // PROGRAMMABLE CONTRACT FEATURES
    // ====================================

    /**
     * Show asset assignments page for per-device billing
     */
    public function assetAssignments(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        try {
            // Load contract with relationships
            $contract->load(['client', 'assetAssignments.asset']);

            // Get all client assets
            $assets = $contract->client->assets()
                ->with(['assetType', 'location'])
                ->orderBy('hostname')
                ->get();

            // Group assets by type for easier management
            $assetsByType = $assets->groupBy('asset_type');

            // Calculate current billing for assigned assets
            $assignedAssets = $contract->assetAssignments()
                ->with('asset')
                ->where('status', 'active')
                ->get();

            $billingCalculation = $this->calculateAssetBilling($contract, $assignedAssets);

            return view('financial.contracts.asset-assignments', compact(
                'contract', 'assets', 'assetsByType', 'assignedAssets', 'billingCalculation'
            ));

        } catch (\Exception $e) {
            Log::error('Asset assignments page failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load asset assignments page');
        }
    }

    /**
     * Show contact assignments page for per-seat billing
     */
    public function contactAssignments(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        try {
            // Load contract with relationships
            $contract->load(['client', 'contactAssignments.contact']);

            // Get all client contacts
            $contacts = $contract->client->contacts()
                ->orderBy('name')
                ->get();

            // Get available access tiers from contract template
            $accessTiers = [];
            if ($contract->template && $contract->template->contact_billing_rules) {
                $accessTiers = $contract->template->contact_billing_rules['access_tiers'] ?? [];
            }

            // Calculate current billing for assigned contacts
            $assignedContacts = $contract->contactAssignments()
                ->with('contact')
                ->where('status', 'active')
                ->get();

            $billingCalculation = $this->calculateContactBilling($contract, $assignedContacts);

            return view('financial.contracts.contact-assignments', compact(
                'contract', 'contacts', 'accessTiers', 'assignedContacts', 'billingCalculation'
            ));

        } catch (\Exception $e) {
            Log::error('Contact assignments page failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load contact assignments page');
        }
    }

    /**
     * Show usage tracking dashboard
     */
    public function usageDashboard(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        try {
            // Load contract with relationships
            $contract->load([
                'client',
                'template',
                'assetAssignments.asset',
                'contactAssignments.contact',
                'billingCalculations' => function ($query) {
                    $query->orderBy('billing_period', 'desc')->limit(12);
                },
            ]);

            // Get billing data for dashboard
            $billingData = $this->gatherBillingData($contract);

            return view('financial.contracts.usage-dashboard', compact(
                'contract', 'billingData'
            ));

        } catch (\Exception $e) {
            Log::error('Usage dashboard failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to load usage dashboard');
        }
    }

    /**
     * Calculate asset billing for contract
     */
    protected function calculateAssetBilling(Contract $contract, $assignedAssets)
    {
        $total = 0;
        $breakdown = [];

        if ($contract->template && $contract->template->asset_billing_rules) {
            $rules = $contract->template->asset_billing_rules;

            foreach ($assignedAssets as $assignment) {
                $asset = $assignment->asset;
                $assetType = $asset->asset_type;

                // Find rate for this asset type
                $rate = $rules['rates'][$assetType] ?? $rules['default_rate'] ?? 0;

                $total += $rate;

                if (! isset($breakdown[$assetType])) {
                    $breakdown[$assetType] = [
                        'count' => 0,
                        'rate' => $rate,
                        'total' => 0,
                    ];
                }

                $breakdown[$assetType]['count']++;
                $breakdown[$assetType]['total'] += $rate;
            }
        }

        return [
            'total_monthly' => $total,
            'breakdown_by_type' => $breakdown,
            'assigned_count' => $assignedAssets->count(),
        ];
    }

    /**
     * Calculate contact billing for contract
     */
    protected function calculateContactBilling(Contract $contract, $assignedContacts)
    {
        $total = 0;
        $breakdown = [];

        if ($contract->template && $contract->template->contact_billing_rules) {
            $rules = $contract->template->contact_billing_rules;
            $accessTiers = $rules['access_tiers'] ?? [];

            foreach ($assignedContacts as $assignment) {
                $tierName = $assignment->access_tier;
                $tier = collect($accessTiers)->firstWhere('name', $tierName);

                if ($tier) {
                    $rate = $tier['monthly_rate'] ?? 0;
                    $total += $rate;

                    if (! isset($breakdown[$tierName])) {
                        $breakdown[$tierName] = [
                            'count' => 0,
                            'rate' => $rate,
                            'total' => 0,
                            'tier' => $tier,
                        ];
                    }

                    $breakdown[$tierName]['count']++;
                    $breakdown[$tierName]['total'] += $rate;
                }
            }
        }

        return [
            'total_monthly' => $total,
            'breakdown_by_tier' => $breakdown,
            'assigned_count' => $assignedContacts->count(),
        ];
    }

    /**
     * Gather comprehensive billing data for dashboard
     */
    protected function gatherBillingData(Contract $contract)
    {
        $data = [];

        // Current MRR calculation
        $assetBilling = 0;
        $contactBilling = 0;

        if ($contract->billing_model === 'per_asset' || $contract->billing_model === 'hybrid') {
            $assignedAssets = $contract->assetAssignments()->where('status', 'active')->get();
            $assetCalc = $this->calculateAssetBilling($contract, $assignedAssets);
            $assetBilling = $assetCalc['total_monthly'];

            $data['asset_breakdown'] = $assetCalc['breakdown_by_type'];
            $data['asset_details'] = $assignedAssets->map(function ($assignment) {
                return [
                    'id' => $assignment->asset->id,
                    'hostname' => $assignment->asset->hostname,
                    'name' => $assignment->asset->name,
                    'ip_address' => $assignment->asset->ip_address,
                    'asset_type' => $assignment->asset->asset_type,
                    'is_online' => $assignment->asset->is_online ?? true,
                    'assigned_date' => $assignment->assigned_date,
                    'monthly_rate' => $assignment->monthly_rate ?? 0,
                    'period_total' => $assignment->monthly_rate ?? 0, // Simplified for now
                ];
            });
        }

        if ($contract->billing_model === 'per_contact' || $contract->billing_model === 'hybrid') {
            $assignedContacts = $contract->contactAssignments()->where('status', 'active')->get();
            $contactCalc = $this->calculateContactBilling($contract, $assignedContacts);
            $contactBilling = $contactCalc['total_monthly'];

            $data['contact_tier_breakdown'] = collect($contactCalc['breakdown_by_tier'])->map(function ($tier, $name) {
                return [
                    'id' => $name,
                    'name' => $name,
                    'contact_count' => $tier['count'],
                    'rate' => $tier['rate'],
                    'total_amount' => $tier['total'],
                ];
            })->values();

            $data['contact_details'] = $assignedContacts->map(function ($assignment) {
                return [
                    'id' => $assignment->contact->id,
                    'name' => $assignment->contact->name,
                    'email' => $assignment->contact->email,
                    'access_tier_name' => $assignment->access_tier,
                    'login_count' => 0, // Would come from actual portal usage tracking
                    'last_login' => null,
                    'assigned_date' => $assignment->assigned_date,
                    'monthly_rate' => $assignment->monthly_rate ?? 0,
                    'period_total' => $assignment->monthly_rate ?? 0, // Simplified for now
                ];
            });
        }

        $data['current_mrr'] = $assetBilling + $contactBilling;
        $data['assigned_assets_count'] = $contract->assetAssignments()->where('status', 'active')->count();
        $data['assigned_contacts_count'] = $contract->contactAssignments()->where('status', 'active')->count();

        // Billing calculations history
        $data['calculations'] = $contract->billingCalculations()->orderBy('billing_period', 'desc')->get()->map(function ($calc) {
            return [
                'id' => $calc->id,
                'billing_period' => $calc->billing_period,
                'calculated_at' => $calc->calculated_at,
                'asset_billing_amount' => $calc->asset_billing_amount ?? 0,
                'contact_billing_amount' => $calc->contact_billing_amount ?? 0,
                'total_amount' => $calc->total_amount,
                'status' => $calc->status ?? 'calculated',
            ];
        });

        return $data;
    }
}
