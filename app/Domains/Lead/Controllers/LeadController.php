<?php

namespace App\Domains\Lead\Controllers;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadSource;
use App\Domains\Lead\Services\LeadScoringService;
use App\Http\Controllers\BaseResourceController;
use App\Traits\HasClientRelation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LeadController extends BaseResourceController
{
    use HasClientRelation;

    protected LeadScoringService $leadScoringService;

    public function __construct(LeadScoringService $leadScoringService)
    {
        $this->leadScoringService = $leadScoringService;
    }

    protected function initializeController(): void
    {
        $this->service = app(\App\Domains\Lead\Services\LeadService::class);
        $this->resourceName = 'lead';
        $this->viewPath = 'leads';
        $this->routePrefix = 'leads';
    }

    protected function getModelClass(): string
    {
        return Lead::class;
    }

    /**
     * Display a listing of leads.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Lead::where('company_id', auth()->user()->company_id)
            ->with(['leadSource', 'assignedUser', 'client']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        if ($request->filled('lead_source_id')) {
            $query->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->filled('score_min')) {
            $query->where('total_score', '>=', $request->score_min);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'total_score');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortField, ['total_score', 'created_at', 'last_contact_date', 'qualified_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $leads = $query->paginate($request->get('per_page', 25));

        // Get filter options
        $leadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->get(['id', 'name']);

        if ($request->expectsJson()) {
            return response()->json([
                'leads' => $leads,
                'filters' => [
                    'statuses' => Lead::getStatuses(),
                    'priorities' => Lead::getPriorities(),
                    'leadSources' => $leadSources,
                    'users' => $users,
                ]
            ]);
        }

        return view('leads.index', compact('leads', 'leadSources', 'users'));
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create(): View
    {
        $leadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->get(['id', 'name']);

        return view('leads.create', compact('leadSources', 'users'));
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|integer|min:1',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
            'utm_term' => 'nullable|string|max:100',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['status'] = Lead::STATUS_NEW;

        $lead = Lead::create($validated);

        // Calculate initial score
        $this->leadScoringService->updateLeadScore($lead);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Lead created successfully',
                'lead' => $lead->load(['leadSource', 'assignedUser'])
            ], 201);
        }

        return redirect()->route('lead.leads.show', $lead)
            ->with('success', 'Lead created successfully');
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): View|JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load([
            'leadSource',
            'assignedUser', 
            'client',
            'activities' => function($query) {
                $query->with('user')->orderBy('activity_date', 'desc')->limit(20);
            }
        ]);

        if (request()->expectsJson()) {
            return response()->json(['lead' => $lead]);
        }

        return view('lead.leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the lead.
     */
    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        $leadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->get(['id', 'name']);

        return view('lead.leads.edit', compact('lead', 'leadSources', 'users'));
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, Lead $lead): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:new,contacted,qualified,unqualified,nurturing,converted,lost',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|integer|min:1',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $lead->update($validated);

        // Recalculate score if important fields changed
        $scoringFields = ['company_size', 'industry', 'notes', 'estimated_value'];
        if (array_intersect(array_keys($validated), $scoringFields)) {
            $this->leadScoringService->updateLeadScore($lead);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Lead updated successfully',
                'lead' => $lead->load(['leadSource', 'assignedUser'])
            ]);
        }

        return redirect()->route('lead.leads.show', $lead)
            ->with('success', 'Lead updated successfully');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Lead deleted successfully']);
        }

        return redirect()->route('lead.leads.index')
            ->with('success', 'Lead deleted successfully');
    }

    /**
     * Convert lead to client.
     */
    public function convertToClient(Request $request, Lead $lead): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $lead);

        if ($lead->status === Lead::STATUS_CONVERTED) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Lead is already converted'], 400);
            }
            return back()->with('error', 'Lead is already converted');
        }

        $validated = $request->validate([
            'client_data' => 'sometimes|array',
            'client_data.hourly_rate' => 'nullable|numeric|min:0',
            'client_data.notes' => 'nullable|string',
        ]);

        $clientData = $validated['client_data'] ?? [];
        $client = $lead->convertToClient($clientData);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Lead converted to client successfully',
                'client' => $client,
                'lead' => $lead->refresh()
            ]);
        }

        return redirect()->route('clients.show', $client)
            ->with('success', 'Lead converted to client successfully');
    }

    /**
     * Update lead score manually.
     */
    public function updateScore(Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $scores = $this->leadScoringService->updateLeadScore($lead);

        return response()->json([
            'message' => 'Lead score updated successfully',
            'scores' => $scores,
            'lead' => $lead->refresh()
        ]);
    }

    /**
     * Get leads dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $stats = [
            'total_leads' => Lead::where('company_id', $companyId)->count(),
            'new_leads' => Lead::where('company_id', $companyId)->where('status', Lead::STATUS_NEW)->count(),
            'qualified_leads' => Lead::where('company_id', $companyId)->where('status', Lead::STATUS_QUALIFIED)->count(),
            'high_score_leads' => Lead::where('company_id', $companyId)->where('total_score', '>=', 70)->count(),
            'converted_this_month' => Lead::where('company_id', $companyId)
                ->where('status', Lead::STATUS_CONVERTED)
                ->whereMonth('converted_at', now()->month)
                ->count(),
        ];

        $recentLeads = Lead::where('company_id', $companyId)
            ->with(['leadSource', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $highScoreLeads = Lead::where('company_id', $companyId)
            ->where('total_score', '>=', 70)
            ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])
            ->with(['leadSource', 'assignedUser'])
            ->orderBy('total_score', 'desc')
            ->limit(10)
            ->get();

        $scoreDistribution = $this->leadScoringService->getScoringDistribution($companyId);

        return response()->json([
            'stats' => $stats,
            'recent_leads' => $recentLeads,
            'high_score_leads' => $highScoreLeads,
            'score_distribution' => $scoreDistribution,
        ]);
    }

    /**
     * Bulk update lead assignments.
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'assigned_user_id' => 'required|exists:users,id',
        ]);

        $companyId = auth()->user()->company_id;
        $leads = Lead::where('company_id', $companyId)
            ->whereIn('id', $validated['lead_ids'])
            ->get();

        foreach ($leads as $lead) {
            $this->authorize('update', $lead);
            $lead->update(['assigned_user_id' => $validated['assigned_user_id']]);
        }

        return response()->json([
            'message' => 'Leads assigned successfully',
            'updated_count' => $leads->count()
        ]);
    }

    /**
     * Bulk update lead status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'status' => 'required|in:new,contacted,qualified,unqualified,nurturing,converted,lost',
        ]);

        $companyId = auth()->user()->company_id;
        $leads = Lead::where('company_id', $companyId)
            ->whereIn('id', $validated['lead_ids'])
            ->get();

        foreach ($leads as $lead) {
            $this->authorize('update', $lead);
            $lead->update(['status' => $validated['status']]);
            
            if ($validated['status'] === Lead::STATUS_QUALIFIED) {
                $lead->update(['qualified_at' => now()]);
            }
        }

        return response()->json([
            'message' => 'Lead status updated successfully',
            'updated_count' => $leads->count()
        ]);
    }

    /**
     * Show the import form.
     */
    public function importForm(): View
    {
        $this->authorize('create', Lead::class);

        $leadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->get(['id', 'name']);

        return view('leads.import', compact('leadSources', 'users'));
    }

    /**
     * Process CSV import.
     */
    public function import(Request $request): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $this->authorize('create', Lead::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'default_status' => 'required|in:' . implode(',', array_keys(Lead::getStatuses())),
            'default_interest_level' => 'required|in:low,medium,high,urgent',
            'skip_duplicates' => 'boolean',
            'import_notes' => 'nullable|string|max:1000',
        ]);

        $importService = app(\App\Domains\Lead\Services\LeadImportService::class);
        
        $options = [
            'lead_source_id' => $request->lead_source_id,
            'assigned_user_id' => $request->assigned_user_id,
            'default_status' => $request->default_status,
            'default_interest_level' => $request->default_interest_level,
            'skip_duplicates' => $request->boolean('skip_duplicates', true),
            'import_notes' => $request->import_notes ?? 'Imported from CSV',
        ];

        $results = $importService->importFromCsv($request->file('csv_file'), $options);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Import completed',
                'results' => $results
            ]);
        }

        $message = sprintf(
            'Import completed: %d successful, %d errors, %d skipped',
            $results['success'],
            $results['errors'],
            $results['skipped']
        );

        if ($results['errors'] > 0) {
            return redirect()->route('leads.import.form')
                ->with('warning', $message)
                ->with('import_details', $results['details']);
        }

        return redirect()->route('leads.index')
            ->with('success', $message);
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $csvContent = \App\Domains\Lead\Services\LeadImportService::generateCsvTemplate();
        
        $fileName = 'leads_import_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'leads_template');
        file_put_contents($tempFile, $csvContent);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend();
    }

    /**
     * Export leads to CSV.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::where('company_id', auth()->user()->company_id)
            ->with(['leadSource', 'assignedUser']);

        // Apply same filters as index page
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('lead_source_id')) {
            $query->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $leads = $query->get();

        $csvContent = $this->generateCsvContent($leads);
        
        $fileName = 'leads_export_' . date('Y-m-d_H-i-s') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'leads_export');
        file_put_contents($tempFile, $csvContent);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend();
    }

    /**
     * Generate CSV content for export.
     */
    protected function generateCsvContent($leads): string
    {
        $output = fopen('php://memory', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Last', 'First', 'Middle', 'Email', 'Phone', 'Company Name',
            'Company Address Line 1', 'Company Address Line 2', 'City', 'State', 'ZIP',
            'Website', 'Status', 'Lead Source', 'Assigned To', 'Total Score',
            'Interest Level', 'Created Date', 'Notes'
        ]);

        // CSV data
        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead->last_name,
                $lead->first_name,
                $lead->middle_name,
                $lead->email,
                $lead->phone,
                $lead->company_name,
                $lead->company_address_line_1,
                $lead->company_address_line_2,
                $lead->company_city,
                $lead->company_state,
                $lead->company_zip,
                $lead->website,
                ucfirst($lead->status),
                $lead->leadSource->name ?? '',
                $lead->assignedUser->name ?? '',
                $lead->total_score,
                ucfirst($lead->interest_level),
                $lead->created_at->format('Y-m-d H:i:s'),
                $lead->notes
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}