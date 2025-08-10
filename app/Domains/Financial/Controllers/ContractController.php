<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractSignature;
use App\Models\ContractMilestone;
use App\Models\ContractAmendment;
use App\Models\Client;
use App\Models\Quote;
use App\Services\ContractGenerationService;
use App\Services\QuoteInvoiceConversionService;
use App\Services\DigitalSignatureService;
use Carbon\Carbon;

/**
 * ContractController
 * 
 * Comprehensive contract management with generation, digital signatures,
 * milestone tracking, amendments, and VoIP-specific contract handling.
 */
class ContractController extends Controller
{
    protected $contractGenerationService;
    protected $conversionService;
    protected $signatureService;

    public function __construct(
        ContractGenerationService $contractGenerationService,
        QuoteInvoiceConversionService $conversionService,
        DigitalSignatureService $signatureService = null
    ) {
        $this->contractGenerationService = $contractGenerationService;
        $this->conversionService = $conversionService;
        $this->signatureService = $signatureService;
    }

    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Contract::with(['client', 'quote', 'template'])
            ->where('company_id', $user->company_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->get('contract_type'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        if ($request->filled('signature_status')) {
            $query->where('signature_status', $request->get('signature_status'));
        }

        if ($request->filled('start_date_from')) {
            $query->where('start_date', '>=', $request->get('start_date_from'));
        }

        if ($request->filled('start_date_to')) {
            $query->where('start_date', '<=', $request->get('start_date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(25);

        // Calculate dashboard statistics
        $statistics = $this->getContractStatistics($user->company_id);

        if ($request->wantsJson()) {
            return response()->json([
                'contracts' => $contracts,
                'statistics' => $statistics
            ]);
        }

        return view('financial.contracts.index', compact('contracts', 'statistics'));
    }

    /**
     * Show the form for creating a new contract
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
                        ->orderBy('name')
                        ->get();
        
        // Get active contract templates
        $templates = ContractTemplate::where('company_id', $user->company_id)
                                   ->where('status', 'active')
                                   ->orderBy('name')
                                   ->get();
        
        // Handle pre-selected quote or client
        $quoteId = $request->get('quote_id');
        $quote = $quoteId ? Quote::findOrFail($quoteId) : null;
        $selectedClient = $quote ? $quote->client : null;
        
        return view('financial.contracts.create', compact('clients', 'templates', 'quote', 'selectedClient'));
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'contract_type' => 'required|in:' . implode(',', array_keys(Contract::getAvailableTypes())),
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'contract_value' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'template_id' => 'nullable|exists:contract_templates,id',
            'quote_id' => 'nullable|exists:quotes,id',
        ]);

        try {
            $client = Client::findOrFail($request->client_id);
            $validated = $request->validated();

            if ($request->quote_id) {
                // Generate from quote
                $quote = Quote::findOrFail($request->quote_id);
                $template = ContractTemplate::find($request->template_id);
                
                if (!$template) {
                    throw new \Exception('Template is required for quote-based contracts');
                }

                $contract = $this->contractGenerationService->generateFromQuote($quote, $template, $validated);
            } elseif ($request->template_id) {
                // Generate from template
                $template = ContractTemplate::findOrFail($request->template_id);
                $contract = $this->contractGenerationService->generateFromTemplate($client, $template, $validated);
            } else {
                // Create custom contract
                $contract = $this->contractGenerationService->createCustomContract($validated);
            }

            Log::info('Contract created', [
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract created successfully',
                    'contract' => $contract
                ], 201);
            }

            return redirect()
                ->route('financial.contracts.show', $contract->id)
                ->with('success', "Contract {$contract->contract_number} created successfully");

        } catch (\Exception $e) {
            Log::error('Contract creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create contract: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create contract: ' . $e->getMessage());
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
            'invoices' => function ($query) {
                $query->orderBy('date', 'desc');
            }
        ]);

        // Calculate contract metrics
        $metrics = $this->calculateContractMetrics($contract);

        // Get upcoming milestones
        $upcomingMilestones = $contract->contractMilestones()
            ->where('status', '!=', ContractMilestone::STATUS_COMPLETED)
            ->where('planned_completion_date', '>=', now())
            ->orderBy('planned_completion_date')
            ->limit(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'contract' => $contract,
                'metrics' => $metrics,
                'upcoming_milestones' => $upcomingMilestones
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
        
        // Only allow editing of draft contracts
        if (!in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_REVIEW])) {
            return back()->with('error', 'Only draft and pending review contracts can be edited');
        }
        
        $user = Auth::user();
        
        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
                        ->orderBy('name')
                        ->get();
        
        // Get active contract templates
        $templates = ContractTemplate::where('company_id', $user->company_id)
                                   ->where('status', 'active')
                                   ->orderBy('name')
                                   ->get();
        
        return view('financial.contracts.edit', compact('contract', 'clients', 'templates'));
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        // Only allow editing of draft contracts
        if (!in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_REVIEW])) {
            return back()->with('error', 'Only draft and pending review contracts can be edited');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'contract_value' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'payment_terms' => 'nullable|string',
            'renewal_type' => 'required|in:' . implode(',', [
                Contract::RENEWAL_NONE,
                Contract::RENEWAL_MANUAL,
                Contract::RENEWAL_AUTOMATIC,
                Contract::RENEWAL_NEGOTIATED,
            ]),
        ]);

        try {
            $contract = $this->contractGenerationService->regenerateContract(
                $contract, 
                $request->validated()
            );
            
            Log::info('Contract updated', [
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract updated successfully',
                    'contract' => $contract
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', "Contract {$contract->contract_number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Contract update failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contract: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update contract: ' . $e->getMessage());
        }
    }

    /**
     * Generate contract from quote
     */
    public function generateFromQuote(Request $request, Quote $quote)
    {
        $this->authorize('create', Contract::class);

        $request->validate([
            'template_id' => 'required|exists:contract_templates,id',
            'contract_data' => 'required|array',
            'conversion_type' => 'required|in:contract_with_invoice,milestone_invoicing,recurring_setup',
        ]);

        try {
            $template = ContractTemplate::findOrFail($request->template_id);
            
            switch ($request->conversion_type) {
                case 'contract_with_invoice':
                    $conversion = $this->conversionService->convertToContractWithInvoice(
                        $quote, 
                        $request->contract_data,
                        $request->invoice_options ?? []
                    );
                    break;
                    
                case 'milestone_invoicing':
                    $conversion = $this->conversionService->convertToMilestoneInvoicing(
                        $quote,
                        $request->contract_data,
                        $request->milestone_schedule ?? []
                    );
                    break;
                    
                case 'recurring_setup':
                    $conversion = $this->conversionService->convertToRecurringInvoices(
                        $quote,
                        $request->contract_data,
                        $request->recurring_options ?? []
                    );
                    break;
                    
                default:
                    throw new \Exception('Invalid conversion type');
            }

            Log::info('Contract generated from quote', [
                'quote_id' => $quote->id,
                'contract_id' => $conversion->contract_id,
                'conversion_type' => $request->conversion_type,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract generated successfully from quote',
                'conversion' => $conversion,
                'contract_id' => $conversion->contract_id
            ]);

        } catch (\Exception $e) {
            Log::error('Contract generation from quote failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send contract for signature
     */
    public function sendForSignature(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status !== Contract::STATUS_PENDING_SIGNATURE) {
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
                'user_id' => Auth::id()
            ]);

            return back()->with('success', "Contract sent for signature to {$sentCount} recipient(s)");

        } catch (\Exception $e) {
            Log::error('Failed to send contract for signature', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to send contract for signature: ' . $e->getMessage());
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
                'payload' => $request->all()
            ]);
            
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update contract status
     */
    public function updateStatus(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Contract::getAvailableStatuses())),
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $oldStatus = $contract->status;
            $newStatus = $request->status;
            $reason = $request->reason;

            // Handle special status transitions
            switch ($newStatus) {
                case Contract::STATUS_ACTIVE:
                    $contract->markAsActive();
                    break;
                    
                case Contract::STATUS_TERMINATED:
                    $contract->terminate($reason);
                    break;
                    
                case Contract::STATUS_SUSPENDED:
                    $contract->suspend($reason);
                    break;
                    
                default:
                    $contract->update(['status' => $newStatus]);
                    break;
            }

            Log::info('Contract status updated', [
                'contract_id' => $contract->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Contract status updated to {$newStatus}"
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', "Contract status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Contract status update failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to update contract status: ' . $e->getMessage());
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
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract amendment created successfully',
                'amendment' => $amendment
            ]);

        } catch (\Exception $e) {
            Log::error('Contract amendment creation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create amendment: ' . $e->getMessage()
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
                'user_id' => Auth::id()
            ]);

            return response()->download(storage_path('app/' . $pdfPath));

        } catch (\Exception $e) {
            Log::error('Contract PDF generation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Delete the specified contract
     */
    public function destroy(Request $request, Contract $contract)
    {
        $this->authorize('delete', $contract);

        // Only allow deletion of draft contracts
        if ($contract->status !== Contract::STATUS_DRAFT) {
            return back()->with('error', 'Only draft contracts can be deleted');
        }

        try {
            $contractNumber = $contract->contract_number;
            $contract->delete();
            
            Log::warning('Contract deleted', [
                'contract_id' => $contract->id,
                'contract_number' => $contractNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract deleted successfully'
                ]);
            }

            return redirect()
                ->route('financial.contracts.index')
                ->with('success', "Contract {$contractNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Contract deletion failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to delete contract: ' . $e->getMessage());
        }
    }

    /**
     * Get contract statistics
     */
    protected function getContractStatistics(int $companyId): array
    {
        $contracts = Contract::where('company_id', $companyId);

        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', Contract::STATUS_ACTIVE)->count(),
            'pending_signature' => $contracts->where('signature_status', Contract::SIGNATURE_PENDING)->count(),
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
}