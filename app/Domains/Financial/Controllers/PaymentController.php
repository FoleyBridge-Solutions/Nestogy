<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Financial\Models\Payment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        $query = Payment::with(['client', 'invoice', 'processedBy'])
            ->where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                      $invoiceQuery->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply payment method filters
        if ($method = $request->get('payment_method')) {
            $query->where('payment_method', $method);
        }

        // Apply gateway filters
        if ($gateway = $request->get('gateway')) {
            $query->where('gateway', $gateway);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        // Apply amount range filter
        if ($amountFrom = $request->get('amount_from')) {
            $query->where('amount', '>=', $amountFrom);
        }
        if ($amountTo = $request->get('amount_to')) {
            $query->where('amount', '<=', $amountTo);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(20)
                         ->appends($request->query());

        // Get filter options
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $paymentMethods = Payment::getPaymentMethods();
        $statuses = Payment::getStatuses();
        $gateways = Payment::getGateways();

        // Get summary statistics
        $stats = $this->getPaymentStats();

        return view('financial.payments.index', compact(
            'payments', 
            'clients', 
            'paymentMethods', 
            'statuses', 
            'gateways',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new payment
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $invoices = Invoice::where('company_id', auth()->user()->company_id)
                          ->where('status', '!=', 'paid')
                          ->with('client')
                          ->orderBy('created_at', 'desc')
                          ->get();

        $selectedClientId = $request->get('client_id');
        $selectedInvoiceId = $request->get('invoice_id');

        $paymentMethods = Payment::getPaymentMethods();
        $gateways = Payment::getGateways();

        return view('financial.payments.create', compact(
            'clients', 
            'invoices', 
            'selectedClientId', 
            'selectedInvoiceId',
            'paymentMethods',
            'gateways'
        ));
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|max:3',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Payment::getPaymentMethods())),
            'gateway' => 'required|string|in:' . implode(',', array_keys(Payment::getGateways())),
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'gateway_transaction_id' => 'nullable|string|max:255',
            'gateway_fee' => 'nullable|numeric|min:0|max:9999.99',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            $payment = new Payment($request->all());
            $payment->company_id = auth()->user()->company_id;
            $payment->processed_by = Auth::id();
            $payment->status = Payment::STATUS_COMPLETED; // Manual payments are completed immediately
            
            // Generate reference if not provided
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = 'PAY-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
            }

            $payment->save();

            // Update invoice status if payment is for an invoice
            if ($payment->invoice_id) {
                $this->updateInvoiceStatus($payment->invoice);
            }

            DB::commit();

            return redirect()->route('financial.payments.index')
                           ->with('success', 'Payment created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to create payment: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified payment
     */
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['client', 'invoice', 'processedBy']);

        return view('financial.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment
     */
    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $invoices = Invoice::where('company_id', auth()->user()->company_id)
                          ->with('client')
                          ->orderBy('created_at', 'desc')
                          ->get();

        $paymentMethods = Payment::getPaymentMethods();
        $gateways = Payment::getGateways();
        $statuses = Payment::getStatuses();

        return view('financial.payments.edit', compact(
            'payment', 
            'clients', 
            'invoices',
            'paymentMethods',
            'gateways',
            'statuses'
        ));
    }

    /**
     * Update the specified payment
     */
    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|max:3',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Payment::getPaymentMethods())),
            'gateway' => 'required|string|in:' . implode(',', array_keys(Payment::getGateways())),
            'status' => 'required|string|in:' . implode(',', array_keys(Payment::getStatuses())),
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'gateway_transaction_id' => 'nullable|string|max:255',
            'gateway_fee' => 'nullable|numeric|min:0|max:9999.99',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldInvoiceId = $payment->invoice_id;
            $payment->fill($request->all());
            $payment->save();

            // Update old invoice status if payment was moved to a different invoice
            if ($oldInvoiceId && $oldInvoiceId != $payment->invoice_id) {
                $oldInvoice = Invoice::find($oldInvoiceId);
                if ($oldInvoice) {
                    $this->updateInvoiceStatus($oldInvoice);
                }
            }

            // Update new invoice status
            if ($payment->invoice_id) {
                $this->updateInvoiceStatus($payment->invoice);
            }

            DB::commit();

            return redirect()->route('financial.payments.index')
                           ->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to update payment: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified payment
     */
    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        try {
            DB::beginTransaction();

            $invoiceId = $payment->invoice_id;
            $payment->delete();

            // Update invoice status after payment deletion
            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    $this->updateInvoiceStatus($invoice);
                }
            }

            DB::commit();

            return redirect()->route('financial.payments.index')
                           ->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Show payment form for guest (public access)
     */
    public function showPaymentForm(Invoice $invoice, $token)
    {
        // Verify the signed URL token
        if (!request()->hasValidSignature()) {
            abort(403, 'Invalid or expired payment link.');
        }

        $invoice->load('client', 'items');
        
        // Check if invoice is already paid
        if ($invoice->status === 'paid') {
            return view('financial.payments.already-paid', compact('invoice'));
        }

        $paymentMethods = [
            Payment::METHOD_CREDIT_CARD => 'Credit Card',
            Payment::METHOD_DEBIT_CARD => 'Debit Card',
            Payment::METHOD_PAYPAL => 'PayPal',
        ];

        return view('financial.payments.guest-form', compact('invoice', 'paymentMethods'));
    }

    /**
     * Process guest payment
     */
    public function processPayment(Request $request, Invoice $invoice, $token)
    {
        // Verify the signed URL token
        if (!request()->hasValidSignature()) {
            abort(403, 'Invalid or expired payment link.');
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'payment_method' => 'required|string|in:credit_card,debit_card,paypal',
            'cardholder_name' => 'required_if:payment_method,credit_card,debit_card|string|max:255',
            'card_number' => 'required_if:payment_method,credit_card,debit_card|string',
            'expiry_month' => 'required_if:payment_method,credit_card,debit_card|string|size:2',
            'expiry_year' => 'required_if:payment_method,credit_card,debit_card|string|size:4',
            'cvv' => 'required_if:payment_method,credit_card,debit_card|string|size:3,4',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            // Process payment through gateway (this is a simplified example)
            $gatewayResult = $this->processGatewayPayment($request->all(), $invoice);

            if (!$gatewayResult['success']) {
                return redirect()->route('payment.cancel', $invoice)
                               ->with('error', $gatewayResult['message']);
            }

            // Create payment record
            $payment = new Payment([
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'amount' => $request->amount,
                'currency' => $invoice->currency ?? 'USD',
                'payment_method' => $request->payment_method,
                'gateway' => $gatewayResult['gateway'],
                'gateway_transaction_id' => $gatewayResult['transaction_id'],
                'gateway_fee' => $gatewayResult['fee'] ?? 0,
                'payment_date' => now(),
                'payment_reference' => $gatewayResult['reference'],
                'status' => Payment::STATUS_COMPLETED,
            ]);

            $payment->save();

            // Update invoice status
            $this->updateInvoiceStatus($invoice);

            DB::commit();

            return redirect()->route('payment.success', $payment);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('payment.cancel', $invoice)
                           ->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Show payment success page
     */
    public function success(Payment $payment)
    {
        $payment->load(['client', 'invoice']);
        return view('financial.payments.success', compact('payment'));
    }

    /**
     * Show payment cancellation page
     */
    public function cancel(Invoice $invoice)
    {
        return view('financial.payments.cancel', compact('invoice'));
    }

    /**
     * Get payment statistics for dashboard
     */
    private function getPaymentStats(): array
    {
        $companyId = auth()->user()->company_id;
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'total_payments' => Payment::where('company_id', $companyId)->count(),
            'total_amount' => Payment::where('company_id', $companyId)
                                   ->where('status', Payment::STATUS_COMPLETED)
                                   ->sum('amount'),
            'this_month_amount' => Payment::where('company_id', $companyId)
                                         ->where('status', Payment::STATUS_COMPLETED)
                                         ->where('payment_date', '>=', $currentMonth)
                                         ->sum('amount'),
            'last_month_amount' => Payment::where('company_id', $companyId)
                                         ->where('status', Payment::STATUS_COMPLETED)
                                         ->whereBetween('payment_date', [$lastMonth, $currentMonth])
                                         ->sum('amount'),
            'pending_count' => Payment::where('company_id', $companyId)
                                     ->where('status', Payment::STATUS_PENDING)
                                     ->count(),
            'failed_count' => Payment::where('company_id', $companyId)
                                    ->where('status', Payment::STATUS_FAILED)
                                    ->count(),
        ];
    }

    /**
     * Update invoice status based on payments
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $totalPaid = Payment::where('invoice_id', $invoice->id)
                           ->where('status', Payment::STATUS_COMPLETED)
                           ->sum('amount');

        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partial']);
        } else {
            $invoice->update(['status' => 'sent']);
        }
    }

    /**
     * Process payment through gateway (simplified example)
     */
    private function processGatewayPayment(array $data, Invoice $invoice): array
    {
        // This is a simplified example - in real implementation,
        // you would integrate with actual payment gateways like Stripe, PayPal, etc.
        
        $success = true; // Simulate successful payment for demo
        
        if ($success) {
            return [
                'success' => true,
                'gateway' => 'stripe', // or determine based on payment method
                'transaction_id' => 'txn_' . Str::random(16),
                'reference' => 'PAY-' . date('YmdHis') . '-' . strtoupper(Str::random(4)),
                'fee' => $data['amount'] * 0.029 + 0.30, // Example Stripe fee
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Payment processing failed.',
            ];
        }
    }
}