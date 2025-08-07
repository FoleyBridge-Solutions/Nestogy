<?php

namespace App\Http\Controllers;

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

class InvoiceController extends Controller
{
    protected $invoiceService;
    protected $paymentService;
    protected $emailService;
    protected $pdfService;

    public function __construct(
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        EmailService $emailService,
        PdfService $pdfService
    ) {
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Invoice::with(['client', 'category'])
            ->where('company_id', $user->company_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
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

        if ($request->wantsJson()) {
            return response()->json([
                'invoices' => $invoices,
                'totals' => $totals
            ]);
        }

        return view('invoices.index', compact('invoices', 'totals'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create(Request $request)
    {
        $clientId = $request->get('client_id');
        $client = $clientId ? Client::findOrFail($clientId) : null;
        $ticketId = $request->get('ticket_id');
        $ticket = $ticketId ? Ticket::findOrFail($ticketId) : null;
        
        return view('invoices.create', compact('client', 'ticket'));
    }

    /**
     * Store a newly created invoice
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            $invoiceData = $this->invoiceService->createInvoice($request->validated());
            
            Log::info('Invoice created', [
                'invoice_id' => $invoiceData['invoice_id'],
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'invoice' => $invoiceData
                ], 201);
            }

            return redirect()
                ->route('invoices.show', $invoiceData['invoice_id'])
                ->with('success', "Invoice #{$invoiceData['number']} created successfully");

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create invoice'
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
                $query->orderBy('date', 'desc');
            },
            'ticket'
        ]);

        // Calculate invoice totals
        $totals = $this->invoiceService->calculateInvoiceTotals($invoice);

        if ($request->wantsJson()) {
            return response()->json([
                'invoice' => $invoice,
                'totals' => $totals
            ]);
        }

        return view('invoices.show', compact('invoice', 'totals'));
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
        
        return view('invoices.edit', compact('invoice'));
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice updated successfully',
                    'invoice' => $updatedInvoice
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice #{$invoice->number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Invoice update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update invoice'
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
            'discount' => 'nullable|numeric|min:0|max:100'
        ]);

        try {
            $item = $this->invoiceService->addInvoiceItem($invoice, $request->all());
            
            Log::info('Invoice item added', [
                'invoice_id' => $invoice->id,
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
                ->route('invoices.show', $invoice)
                ->with('success', 'Item added successfully');

        } catch (\Exception $e) {
            Log::error('Invoice item addition failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
            'discount' => 'nullable|numeric|min:0|max:100'
        ]);

        try {
            $updatedItem = $this->invoiceService->updateInvoiceItem($item, $request->all());
            
            Log::info('Invoice item updated', [
                'invoice_id' => $invoice->id,
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
                ->route('invoices.show', $invoice)
                ->with('success', 'Item updated successfully');

        } catch (\Exception $e) {
            Log::error('Invoice item update failed', [
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item deleted successfully'
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
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment added successfully',
                    'payment' => $payment
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Payment added successfully');

        } catch (\Exception $e) {
            Log::error('Payment addition failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
            'status' => 'required|in:Draft,Sent,Paid,Cancelled'
        ]);

        try {
            $oldStatus = $invoice->status;
            $newStatus = $request->get('status');
            
            $this->invoiceService->updateInvoiceStatus($invoice, $newStatus);

            Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Invoice status updated to {$newStatus}"
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Invoice status update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice sent successfully'
                ]);
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Invoice sent successfully');

        } catch (\Exception $e) {
            Log::error('Invoice email failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
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
            'date' => 'required|date'
        ]);

        try {
            $newInvoice = $this->invoiceService->copyInvoice($invoice, $request->get('date'));
            
            Log::info('Invoice copied', [
                'original_invoice_id' => $invoice->id,
                'new_invoice_id' => $newInvoice->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice copied successfully',
                    'invoice' => $newInvoice
                ]);
            }

            return redirect()
                ->route('invoices.show', $newInvoice)
                ->with('success', "Invoice copied as #{$newInvoice->number}");

        } catch (\Exception $e) {
            Log::error('Invoice copy failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice deleted successfully'
                ]);
            }

            return redirect()
                ->route('invoices.index')
                ->with('success', "Invoice #{$invoiceNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Invoice deletion failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
        $filename = 'invoices-' . now()->format('Y-m-d') . '.csv';
        
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
                'Status'
            ]);

            // CSV data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->prefix . $invoice->number,
                    $invoice->scope,
                    $invoice->amount,
                    $invoice->date,
                    $invoice->due_date,
                    $invoice->status
                ]);
            }
            
            fclose($file);
        };

        Log::info('Invoices exported to CSV', [
            'count' => $invoices->count(),
            'client_id' => $clientId,
            'user_id' => Auth::id()
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
            'notes' => 'nullable|string'
        ]);

        try {
            $invoice->update(['notes' => $request->get('notes')]);
            
            Log::info('Invoice notes updated', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice notes update failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes'
            ], 500);
        }
    }
}