<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Services\ClientCreditService;
use App\Http\Controllers\Controller;
use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\ClientCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientCreditController extends Controller
{
    public function __construct(
        protected ClientCreditService $creditService
    ) {}

    public function index(Request $request)
    {
        return view('financial.credits.index');
    }

    public function create()
    {
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('financial.credits.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:' . implode(',', array_keys(ClientCredit::getTypes())),
            'currency' => 'nullable|string|size:3',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $client = Client::findOrFail($validated['client_id']);

        try {
            $credit = $this->creditService->createManualCredit(
                $client,
                $validated['amount'],
                $validated['type'],
                $validated
            );

            return redirect()->route('financial.credits.show', $credit)
                ->with('success', 'Client credit created successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(ClientCredit $credit)
    {
        $this->authorize('view', $credit);

        $credit->load(['client', 'applications.invoice', 'applications.appliedBy', 'createdBy']);

        return view('financial.credits.show', compact('credit'));
    }

    public function apply(Request $request, ClientCredit $credit)
    {
        $this->authorize('update', $credit);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $invoice = \App\Models\Invoice::findOrFail($validated['invoice_id']);
            
            $application = $this->creditService->applyCreditToInvoice(
                $credit,
                $invoice,
                $validated['amount']
            );

            return redirect()->back()->with('success', 'Credit applied to invoice');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function void(Request $request, ClientCredit $credit)
    {
        $this->authorize('delete', $credit);

        try {
            $this->creditService->voidCredit($credit, 'Voided by user');

            return redirect()->route('financial.credits.index')
                ->with('success', 'Credit voided successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
