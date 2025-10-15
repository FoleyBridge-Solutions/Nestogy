<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Core\Controllers\Traits\UsesSelectedClient;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use UsesSelectedClient;

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $selectedClientId = $this->getSelectedClientId();

        $payments = Payment::where('company_id', $companyId)
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('payment_method'), function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($selectedClientId, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('applications.applicable', function ($applicable) use ($search) {
                            $applicable->where('number', 'like', "%{$search}%");
                        });
                });
            })
            ->with(['client', 'applications.applicable'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $clients = Client::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];

        $paymentMethods = [
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'cash' => 'Cash',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'other' => 'Other',
        ];

        $gateways = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'square' => 'Square',
            'authorize_net' => 'Authorize.Net',
            'manual' => 'Manual Entry',
        ];

        // Calculate statistics (filtered by selected client if applicable)
        $baseQuery = Payment::where('company_id', $companyId);
        if ($selectedClientId) {
            $baseQuery->where('client_id', $selectedClientId);
        }

        $stats = [
            'total_amount' => (clone $baseQuery)->where('status', 'completed')->sum('amount'),
            'this_month_amount' => (clone $baseQuery)
                ->where('status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_count' => (clone $baseQuery)->where('status', 'failed')->count(),
            'total_paid' => (clone $baseQuery)->where('status', 'completed')->sum('amount'),
            'this_month' => (clone $baseQuery)
                ->where('status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'total_count' => (clone $baseQuery)->count(),
        ];

        return view('financial.payments.index', compact('payments', 'clients', 'statuses', 'paymentMethods', 'gateways', 'stats'));
    }

    public function create()
    {
        return view('financial.payments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'auto_apply' => 'nullable|boolean',
        ]);

        $validated['company_id'] = Auth::user()->company_id;
        $validated['status'] = 'completed';
        $validated['processed_by'] = Auth::id();
        $validated['currency'] = $validated['currency'] ?? 'USD';

        $paymentService = app(\App\Domains\Financial\Services\PaymentService::class);
        $payment = $paymentService->createPayment($validated);

        return redirect()->route('financial.payments.show', $payment)
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['client', 'applications.applicable', 'applications.appliedBy', 'processedBy']);

        return view('financial.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);

        $invoices = Invoice::where('company_id', Auth::user()->company_id)
            ->with('client')
            ->get();

        return view('financial.payments.edit', compact('payment', 'invoices'));
    }

    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return redirect()->route('financial.payments.show', $payment)
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        foreach ($payment->activeApplications as $application) {
            $invoice = $application->applicable;
            $application->delete();
            if ($invoice instanceof Invoice) {
                $invoice->updatePaymentStatus();
            }
        }

        $payment->delete();

        return redirect()->route('financial.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}
