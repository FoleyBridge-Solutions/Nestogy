<?php

namespace App\Domains\Financial\Controllers\Api;

use App\Domains\Core\Services\NotificationService;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InvoicesController extends Controller
{
    protected RecurringBillingService $billingService;

    protected NotificationService $notificationService;

    public function __construct(
        RecurringBillingService $billingService,
        NotificationService $notificationService
    ) {
        $this->billingService = $billingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['client', 'items', 'payments'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->where('status', '!=', 'paid')
                ->where('due_date', '<', now());
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $invoices = $query->paginate($request->get('per_page', 25));

        // Calculate additional metrics
        $invoices->getCollection()->transform(function ($invoice) {
            $invoice->total_paid = $invoice->payments->sum('amount');
            $invoice->balance_due = $invoice->total - $invoice->total_paid;
            $invoice->is_overdue = $invoice->status !== 'paid' && $invoice->due_date < now();
            $invoice->days_overdue = $invoice->is_overdue
                ? now()->diffInDays($invoice->due_date)
                : 0;

            return $invoice;
        });

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'summary' => [
                'total_amount' => $query->sum('total'),
                'total_paid' => $query->whereHas('payments')->get()->sum(function ($invoice) {
                    return $invoice->payments->sum('amount');
                }),
                'total_outstanding' => $query->where('status', '!=', 'paid')->sum('total'),
            ],
            'message' => 'Invoices retrieved successfully',
        ]);
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => ['sometimes', Rule::in(['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])],
            'po_number' => 'nullable|string|max:50',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['rate'];
                $subtotal += $itemTotal;

                if (isset($item['tax_rate'])) {
                    $taxTotal += $itemTotal * ($item['tax_rate'] / 100);
                }
            }

            // Apply discount
            $discountAmount = 0;
            if (isset($validated['discount_type']) && isset($validated['discount_value'])) {
                if ($validated['discount_type'] === 'percentage') {
                    $discountAmount = $subtotal * ($validated['discount_value'] / 100);
                } else {
                    $discountAmount = $validated['discount_value'];
                }
            }

            // Apply invoice-level tax if set
            if (isset($validated['tax_rate'])) {
                $taxTotal += ($subtotal - $discountAmount) * ($validated['tax_rate'] / 100);
            }

            $total = $subtotal - $discountAmount + $taxTotal;

            // Create invoice
            $invoice = Invoice::create([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => $validated['status'] ?? 'draft',
                'po_number' => $validated['po_number'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax' => $taxTotal,
                'total' => $total,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['rate'];
                $itemTax = isset($item['tax_rate'])
                    ? $itemTotal * ($item['tax_rate'] / 100)
                    : 0;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'amount' => $itemTotal,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal + $itemTax,
                ]);
            }

            // Send notification if invoice is sent
            if ($invoice->status === 'sent') {
                $this->notificationService->notifyInvoiceSent($invoice);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice->load(['client', 'items']),
                'message' => 'Invoice created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        // Ensure user has access to this invoice
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $invoice->load(['client', 'items', 'payments.paymentMethod']);

        // Calculate additional metrics
        $invoice->total_paid = $invoice->payments->sum('amount');
        $invoice->balance_due = $invoice->total - $invoice->total_paid;
        $invoice->is_overdue = $invoice->status !== 'paid' && $invoice->due_date < now();
        $invoice->days_overdue = $invoice->is_overdue
            ? now()->diffInDays($invoice->due_date)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully',
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        // Ensure user has access to this invoice
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Cannot edit paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit '.$invoice->status.' invoices',
            ], 422);
        }

        $validated = $request->validate([
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'status' => ['sometimes', Rule::in(['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])],
            'po_number' => 'nullable|string|max:50',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $invoice->status;

            // If items are being updated, recalculate totals
            if ($request->has('items')) {
                $itemsValidated = $request->validate([
                    'items' => 'required|array|min:1',
                    'items.*.id' => 'nullable|exists:invoice_items,id',
                    'items.*.description' => 'required|string',
                    'items.*.quantity' => 'required|numeric|min:0',
                    'items.*.rate' => 'required|numeric|min:0',
                    'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                ]);

                // Delete existing items
                $invoice->items()->delete();

                // Calculate new totals
                $subtotal = 0;
                $taxTotal = 0;

                foreach ($itemsValidated['items'] as $item) {
                    $itemTotal = $item['quantity'] * $item['rate'];
                    $subtotal += $itemTotal;

                    if (isset($item['tax_rate'])) {
                        $taxTotal += $itemTotal * ($item['tax_rate'] / 100);
                    }

                    $itemTax = isset($item['tax_rate'])
                        ? $itemTotal * ($item['tax_rate'] / 100)
                        : 0;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'rate' => $item['rate'],
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'amount' => $itemTotal,
                        'tax_amount' => $itemTax,
                        'total' => $itemTotal + $itemTax,
                    ]);
                }

                // Apply discount
                $discountAmount = 0;
                $discountType = $validated['discount_type'] ?? $invoice->discount_type;
                $discountValue = $validated['discount_value'] ?? $invoice->discount_value;

                if ($discountType && $discountValue) {
                    if ($discountType === 'percentage') {
                        $discountAmount = $subtotal * ($discountValue / 100);
                    } else {
                        $discountAmount = $discountValue;
                    }
                }

                // Apply invoice-level tax
                $taxRate = $validated['tax_rate'] ?? $invoice->tax_rate ?? 0;
                if ($taxRate) {
                    $taxTotal += ($subtotal - $discountAmount) * ($taxRate / 100);
                }

                $total = $subtotal - $discountAmount + $taxTotal;

                $validated['subtotal'] = $subtotal;
                $validated['tax'] = $taxTotal;
                $validated['total'] = $total;
            }

            $invoice->update($validated);

            // Send notifications for status changes
            if (isset($validated['status']) && $oldStatus !== $validated['status']) {
                if ($validated['status'] === 'sent') {
                    $this->notificationService->notifyInvoiceSent($invoice);
                } elseif ($validated['status'] === 'paid') {
                    $this->notificationService->notifyPaymentReceived($invoice, $invoice->total);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice->fresh(['client', 'items']),
                'message' => 'Invoice updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update invoice', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        // Ensure user has access to this invoice
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Cannot delete paid invoices
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete paid invoices',
            ], 422);
        }

        try {
            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete invoice', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate recurring invoices
     */
    public function generateRecurring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contract_id' => 'nullable|exists:contracts,id',
            'dry_run' => 'sometimes|boolean',
        ]);

        try {
            $dryRun = $validated['dry_run'] ?? false;

            if (isset($validated['contract_id'])) {
                // Generate for specific contract
                $contract = \App\Domains\Contract\Models\Contract::find($validated['contract_id']);

                if ($contract->company_id !== auth()->user()->company_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $invoice = $this->billingService->generateInvoiceFromContract($contract, $dryRun);

                return response()->json([
                    'success' => true,
                    'data' => $dryRun ? ['preview' => $invoice] : $invoice,
                    'message' => $dryRun ? 'Invoice preview generated' : 'Invoice generated successfully',
                ]);
            } else {
                // Generate for all due contracts
                $result = $this->billingService->generateBulkInvoices($dryRun);

                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => $dryRun
                        ? 'Bulk invoice preview generated'
                        : 'Bulk invoices generated successfully',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate recurring invoices', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recurring invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment retry for failed invoices
     */
    public function retryPayment(Invoice $invoice): JsonResponse
    {
        // Ensure user has access to this invoice
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only retry for unpaid invoices with payment method
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already paid',
            ], 422);
        }

        try {
            $result = $this->billingService->retryFailedPayment($invoice);

            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['success']
                    ? 'Payment processed successfully'
                    : 'Payment retry failed',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retry payment', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing forecast
     */
    public function forecast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'months' => 'sometimes|integer|min:1|max:12',
        ]);

        try {
            $months = $validated['months'] ?? 3;
            $forecast = $this->billingService->generateBillingForecast($months);

            return response()->json([
                'success' => true,
                'data' => $forecast,
                'message' => 'Billing forecast generated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate billing forecast', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate billing forecast',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Invoice $invoice): JsonResponse
    {
        // Ensure user has access to this invoice
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            // Update status to sent if it's still draft
            if ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            // Send email notification
            $this->notificationService->notifyInvoiceSent($invoice);

            // Log activity
            activity()
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->log('Invoice sent via email');

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        $lastInvoice = Invoice::where('company_id', auth()->user()->company_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/INV-\d{6}-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
}
