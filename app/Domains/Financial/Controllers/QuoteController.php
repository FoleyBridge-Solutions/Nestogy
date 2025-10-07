<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Contract\Services\ContractGenerationService;
use App\Domains\Core\Services\PdfService;
use App\Domains\Email\Services\EmailService;
use App\Domains\Financial\Exceptions\FinancialException;
use App\Domains\Financial\Exceptions\QuoteExceptionHandler;
use App\Domains\Financial\Services\QuoteInvoiceConversionService;
use App\Domains\Financial\Services\QuoteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveQuoteRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Financial\QuoteCollection;
use App\Http\Resources\Financial\QuoteResource;
use App\Models\Category;
use App\Models\Client;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * QuoteController
 *
 * Enterprise-grade quote management controller with full CRUD operations,
 * approval workflows, versioning, and VoIP-specific features.
 */
class QuoteController extends Controller
{
    private const VALIDATION_NULLABLE_ARRAY = 'nullable|array';

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
     * Display a listing of quotes (optimized with eager loading)
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'client_id' => $request->get('client_id'),
                'category_id' => $request->get('category_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'search' => $request->get('search'),
            ];

            $perPage = $request->get('per_page', 25);
            $companyId = auth()->user()->company_id;

            // Use optimized query method with eager loading
            $quotes = $this->quoteService->getCompanyQuotes($companyId, $filters, $perPage);

            // Get real statistics
            $statistics = $this->quoteService->getQuoteStatistics($companyId);
            $statsFormatted = [
                'draft_quotes' => $statistics['totals']->draft_quotes ?? 0,
                'sent_quotes' => $statistics['totals']->sent_quotes ?? 0,
                'accepted_quotes' => $statistics['totals']->accepted_quotes ?? 0,
                'conversion_rate' => $statistics['conversion_rate'] ?? 0,
                'total_value' => $statistics['totals']->accepted_value ?? 0,
            ];

            if ($request->wantsJson()) {
                return ApiResponse::success(
                    new QuoteCollection($quotes),
                    'Quotes retrieved successfully'
                );
            }

            return view('financial.quotes.index', compact('quotes', 'statsFormatted') + ['stats' => $statsFormatted]);

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Show the form for creating a new quote
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get all clients for dropdown (optimized for Livewire)
        $clients = Client::where('company_id', $user->company_id)
            ->orderBy('name')
            ->select(['id', 'name', 'company_name', 'email'])
            ->get();

        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
            ->where('type', 'quote')
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();

        // Get active quote templates (optimized for Livewire)
        $templates = QuoteTemplate::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name', 'description', 'items'])
            ->get();

        // Get products for item selection
        $products = Product::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name', 'description', 'price', 'category'])
            ->limit(50) // Initial load limit for performance
            ->get();

        // Get tax rates
        $taxes = Tax::orderBy('name')->get();

        // Handle copy functionality
        $copyData = null;
        $copyFromQuote = null;
        if ($request->get('copy_from')) {
            $copyData = session('quote_copy_data');
            if ($copyData) {
                // Clear the session data after retrieving
                session()->forget('quote_copy_data');

                // Get source quote for reference
                $copyFromQuote = Quote::find($request->get('copy_from'));

                Log::info('Quote create form loaded with copy data', [
                    'copy_from_quote_id' => $request->get('copy_from'),
                    'items_count' => count($copyData['items'] ?? []),
                    'user_id' => Auth::id(),
                ]);
            }
        }

        // Handle pre-selected client or template
        $clientId = $request->get('client_id');
        $selectedClient = $clientId ? Client::findOrFail($clientId) : null;

        // If no query parameter, check session for selected client
        if (! $selectedClient) {
            $sessionClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
            if ($sessionClient) {
                $selectedClient = $sessionClient;
            }
        }
        $templateId = $request->get('template_id');
        $selectedTemplate = $templateId ? QuoteTemplate::findOrFail($templateId) : null;

        return view('financial.quotes.create', compact(
            'clients',
            'categories',
            'templates',
            'products',
            'taxes',
            'selectedClient',
            'selectedTemplate',
            'copyData',
            'copyFromQuote'
        ));
    }

    /**
     * Store a newly created quote
     */
    public function store(StoreQuoteRequest $request)
    {
        try {
            // Debug: Log the incoming data
            Log::info('Quote creation attempt', [
                'data' => $request->all(),
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
            ]);

            $quote = $this->quoteService->createQuote($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote created successfully',
                    'data' => new QuoteResource($quote),
                ], 201);
            }

            return redirect()
                ->route('financial.quotes.show', $quote->id)
                ->with('success', "Quote #{$quote->quote_number} created successfully");

        } catch (FinancialException $e) {
            Log::error('Quote creation failed (FinancialException)', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Quote creation failed (Exception)', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create quote: '.$e->getMessage(),
                    'errors' => [],
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to create quote: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified quote
     */
    public function show(Request $request, Quote $quote)
    {
        try {
            $this->authorize('view', $quote);

            $quote = $this->quoteService->findQuote($quote->id);

            // Load tax calculations for detailed breakdown
            $quote->load(['taxCalculations' => function ($query) {
                $query->where('status', '!=', 'voided')->latest();
            }]);

            if ($request->wantsJson()) {
                return ApiResponse::success(
                    (new QuoteResource($quote))->toArrayDetailed($request),
                    'Quote retrieved successfully'
                );
            }

            // Calculate totals for the view
            $totals = [
                'subtotal' => $quote->getSubtotal(),
                'discount_amount' => $quote->getDiscountAmount(),
                'tax_amount' => $quote->getTotalTax(),
                'total' => $quote->amount, // The total amount from the quote
            ];

            // Add VoIP breakdown if this quote has VoIP services
            if ($quote->hasVoIPServices()) {
                $voipBreakdown = $quote->getVoIPTaxBreakdown();
                if (! empty($voipBreakdown)) {
                    $totals['voip_breakdown'] = [
                        'setup_fees' => 0, // You may need to calculate this
                        'monthly_recurring' => 0, // You may need to calculate this
                        'equipment_costs' => 0, // You may need to calculate this
                    ];
                }
            }

            return view('financial.quotes.show', compact('quote', 'totals'));

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Show the form for editing the specified quote
     */
    public function edit(Quote $quote)
    {
        $this->authorize('update', $quote);

        // Only allow editing of draft quotes or those not yet approved
        if (! $quote->isDraft() && $quote->approval_status !== Quote::APPROVAL_REJECTED) {
            return back()->with('error', 'Only draft or rejected quotes can be edited');
        }

        $user = Auth::user();

        // Get all clients for dropdown (optimized for Livewire)
        $clients = Client::where('company_id', $user->company_id)
            ->orderBy('name')
            ->select(['id', 'name', 'company_name', 'email'])
            ->get();

        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
            ->where('type', 'quote')
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();

        // Get active quote templates (optimized for Livewire)
        $templates = QuoteTemplate::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name', 'description', 'items'])
            ->get();

        // Get products for item selection
        $products = Product::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name', 'description', 'price', 'category'])
            ->limit(50) // Initial load limit for performance
            ->get();

        // Get tax rates
        $taxes = Tax::orderBy('name')->get();

        // Load quote relationships
        $quote->load('client', 'category', 'items');

        return view('financial.quotes.edit', compact(
            'quote',
            'clients',
            'categories',
            'templates',
            'products',
            'taxes'
        ));
    }

    /**
     * Update the specified quote
     */
    public function update(UpdateQuoteRequest $request, Quote $quote)
    {
        try {
            $this->authorize('update', $quote);

            $updatedQuote = $this->quoteService->updateQuote($quote, $request->validated());

            if ($request->wantsJson()) {
                return ApiResponse::updated(
                    new QuoteResource($updatedQuote),
                    'Quote updated successfully'
                );
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', "Quote #{$quote->quote_number} updated successfully");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
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
            'discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $item = $this->quoteService->addQuoteItem($quote, $request->all());

            Log::info('Quote item added', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added successfully',
                    'item' => $item,
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Item added successfully');

        } catch (\Exception $e) {
            Log::error('Quote item addition failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to add item: '.$e->getMessage());
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
            'discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $updatedItem = $this->quoteService->updateQuoteItem($item, $request->all());

            Log::info('Quote item updated', [
                'quote_id' => $quote->id,
                'item_id' => $item->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item updated successfully',
                    'item' => $updatedItem,
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
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update item: '.$e->getMessage());
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
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item deleted successfully',
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
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete item: '.$e->getMessage());
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
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote submitted for approval successfully',
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Quote submitted for approval successfully');

        } catch (\Exception $e) {
            Log::error('Quote approval submission failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to submit for approval: '.$e->getMessage());
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
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Quote {$action}ed at {$level} level successfully",
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', "Quote {$action}ed at {$level} level successfully");

        } catch (\Exception $e) {
            Log::error('Quote approval processing failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to process approval: '.$e->getMessage());
        }
    }

    /**
     * Send quote via email
     */
    public function sendEmail(Request $request, Quote $quote)
    {
        $this->authorize('send', $quote);

        if (! $quote->isFullyApproved() && $quote->approval_status !== Quote::APPROVAL_NOT_REQUIRED) {
            return back()->with('error', 'Quote must be approved before sending');
        }

        try {
            $this->quoteService->sendQuote($quote);

            Log::info('Quote emailed', [
                'quote_id' => $quote->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote sent successfully',
                ]);
            }

            return redirect()
                ->route('financial.quotes.show', $quote)
                ->with('success', 'Quote sent successfully');

        } catch (\Exception $e) {
            Log::error('Quote email failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to send quote: '.$e->getMessage());
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
                'user_id' => Auth::id(),
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
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice(Request $request, Quote $quote)
    {
        $this->authorize('convert', $quote);

        if (! $quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before converting to invoice');
        }

        try {
            $invoice = $this->quoteService->convertToInvoice($quote);

            Log::info('Quote converted to invoice', [
                'quote_id' => $quote->id,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to invoice successfully',
                    'invoice' => $invoice,
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', "Quote converted to invoice #{$invoice->getFullNumber()} successfully");

        } catch (\Exception $e) {
            Log::error('Quote to invoice conversion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to convert to invoice: '.$e->getMessage());
        }
    }

    /**
     * Convert quote to recurring billing
     */
    public function convertToRecurring(Request $request, Quote $quote)
    {
        $this->authorize('convert-quotes-to-recurring');

        if (! $quote->isAccepted()) {
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
            $recurringService = app(\App\Domains\Financial\Services\RecurringBillingService::class);
            $recurring = $recurringService->createFromQuote($quote, $recurringData);

            // Update quote status to indicate conversion
            $quote->update([
                'converted_to_recurring' => true,
                'recurring_id' => $recurring->id,
            ]);

            Log::info('Quote converted to recurring billing', [
                'quote_id' => $quote->id,
                'recurring_id' => $recurring->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to recurring billing successfully',
                    'recurring' => $recurring,
                ]);
            }

            return redirect()
                ->route('financial.recurring.show', $recurring)
                ->with('success', "Quote converted to recurring billing #{$recurring->id} successfully");

        } catch (\Exception $e) {
            Log::error('Quote to recurring billing conversion failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to convert to recurring billing: '.$e->getMessage());
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
                'annually' => $quote->total_amount,
            ],
            'available_voip_services' => [
                'hosted_pbx' => 'Hosted PBX',
                'sip_trunking' => 'SIP Trunking',
                'voip_lines' => 'VoIP Lines',
                'unified_communications' => 'Unified Communications',
            ],
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
        try {
            $this->authorize('create', Quote::class);

            $overrides = [
                'date' => $request->get('date', now()->format('Y-m-d')),
                'expire_date' => now()->addDays(30)->format('Y-m-d'),
            ];

            $newQuote = $this->quoteService->duplicateQuote($quote, $overrides);

            if ($request->wantsJson()) {
                return ApiResponse::created(
                    new QuoteResource($newQuote),
                    'Quote duplicated successfully'
                );
            }

            return redirect()
                ->route('financial.quotes.show', $newQuote)
                ->with('success', "Quote duplicated as #{$newQuote->quote_number}");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Copy quote - redirects to create form with pre-filled data
     */
    public function copy(Request $request, Quote $quote)
    {
        try {
            $this->authorize('view', $quote);
            $this->authorize('create', Quote::class);

            // Prepare quote data for copying
            $copyData = $this->quoteService->prepareQuoteForCopy($quote);

            // Build query parameters for the create route
            $queryParams = [
                'copy_from' => $quote->id,
                'client_id' => $quote->client_id,
            ];

            Log::info('Quote copy initiated', [
                'source_quote_id' => $quote->id,
                'source_quote_number' => $quote->getFullNumber(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote prepared for copying',
                    'redirect_url' => route('financial.quotes.create', $queryParams),
                    'copy_data' => $copyData,
                ]);
            }

            // Store copy data in session for the create form
            session(['quote_copy_data' => $copyData]);

            return redirect()
                ->route('financial.quotes.create', $queryParams)
                ->with('info', "Copying quote #{$quote->getFullNumber()}. You can modify any details before saving.");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            Log::error('Quote copy failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to copy quote: '.$e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to copy quote: '.$e->getMessage());
        }
    }

    /**
     * Create revision of quote
     */
    public function createRevision(Request $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $revision = $this->quoteService->createRevision($quote, [], $request->get('reason'));

            Log::info('Quote revision created', [
                'original_quote_id' => $quote->id,
                'revision_quote_id' => $revision->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote revision created successfully',
                    'quote' => $revision,
                ]);
            }

            return redirect()
                ->route('financial.quotes.edit', $revision)
                ->with('success', "Quote revision #{$revision->getFullNumber()} created successfully");

        } catch (\Exception $e) {
            Log::error('Quote revision creation failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to create revision: '.$e->getMessage());
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
            'customizations' => self::VALIDATION_NULLABLE_ARRAY,
        ]);

        try {
            $client = Client::findOrFail($request->get('client_id'));
            $customizations = $request->get('customizations', []);

            $quote = $this->quoteService->createFromTemplate($template, $client, $customizations);

            Log::info('Quote created from template', [
                'template_id' => $template->id,
                'quote_id' => $quote->id,
                'client_id' => $client->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote created from template successfully',
                    'quote' => $quote,
                ]);
            }

            return redirect()
                ->route('financial.quotes.edit', $quote)
                ->with('success', "Quote #{$quote->getFullNumber()} created from template successfully");

        } catch (\Exception $e) {
            Log::error('Quote creation from template failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to create quote from template: '.$e->getMessage());
        }
    }

    /**
     * Delete the specified quote
     */
    public function destroy(Request $request, Quote $quote)
    {
        try {
            $this->authorize('delete', $quote);

            $this->quoteService->deleteQuote($quote);

            if ($request->wantsJson()) {
                return ApiResponse::deleted('Quote deleted successfully');
            }

            return redirect()
                ->route('financial.quotes.index')
                ->with('success', "Quote #{$quote->quote_number} deleted successfully");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Cancel the specified quote
     */
    public function cancel(Request $request, Quote $quote)
    {
        try {
            $this->authorize('cancel', $quote);

            // Validate that quote can be cancelled
            if (in_array($quote->status, [Quote::STATUS_CANCELLED, Quote::STATUS_EXPIRED, Quote::STATUS_CONVERTED])) {
                throw new FinancialException('Quote cannot be cancelled in its current status.', 'invalid_status');
            }

            // Update quote status to cancelled
            $quote->update([
                'status' => Quote::STATUS_CANCELLED,
            ]);

            // Log the cancellation
            Log::info('Quote cancelled', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->getFullNumber(),
                'previous_status' => $quote->getOriginal('status'),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote cancelled successfully',
                    'quote' => [
                        'id' => $quote->id,
                        'status' => $quote->status,
                        'number' => $quote->getFullNumber(),
                    ],
                ]);
            }

            return redirect()
                ->route('financial.quotes.index')
                ->with('success', "Quote #{$quote->getFullNumber()} cancelled successfully");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
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
        $filename = 'quotes-'.now()->format('Y-m-d').'.csv';

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
                'Approval Status',
            ]);

            // CSV data
            foreach ($quotes as $quote) {
                fputcsv($file, [
                    $quote->getFullNumber(),
                    'v'.$quote->version,
                    $quote->client->name ?? '',
                    $quote->scope,
                    $quote->amount,
                    $quote->date->format('Y-m-d'),
                    $quote->expire_date ? $quote->expire_date->format('Y-m-d') : '',
                    $quote->status,
                    $quote->approval_status,
                ]);
            }

            fclose($file);
        };

        Log::info('Quotes exported to CSV', [
            'count' => $quotes->count(),
            'client_id' => $clientId,
            'user_id' => Auth::id(),
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
            },
        ]);

        return view('financial.quotes.approve', compact('quote'));
    }

    /**
     * Convert quote with invoice generation
     */
    public function convertWithInvoiceGeneration(Request $request, Quote $quote)
    {
        $this->authorize('convert', $quote);

        if (! $quote->isAccepted()) {
            return back()->with('error', 'Quote must be accepted before conversion');
        }

        $request->validate([
            'conversion_type' => 'required|string|in:contract_only,contract_with_invoice,contract_with_recurring',
            'contract_data' => 'required|array',
            'invoice_data' => self::VALIDATION_NULLABLE_ARRAY,
            'recurring_data' => self::VALIDATION_NULLABLE_ARRAY,
        ]);

        try {
            $result = $this->conversionService->convertQuoteWithOptions($quote, $request->validated());

            Log::info('Quote converted with options', [
                'quote_id' => $quote->id,
                'conversion_type' => $request->get('conversion_type'),
                'contract_id' => $result['contract']->id ?? null,
                'invoice_id' => $result['invoice']->id ?? null,
                'recurring_id' => $result['recurring']->id ?? null,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote conversion completed successfully',
                    'result' => $result,
                ]);
            }

            $successMessage = 'Quote conversion completed successfully.';
            $redirectRoute = 'contracts.show';
            $redirectParam = $result['contract'];

            if (isset($result['invoice'])) {
                $successMessage .= " Invoice #{$result['invoice']->number} created.";
            }

            if (isset($result['recurring'])) {
                $successMessage .= ' Recurring billing schedule created.';
            }

            return redirect()
                ->route($redirectRoute, $redirectParam)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Quote conversion with options failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to complete conversion: '.$e->getMessage());
        }
    }

    /**
     * Get conversion status and options
     */
    public function getConversionStatus(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $status = [
            'can_convert_to_invoice' => $quote->isAccepted() && ! $quote->converted_to_invoice,
            'can_convert_to_contract' => $quote->isAccepted() && ! $quote->converted_to_contract,
            'can_convert_to_recurring' => $quote->isAccepted() && ! $quote->converted_to_recurring,
            'existing_conversions' => [
                'invoice' => $quote->invoice ?? null,
                'contract' => $quote->contract ?? null,
                'recurring' => $quote->recurring ?? null,
            ],
            'conversion_history' => $quote->conversions ?? [],
        ];

        return response()->json($status);
    }

    /**
     * Auto-save quote draft
     */
    public function autoSave(Request $request)
    {
        $request->validate([
            'quote_id' => 'nullable|exists:quotes,id',
            'document' => 'required|array',
        ]);

        try {
            $result = $this->quoteService->autoSave($request->input('document'));

            return ApiResponse::success($result, 'Draft auto-saved successfully');

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Generate PDF preview
     */
    public function previewPdf(Request $request)
    {
        $request->validate([
            'document' => 'required|array',
            'preview' => 'boolean',
        ]);

        try {
            $data = $request->input('document');

            // Create temporary quote object for preview
            $quote = new Quote($data);
            $quote->items = collect($data['items'] ?? [])->map(function ($item) {
                return (object) $item;
            });

            // Generate preview URL (implement based on your PDF service)
            $previewUrl = $this->pdfService->generatePreviewUrl('quote', $data);
            $downloadUrl = $this->pdfService->generateDownloadUrl('quote', $data);

            return response()->json([
                'success' => true,
                'preview_url' => $previewUrl,
                'download_url' => $downloadUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Quote PDF preview failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PDF preview generation failed',
            ], 500);
        }
    }

    /**
     * Email PDF
     */
    public function emailPdf(Request $request)
    {
        $request->validate([
            'document' => 'required|array',
            'recipient_email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'cc_self' => 'boolean',
        ]);

        try {
            $data = $request->input('document');

            // Create temporary quote object for emailing
            $quote = new Quote($data);
            $quote->items = collect($data['items'] ?? [])->map(function ($item) {
                return (object) $item;
            });

            // Send email (implement based on your email service)
            $this->emailService->sendQuotePdf(
                $quote,
                $request->input('recipient_email'),
                $request->input('subject', 'Quote from '.config('app.name')),
                $request->input('message', ''),
                $request->boolean('cc_self', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Quote emailed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Quote email failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email sending failed',
            ], 500);
        }
    }

    /**
     * Bulk update quote statuses (optimized)
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'quote_ids' => 'required|array|min:1',
            'quote_ids.*' => 'integer|exists:quotes,id',
            'status' => 'required|string|in:draft,sent,accepted,rejected,expired',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $updated = $this->quoteService->bulkUpdateStatus(
                $request->input('quote_ids'),
                $request->input('status'),
                $companyId
            );

            if ($request->wantsJson()) {
                return ApiResponse::success([
                    'updated_count' => $updated,
                ], "Successfully updated {$updated} quotes");
            }

            return redirect()->back()->with('success', "Successfully updated {$updated} quotes");

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Get quote statistics (optimized)
     */
    public function statistics(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $statistics = $this->quoteService->getQuoteStatistics($companyId);

            if ($request->wantsJson()) {
                return ApiResponse::success($statistics, 'Statistics retrieved successfully');
            }

            return view('financial.quotes.statistics', compact('statistics'));

        } catch (FinancialException $e) {
            return QuoteExceptionHandler::handle($e, $request);
        } catch (\Exception $e) {
            $exception = QuoteExceptionHandler::normalize($e);

            return QuoteExceptionHandler::handle($exception, $request);
        }
    }

    /**
     * Search products, services, and bundles for AJAX calls
     */
    public function searchProducts(Request $request)
    {
        $searchService = app(\App\Domains\Financial\Services\ProductSearchService::class);

        $filters = $request->only([
            'search', 'category', 'billing_model', 'type',
            'min_price', 'max_price', 'sort_by', 'sort_order',
        ]);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        $type = $request->get('type', 'products'); // Default to products

        // Get the appropriate data based on the requested type
        switch ($type) {
            case 'services':
                $items = $searchService->searchServices($filters, $client);

                return response()->json([
                    'success' => true,
                    'services' => $items->toArray(),
                    'total' => $items->count(),
                ]);

            case 'bundles':
                $items = $searchService->searchBundles($filters, $client);

                return response()->json([
                    'success' => true,
                    'bundles' => $items->toArray(),
                    'total' => $items->count(),
                ]);

            case 'products':
            default:
                $items = $searchService->searchProducts($filters, $client);

                return response()->json([
                    'success' => true,
                    'products' => $items->toArray(),
                    'total' => $items->count(),
                ]);
        }
    }

    /**
     * Search clients for AJAX calls
     */
    public function searchClients(Request $request)
    {
        $clientService = app(\App\Domains\Client\Services\ClientService::class);

        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $clients = $clientService->searchClients($query, $limit);

        return response()->json([
            'success' => true,
            'clients' => $clients,
        ]);
    }

    /**
     * Get product categories for AJAX calls
     */
    public function getProductCategories(Request $request)
    {
        $categories = Product::where('company_id', auth()->user()->company_id)
            ->whereNotNull('category_id')
            ->with('category')
            ->get()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Get product details for AJAX calls
     */
    public function getProductDetails(Product $product, Request $request)
    {
        // Ensure product belongs to current company
        if ($product->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        try {
            $product->load(['service', 'category']);

            // For now, return basic product details
            // Can be enhanced later with compatible products, pricing tiers, etc.
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'category' => $product->category?->name,
                    'base_price' => $product->base_price,
                    'billing_cycle' => $product->billing_cycle,
                    'features' => $product->features ?? [],
                    'pricing_tiers' => [],
                    'availability' => ['status' => 'available'],
                ],
                'compatible_products' => [],
                'pricing_tiers' => [],
                'availability' => ['status' => 'available'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading product details',
            ], 500);
        }
    }

    /**
     * Get active keyboard shortcuts for AJAX calls
     */
    public function getActiveShortcuts(Request $request)
    {
        $shortcuts = [
            [
                'key' => 'ctrl+s',
                'action' => 'save',
                'description' => 'Save quote',
            ],
            [
                'key' => 'ctrl+n',
                'action' => 'new',
                'description' => 'New quote',
            ],
            [
                'key' => '/',
                'action' => 'search',
                'description' => 'Focus search',
            ],
            [
                'key' => 'ctrl+d',
                'action' => 'duplicate',
                'description' => 'Duplicate quote',
            ],
            [
                'key' => 'escape',
                'action' => 'cancel',
                'description' => 'Cancel/Close',
            ],
        ];

        return response()->json([
            'shortcuts' => $shortcuts,
            'enabled' => true,
        ]);
    }
}
