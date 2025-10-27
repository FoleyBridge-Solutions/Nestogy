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
    private const NULLABLE_STRING_RULE = 'nullable|string';

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
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->where('status', '!=', 'paid')
                ->where('due_date', '<', now());
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $invoices = $query->paginate($request->get('per_page', 25));

        // Calculate additional metrics
        $invoices->getCollection()->transform(function ($invoice) {
            $invoice->total_paid = $invoice->payments->sum('amount');
            $invoice->balance_due = $invoice->amount - $invoice->total_paid;
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
                'total_amount' => $query->sum('amount'),
                'total_paid' => $query->whereHas('payments')->get()->sum(function ($invoice) {
                    return $invoice->payments->sum('amount');
                }),
                'total_outstanding' => $query->where('status', '!=', 'paid')->sum('amount'),
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
            'date' => 'sometimes|date',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:' . ($request->has('date') ? 'date' : 'invoice_date'),
            'status' => ['sometimes', Rule::in(['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])],
            'category_id' => 'nullable|exists:categories,id',
            'po_number' => 'nullable|string|max:50',
            'terms' => self::NULLABLE_STRING_RULE,
            'notes' => self::NULLABLE_STRING_RULE,
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
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

            // Determine the invoice date
            $invoiceDate = $validated['date'] ?? $validated['invoice_date'] ?? now();
            $dueDate = $validated['due_date'] ?? now()->addDays(30);

            // Get or create default category if not provided
            $categoryId = $validated['category_id'] ?? $this->getDefaultCategoryId();

            // Create invoice
            $invoice = Invoice::create([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'prefix' => 'INV',
                'number' => $this->getNextInvoiceNumber(),
                'date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => $validated['status'] ?? 'draft',
                'category_id' => $categoryId,
                'discount_amount' => $discountAmount,
                'amount' => $total,
                'currency_code' => 'USD',
                'note' => $validated['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['rate'];
                $itemTax = isset($item['tax_rate'])
                    ? $itemSubtotal * ($item['tax_rate'] / 100)
                    : 0;
                $itemTotal = $itemSubtotal + $itemTax;

                InvoiceItem::create([
                    'company_id' => auth()->user()->company_id,
                    'invoice_id' => $invoice->id,
                    'name' => $item['description'], // Use description as name
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['rate'],
                    'discount' => 0,
                    'subtotal' => $itemSubtotal,
                    'tax' => $itemTax,
                    'total' => $itemTotal,
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
        $invoice->balance_due = $invoice->amount - $invoice->total_paid;
        $invoice->is_overdue = $invoice->status !== 'paid' && $invoice->due_date < now();
        $invoice->days_overdue = $invoice->is_overdue
            ? now()->diffInDays($invoice->due_date)
            : 0;
        
        $invoice->total = $invoice->amount; // Alias for backwards compatibility

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
        if ($invoice->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit '.$invoice->status.' invoices',
            ], 422);
        }

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:date',
            'status' => ['sometimes', Rule::in(['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])],
            'po_number' => 'nullable|string|max:50',
            'terms' => self::NULLABLE_STRING_RULE,
            'notes' => self::NULLABLE_STRING_RULE,
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $invoice->status;

            if ($request->has('items')) {
                $this->updateInvoiceItems($request, $invoice, $validated);
            }

            $invoice->update($validated);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update invoice', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Send notifications after commit to avoid transaction issues
        // Failures here should not affect the invoice update success
        try {
            $this->handleStatusChangeNotifications($invoice, $oldStatus, $validated);
        } catch (\Exception $e) {
            Log::warning('Failed to send notification after invoice update', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice->fresh(['client', 'items']),
            'message' => 'Invoice updated successfully',
        ]);
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
     * Update invoice items and recalculate totals
     */
    private function updateInvoiceItems(Request $request, Invoice $invoice, array &$validated): void
    {
        $itemsValidated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice->items()->delete();

        $totals = $this->calculateInvoiceTotals(
            $itemsValidated['items'],
            $validated,
            $invoice
        );

        $this->createInvoiceItems($invoice->id, $itemsValidated['items']);

        $validated['subtotal'] = $totals['subtotal'];
        $validated['tax'] = $totals['tax'];
        $validated['total'] = $totals['total'];
    }

    /**
     * Calculate invoice totals including discount and tax
     */
    private function calculateInvoiceTotals(array $items, array $validated, Invoice $invoice): array
    {
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['rate'];
            $subtotal += $itemTotal;

            if (isset($item['tax_rate'])) {
                $taxTotal += $itemTotal * ($item['tax_rate'] / 100);
            }
        }

        $discountAmount = $this->calculateDiscountAmount(
            $subtotal,
            $validated['discount_type'] ?? $invoice->discount_type,
            $validated['discount_value'] ?? $invoice->discount_value
        );

        $taxRate = $validated['tax_rate'] ?? $invoice->tax_rate ?? 0;
        if ($taxRate) {
            $taxTotal += ($subtotal - $discountAmount) * ($taxRate / 100);
        }

        $total = $subtotal - $discountAmount + $taxTotal;

        return [
            'subtotal' => $subtotal,
            'tax' => $taxTotal,
            'total' => $total,
        ];
    }

    /**
     * Calculate discount amount based on type and value
     */
    private function calculateDiscountAmount(float $subtotal, ?string $discountType, ?float $discountValue): float
    {
        if (!$discountType || !$discountValue) {
            return 0;
        }

        if ($discountType === 'percentage') {
            return $subtotal * ($discountValue / 100);
        }

        return $discountValue;
    }

    /**
     * Create invoice items from array
     */
    private function createInvoiceItems(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            $itemSubtotal = $item['quantity'] * $item['rate'];
            $itemTax = isset($item['tax_rate'])
                ? $itemSubtotal * ($item['tax_rate'] / 100)
                : 0;
            $itemTotal = $itemSubtotal + $itemTax;

            InvoiceItem::create([
                'company_id' => auth()->user()->company_id,
                'invoice_id' => $invoiceId,
                'name' => $item['description'], // Use description as name
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['rate'],
                'discount' => 0,
                'subtotal' => $itemSubtotal,
                'tax' => $itemTax,
                'total' => $itemTotal,
            ]);
        }
    }

    /**
     * Handle notifications for invoice status changes
     */
    private function handleStatusChangeNotifications(Invoice $invoice, string $oldStatus, array $validated): void
    {
        if (!isset($validated['status']) || $oldStatus === $validated['status']) {
            return;
        }

        if ($validated['status'] === 'sent') {
            $this->notificationService->notifyInvoiceSent($invoice);
        } elseif ($validated['status'] === 'paid') {
            $this->notificationService->notifyPaymentReceived($invoice, $invoice->amount);
        }
    }

    /**
     * Generate a unique invoice number (unused - kept for compatibility)
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        $nextNumber = $this->getNextInvoiceNumber();

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
    
    /**
     * Get the next invoice number for company
     */
    private function getNextInvoiceNumber(): int
    {
        $lastInvoice = Invoice::where('company_id', auth()->user()->company_id)
            ->where('prefix', 'INV')
            ->orderBy('number', 'desc')
            ->first();
            
        return $lastInvoice ? $lastInvoice->number + 1 : 1;
    }

    /**
     * Get or create default category for invoices
     */
    private function getDefaultCategoryId(): int
    {
        $category = \App\Domains\Financial\Models\Category::firstOrCreate(
            [
                'company_id' => auth()->user()->company_id,
                'name' => 'General',
            ],
            [
                'description' => 'Default category for invoices',
                'type' => 'income',
            ]
        );

        return $category->id;
    }
}
