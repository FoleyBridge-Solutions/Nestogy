<?php

namespace App\Domains\Financial\Controllers;

use App\Contracts\Services\EmailServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Services\ContractGenerationService;
use App\Domains\Financial\Services\InvoiceService;
use App\Domains\Financial\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\UsesSelectedClient;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\QuoteInvoiceConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use UsesSelectedClient;

    protected $invoiceService;

    protected $paymentService;

    protected $emailService;

    protected $pdfService;

    protected $conversionService;

    protected $contractGenerationService;

    public function __construct(
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        EmailServiceInterface $emailService,
        PdfServiceInterface $pdfService,
        QuoteInvoiceConversionService $conversionService,
        ContractGenerationService $contractGenerationService
    ) {
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
        $this->conversionService = $conversionService;
        $this->contractGenerationService = $contractGenerationService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        // Get the selected client from session
        $client = $this->getSelectedClient();
        
        // Calculate statistics based on selected client
        $companyId = Auth::user()->company_id;
        $baseQuery = Invoice::where('company_id', $companyId);
        
        if ($client) {
            $baseQuery->where('client_id', $client->id);
        }
        
        $stats = [
            'total_revenue' => (clone $baseQuery)->whereIn('status', ['paid', 'partial'])->sum('amount'),
            'outstanding' => (clone $baseQuery)->whereIn('status', ['sent', 'partial'])->sum('amount'),
            'overdue' => (clone $baseQuery)
                ->whereIn('status', ['sent', 'partial'])
                ->where('due_date', '<', now())
                ->sum('amount'),
            'total_count' => (clone $baseQuery)->count()
        ];
        
        // The Livewire component handles all the logic now
        // We keep the JSON response for API compatibility
        if ($request->wantsJson()) {
            $user = Auth::user();
            $query = Invoice::with(['client', 'category'])
                ->where('company_id', $user->company_id);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Apply client filter from session if client is selected
            $this->applyClientFilter($query);

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
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $invoices = $query->orderBy('created_at', 'desc')->paginate(25);

            // Calculate totals
            $totals = [
                'draft' => Invoice::where('company_id', $user->company_id)->where('status', 'Draft')->sum('amount'),
                'sent' => Invoice::where('company_id', $user->company_id)->where('status', 'Sent')->sum('amount'),
                'paid' => Invoice::where('company_id', $user->company_id)->where('status', 'Paid')->sum('amount'),
                'overdue' => Invoice::where('company_id', $user->company_id)
                    ->where('status', 'Sent')
                    ->where('due_date', '<', now())
                    ->sum('amount'),
            ];

            return response()->json([
                'invoices' => $invoices,
                'totals' => $totals,
            ]);
        }

        return view('financial.invoices.index', compact('client', 'stats'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create(Request $request)
    {
        // The Livewire component handles all the logic now
        return view('financial.invoices.create');
    }

    /**
     * Store a newly created invoice
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            $validated = $request->validated();

            // Get the client object
            $client = Client::findOrFail($validated['client_id']);

            // Remove client_id from data since it's passed as separate parameter
            $invoiceData = \Illuminate\Support\Arr::except($validated, ['client_id']);

            $invoice = $this->invoiceService->createInvoice($client, $invoiceData);

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'invoice' => $invoice,
                ], 201);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice->id)
                ->with('success', "Invoice #{$invoice->number} created successfully");

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create invoice',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create invoice');
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'client',
            'category',
            'items' => function ($query) {
                $query->orderBy('order');
            },
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            },
            'taxCalculations' => function ($query) {
                $query->where('status', '!=', 'voided')->latest();
            },
        ]);

        // Calculate invoice totals
        $totals = $this->invoiceService->calculateInvoiceTotals($invoice);

        if ($request->wantsJson()) {
            return response()->json([
                'invoice' => $invoice,
                'totals' => $totals,
            ]);
        }

        return view('financial.invoices.show', compact('invoice', 'totals'));
    }

    /**
     * Show the form for editing the specified invoice
     */
    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Only allow editing of draft invoices
        if ($invoice->status !== 'Draft') {
            return back()->with('error', 'Only draft invoices can be edited');
        }

        $user = Auth::user();

        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Load invoice relationships
        $invoice->load('client', 'category', 'items');

        return view('financial.invoices.edit', compact('invoice', 'clients', 'categories'));
    }

    /**
     * Update the specified invoice
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Only allow editing of draft invoices
        if ($invoice->status !== 'Draft') {
            return back()->with('error', 'Only draft invoices can be edited');
        }

        try {
            $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

            Log::info('Invoice updated', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice updated successfully',
                    'invoice' => $updatedInvoice,
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice #{$invoice->number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Invoice update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update invoice',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update invoice');
        }
    }

    /**
     * Add item to invoice
     */
    public function addItem(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:categories,id',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $item = $this->invoiceService->addInvoiceItem($invoice, $request->all());

            Log::info('Invoice item added', [
                'invoice_id' => $invoice->id,
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
                ->route('invoices.show', $invoice)
                ->with('success', 'Item added successfully');

        } catch (\Exception $e) {
            Log::error('Invoice item addition failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to add item');
        }
    }

    /**
     * Update invoice item
     */
    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:categories,id',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $updatedItem = $this->invoiceService->updateInvoiceItem($item, $request->all());

            Log::info('Invoice item updated', [
                'invoice_id' => $invoice->id,
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
                ->route('invoices.show', $invoice)
                ->with('success', 'Item updated successfully');

        } catch (\Exception $e) {
            Log::error('Invoice item update failed', [
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update item');
        }
    }

    /**
     * Delete invoice item
     */
    public function deleteItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->authorize('update', $invoice);

        try {
            $this->invoiceService->deleteInvoiceItem($item);

            Log::info('Invoice item deleted', [
                'invoice_id' => $invoice->id,
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
                ->route('invoices.show', $invoice)
                ->with('success', 'Item deleted successfully');

        } catch (\Exception $e) {
            Log::error('Invoice item deletion failed', [
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete item');
        }
    }

    /**
     * Add payment to invoice
     */
    public function addPayment(StorePaymentRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        try {
            $payment = $this->paymentService->createPayment($invoice, $request->validated());

            // Send receipt email if requested
            if ($request->get('email_receipt')) {
                $this->emailService->sendPaymentReceiptEmail($payment);
            }

            Log::info('Payment added to invoice', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment added successfully',
                    'payment' => $payment,
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Payment added successfully');

        } catch (\Exception $e) {
            Log::error('Payment addition failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to add payment');
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'status' => 'required|in:Draft,Sent,Paid,Cancelled',
        ]);

        try {
            $oldStatus = $invoice->status;
            $newStatus = $request->get('status');

            $this->invoiceService->updateInvoiceStatus($invoice, $newStatus);

            Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Invoice status updated to {$newStatus}",
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Invoice status update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update invoice status');
        }
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $this->emailService->sendInvoiceEmail($invoice);

            // Update status to sent if it was draft
            if ($invoice->status === 'Draft') {
                $this->invoiceService->updateInvoiceStatus($invoice, 'Sent');
            }

            Log::info('Invoice emailed', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice sent successfully',
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Invoice sent successfully');

        } catch (\Exception $e) {
            Log::error('Invoice email failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to send invoice');
        }
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $invoice->load(['client', 'items', 'payments']);

            $filename = $this->pdfService->generateFilename('invoice', $invoice->number);

            Log::info('Invoice PDF generated', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return $this->pdfService->download(
                view: 'pdf.invoice',
                data: ['invoice' => $invoice],
                filename: $filename,
                options: ['template' => 'invoice']
            );

        } catch (\Exception $e) {
            Log::error('Invoice PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to generate PDF');
        }
    }

    /**
     * Copy invoice
     */
    public function copy(Request $request, Invoice $invoice)
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $newInvoice = $this->invoiceService->copyInvoice($invoice, $request->get('date'));

            Log::info('Invoice copied', [
                'original_invoice_id' => $invoice->id,
                'new_invoice_id' => $newInvoice->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice copied successfully',
                    'invoice' => $newInvoice,
                ]);
            }

            return redirect()
                ->route('invoices.show', $newInvoice)
                ->with('success', "Invoice copied as #{$newInvoice->number}");

        } catch (\Exception $e) {
            Log::error('Invoice copy failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to copy invoice');
        }
    }

    /**
     * Delete the specified invoice
     */
    public function destroy(Request $request, Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        try {
            $invoiceNumber = $invoice->number;
            $this->invoiceService->deleteInvoice($invoice);

            Log::warning('Invoice deleted', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice deleted successfully',
                ]);
            }

            return redirect()
                ->route('invoices.index')
                ->with('success', "Invoice #{$invoiceNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Invoice deletion failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete invoice');
        }
    }

    /**
     * Export invoices to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $clientId = $request->get('client_id');

        $query = Invoice::with('client')
            ->where('company_id', $user->company_id);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->orderBy('number')->get();
        $filename = 'invoices-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Invoice Number',
                'Scope',
                'Amount',
                'Issued Date',
                'Due Date',
                'Status',
            ]);

            // CSV data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->prefix.$invoice->number,
                    $invoice->scope,
                    $invoice->amount,
                    $invoice->date,
                    $invoice->due_date,
                    $invoice->status,
                ]);
            }

            fclose($file);
        };

        Log::info('Invoices exported to CSV', [
            'count' => $invoices->count(),
            'client_id' => $clientId,
            'user_id' => Auth::id(),
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update invoice notes
     */
    public function updateNotes(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $invoice->update(['notes' => $request->get('notes')]);

            Log::info('Invoice notes updated', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice notes update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes',
            ], 500);
        }
    }

    /**
     * Convert invoice to contract
     */
    public function convertToContract(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if ($invoice->status === 'Draft') {
            return back()->with('error', 'Invoice must be sent or paid before converting to contract');
        }

        $request->validate([
            'contract_type' => 'required|string|in:maintenance,support,master_service,recurring_service',
            'contract_template_id' => 'nullable|exists:contract_templates,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'payment_terms' => 'nullable|string|in:net_15,net_30,net_45,net_60,immediate',
            'auto_renewal' => 'boolean',
            'renewal_notice_days' => 'nullable|integer|min:1|max:365',
            'terms_and_conditions' => 'nullable|string',
        ]);

        try {
            $contractData = array_merge($request->validated(), [
                'title' => $invoice->scope ?? 'Service Contract',
                'description' => $invoice->description ?? 'Contract generated from invoice',
                'contract_value' => $invoice->amount,
                'currency' => 'USD',
                'client_id' => $invoice->client_id,
            ]);

            $contract = $this->conversionService->convertInvoiceToContract($invoice, $contractData);

            // Link the invoice to the contract
            $invoice->update(['contract_id' => $contract->id]);

            Log::info('Invoice converted to contract', [
                'invoice_id' => $invoice->id,
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice converted to contract successfully',
                    'contract' => $contract,
                ]);
            }

            return redirect()
                ->route('contracts.show', $contract)
                ->with('success', "Invoice converted to contract #{$contract->contract_number} successfully");

        } catch (\Exception $e) {
            Log::error('Invoice to contract conversion failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to convert to contract: '.$e->getMessage());
        }
    }

    /**
     * Preview invoice to contract conversion
     */
    public function previewContractConversion(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items']);

        // Get available contract templates
        $user = Auth::user();
        $contractTemplates = \App\Domains\Contract\Models\ContractTemplate::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $previewData = [
            'invoice' => $invoice,
            'contract_templates' => $contractTemplates,
            'contract_types' => [
                'maintenance' => 'Maintenance Contract',
                'support' => 'Support Contract',
                'master_service' => 'Master Service Agreement',
                'recurring_service' => 'Recurring Service Contract',
            ],
            'payment_terms' => [
                'net_15' => 'Net 15 Days',
                'net_30' => 'Net 30 Days',
                'net_45' => 'Net 45 Days',
                'net_60' => 'Net 60 Days',
                'immediate' => 'Immediate Payment',
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($previewData);
        }

        return view('financial.invoices.contract-preview', $previewData);
    }

    /**
     * Generate recurring invoices from contract
     */
    public function generateFromContract(Request $request, Contract $contract)
    {
        $this->authorize('create', Invoice::class);

        if (! $contract->isActive()) {
            return back()->with('error', 'Contract must be active to generate invoices');
        }

        $request->validate([
            'billing_period_start' => 'required|date',
            'billing_period_end' => 'required|date|after:billing_period_start',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'description_override' => 'nullable|string',
            'milestone_ids' => 'nullable|array',
            'milestone_ids.*' => 'exists:contract_milestones,id',
        ]);

        try {
            $invoiceData = [
                'billing_period_start' => $request->get('billing_period_start'),
                'billing_period_end' => $request->get('billing_period_end'),
                'invoice_date' => $request->get('invoice_date'),
                'due_date' => $request->get('due_date'),
                'description_override' => $request->get('description_override'),
                'milestone_ids' => $request->get('milestone_ids', []),
            ];

            $invoice = $this->conversionService->generateInvoiceFromContract($contract, $invoiceData);

            Log::info('Invoice generated from contract', [
                'contract_id' => $contract->id,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice generated from contract successfully',
                    'invoice' => $invoice,
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice #{$invoice->number} generated from contract successfully");

        } catch (\Exception $e) {
            Log::error('Invoice generation from contract failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to generate invoice from contract: '.$e->getMessage());
        }
    }

    /**
     * Link invoice to existing contract
     */
    public function linkToContract(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'milestone_id' => 'nullable|exists:contract_milestones,id',
        ]);

        try {
            $contract = Contract::findOrFail($request->get('contract_id'));

            // Verify the contract belongs to the same client
            if ($contract->client_id !== $invoice->client_id) {
                return back()->with('error', 'Contract and invoice must belong to the same client');
            }

            $invoice->update([
                'contract_id' => $contract->id,
                'contract_milestone_id' => $request->get('milestone_id'),
            ]);

            // Update milestone if specified
            if ($request->get('milestone_id')) {
                $milestone = $contract->milestones()->find($request->get('milestone_id'));
                if ($milestone && $invoice->status === 'Paid') {
                    $milestone->update([
                        'status' => 'completed',
                        'completed_at' => $invoice->updated_at,
                    ]);
                }
            }

            Log::info('Invoice linked to contract', [
                'invoice_id' => $invoice->id,
                'contract_id' => $contract->id,
                'milestone_id' => $request->get('milestone_id'),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice linked to contract successfully',
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Invoice linked to contract successfully');

        } catch (\Exception $e) {
            Log::error('Invoice to contract linking failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to link invoice to contract: '.$e->getMessage());
        }
    }

    /**
     * Get available contracts for linking
     */
    public function getAvailableContracts(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $contracts = Contract::where('client_id', $invoice->client_id)
            ->where('company_id', Auth::user()->company_id)
            ->whereIn('status', ['active', 'pending_signature'])
            ->with(['milestones' => function ($query) {
                $query->where('status', '!=', 'completed');
            }])
            ->get();

        return response()->json([
            'contracts' => $contracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'status' => $contract->status,
                    'contract_value' => $contract->contract_value,
                    'milestones' => $contract->milestones->map(function ($milestone) {
                        return [
                            'id' => $milestone->id,
                            'title' => $milestone->title,
                            'amount' => $milestone->amount,
                            'due_date' => $milestone->due_date,
                            'status' => $milestone->status,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Update contract milestone based on invoice payment
     */
    public function updateContractMilestone(Invoice $invoice)
    {
        if (! $invoice->contract_milestone_id || $invoice->status !== 'Paid') {
            return;
        }

        try {
            $milestone = \App\Domains\Contract\Models\ContractMilestone::find($invoice->contract_milestone_id);
            if ($milestone && $milestone->status !== 'completed') {
                $milestone->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completion_notes' => "Automatically completed via invoice #{$invoice->number} payment",
                ]);

                Log::info('Contract milestone updated from invoice payment', [
                    'invoice_id' => $invoice->id,
                    'milestone_id' => $milestone->id,
                    'contract_id' => $milestone->contract_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update contract milestone', [
                'invoice_id' => $invoice->id,
                'milestone_id' => $invoice->contract_milestone_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function overdue(Request $request)
    {
        $invoices = Invoice::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->paginate(20);

        return view('financial.invoices.index', compact('invoices'));
    }

    public function draft(Request $request)
    {
        $invoices = Invoice::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('financial.invoices.index', compact('invoices'));
    }

    public function sent(Request $request)
    {
        $invoices = Invoice::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('financial.invoices.index', compact('invoices'));
    }

    public function paid(Request $request)
    {
        $invoices = Invoice::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->paginate(20);

        return view('financial.invoices.index', compact('invoices'));
    }

    public function recurring(Request $request)
    {
        // Redirect to the recurring invoices controller
        return redirect()->route('financial.recurring-invoices.index');
    }
}
