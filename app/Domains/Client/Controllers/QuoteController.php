<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    /**
     * Display a listing of all quotes (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientQuote::with(['client', 'creator'])
            ->whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
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
        if ($request->get('expiring_soon')) {
            $query->expiringSoon(7);
        } elseif ($request->get('overdue')) {
            $query->overdue();
        } elseif ($request->get('follow_up_due')) {
            $query->followUpDue();
        }

        $quotes = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $statuses = ClientQuote::getStatuses();
        $currencies = ClientQuote::getCurrencies();

        return view('clients.quotes.index', compact('quotes', 'clients', 'statuses', 'currencies'));
    }

    /**
     * Show the form for creating a new quote
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $selectedClientId = $request->get('client_id');
        $statuses = ClientQuote::getStatuses();
        $currencies = ClientQuote::getCurrencies();
        $discountTypes = ClientQuote::getDiscountTypes();

        return view('clients.quotes.create', compact('clients', 'selectedClientId', 'statuses', 'currencies', 'discountTypes'));
    }

    /**
     * Store a newly created quote
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|in:'.implode(',', array_keys(ClientQuote::getCurrencies())),
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:'.implode(',', array_keys(ClientQuote::getDiscountTypes())),
            'valid_until' => 'nullable|date|after:today',
            'issued_date' => 'nullable|date',
            'status' => 'required|in:'.implode(',', array_keys(ClientQuote::getStatuses())),
            'conversion_probability' => 'nullable|numeric|min:0|max:100',
            'follow_up_date' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'delivery_timeframe' => 'nullable|string',
            'project_scope' => 'nullable|string',
            'line_items' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Process line items
        $lineItems = [];
        if ($request->line_items) {
            $lines = explode("\n", $request->line_items);
            foreach ($lines as $line) {
                $line = trim($line);
                if (! empty($line)) {
                    // Try to parse "Description | Qty | Price" format
                    $parts = explode('|', $line);
                    if (count($parts) >= 3) {
                        $lineItems[] = [
                            'description' => trim($parts[0]),
                            'quantity' => (float) trim($parts[1]),
                            'unit_price' => (float) trim($parts[2]),
                        ];
                    } else {
                        $lineItems[] = [
                            'description' => $line,
                            'quantity' => 1,
                            'unit_price' => 0,
                        ];
                    }
                }
            }
        }

        $quote = new ClientQuote([
            'client_id' => $request->client_id,
            'quote_number' => ClientQuote::generateQuoteNumber(),
            'title' => $request->title,
            'description' => $request->description,
            'currency' => $request->currency,
            'tax_rate' => $request->tax_rate ?: 0,
            'discount_amount' => $request->discount_amount ?: 0,
            'discount_type' => $request->discount_type ?: 'fixed',
            'valid_until' => $request->valid_until,
            'issued_date' => $request->issued_date ?: now()->toDate(),
            'status' => $request->status,
            'conversion_probability' => $request->conversion_probability,
            'follow_up_date' => $request->follow_up_date,
            'terms_conditions' => $request->terms_conditions,
            'notes' => $request->notes,
            'payment_terms' => $request->payment_terms,
            'delivery_timeframe' => $request->delivery_timeframe,
            'project_scope' => $request->project_scope,
            'line_items' => $lineItems,
            'created_by' => auth()->id(),
        ]);

        $quote->company_id = auth()->user()->company_id;

        // Calculate totals
        $quote->calculateTotals();

        $quote->save();

        return redirect()->route('clients.quotes.standalone.index')
            ->with('success', 'Quote created successfully.');
    }

    /**
     * Display the specified quote
     */
    public function show(ClientQuote $quote)
    {
        $this->authorize('view', $quote);

        $quote->load('client', 'creator', 'approver', 'invoice');

        return view('clients.quotes.show', compact('quote'));
    }

    /**
     * Show the form for editing the specified quote
     */
    public function edit(ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $statuses = ClientQuote::getStatuses();
        $currencies = ClientQuote::getCurrencies();
        $discountTypes = ClientQuote::getDiscountTypes();

        return view('clients.quotes.edit', compact('quote', 'clients', 'statuses', 'currencies', 'discountTypes'));
    }

    /**
     * Update the specified quote
     */
    public function update(Request $request, ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|in:'.implode(',', array_keys(ClientQuote::getCurrencies())),
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:'.implode(',', array_keys(ClientQuote::getDiscountTypes())),
            'valid_until' => 'nullable|date',
            'issued_date' => 'nullable|date',
            'status' => 'required|in:'.implode(',', array_keys(ClientQuote::getStatuses())),
            'conversion_probability' => 'nullable|numeric|min:0|max:100',
            'follow_up_date' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'delivery_timeframe' => 'nullable|string',
            'project_scope' => 'nullable|string',
            'line_items' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Process line items
        $lineItems = [];
        if ($request->line_items) {
            $lines = explode("\n", $request->line_items);
            foreach ($lines as $line) {
                $line = trim($line);
                if (! empty($line)) {
                    // Try to parse "Description | Qty | Price" format
                    $parts = explode('|', $line);
                    if (count($parts) >= 3) {
                        $lineItems[] = [
                            'description' => trim($parts[0]),
                            'quantity' => (float) trim($parts[1]),
                            'unit_price' => (float) trim($parts[2]),
                        ];
                    } else {
                        $lineItems[] = [
                            'description' => $line,
                            'quantity' => 1,
                            'unit_price' => 0,
                        ];
                    }
                }
            }
        }

        $quote->fill([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'currency' => $request->currency,
            'tax_rate' => $request->tax_rate ?: 0,
            'discount_amount' => $request->discount_amount ?: 0,
            'discount_type' => $request->discount_type ?: 'fixed',
            'valid_until' => $request->valid_until,
            'issued_date' => $request->issued_date,
            'status' => $request->status,
            'conversion_probability' => $request->conversion_probability,
            'follow_up_date' => $request->follow_up_date,
            'terms_conditions' => $request->terms_conditions,
            'notes' => $request->notes,
            'payment_terms' => $request->payment_terms,
            'delivery_timeframe' => $request->delivery_timeframe,
            'project_scope' => $request->project_scope,
            'line_items' => $lineItems,
        ]);

        // Recalculate totals
        $quote->calculateTotals();
        $quote->save();

        return redirect()->route('clients.quotes.standalone.index')
            ->with('success', 'Quote updated successfully.');
    }

    /**
     * Remove the specified quote
     */
    public function destroy(ClientQuote $quote)
    {
        $this->authorize('delete', $quote);

        $quote->delete();

        return redirect()->route('clients.quotes.standalone.index')
            ->with('success', 'Quote deleted successfully.');
    }

    /**
     * Send quote to client
     */
    public function send(ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        if ($quote->send()) {
            return redirect()->route('clients.quotes.standalone.show', $quote)
                ->with('success', 'Quote sent to client successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Quote cannot be sent in its current status.');
        }
    }

    /**
     * Accept the quote
     */
    public function accept(Request $request, ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        $signatureData = null;
        if ($request->has('signature')) {
            $signatureData = [
                'signature' => $request->signature,
                'ip' => $request->ip(),
            ];
        }

        if ($quote->accept($signatureData)) {
            return redirect()->route('clients.quotes.standalone.show', $quote)
                ->with('success', 'Quote accepted successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Quote cannot be accepted in its current status.');
        }
    }

    /**
     * Decline the quote
     */
    public function decline(Request $request, ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        if ($quote->decline($request->get('reason'))) {
            return redirect()->route('clients.quotes.standalone.show', $quote)
                ->with('success', 'Quote declined.');
        } else {
            return redirect()->back()
                ->with('error', 'Quote cannot be declined in its current status.');
        }
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice(ClientQuote $quote)
    {
        $this->authorize('update', $quote);

        $invoice = $quote->convertToInvoice();

        if ($invoice) {
            return redirect()->route('clients.quotes.standalone.show', $quote)
                ->with('success', 'Quote converted to invoice successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Quote cannot be converted in its current status.');
        }
    }

    /**
     * Duplicate the quote
     */
    public function duplicate(ClientQuote $quote)
    {
        $this->authorize('view', $quote);

        $newQuote = $quote->replicate([
            'quote_number',
            'status',
            'sent_at',
            'viewed_at',
            'accepted_date',
            'declined_date',
            'converted_date',
            'invoice_id',
        ]);

        $newQuote->quote_number = ClientQuote::generateQuoteNumber();
        $newQuote->status = 'draft';
        $newQuote->title = $quote->title.' (Copy)';
        $newQuote->created_by = auth()->id();
        $newQuote->save();

        return redirect()->route('clients.quotes.standalone.edit', $newQuote)
            ->with('success', 'Quote duplicated successfully.');
    }

    /**
     * Export quotes to CSV
     */
    public function export(Request $request)
    {
        $query = ClientQuote::with(['client', 'creator'])
            ->whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $quotes = $query->orderBy('created_at', 'desc')->get();

        $filename = 'quotes_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($quotes) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Quote Number',
                'Title',
                'Client Name',
                'Status',
                'Amount',
                'Total Amount',
                'Currency',
                'Valid Until',
                'Issued Date',
                'Conversion Probability',
                'Created At',
            ]);

            // CSV data
            foreach ($quotes as $quote) {
                fputcsv($file, [
                    $quote->quote_number,
                    $quote->title,
                    $quote->client->display_name,
                    $quote->status,
                    $quote->amount,
                    $quote->total_amount,
                    $quote->currency,
                    $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '',
                    $quote->issued_date ? $quote->issued_date->format('Y-m-d') : '',
                    $quote->conversion_probability.'%',
                    $quote->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
