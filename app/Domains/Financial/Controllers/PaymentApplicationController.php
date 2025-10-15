<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Services\PaymentApplicationService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentApplicationController extends Controller
{
    public function __construct(
        protected PaymentApplicationService $applicationService
    ) {}

    public function apply(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'allocations.*.notes' => 'nullable|string',
        ]);

        try {
            $applications = $this->applicationService->applyPaymentToMultipleInvoices(
                $payment,
                $validated['allocations']
            );

            return redirect()->back()->with('success', "Payment applied to {$applications->count()} invoice(s)");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(PaymentApplication $application)
    {
        $this->authorize('update', $application->payment);

        $validated = request()->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->applicationService->unapplyPayment($application, $validated['reason']);

            return redirect()->back()->with('success', 'Payment application removed');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reallocate(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->applicationService->reallocatePayment($payment, $validated['allocations']);

            return redirect()->back()->with('success', 'Payment reallocated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
