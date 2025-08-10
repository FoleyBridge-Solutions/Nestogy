<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\QuoteTemplate;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Category;
use App\Models\Tax;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Requests\ApproveQuoteRequest;
use App\Services\QuoteService;
use App\Services\EmailService;
use App\Services\PdfService;
use App\Services\QuoteInvoiceConversionService;
use App\Services\ContractGenerationService;
use App\Models\Contract;

/**
 * QuoteController
 * 
 * Enterprise-grade quote management controller with full CRUD operations,
 * approval workflows, versioning, and VoIP-specific features.
 */
class QuoteController extends Controller
{
    protected $quoteService;
    protected $emailService;
    protected $pdfService;
    protected $conversionService;
    protected $contractGenerationService;

    public function __construct(
        QuoteService $quoteService,
        EmailService $emailService,
        PdfService $pdfService,
        QuoteInvoiceConversionService $conversionService,
        ContractGenerationService $contractGenerationService
    ) {
        $this->quoteService = $quoteService;
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
        $this->conversionService = $conversionService;
        $this->contractGenerationService = $contractGenerationService;
    }

    /**
     * Display a listing of quotes
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Quote::with(['client', 'category', 'creator', 'approver'])
            ->where('company_id', $user->company_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->get('approval_status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->get('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('scope', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $quotes = $query->orderBy('created_at', 'desc')->paginate(25);

        // Calculate summary statistics
        $stats = $this->quoteService->getQuoteStatistics($user->company_id);

        if ($request->wantsJson()) {
            return response()->json([
                'quotes' => $quotes,
                'stats' => $stats
            ]);
        }

        return view('financial.quotes.index', compact('quotes', 'stats'));
    }

    /**
     * Show the form for creating a new quote
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
                        ->orderBy('name')
                        ->get();
        
        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
                            ->where('type', 'quote')
                            ->orderBy('name')
                            ->get();

        // Get active quote templates
        $templates = QuoteTemplate::where('company_id', $user->company_id)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get();

        // Get tax rates
        $taxes = Tax::orderBy('name')->get();
        
        // Handle pre-selected client or template
        $clientId = $request->get('client_id');
        $selectedClient = $clientId ? Client::findOrFail($clientId) : null;
        $templateId = $request->get('template_id');
        $selectedTemplate = $templateId ? QuoteTemplate::findOrFail($templateId) : null;
        
        return view('financial.quotes.create', compact(
            'clients', 
            'categories', 
            'templates',
            'taxes',
            'selectedClient', 
            'selectedTemplate'
        ));
    }

    /**
     * Store a newly created quote
     */
    public function store(StoreQuoteRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Get the client object
            $client = Client::findOrFail($validated['client_id']);
            
            // Remove client_id from data since it's passed as separate parameter
            $quoteData = \Illuminate\Support\Arr::except($validated, ['client_id']);
            
            $quote = $this->quoteService->createQuote($client, $quoteData);
            
            Log::info('Quote created', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote created successfully',
                    'quote' => $quote
                ], 201);
            }

            return redirect()
                ->route('financial.quotes.show', $quote->id)
                ->with('success', "Quote #{$quote->getFullNumber()} created successfully");

        } catch (\Exception $e) {
            Log::error('Quote creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create quote'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create quote: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified quote
     */
    public function show(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->load([
            'client',
            'category',
            'creator',
            'approver',
            'items' => function ($query) {
                $query->orderBy('order');
            },
            'approvals' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'versions' => function ($query) {
                $query->with('creator')->orderBy('version_number', 'desc');
            }
        ]);

        // Calculate quote totals
        $totals = $this->quoteService->calculateQuoteTotals($quote);

        if ($request->wantsJson()) {
            return response()->json([
                'quote' => $quote,
                'totals' => $totals
            ]);
        }

        return view('financial.quotes.show', compact('quote', 'totals'));
    }

    /**
     * Show the form for editing the specified quote
     */
    public function edit(Quote $quote)
    {
        $this->authorize('update', $quote);
        
        // Only allow editing of draft quotes or those not yet approved
        if (!$quote->isDraft() && $quote->approval_status !== Quote::APPROVAL_REJECTED) {
            return back()->with('error', 'Only draft or rejected quotes can be edited');
        }
        
        $user = Auth::user();
        
        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
                        ->orderBy('name')
                        ->get();
        
        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
                            ->where('type', 'quote')
                            ->orderBy('name')
                            ->get();

        // Get tax rates
        $taxes = Tax::orderBy('name')->get();
        
        // Load quote relationships
        $quote->load('client', 'category', 'items');
        
        return view('financial.quotes.edit', compact('quote', 'clients', 'categories', 'taxes'));
    }

    /**
     * Update the specified quote
     */
    public function update(UpdateQuoteRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        // Only allow editing of draft quotes or those not yet approved
        if (!$quote->isDraft() && $quote->approval_status !== Quote::APPROVAL_REJECTED) {
            return back()->with('error', 'Only draft or rejected quotes can be edited');
        }

        try {
            $updatedQuote = $this->quoteService->updateQuote($quote, $request->validated());
            
            Log::info('Quote updated', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote updated successfully',
                    'quote' => $updatedQuote
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', "Quote #{$quote->getFullNumber()} updated successfully");

        } catch (\Exception $e) {
            Log::error('Quote update failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update quote'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update quote: ' . $e->getMessage());
        }
    }

    /**
     * Add item to quote
     */
    public function addItem(Request $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:categories,id',
            'discount' => 'nullable|numeric|min:0'
        ]);

        try {
            $item = $this->quoteService->addQuoteItem($quote, $request->all());
            
            Log::info('Quote item added', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added successfully',
                    'item' => $item
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Item added successfully');

        } catch (\Exception $e) {
            Log::error('Quote item addition failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to add item: ' . $e->getMessage());
        }
    }

    /**
     * Update quote item
     */
    public function updateItem(Request $request, Quote $quote, InvoiceItem $item)
    {
        $this->authorize('update', $quote);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:categories,id',
            'discount' => 'nullable|numeric|min:0'
        ]);

        try {
            $updatedItem = $this->quoteService->updateQuoteItem($item, $request->all());
            
            Log::info('Quote item updated', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item updated successfully',
                    'item' => $updatedItem
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Item updated successfully');

        } catch (\Exception $e) {
            Log::error('Quote item update failed', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to update item: ' . $e->getMessage());
        }
    }

    /**
     * Delete quote item
     */
    public function deleteItem(Request $request, Quote $quote, InvoiceItem $item)
    {
        $this->authorize('update', $quote);

        try {
            $this->quoteService->deleteQuoteItem($item);
            
            Log::info('Quote item deleted', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item deleted successfully'
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Item deleted successfully');

        } catch (\Exception $e) {
            Log::error('Quote item deletion failed', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }

    /**
     * Submit quote for approval
     */
    public function submitForApproval(Request $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        try {
            $this->quoteService->submitForApproval($quote);

            Log::info('Quote submitted for approval', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote submitted for approval successfully'
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Quote submitted for approval successfully');

        } catch (\Exception $e) {
            Log::error('Quote approval submission failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Process quote approval
     */
    public function processApproval(ApproveQuoteRequest $request, Quote $quote)
    {
        $this->authorize('approve', $quote);

        try {
            $validated = $request->validated();
            
            $this->quoteService->processApproval(
                $quote,
                $validated['level'],
                $validated['action'],
                $validated['comments'] ?? null
            );

            $action = $validated['action'];
            $level = $validated['level'];

            Log::info('Quote approval processed', [
                'quote_id' => $quote->id,
                'action' => $action,
                'level' => $level,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Quote {$action}ed at {$level} level successfully"
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', "Quote {$action}ed at {$level} level successfully");

        } catch (\Exception $e) {
            Log::error('Quote approval processing failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to process approval: ' . $e->getMessage());
        }
    }

    /**
     * Send quote via email
     */
    public function sendEmail(Request $request, Quote $quote)
    {
        $this->authorize('send', $quote);

        if (!$quote->isFullyApproved() && $quote->approval_status !== Quote::APPROVAL_NOT_REQUIRED) {
            return back()->with('error', 'Quote must be approved before sending');
        }

        try {
            $this->quoteService->sendQuote($quote);
            
            Log::info('Quote emailed', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote sent successfully'
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Quote sent successfully');

        } catch (\Exception $e) {
            Log::error('Quote email failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to send quote: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for quote
     */
    public function generatePdf(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        try {
            $quote->load(['client', 'items', 'category']);
            
            $filename = $this->pdfService->generateFilename('quote', $quote->getFullNumber());
            
            Log::info('Quote PDF generated', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id()
            ]);

            return $this->pdfService->download(
                view: 'pdf.quote',
                data: ['quote' => $quote],
                filename: $filename,
                options: ['template' => 'quote']
            );

        } catch (\Exception $e) {
            Log::error('Quote PDF generation failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice(Request $request, Quote $quote)
    {
        $this->authorize('convert', $quote);

        if (!$quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before converting to invoice');
        }

        try {
            $invoice = $this->quoteService->convertToInvoice($quote);
            
            Log::info('Quote converted to invoice', [
                'quote_id' => $quote->id,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to invoice successfully',
                    'invoice' => $invoice
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', "Quote converted to invoice #{$invoice->getFullNumber()} successfully");

        } catch (\Exception $e) {
            Log::error('Quote to invoice conversion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to convert to invoice: ' . $e->getMessage());
        }
    }

    /**
     * Convert quote to recurring billing
     */
    public function convertToRecurring(Request $request, Quote $quote)
    {
        $this->authorize('convert-quotes-to-recurring');

        if (!$quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before converting to recurring billing');
        }

        $request->validate([
            'billing_frequency' => 'required|string|in:weekly,monthly,quarterly,semi-annually,annually',
            'start_date' => 'required|date|after:today',
            'contract_term' => 'nullable|integer|min:1',
            'auto_generate' => 'boolean',
            'voip_service_type' => 'nullable|string|in:hosted_pbx,sip_trunking,voip_lines,unified_communications',
            'escalation_percentage' => 'nullable|numeric|min:0|max:50',
            'escalation_frequency' => 'nullable|string|in:annual,biennial',
        ]);

        try {
            // Create recurring billing record from quote
            $recurringData = [
                'client_id' => $quote->client_id,
                'billing_frequency' => $request->get('billing_frequency'),
                'start_date' => $request->get('start_date'),
                'amount' => $quote->total_amount,
                'status' => 'active',
                'auto_generate' => $request->boolean('auto_generate', true),
                'contract_term' => $request->get('contract_term'),
                'voip_service_type' => $request->get('voip_service_type'),
                'escalation_percentage' => $request->get('escalation_percentage'),
                'escalation_frequency' => $request->get('escalation_frequency', 'annual'),
                'quote_id' => $quote->id,
                'company_id' => $quote->company_id,
                'created_by' => Auth::id(),
            ];

            // Use the RecurringBillingService to create the recurring record
            $recurringService = app(\App\Services\RecurringBillingService::class);
            $recurring = $recurringService->createFromQuote($quote, $recurringData);

            // Update quote status to indicate conversion
            $quote->update([
                'converted_to_recurring' => true,
                'recurring_id' => $recurring->id
            ]);

            Log::info('Quote converted to recurring billing', [
                'quote_id' => $quote->id,
                'recurring_id' => $recurring->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to recurring billing successfully',
                    'recurring' => $recurring
                ]);
            }

            return redirect()
                ->route('financial.recurring.show', $recurring)
                ->with('success', "Quote converted to recurring billing #{$recurring->id} successfully");

        } catch (\Exception $e) {
            Log::error('Quote to recurring billing conversion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to convert to recurring billing: ' . $e->getMessage());
        }
    }

    /**
     * Preview quote to recurring billing conversion
     */
    public function previewRecurringConversion(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->load(['client', 'items', 'category']);

        // Calculate preview data
        $previewData = [
            'quote' => $quote,
            'projected_annual_revenue' => 0,
            'billing_frequencies' => [
                'monthly' => $quote->total_amount * 12,
                'quarterly' => $quote->total_amount * 4,
                'semi-annually' => $quote->total_amount * 2,
                'annually' => $quote->total_amount
            ],
            'available_voip_services' => [
                'hosted_pbx' => 'Hosted PBX',
                'sip_trunking' => 'SIP Trunking',
                'voip_lines' => 'VoIP Lines',
                'unified_communications' => 'Unified Communications'
            ]
        ];

        if ($request->wantsJson()) {
            return response()->json($previewData);
        }

        return view('financial.quotes.recurring-preview', $previewData);
    }

    /**
     * Duplicate quote
     */
    public function duplicate(Request $request, Quote $quote)
    {
        $this->authorize('create', Quote::class);

        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $newQuote = $this->quoteService->duplicateQuote($quote, [
                'date' => $request->get('date'),
                'expire_date' => now()->addDays(30),
                'valid_until' => now()->addDays(30),
            ]);
            
            Log::info('Quote duplicated', [
                'original_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote duplicated successfully',
                    'quote' => $newQuote
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $newQuote)
                ->with('success', "Quote duplicated as #{$newQuote->getFullNumber()}");

        } catch (\Exception $e) {
            Log::error('Quote duplication failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to duplicate quote: ' . $e->getMessage());
        }
    }

    /**
     * Create revision of quote
     */
    public function createRevision(Request $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $revision = $this->quoteService->createRevision($quote, [], $request->get('reason'));
            
            Log::info('Quote revision created', [
                'original_quote_id' => $quote->id,
                'revision_quote_id' => $revision->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote revision created successfully',
                    'quote' => $revision
                ]);
            }

            return redirect()
                ->route('financial.quotes.edit', $revision)
                ->with('success', "Quote revision #{$revision->getFullNumber()} created successfully");

        } catch (\Exception $e) {
            Log::error('Quote revision creation failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to create revision: ' . $e->getMessage());
        }
    }

    /**
     * Create quote from template
     */
    public function createFromTemplate(Request $request, QuoteTemplate $template)
    {
        $this->authorize('create', Quote::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'customizations' => 'nullable|array'
        ]);

        try {
            $client = Client::findOrFail($request->get('client_id'));
            $customizations = $request->get('customizations', []);
            
            $quote = $this->quoteService->createFromTemplate($template, $client, $customizations);
            
            Log::info('Quote created from template', [
                'template_id' => $template->id,
                'quote_id' => $quote->id,
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote created from template successfully',
                    'quote' => $quote
                ]);
            }

            return redirect()
                ->route('financial.quotes.edit', $quote)
                ->with('success', "Quote #{$quote->getFullNumber()} created from template successfully");

        } catch (\Exception $e) {
            Log::error('Quote creation from template failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to create quote from template: ' . $e->getMessage());
        }
    }

    /**
     * Delete the specified quote
     */
    public function destroy(Request $request, Quote $quote)
    {
        $this->authorize('delete', $quote);

        // Only allow deletion of draft quotes
        if (!$quote->isDraft()) {
            return back()->with('error', 'Only draft quotes can be deleted');
        }

        try {
            $quoteNumber = $quote->getFullNumber();
            $quote->delete();
            
            Log::warning('Quote deleted', [
                'quote_id' => $quote->id,
                'quote_number' => $quoteNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote deleted successfully'
                ]);
            }

            return redirect()
                ->route('financial.quotes.index')
                ->with('success', "Quote #{$quoteNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Quote deletion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to delete quote: ' . $e->getMessage());
        }
    }

    /**
     * Export quotes to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $clientId = $request->get('client_id');
        
        $query = Quote::with('client')
            ->where('company_id', $user->company_id);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $quotes = $query->orderBy('number')->get();
        $filename = 'quotes-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($quotes) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Quote Number',
                'Version',
                'Client',
                'Scope',
                'Amount',
                'Date',
                'Expires',
                'Status',
                'Approval Status'
            ]);

            // CSV data
            foreach ($quotes as $quote) {
                fputcsv($file, [
                    $quote->getFullNumber(),
                    'v' . $quote->version,
                    $quote->client->name ?? '',
                    $quote->scope,
                    $quote->amount,
                    $quote->date->format('Y-m-d'),
                    $quote->expire_date ? $quote->expire_date->format('Y-m-d') : '',
                    $quote->status,
                    $quote->approval_status
                ]);
            }
            
            fclose($file);
        };

        Log::info('Quotes exported to CSV', [
            'count' => $quotes->count(),
            'client_id' => $clientId,
            'user_id' => Auth::id()
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show approval workflow interface
     */
    public function approve(Quote $quote)
    {
        $this->authorize('approve', $quote);

        $quote->load([
            'client',
            'category',
            'items',
            'approvals' => function ($query) {
                $query->with('user')->orderBy('approval_level');
            }
        ]);

        return view('financial.quotes.approve', compact('quote'));
    }

    /**
     * Convert quote to contract
     */
    public function convertToContract(Request $request, Quote $quote)
    {
        $this->authorize('convert', $quote);

        if (!$quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before converting to contract');
        }

        $request->validate([
            'contract_type' => 'required|string|in:one_time_service,recurring_service,maintenance,support,master_service,data_processing,international_service',
            'contract_template_id' => 'nullable|exists:contract_templates,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'payment_terms' => 'nullable|string|in:net_15,net_30,net_45,net_60,immediate,custom',
            'payment_schedule' => 'nullable|string|in:upfront,milestone,monthly,quarterly,annually',
            'auto_renewal' => 'boolean',
            'renewal_notice_days' => 'nullable|integer|min:1|max:365',
            'terms_and_conditions' => 'nullable|string',
            'special_provisions' => 'nullable|array',
            'compliance_requirements' => 'nullable|array',
        ]);

        try {
            $contractData = array_merge($request->validated(), [
                'title' => $quote->scope ?? 'Service Contract',
                'description' => $quote->note,
                'contract_value' => $quote->total_amount,
                'currency' => 'USD', // Default currency, can be made configurable
            ]);

            $contract = $this->conversionService->convertQuoteToContract($quote, $contractData);

            Log::info('Quote converted to contract', [
                'quote_id' => $quote->id,
                'contract_id' => $contract->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to contract successfully',
                    'contract' => $contract
                ]);
            }

            return redirect()
                ->route('contracts.show', $contract)
                ->with('success', "Quote converted to contract #{$contract->contract_number} successfully");

        } catch (\Exception $e) {
            Log::error('Quote to contract conversion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to convert to contract: ' . $e->getMessage());
        }
    }

    /**
     * Preview quote to contract conversion
     */
    public function previewContractConversion(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->load(['client', 'items', 'category']);

        // Get available contract templates
        $user = Auth::user();
        $contractTemplates = \App\Models\ContractTemplate::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $previewData = [
            'quote' => $quote,
            'contract_templates' => $contractTemplates,
            'contract_types' => [
                'one_time_service' => 'One-time Service',
                'recurring_service' => 'Recurring Service',
                'maintenance' => 'Maintenance Contract',
                'support' => 'Support Contract',
                'master_service' => 'Master Service Agreement',
                'data_processing' => 'Data Processing Agreement',
                'international_service' => 'International Service Agreement'
            ],
            'payment_terms' => [
                'net_15' => 'Net 15 Days',
                'net_30' => 'Net 30 Days',
                'net_45' => 'Net 45 Days',
                'net_60' => 'Net 60 Days',
                'immediate' => 'Immediate Payment',
                'custom' => 'Custom Terms'
            ],
            'payment_schedules' => [
                'upfront' => 'Full Payment Upfront',
                'milestone' => 'Milestone-based Payments',
                'monthly' => 'Monthly Payments',
                'quarterly' => 'Quarterly Payments',
                'annually' => 'Annual Payments'
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($previewData);
        }

        return view('financial.quotes.contract-preview', $previewData);
    }

    /**
     * Convert quote with invoice generation
     */
    public function convertWithInvoiceGeneration(Request $request, Quote $quote)
    {
        $this->authorize('convert', $quote);

        if (!$quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before conversion');
        }

        $request->validate([
            'conversion_type' => 'required|string|in:contract_only,contract_with_invoice,contract_with_recurring',
            'contract_data' => 'required|array',
            'invoice_data' => 'nullable|array',
            'recurring_data' => 'nullable|array',
        ]);

        try {
            $result = $this->conversionService->convertQuoteWithOptions($quote, $request->validated());

            Log::info('Quote converted with options', [
                'quote_id' => $quote->id,
                'conversion_type' => $request->get('conversion_type'),
                'contract_id' => $result['contract']->id ?? null,
                'invoice_id' => $result['invoice']->id ?? null,
                'recurring_id' => $result['recurring']->id ?? null,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote conversion completed successfully',
                    'result' => $result
                ]);
            }

            $successMessage = 'Quote conversion completed successfully.';
            $redirectRoute = 'contracts.show';
            $redirectParam = $result['contract'];

            if (isset($result['invoice'])) {
                $successMessage .= " Invoice #{$result['invoice']->number} created.";
            }

            if (isset($result['recurring'])) {
                $successMessage .= " Recurring billing schedule created.";
            }

            return redirect()
                ->route($redirectRoute, $redirectParam)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Quote conversion with options failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to complete conversion: ' . $e->getMessage());
        }
    }

    /**
     * Get conversion status and options
     */
    public function getConversionStatus(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $status = [
            'can_convert_to_invoice' => $quote->isAccepted() && !$quote->converted_to_invoice,
            'can_convert_to_contract' => $quote->isAccepted() && !$quote->converted_to_contract,
            'can_convert_to_recurring' => $quote->isAccepted() && !$quote->converted_to_recurring,
            'existing_conversions' => [
                'invoice' => $quote->invoice ?? null,
                'contract' => $quote->contract ?? null,
                'recurring' => $quote->recurring ?? null,
            ],
            'conversion_history' => $quote->conversions ?? [],
        ];

        return response()->json($status);
    }
}