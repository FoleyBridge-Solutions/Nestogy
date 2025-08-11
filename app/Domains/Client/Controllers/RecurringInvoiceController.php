<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientRecurringInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecurringInvoiceController extends Controller
{
    /**
     * Display a listing of all recurring invoices (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientRecurringInvoice::with(['client', 'creator'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('invoice_prefix', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply frequency filter
        if ($frequency = $request->get('frequency')) {
            $query->where('frequency', $frequency);
        }

        // Apply currency filter
        if ($currency = $request->get('currency')) {
            $query->where('currency', $currency);
        }

        // Apply amount range filters
        if ($minAmount = $request->get('min_amount')) {
            $query->where('total_amount', '>=', $minAmount);
        }
        if ($maxAmount = $request->get('max_amount')) {
            $query->where('total_amount', '<=', $maxAmount);
        }

        // Apply date filters
        if ($request->get('due_soon')) {
            $query->upcoming(7);
        } elseif ($request->get('overdue')) {
            $query->due();
        }

        $invoices = $query->orderBy('next_invoice_date', 'asc')
                         ->paginate(20)
                         ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientRecurringInvoice::getStatuses();
        $frequencies = ClientRecurringInvoice::getFrequencies();
        $currencies = ClientRecurringInvoice::getCurrencies();

        return view('clients.recurring-invoices.index', compact('invoices', 'clients', 'statuses', 'frequencies', 'currencies'));
    }

    /**
     * Show the form for creating a new recurring invoice
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $statuses = ClientRecurringInvoice::getStatuses();
        $frequencies = ClientRecurringInvoice::getFrequencies();
        $currencies = ClientRecurringInvoice::getCurrencies();

        return view('clients.recurring-invoices.create', compact('clients', 'selectedClientId', 'statuses', 'frequencies', 'currencies'));
    }

    /**
     * Store a newly created recurring invoice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getCurrencies())),
            'frequency' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getFrequencies())),
            'interval_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getStatuses())),
            'auto_send' => 'boolean',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_flat_amount' => 'nullable|numeric|min:0',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_notes' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
            'line_items' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Calculate tax and total amounts
        $amount = $request->amount;
        $taxRate = $request->tax_rate ?: 0;
        $taxAmount = $amount * ($taxRate / 100);
        $totalAmount = $amount + $taxAmount;

        // Process line items
        $lineItems = [];
        if ($request->line_items) {
            $lines = explode("\n", $request->line_items);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $lineItems[] = ['description' => $line];
                }
            }
        }

        $invoice = new ClientRecurringInvoice([
            'client_id' => $request->client_id,
            'template_name' => $request->template_name,
            'description' => $request->description,
            'amount' => $amount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => $request->currency,
            'frequency' => $request->frequency,
            'interval_count' => $request->interval_count ?: 1,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'day_of_month' => $request->day_of_month,
            'day_of_week' => $request->day_of_week,
            'status' => $request->status,
            'auto_send' => $request->has('auto_send'),
            'payment_terms_days' => $request->payment_terms_days ?: 30,
            'late_fee_percentage' => $request->late_fee_percentage ?: 0,
            'late_fee_flat_amount' => $request->late_fee_flat_amount ?: 0,
            'invoice_prefix' => $request->invoice_prefix ?: 'REC',
            'invoice_notes' => $request->invoice_notes,
            'payment_instructions' => $request->payment_instructions,
            'line_items' => $lineItems,
            'created_by' => auth()->id(),
        ]);
        
        $invoice->company_id = auth()->user()->company_id;
        
        // Calculate initial next invoice date
        $invoice->next_invoice_date = $invoice->calculateNextInvoiceDate($invoice->start_date);
        
        $invoice->save();

        return redirect()->route('clients.recurring-invoices.standalone.index')
                        ->with('success', 'Recurring invoice created successfully.');
    }

    /**
     * Display the specified recurring invoice
     */
    public function show(ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('view', $recurringInvoice);

        $recurringInvoice->load('client', 'creator');
        
        return view('clients.recurring-invoices.show', compact('recurringInvoice'));
    }

    /**
     * Show the form for editing the specified recurring invoice
     */
    public function edit(ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientRecurringInvoice::getStatuses();
        $frequencies = ClientRecurringInvoice::getFrequencies();
        $currencies = ClientRecurringInvoice::getCurrencies();

        return view('clients.recurring-invoices.edit', compact('recurringInvoice', 'clients', 'statuses', 'frequencies', 'currencies'));
    }

    /**
     * Update the specified recurring invoice
     */
    public function update(Request $request, ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getCurrencies())),
            'frequency' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getFrequencies())),
            'interval_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'required|in:' . implode(',', array_keys(ClientRecurringInvoice::getStatuses())),
            'auto_send' => 'boolean',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_flat_amount' => 'nullable|numeric|min:0',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_notes' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
            'line_items' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Calculate tax and total amounts
        $amount = $request->amount;
        $taxRate = $request->tax_rate ?: 0;
        $taxAmount = $amount * ($taxRate / 100);
        $totalAmount = $amount + $taxAmount;

        // Process line items
        $lineItems = [];
        if ($request->line_items) {
            $lines = explode("\n", $request->line_items);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $lineItems[] = ['description' => $line];
                }
            }
        }

        $recurringInvoice->fill([
            'client_id' => $request->client_id,
            'template_name' => $request->template_name,
            'description' => $request->description,
            'amount' => $amount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => $request->currency,
            'frequency' => $request->frequency,
            'interval_count' => $request->interval_count ?: 1,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'day_of_month' => $request->day_of_month,
            'day_of_week' => $request->day_of_week,
            'status' => $request->status,
            'auto_send' => $request->has('auto_send'),
            'payment_terms_days' => $request->payment_terms_days ?: 30,
            'late_fee_percentage' => $request->late_fee_percentage ?: 0,
            'late_fee_flat_amount' => $request->late_fee_flat_amount ?: 0,
            'invoice_prefix' => $request->invoice_prefix ?: 'REC',
            'invoice_notes' => $request->invoice_notes,
            'payment_instructions' => $request->payment_instructions,
            'line_items' => $lineItems,
        ]);

        // Recalculate next invoice date if frequency or timing changed
        if ($recurringInvoice->isDirty(['frequency', 'interval_count', 'start_date', 'day_of_month', 'day_of_week'])) {
            $recurringInvoice->updateNextInvoiceDate();
        }

        $recurringInvoice->save();

        return redirect()->route('clients.recurring-invoices.standalone.index')
                        ->with('success', 'Recurring invoice updated successfully.');
    }

    /**
     * Remove the specified recurring invoice
     */
    public function destroy(ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('delete', $recurringInvoice);

        $recurringInvoice->delete();

        return redirect()->route('clients.recurring-invoices.standalone.index')
                        ->with('success', 'Recurring invoice deleted successfully.');
    }

    /**
     * Pause the specified recurring invoice
     */
    public function pause(Request $request, ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->pause($request->get('reason'));

        return redirect()->route('clients.recurring-invoices.standalone.show', $recurringInvoice)
                        ->with('success', 'Recurring invoice paused successfully.');
    }

    /**
     * Resume the specified recurring invoice
     */
    public function resume(ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->resume();

        return redirect()->route('clients.recurring-invoices.standalone.show', $recurringInvoice)
                        ->with('success', 'Recurring invoice resumed successfully.');
    }

    /**
     * Cancel the specified recurring invoice
     */
    public function cancel(Request $request, ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->cancel($request->get('reason'));

        return redirect()->route('clients.recurring-invoices.standalone.show', $recurringInvoice)
                        ->with('success', 'Recurring invoice cancelled successfully.');
    }

    /**
     * Generate invoice from recurring invoice
     */
    public function generateInvoice(ClientRecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        if (!$recurringInvoice->isDue()) {
            return redirect()->back()
                           ->with('error', 'This recurring invoice is not due for generation yet.');
        }

        $invoice = $recurringInvoice->generateInvoice();

        if ($invoice) {
            return redirect()->route('clients.recurring-invoices.standalone.show', $recurringInvoice)
                           ->with('success', 'Invoice generated successfully.');
        } else {
            return redirect()->back()
                           ->with('error', 'Failed to generate invoice.');
        }
    }

    /**
     * Export recurring invoices to CSV
     */
    public function export(Request $request)
    {
        $query = ClientRecurringInvoice::with(['client', 'creator'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->orderBy('next_invoice_date', 'asc')->get();

        $filename = 'recurring_invoices_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Template Name',
                'Client Name',
                'Description',
                'Amount',
                'Tax Rate',
                'Total Amount',
                'Currency',
                'Frequency',
                'Status',
                'Next Invoice Date',
                'Invoice Count',
                'Total Revenue',
                'Created At'
            ]);

            // CSV data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->template_name,
                    $invoice->client->display_name,
                    $invoice->description,
                    $invoice->amount,
                    $invoice->tax_rate . '%',
                    $invoice->total_amount,
                    $invoice->currency,
                    $invoice->frequency_description,
                    $invoice->status,
                    $invoice->next_invoice_date ? $invoice->next_invoice_date->format('Y-m-d') : '',
                    $invoice->invoice_count ?: 0,
                    $invoice->total_revenue ?: 0,
                    $invoice->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}