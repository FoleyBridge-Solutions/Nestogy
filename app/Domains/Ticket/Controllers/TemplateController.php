<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\TicketTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Template Controller
 * 
 * Manages ticket templates with full CRUD operations, template processing,
 * duplication, and usage analytics following the domain architecture pattern.
 */
class TemplateController extends Controller
{
    /**
     * Display a listing of ticket templates
     */
    public function index(Request $request)
    {
        $query = TicketTemplate::where('tenant_id', auth()->user()->tenant_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('subject_template', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Apply category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Apply priority filter
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        $templates = $query->with('defaultAssignee')
                          ->withCount('tickets')
                          ->orderBy('name')
                          ->paginate(20)
                          ->appends($request->query());

        // Get filter options
        $categories = TicketTemplate::where('tenant_id', auth()->user()->tenant_id)
                                   ->whereNotNull('category')
                                   ->distinct()
                                   ->pluck('category');

        if ($request->wantsJson()) {
            return response()->json([
                'templates' => $templates,
                'categories' => $categories,
            ]);
        }

        return view('tickets.templates.index', compact('templates', 'categories'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        $assignees = User::where('tenant_id', auth()->user()->tenant_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $categories = $this->getAvailableCategories();

        return view('tickets.templates.create', compact('assignees', 'priorities', 'categories'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_template' => 'required|string|max:255',
            'body_template' => 'required|string',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'category' => 'nullable|string|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'default_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'is_active' => 'boolean',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $template = TicketTemplate::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
            'subject_template' => $request->subject_template,
            'body_template' => $request->body_template,
            'priority' => $request->priority ?? 'Medium',
            'category' => $request->category,
            'estimated_hours' => $request->estimated_hours,
            'default_assignee_id' => $request->default_assignee_id,
            'is_active' => $request->boolean('is_active', true),
            'custom_fields' => $request->custom_fields ?? [],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template created successfully',
                'template' => $template->load('defaultAssignee')
            ], 201);
        }

        return redirect()->route('tickets.templates.index')
                        ->with('success', 'Template "' . $template->name . '" created successfully.');
    }

    /**
     * Display the specified template
     */
    public function show(TicketTemplate $template)
    {
        $this->authorize('view', $template);

        $template->load(['defaultAssignee', 'tickets' => function($query) {
            $query->latest()->limit(10);
        }]);

        // Get usage statistics
        $usageStats = $template->getUsageStats();

        // Get template preview
        $preview = $template->preview;

        if (request()->wantsJson()) {
            return response()->json([
                'template' => $template,
                'usage_stats' => $usageStats,
                'preview' => $preview,
            ]);
        }

        return view('tickets.templates.show', compact('template', 'usageStats', 'preview'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(TicketTemplate $template)
    {
        $this->authorize('update', $template);

        $assignees = User::where('tenant_id', auth()->user()->tenant_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $categories = $this->getAvailableCategories();

        return view('tickets.templates.edit', compact('template', 'assignees', 'priorities', 'categories'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, TicketTemplate $template)
    {
        $this->authorize('update', $template);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_template' => 'required|string|max:255',
            'body_template' => 'required|string',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'category' => 'nullable|string|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'default_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'is_active' => 'boolean',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $template->update($request->only([
            'name',
            'description',
            'subject_template',
            'body_template',
            'priority',
            'category',
            'estimated_hours',
            'default_assignee_id',
            'custom_fields'
        ]) + ['is_active' => $request->boolean('is_active')]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully',
                'template' => $template->load('defaultAssignee')
            ]);
        }

        return redirect()->route('tickets.templates.index')
                        ->with('success', 'Template "' . $template->name . '" updated successfully.');
    }

    /**
     * Remove the specified template
     */
    public function destroy(TicketTemplate $template)
    {
        $this->authorize('delete', $template);

        $templateName = $template->name;

        // Check if template is being used by active recurring tickets
        $activeRecurring = $template->recurringTickets()->where('is_active', true)->count();
        
        if ($activeRecurring > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete template with active recurring tickets'
                ], 422);
            }

            return redirect()->back()
                           ->with('error', 'Cannot delete template "' . $templateName . '" because it has active recurring tickets.');
        }

        $template->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        }

        return redirect()->route('tickets.templates.index')
                        ->with('success', 'Template "' . $templateName . '" deleted successfully.');
    }

    /**
     * Duplicate the specified template
     */
    public function duplicate(Request $request, TicketTemplate $template)
    {
        $this->authorize('view', $template);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $duplicate = $template->duplicate($request->name);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template duplicated successfully',
                'template' => $duplicate->load('defaultAssignee')
            ], 201);
        }

        return redirect()->route('tickets.templates.edit', $duplicate)
                        ->with('success', 'Template duplicated successfully. Review and activate when ready.');
    }

    /**
     * Preview template with sample data
     */
    public function preview(Request $request, TicketTemplate $template)
    {
        $this->authorize('view', $template);

        $sampleData = $request->get('variables', []);
        $processed = $template->processTemplate($sampleData);

        return response()->json([
            'success' => true,
            'preview' => $processed,
            'available_variables' => $template->available_variables,
        ]);
    }

    /**
     * Create ticket from template
     */
    public function createTicket(Request $request, TicketTemplate $template)
    {
        $this->authorize('view', $template);

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'variables' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $ticket = $template->createTicket($request->only([
                'client_id',
                'variables',
                'assigned_to',
                'scheduled_at',
            ]));

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket created successfully from template',
                    'ticket' => $ticket
                ], 201);
            }

            return redirect()->route('tickets.show', $ticket)
                            ->with('success', 'Ticket created successfully from template.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create ticket from template'
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to create ticket from template.');
        }
    }

    /**
     * Export templates to CSV
     */
    public function export(Request $request)
    {
        $query = TicketTemplate::where('tenant_id', auth()->user()->tenant_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $templates = $query->with('defaultAssignee')->orderBy('name')->get();
        $filename = 'ticket-templates_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($templates) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Name',
                'Category',
                'Priority',
                'Subject Template',
                'Estimated Hours',
                'Default Assignee',
                'Active',
                'Tickets Created',
                'Created Date'
            ]);

            // CSV data
            foreach ($templates as $template) {
                fputcsv($file, [
                    $template->name,
                    $template->category,
                    $template->priority,
                    $template->subject_template,
                    $template->estimated_hours,
                    $template->defaultAssignee?->name,
                    $template->is_active ? 'Yes' : 'No',
                    $template->tickets_count ?? 0,
                    $template->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get available categories for filters and forms
     */
    private function getAvailableCategories(): array
    {
        return [
            'Hardware Issues',
            'Software Problems',
            'Network Issues',
            'Security Incidents',
            'Maintenance Tasks',
            'User Support',
            'System Administration',
            'Data Backup',
            'Performance Issues',
            'Training Requests',
        ];
    }
}