<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Category;
use App\Models\Tax;
use App\Models\Ticket;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\EmailService;
use App\Services\PdfService;
use App\Services\QuoteInvoiceConversionService;
use App\Services\ContractGenerationService;
use App\Models\Contract;

class InvoiceControllerRefactored extends BaseController
{
    protected $invoiceService;
    protected $paymentService;
    protected $emailService;
    protected $pdfService;
    protected $conversionService;
    protected $contractGenerationService;

    public function __construct(
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        EmailService $emailService,
        PdfService $pdfService,
        QuoteInvoiceConversionService $conversionService,
        ContractGenerationService $contractGenerationService
    ) {
        parent::__construct();
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
        $this->conversionService = $conversionService;
        $this->contractGenerationService = $contractGenerationService;
    }

    protected function initializeController(): void
    {
        $this->modelClass = Invoice::class;
        $this->serviceClass = InvoiceService::class;
        $this->resourceName = 'financial.invoices';
        $this->viewPrefix = 'financial.invoices';
        $this->eagerLoadRelations = ['client', 'items', 'payments'];
    }

    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'status', 'client_id', 'date_from', 'date_to']);
    }

    protected function applyCustomFilters($query, Request $request)
    {
        // Apply client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        // Apply date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        return $query;
    }

    protected function getIndexViewData(Request $request): array
    {
        return [
            'clients' => Client::where('company_id', Auth::user()->company_id)
                ->whereNull('archived_at')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'statistics' => $this->invoiceService->getStatistics(),
        ];
    }

    protected function getShowViewData(\Illuminate\Database\Eloquent\Model $model): array
    {
        $invoice = $model;
        
        // Load additional relationships for show view
        $invoice->load([
            'client.primaryContact',
            'client.primaryLocation',
            'items.category',
            'payments',
            'tickets'
        ]);

        return [
            'invoice' => $invoice,
            'relatedTickets' => $invoice->tickets,
            'paymentHistory' => $invoice->payments()->orderBy('created_at', 'desc')->get(),
            'canEdit' => $invoice->status === 'Draft',
            'canSend' => in_array($invoice->status, ['Draft', 'Sent']),
            'canPay' => in_array($invoice->status, ['Sent', 'Viewed']),
        ];
    }

    protected function getCreateViewData(): array
    {
        return [
            'clients' => Client::where('company_id', Auth::user()->company_id)
                ->whereNull('archived_at')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => Category::where('company_id', Auth::user()->company_id)
                ->where('type', 'expense')
                ->orderBy('name')
                ->get(['id', 'name']),
            'taxes' => Tax::where('company_id', Auth::user()->company_id)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'rate']),
        ];
    }

    protected function getEditViewData(\Illuminate\Database\Eloquent\Model $model): array
    {
        $invoice = $model;
        
        if ($invoice->status !== 'Draft') {
            abort(403, 'Only draft invoices can be edited');
        }

        return array_merge($this->getCreateViewData(), [
            'invoice' => $invoice->load(['items', 'client'])
        ]);
    }

    // Custom invoice-specific methods

    /**
     * Send invoice to client
     */
    public function send(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if (!in_array($invoice->status, ['Draft', 'Sent'])) {
            return back()->with('error', 'Invoice cannot be sent in its current status');
        }

        try {
            $result = $this->invoiceService->sendInvoice($invoice, $request->all());
            
            $this->logActivity($invoice, 'sent', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice sent successfully'
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', 'Invoice sent successfully');

        } catch (\Exception $e) {
            $this->logError('send', $e, $request, $invoice);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send invoice'
                ], 500);
            }

            return back()->with('error', 'Failed to send invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if (!in_array($invoice->status, ['Sent', 'Viewed'])) {
            return back()->with('error', 'Invoice cannot be marked as paid in its current status');
        }

        try {
            $this->invoiceService->markAsPaid($invoice, $request->all());
            
            $this->logActivity($invoice, 'marked_paid', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice marked as paid'
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', 'Invoice marked as paid');

        } catch (\Exception $e) {
            $this->logError('mark_paid', $e, $request, $invoice);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark invoice as paid'
                ], 500);
            }

            return back()->with('error', 'Failed to mark invoice as paid: ' . $e->getMessage());
        }
    }

    /**
     * Add payment to invoice
     */
    public function addPayment(StorePaymentRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        try {
            $payment = $this->paymentService->createPayment(
                array_merge($request->validated(), ['invoice_id' => $invoice->id])
            );
            
            $this->logActivity($invoice, 'payment_added', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment added successfully',
                    'payment' => $payment
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', 'Payment added successfully');

        } catch (\Exception $e) {
            $this->logError('add_payment', $e, $request, $invoice);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add payment'
                ], 500);
            }

            return back()->with('error', 'Failed to add payment: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for invoice
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $pdf = $this->pdfService->generateInvoicePdf($invoice);
            
            $this->logActivity($invoice, 'pdf_generated', request());

            return $pdf->download("invoice-{$invoice->number}.pdf");

        } catch (\Exception $e) {
            $this->logError('pdf_generation', $e, request(), $invoice);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate invoice
     */
    public function duplicate(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $newInvoice = $this->invoiceService->duplicateInvoice($invoice);
            
            $this->logActivity($invoice, 'duplicated', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice duplicated successfully',
                    'invoice_id' => $newInvoice->id
                ]);
            }

            return redirect()
                ->route('financial.invoices.edit', $newInvoice)
                ->with('success', 'Invoice duplicated successfully');

        } catch (\Exception $e) {
            $this->logError('duplication', $e, $request, $invoice);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to duplicate invoice'
                ], 500);
            }

            return back()->with('error', 'Failed to duplicate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Convert quote to invoice
     */
    public function convertFromQuote(Request $request, $quoteId)
    {
        try {
            $invoice = $this->conversionService->convertQuoteToInvoice($quoteId);
            
            Log::info('Quote converted to invoice', [
                'quote_id' => $quoteId,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote converted to invoice successfully',
                    'invoice_id' => $invoice->id
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', 'Quote converted to invoice successfully');

        } catch (\Exception $e) {
            Log::error('Quote conversion failed', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to convert quote'
                ], 500);
            }

            return back()->with('error', 'Failed to convert quote: ' . $e->getMessage());
        }
    }

    /**
     * Generate contract from invoice
     */
    public function generateContract(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $contract = $this->contractGenerationService->generateFromInvoice($invoice);
            
            $this->logActivity($invoice, 'contract_generated', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract generated successfully',
                    'contract_id' => $contract->id
                ]);
            }

            return redirect()
                ->route('financial.contracts.show', $contract)
                ->with('success', 'Contract generated successfully');

        } catch (\Exception $e) {
            $this->logError('contract_generation', $e, $request, $invoice);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate contract'
                ], 500);
            }

            return back()->with('error', 'Failed to generate contract: ' . $e->getMessage());
        }
    }

    /**
     * Export invoices to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $this->getFilters($request);
        
        try {
            $export = $this->invoiceService->exportToCsv($filters);
            
            Log::info('Invoices exported', [
                'filters' => $filters,
                'user_id' => Auth::id()
            ]);

            return $export;

        } catch (\Exception $e) {
            Log::error('Invoice export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to export invoices: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions for invoices
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:send,mark_paid,archive,delete',
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:invoices,id'
        ]);

        $invoices = Invoice::whereIn('id', $request->invoice_ids)
            ->where('company_id', Auth::user()->company_id)
            ->get();

        $processed = 0;
        $errors = [];

        foreach ($invoices as $invoice) {
            try {
                if (!Auth::user()->can($request->action === 'delete' ? 'delete' : 'update', $invoice)) {
                    continue;
                }

                switch ($request->action) {
                    case 'send':
                        if (in_array($invoice->status, ['Draft', 'Sent'])) {
                            $this->invoiceService->sendInvoice($invoice);
                            $processed++;
                        }
                        break;
                    case 'mark_paid':
                        if (in_array($invoice->status, ['Sent', 'Viewed'])) {
                            $this->invoiceService->markAsPaid($invoice);
                            $processed++;
                        }
                        break;
                    case 'archive':
                        $this->invoiceService->archive($invoice);
                        $processed++;
                        break;
                    case 'delete':
                        $this->invoiceService->delete($invoice);
                        $processed++;
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Invoice #{$invoice->number}: " . $e->getMessage();
            }
        }

        Log::info('Bulk invoice action performed', [
            'action' => $request->action,
            'processed' => $processed,
            'errors' => count($errors),
            'user_id' => Auth::id()
        ]);

        $message = "Successfully processed {$processed} invoices.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= " and " . (count($errors) - 3) . " more.";
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'errors' => $errors
            ]);
        }

        return redirect()
            ->route('financial.invoices.index')
            ->with($processed > 0 ? 'success' : 'warning', $message);
    }
}