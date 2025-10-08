<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Client\Models\ClientITDocumentation;
use App\Domains\Client\Services\ClientITDocumentationService;
use App\Domains\Client\Services\ComplianceEngineService;
use App\Domains\Client\Services\DocumentationTemplateService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ITDocumentationController extends Controller
{
    private const NULLABLE_ARRAY_RULE = 'nullable|array';

    protected ClientITDocumentationService $service;

    protected DocumentationTemplateService $templateService;

    protected ComplianceEngineService $complianceService;

    public function __construct(
        ClientITDocumentationService $service,
        DocumentationTemplateService $templateService,
        ComplianceEngineService $complianceService
    ) {
        $this->service = $service;
        $this->templateService = $templateService;
        $this->complianceService = $complianceService;
    }

    /**
     * Display a listing of IT documentation.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'client_id', 'it_category', 'access_level', 'needs_review', 'active']);
        $filters['active'] = $filters['active'] ?? true; // Default to active documents

        $documentation = $this->service->searchDocumentation($filters);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $categories = ClientITDocumentation::getITCategories();
        $accessLevels = ClientITDocumentation::getAccessLevels();

        return view('clients.it-documentation.index', compact(
            'documentation',
            'clients',
            'categories',
            'accessLevels',
            'filters'
        ));
    }

    /**
     * Display IT documentation for a specific client.
     */
    public function clientIndex(Client $client, Request $request)
    {
        $filters = array_merge($request->only(['search', 'it_category', 'access_level', 'needs_review']), [
            'client_id' => $client->id,
            'active' => true,
        ]);

        $documentation = $this->service->searchDocumentation($filters);
        $statistics = $this->service->getClientStatistics($client->id);

        $categories = ClientITDocumentation::getITCategories();
        $accessLevels = ClientITDocumentation::getAccessLevels();

        return view('clients.it-documentation.client-index', compact(
            'client',
            'documentation',
            'statistics',
            'categories',
            'accessLevels',
            'filters'
        ));
    }

    /**
     * Show the form for creating new IT documentation.
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $selectedClientId = $request->get('client_id');
        $categories = ClientITDocumentation::getITCategories();
        $accessLevels = ClientITDocumentation::getAccessLevels();
        $reviewSchedules = ClientITDocumentation::getReviewSchedules();

        // Get template data
        $templates = $this->templateService->getTemplates();
        $availableTabs = $this->templateService->getAvailableTabs();
        $complianceFrameworks = $this->complianceService->getComplianceFrameworks();

        return view('clients.it-documentation.create', compact(
            'clients',
            'selectedClientId',
            'categories',
            'accessLevels',
            'reviewSchedules',
            'templates',
            'availableTabs',
            'complianceFrameworks'
        ));
    }

    /**
     * Store newly created IT documentation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'it_category' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getITCategories())),
            'access_level' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getAccessLevels())),
            'review_schedule' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getReviewSchedules())),
            'system_references' => self::NULLABLE_ARRAY_RULE,
            'ip_addresses' => self::NULLABLE_ARRAY_RULE,
            'software_versions' => self::NULLABLE_ARRAY_RULE,
            'compliance_requirements' => self::NULLABLE_ARRAY_RULE,
            'procedure_steps' => self::NULLABLE_ARRAY_RULE,
            'network_diagram' => 'nullable|string',
            'related_entities' => self::NULLABLE_ARRAY_RULE,
            'tags' => 'nullable|string',
            'file' => 'nullable|file|max:51200', // 50MB max

            // New tab configuration fields
            'enabled_tabs' => self::NULLABLE_ARRAY_RULE,
            'template_used' => 'nullable|string',

            // Additional comprehensive fields
            'status' => 'nullable|string|in:draft,review,approved,published',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'ports' => self::NULLABLE_ARRAY_RULE,
            'api_endpoints' => self::NULLABLE_ARRAY_RULE,
            'ssl_certificates' => self::NULLABLE_ARRAY_RULE,
            'dns_entries' => self::NULLABLE_ARRAY_RULE,
            'firewall_rules' => self::NULLABLE_ARRAY_RULE,
            'vpn_settings' => self::NULLABLE_ARRAY_RULE,
            'hardware_references' => self::NULLABLE_ARRAY_RULE,
            'environment_variables' => self::NULLABLE_ARRAY_RULE,
            'procedure_diagram' => 'nullable|string',
            'rollback_procedures' => self::NULLABLE_ARRAY_RULE,
            'prerequisites' => self::NULLABLE_ARRAY_RULE,
            'data_classification' => 'nullable|string',
            'encryption_required' => 'nullable|boolean',
            'audit_requirements' => self::NULLABLE_ARRAY_RULE,
            'security_controls' => self::NULLABLE_ARRAY_RULE,
            'external_resources' => self::NULLABLE_ARRAY_RULE,
            'vendor_contacts' => self::NULLABLE_ARRAY_RULE,
            'support_contracts' => self::NULLABLE_ARRAY_RULE,
            'test_cases' => self::NULLABLE_ARRAY_RULE,
            'validation_checklist' => self::NULLABLE_ARRAY_RULE,
            'performance_benchmarks' => self::NULLABLE_ARRAY_RULE,
            'health_checks' => self::NULLABLE_ARRAY_RULE,
            'automation_scripts' => self::NULLABLE_ARRAY_RULE,
            'integrations' => self::NULLABLE_ARRAY_RULE,
            'webhooks' => self::NULLABLE_ARRAY_RULE,
            'scheduled_tasks' => self::NULLABLE_ARRAY_RULE,
            'uptime_requirement' => 'nullable|numeric|min:0|max:100',
            'rto' => 'nullable|string',
            'rpo' => 'nullable|string',
            'performance_metrics' => self::NULLABLE_ARRAY_RULE,
            'alert_thresholds' => self::NULLABLE_ARRAY_RULE,
            'escalation_paths' => self::NULLABLE_ARRAY_RULE,
        ]);

        // Process tags
        if (isset($validated['tags']) && $validated['tags']) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        // Process network diagram JSON
        if (! empty($validated['network_diagram'])) {
            $validated['network_diagram'] = json_decode($validated['network_diagram'], true);
        }

        // Process procedure diagram JSON
        if (! empty($validated['procedure_diagram'])) {
            $validated['procedure_diagram'] = json_decode($validated['procedure_diagram'], true);
        }

        // If no enabled tabs specified, use defaults based on category
        if (empty($validated['enabled_tabs'])) {
            $validated['enabled_tabs'] = $this->templateService->getDefaultTabsForCategory($validated['it_category']);
        }

        $file = $request->file('file');
        $documentation = $this->service->createITDocumentation($validated, $file);

        // Calculate initial completeness
        $documentation->documentation_completeness = $this->templateService->calculateCompleteness($documentation);
        $documentation->save();

        return redirect()->route('clients.it-documentation.show', $documentation)
            ->with('success', 'IT documentation created successfully.');
    }

    /**
     * Display the specified IT documentation.
     */
    public function show(ClientITDocumentation $itDocumentation)
    {
        $this->authorize('view', $itDocumentation);

        $itDocumentation->load(['client', 'author', 'versions', 'parentDocument']);

        // Mark as accessed
        $this->service->markAsAccessed($itDocumentation);

        // Get related documents
        $relatedDocuments = $this->service->getRelatedDocuments($itDocumentation);

        return view('clients.it-documentation.show', compact('itDocumentation', 'relatedDocuments'));
    }

    /**
     * Show the form for editing IT documentation.
     */
    public function edit(ClientITDocumentation $itDocumentation)
    {
        $this->authorize('update', $itDocumentation);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $categories = ClientITDocumentation::getITCategories();
        $accessLevels = ClientITDocumentation::getAccessLevels();
        $reviewSchedules = ClientITDocumentation::getReviewSchedules();

        return view('clients.it-documentation.edit', compact(
            'itDocumentation',
            'clients',
            'categories',
            'accessLevels',
            'reviewSchedules'
        ));
    }

    /**
     * Update the specified IT documentation.
     */
    public function update(Request $request, ClientITDocumentation $itDocumentation)
    {
        $this->authorize('update', $itDocumentation);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'it_category' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getITCategories())),
            'access_level' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getAccessLevels())),
            'review_schedule' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getReviewSchedules())),
            'system_references' => self::NULLABLE_ARRAY_RULE,
            'ip_addresses' => self::NULLABLE_ARRAY_RULE,
            'software_versions' => self::NULLABLE_ARRAY_RULE,
            'compliance_requirements' => self::NULLABLE_ARRAY_RULE,
            'procedure_steps' => self::NULLABLE_ARRAY_RULE,
            'related_entities' => self::NULLABLE_ARRAY_RULE,
            'tags' => 'nullable|string',
            'file' => 'nullable|file|max:51200', // 50MB max
            'is_active' => 'boolean',
        ]);

        // Process tags
        if ($validated['tags']) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        $file = $request->file('file');
        $documentation = $this->service->updateITDocumentation($itDocumentation, $validated, $file);

        return redirect()->route('clients.it-documentation.show', $documentation)
            ->with('success', 'IT documentation updated successfully.');
    }

    /**
     * Remove the specified IT documentation.
     */
    public function destroy(ClientITDocumentation $itDocumentation)
    {
        $this->authorize('delete', $itDocumentation);

        $clientId = $itDocumentation->client_id;
        $itDocumentation->delete();

        return redirect()->route('clients.it-documentation.client-index', $clientId)
            ->with('success', 'IT documentation deleted successfully.');
    }

    /**
     * Download the attached file.
     */
    public function download(ClientITDocumentation $itDocumentation)
    {
        $this->authorize('view', $itDocumentation);

        if (! $itDocumentation->hasFile() || ! $itDocumentation->fileExists()) {
            abort(404, 'File not found');
        }

        // Mark as accessed
        $this->service->markAsAccessed($itDocumentation);

        return Storage::download($itDocumentation->file_path, $itDocumentation->original_filename);
    }

    /**
     * Create a new version of existing documentation.
     */
    public function createVersion(Request $request, ClientITDocumentation $itDocumentation)
    {
        $this->authorize('update', $itDocumentation);

        $validated = $request->validate([
            'description' => 'nullable|string',
            'procedure_steps' => self::NULLABLE_ARRAY_RULE,
            'version_notes' => 'nullable|string',
            'file' => 'nullable|file|max:51200',
        ]);

        // Add version notes to description if provided
        if ($request->version_notes) {
            $validated['description'] = ($validated['description'] ?? $itDocumentation->description).
                                      "\n\nVersion Notes: ".$request->version_notes;
        }

        $file = $request->file('file');
        $newVersion = $this->service->generateNewVersion($itDocumentation, $validated, $file);

        return redirect()->route('clients.it-documentation.show', $newVersion)
            ->with('success', 'New version created successfully.');
    }

    /**
     * Duplicate documentation for another client.
     */
    public function duplicate(Request $request, ClientITDocumentation $itDocumentation)
    {
        $this->authorize('view', $itDocumentation);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $duplicated = $this->service->duplicateForClient($itDocumentation, $validated['client_id']);

        return redirect()->route('clients.it-documentation.edit', $duplicated)
            ->with('success', 'Documentation duplicated successfully. You can now customize it for the new client.');
    }

    /**
     * Export documentation to CSV.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'client_id', 'it_category', 'access_level', 'needs_review', 'active']);
        $data = $this->service->exportData($filters);

        $filename = 'it_documentation_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Name',
                'Description',
                'Category',
                'Client',
                'Access Level',
                'Version',
                'Author',
                'Created At',
                'Last Reviewed',
                'Next Review',
                'Tags',
            ]);

            // CSV data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get overdue reviews.
     */
    public function overdueReviews(Request $request)
    {
        $clientId = $request->get('client_id');
        $overdueReviews = $this->service->getOverdueReviews($clientId);

        return view('clients.it-documentation.overdue-reviews', compact('overdueReviews'));
    }

    /**
     * Bulk update access levels.
     */
    public function bulkUpdateAccess(Request $request)
    {
        $validated = $request->validate([
            'documentation_ids' => 'required|array',
            'documentation_ids.*' => 'exists:client_it_documentation,id',
            'access_level' => 'required|in:'.implode(',', array_keys(ClientITDocumentation::getAccessLevels())),
        ]);

        $updated = $this->service->bulkUpdateAccessLevel(
            $validated['documentation_ids'],
            $validated['access_level']
        );

        return response()->json([
            'success' => true,
            'message' => "Updated access level for {$updated} documents.",
        ]);
    }

    /**
     * Mark review as completed.
     */
    public function completeReview(ClientITDocumentation $itDocumentation)
    {
        $this->authorize('update', $itDocumentation);

        $this->service->scheduleReview($itDocumentation, $itDocumentation->review_schedule);

        return redirect()->back()
            ->with('success', 'Review completed and next review scheduled.');
    }
}
