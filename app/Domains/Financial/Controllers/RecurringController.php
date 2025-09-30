<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Core\Services\PdfService;
use App\Domains\Email\Services\EmailService;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Domains\Financial\Services\VoIPTaxService;
use App\Domains\Product\Services\VoIPUsageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateRecurringInvoicesRequest;
use App\Http\Requests\ProcessUsageDataRequest;
use App\Http\Requests\StoreRecurringRequest;
use App\Http\Requests\UpdateRecurringRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Recurring;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RecurringController
 *
 * Comprehensive controller for VoIP recurring billing management with sophisticated
 * features including usage-based billing, tiered pricing, proration calculations,
 * automated invoice generation, and VoIP tax integration.
 */
class RecurringController extends Controller
{
    protected $recurringBillingService;

    protected $voipUsageService;

    protected $voipTaxService;

    protected $emailService;

    protected $pdfService;

    public function __construct(
        RecurringBillingService $recurringBillingService,
        VoIPUsageService $voipUsageService,
        VoIPTaxService $voipTaxService,
        EmailService $emailService,
        PdfService $pdfService
    ) {
        $this->recurringBillingService = $recurringBillingService;
        $this->voipUsageService = $voipUsageService;
        $this->voipTaxService = $voipTaxService;
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of recurring billing records
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Recurring::with(['client', 'category', 'quote'])
            ->where('company_id', $user->company_id);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('status', true);
            } elseif ($request->get('status') === 'inactive') {
                $query->where('status', false);
            }
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->get('frequency'));
        }

        if ($request->filled('billing_type')) {
            $query->where('billing_type', $request->get('billing_type'));
        }

        if ($request->filled('due_filter')) {
            switch ($request->get('due_filter')) {
                case 'due_now':
                    $query->due();
                    break;
                case 'due_soon':
                    $query->where('next_date', '>=', now())
                        ->where('next_date', '<=', now()->addDays(7))
                        ->where('status', true);
                    break;
                case 'overdue':
                    $query->where('next_date', '<', now())
                        ->where('status', true);
                    break;
                case 'expiring':
                    $query->expiringSoon(30);
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('scope', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'next_date');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = ['next_date', 'amount', 'client_id', 'frequency', 'created_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'next_date';
        }

        $query->orderBy($sortBy, $sortDirection);

        $recurring = $query->paginate(25);

        // Calculate summary statistics
        $stats = [
            'total_active' => Recurring::where('company_id', $user->company_id)
                ->where('status', true)->count(),
            'total_monthly_revenue' => Recurring::where('company_id', $user->company_id)
                ->where('status', true)
                ->where('frequency', Recurring::FREQUENCY_MONTHLY)
                ->sum('amount'),
            'due_now' => Recurring::where('company_id', $user->company_id)
                ->due()->count(),
            'expiring_soon' => Recurring::where('company_id', $user->company_id)
                ->expiringSoon(30)->count(),
            'voip_services' => Recurring::where('company_id', $user->company_id)
                ->voipServices()->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'recurring' => $recurring,
                'stats' => $stats,
            ]);
        }

        return view('financial.recurring.index', compact('recurring', 'stats'));
    }

    /**
     * Show the form for creating a new recurring billing
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Get quotes for potential conversion
        $quotes = Quote::where('company_id', $user->company_id)
            ->where('status', 'Accepted')
            ->whereNull('converted_invoice_id')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        // Handle pre-selected client or quote
        $clientId = $request->get('client_id');
        $selectedClient = $clientId ? Client::findOrFail($clientId) : null;

        $quoteId = $request->get('quote_id');
        $selectedQuote = $quoteId ? Quote::with(['client', 'items'])->findOrFail($quoteId) : null;

        return view('financial.recurring.create', compact(
            'clients',
            'categories',
            'quotes',
            'selectedClient',
            'selectedQuote'
        ));
    }

    /**
     * Store a newly created recurring billing
     */
    public function store(StoreRecurringRequest $request)
    {
        try {
            $validated = $request->validated();
            $client = Client::findOrFail($validated['client_id']);

            $recurring = $this->recurringBillingService->createRecurring($client, $validated);

            Log::info('Recurring billing created', [
                'recurring_id' => $recurring->id,
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring billing created successfully',
                    'recurring' => $recurring->load(['client', 'category']),
                ], 201);
            }

            return redirect()
                ->route('financial.recurring.show', $recurring->id)
                ->with('success', "Recurring billing {$recurring->getFullNumber()} created successfully");

        } catch (\Exception $e) {
            Log::error('Recurring billing creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $validated ?? [],
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create recurring billing',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create recurring billing: '.$e->getMessage());
        }
    }

    /**
     * Display the specified recurring billing
     */
    public function show(Request $request, Recurring $recurring)
    {
        $this->authorize('view', $recurring);

        $recurring->load([
            'client',
            'category',
            'quote',
            'items' => function ($query) {
                $query->orderBy('order');
            },
            'invoices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
        ]);

        // Get recent usage data for VoIP services
        $usageData = [];
        if ($recurring->hasVoIPServices()) {
            $usageData = $this->voipUsageService->getUsageSummary(
                $recurring->client_id,
                now()->subDays(30),
                now()
            );
        }

        // Calculate upcoming invoice preview
        $upcomingInvoicePreview = null;
        if ($recurring->isDue() || $request->get('preview') === '1') {
            $upcomingInvoicePreview = $this->recurringBillingService->previewInvoice($recurring);
        }

        // Get billing history summary
        $billingHistory = $recurring->metadata['billing_history'] ?? [];
        $billingStats = [
            'total_invoices' => $recurring->invoices_generated,
            'total_revenue' => collect($billingHistory)->sum('amount'),
            'avg_invoice_amount' => collect($billingHistory)->avg('amount') ?? 0,
            'last_invoice_date' => $recurring->last_sent?->format('M j, Y'),
            'next_invoice_date' => $recurring->next_date?->format('M j, Y'),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'recurring' => $recurring,
                'usage_data' => $usageData,
                'upcoming_preview' => $upcomingInvoicePreview,
                'billing_stats' => $billingStats,
            ]);
        }

        return view('financial.recurring.show', compact(
            'recurring',
            'usageData',
            'upcomingInvoicePreview',
            'billingStats'
        ));
    }

    /**
     * Show the form for editing the specified recurring billing
     */
    public function edit(Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        $user = Auth::user();

        // Get all clients for dropdown
        $clients = Client::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get all categories for dropdown
        $categories = Category::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Load recurring relationships
        $recurring->load('client', 'category', 'items', 'quote');

        return view('financial.recurring.edit', compact(
            'recurring',
            'clients',
            'categories'
        ));
    }

    /**
     * Update the specified recurring billing
     */
    public function update(UpdateRecurringRequest $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        try {
            $updatedRecurring = $this->recurringBillingService->updateRecurring(
                $recurring,
                $request->validated()
            );

            Log::info('Recurring billing updated', [
                'recurring_id' => $recurring->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring billing updated successfully',
                    'recurring' => $updatedRecurring->load(['client', 'category']),
                ]);
            }

            return redirect()
                ->route('financial.recurring.show', $recurring)
                ->with('success', "Recurring billing {$recurring->getFullNumber()} updated successfully");

        } catch (\Exception $e) {
            Log::error('Recurring billing update failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update recurring billing',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update recurring billing: '.$e->getMessage());
        }
    }

    /**
     * Generate invoice from recurring billing
     */
    public function generateInvoice(Request $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        try {
            $invoice = $this->recurringBillingService->generateInvoiceFromRecurring($recurring);

            Log::info('Invoice generated from recurring', [
                'recurring_id' => $recurring->id,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice generated successfully',
                    'invoice' => $invoice,
                    'recurring' => $recurring->fresh(),
                ]);
            }

            return redirect()
                ->route('financial.invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->getFullNumber()} generated successfully");

        } catch (\Exception $e) {
            Log::error('Invoice generation failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate invoice',
                ], 500);
            }

            return back()->with('error', 'Failed to generate invoice: '.$e->getMessage());
        }
    }

    /**
     * Bulk generate invoices for multiple recurring billings
     */
    public function bulkGenerate(GenerateRecurringInvoicesRequest $request)
    {
        try {
            $results = $this->recurringBillingService->bulkGenerateInvoices(
                $request->validated()
            );

            Log::info('Bulk invoice generation completed', [
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf(
                        'Generated %d invoices successfully, %d failed',
                        count($results['successful']),
                        count($results['failed'])
                    ),
                    'results' => $results,
                ]);
            }

            $message = sprintf(
                'Generated %d invoices successfully',
                count($results['successful'])
            );

            if (! empty($results['failed'])) {
                $message .= sprintf(', %d failed', count($results['failed']));
            }

            return redirect()
                ->route('financial.recurring.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk invoice generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate invoices',
                ], 500);
            }

            return back()->with('error', 'Failed to generate invoices: '.$e->getMessage());
        }
    }

    /**
     * Process usage data for VoIP services
     */
    public function processUsageData(ProcessUsageDataRequest $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        try {
            $results = $this->voipUsageService->processUsageData(
                $recurring,
                $request->validated()
            );

            Log::info('Usage data processed', [
                'recurring_id' => $recurring->id,
                'records_processed' => $results['processed_count'],
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usage data processed successfully',
                    'results' => $results,
                ]);
            }

            return back()->with('success',
                "Processed {$results['processed_count']} usage records successfully"
            );

        } catch (\Exception $e) {
            Log::error('Usage data processing failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process usage data',
                ], 500);
            }

            return back()->with('error', 'Failed to process usage data: '.$e->getMessage());
        }
    }

    /**
     * Add proration adjustment
     */
    public function addProrationAdjustment(Request $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        $request->validate([
            'adjustment_type' => 'required|in:addition,removal,modification,credit,debit',
            'service_type' => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        try {
            $adjustment = $recurring->addProrationAdjustment($request->all());

            Log::info('Proration adjustment added', [
                'recurring_id' => $recurring->id,
                'adjustment_amount' => $request->get('amount'),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Proration adjustment added successfully',
                    'adjustment' => $adjustment,
                ]);
            }

            return back()->with('success', 'Proration adjustment added successfully');

        } catch (\Exception $e) {
            Log::error('Proration adjustment failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to add proration adjustment: '.$e->getMessage());
        }
    }

    /**
     * Pause recurring billing
     */
    public function pause(Request $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        $request->validate([
            'resume_date' => 'nullable|date|after:today',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $resumeDate = $request->get('resume_date') ? Carbon::parse($request->get('resume_date')) : null;
            $recurring->pause($resumeDate);

            Log::info('Recurring billing paused', [
                'recurring_id' => $recurring->id,
                'resume_date' => $resumeDate?->toDateString(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring billing paused successfully',
                ]);
            }

            return back()->with('success', 'Recurring billing paused successfully');

        } catch (\Exception $e) {
            Log::error('Recurring billing pause failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to pause recurring billing: '.$e->getMessage());
        }
    }

    /**
     * Resume recurring billing
     */
    public function resume(Request $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        $request->validate([
            'next_date' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $nextDate = $request->get('next_date') ? Carbon::parse($request->get('next_date')) : null;
            $recurring->resume($nextDate);

            Log::info('Recurring billing resumed', [
                'recurring_id' => $recurring->id,
                'next_date' => $recurring->fresh()->next_date->toDateString(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring billing resumed successfully',
                ]);
            }

            return back()->with('success', 'Recurring billing resumed successfully');

        } catch (\Exception $e) {
            Log::error('Recurring billing resume failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to resume recurring billing: '.$e->getMessage());
        }
    }

    /**
     * Apply contract escalation
     */
    public function applyEscalation(Request $request, Recurring $recurring)
    {
        $this->authorize('update', $recurring);

        try {
            $applied = $recurring->applyContractEscalation();

            if (! $applied) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Escalation not due or already applied',
                    ], 422);
                }

                return back()->with('warning', 'Contract escalation not due or already applied');
            }

            Log::info('Contract escalation applied', [
                'recurring_id' => $recurring->id,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract escalation applied successfully',
                    'recurring' => $recurring->fresh(),
                ]);
            }

            return back()->with('success', 'Contract escalation applied successfully');

        } catch (\Exception $e) {
            Log::error('Contract escalation failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to apply contract escalation: '.$e->getMessage());
        }
    }

    /**
     * Delete the specified recurring billing
     */
    public function destroy(Request $request, Recurring $recurring)
    {
        $this->authorize('delete', $recurring);

        try {
            $recurringNumber = $recurring->getFullNumber();

            // Archive instead of hard delete
            $recurring->delete();

            Log::warning('Recurring billing deleted', [
                'recurring_id' => $recurring->id,
                'recurring_number' => $recurringNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring billing deleted successfully',
                ]);
            }

            return redirect()
                ->route('financial.recurring.index')
                ->with('success', "Recurring billing {$recurringNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Recurring billing deletion failed', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete recurring billing',
                ], 500);
            }

            return back()->with('error', 'Failed to delete recurring billing: '.$e->getMessage());
        }
    }

    /**
     * Export recurring billing data to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();

        $query = Recurring::with('client')
            ->where('company_id', $user->company_id);

        // Apply filters from request
        if ($request->filled('status')) {
            $query->where('status', $request->get('status') === 'active');
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->get('frequency'));
        }

        if ($request->filled('billing_type')) {
            $query->where('billing_type', $request->get('billing_type'));
        }

        $recurring = $query->orderBy('created_at', 'desc')->get();
        $filename = 'recurring-billing-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($recurring) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Number',
                'Client',
                'Scope',
                'Frequency',
                'Billing Type',
                'Amount',
                'Currency',
                'Next Date',
                'Status',
                'Created Date',
            ]);

            // CSV data
            foreach ($recurring as $record) {
                fputcsv($file, [
                    $record->getFullNumber(),
                    $record->client->name ?? 'N/A',
                    $record->scope,
                    $record->frequency,
                    $record->billing_type,
                    $record->amount,
                    $record->currency_code,
                    $record->next_date->format('Y-m-d'),
                    $record->status ? 'Active' : 'Inactive',
                    $record->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        Log::info('Recurring billing exported to CSV', [
            'count' => $recurring->count(),
            'user_id' => Auth::id(),
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get due recurring billings for processing
     */
    public function getDueRecurring(Request $request)
    {
        $user = Auth::user();

        $dueRecurring = Recurring::with(['client', 'category'])
            ->where('company_id', $user->company_id)
            ->due()
            ->orderBy('next_date')
            ->limit(50)
            ->get();

        return response()->json([
            'due_recurring' => $dueRecurring,
            'count' => $dueRecurring->count(),
            'total_amount' => $dueRecurring->sum('amount'),
        ]);
    }
}
