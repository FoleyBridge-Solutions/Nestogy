<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\TicketWorkflow;
use App\Domains\Ticket\Models\TicketStatusTransition;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Workflow Controller
 * 
 * Manages ticket workflows with status transitions, automated actions,
 * conditional rules, and workflow execution following the domain architecture pattern.
 */
class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows
     */
    public function index(Request $request)
    {
        $query = TicketWorkflow::where('tenant_id', auth()->user()->tenant_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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

        $workflows = $query->withCount(['transitions', 'tickets'])
                          ->orderBy('created_at', 'desc')
                          ->paginate(20)
                          ->appends($request->query());

        // Get available categories
        $categories = TicketWorkflow::where('tenant_id', auth()->user()->tenant_id)
                                   ->whereNotNull('category')
                                   ->distinct()
                                   ->pluck('category');

        if ($request->wantsJson()) {
            return response()->json([
                'workflows' => $workflows,
                'categories' => $categories,
            ]);
        }

        return view('tickets.workflows.index', compact('workflows', 'categories'));
    }

    /**
     * Show the form for creating a new workflow
     */
    public function create()
    {
        $availableStatuses = $this->getAvailableStatuses();
        $actionTypes = $this->getActionTypes();
        $conditionTypes = $this->getConditionTypes();
        
        $users = User::where('tenant_id', auth()->user()->tenant_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('tickets.workflows.create', compact(
            'availableStatuses', 'actionTypes', 'conditionTypes', 'users'
        ));
    }

    /**
     * Store a newly created workflow
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'initial_status' => 'required|string|max:50',
            'final_statuses' => 'nullable|array',
            'final_statuses.*' => 'string|max:50',
            'is_active' => 'boolean',
            'auto_assign_rules' => 'nullable|array',
            'global_conditions' => 'nullable|array',
            'transitions' => 'required|array|min:1',
            'transitions.*.from_status' => 'required|string|max:50',
            'transitions.*.to_status' => 'required|string|max:50',
            'transitions.*.name' => 'required|string|max:255',
            'transitions.*.conditions' => 'nullable|array',
            'transitions.*.actions' => 'nullable|array',
            'transitions.*.required_role' => 'nullable|string|max:50',
            'transitions.*.is_automatic' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        DB::transaction(function () use ($request) {
            // Create workflow
            $workflow = TicketWorkflow::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'initial_status' => $request->initial_status,
                'final_statuses' => $request->final_statuses ?? [],
                'is_active' => $request->boolean('is_active', true),
                'auto_assign_rules' => $request->auto_assign_rules ?? [],
                'global_conditions' => $request->global_conditions ?? [],
                'created_by' => auth()->id(),
            ]);

            // Create transitions
            foreach ($request->transitions as $transitionData) {
                $workflow->transitions()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'from_status' => $transitionData['from_status'],
                    'to_status' => $transitionData['to_status'],
                    'name' => $transitionData['name'],
                    'conditions' => $transitionData['conditions'] ?? [],
                    'actions' => $transitionData['actions'] ?? [],
                    'required_role' => $transitionData['required_role'] ?? null,
                    'is_automatic' => $transitionData['is_automatic'] ?? false,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Workflow created successfully',
                'workflow' => $workflow->load('transitions')
            ], 201);
        }

        return redirect()->route('tickets.workflows.index')
                        ->with('success', 'Workflow "' . $workflow->name . '" created successfully.');
    }

    /**
     * Display the specified workflow
     */
    public function show(TicketWorkflow $workflow)
    {
        $this->authorize('view', $workflow);

        $workflow->load(['transitions', 'createdBy']);

        // Get workflow statistics
        $stats = [
            'total_tickets' => $workflow->tickets()->count(),
            'active_tickets' => $workflow->tickets()->whereNotIn('status', $workflow->final_statuses)->count(),
            'completed_tickets' => $workflow->tickets()->whereIn('status', $workflow->final_statuses)->count(),
            'transitions_count' => $workflow->transitions()->count(),
            'automatic_transitions' => $workflow->transitions()->where('is_automatic', true)->count(),
        ];

        // Get recent activity
        $recentTickets = $workflow->tickets()
                                 ->with('client')
                                 ->latest('updated_at')
                                 ->limit(10)
                                 ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'workflow' => $workflow,
                'stats' => $stats,
                'recent_tickets' => $recentTickets,
            ]);
        }

        return view('tickets.workflows.show', compact('workflow', 'stats', 'recentTickets'));
    }

    /**
     * Show the form for editing the specified workflow
     */
    public function edit(TicketWorkflow $workflow)
    {
        $this->authorize('update', $workflow);

        $workflow->load('transitions');
        
        $availableStatuses = $this->getAvailableStatuses();
        $actionTypes = $this->getActionTypes();
        $conditionTypes = $this->getConditionTypes();
        
        $users = User::where('tenant_id', auth()->user()->tenant_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('tickets.workflows.edit', compact(
            'workflow', 'availableStatuses', 'actionTypes', 'conditionTypes', 'users'
        ));
    }

    /**
     * Update the specified workflow
     */
    public function update(Request $request, TicketWorkflow $workflow)
    {
        $this->authorize('update', $workflow);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'initial_status' => 'required|string|max:50',
            'final_statuses' => 'nullable|array',
            'final_statuses.*' => 'string|max:50',
            'is_active' => 'boolean',
            'auto_assign_rules' => 'nullable|array',
            'global_conditions' => 'nullable|array',
            'transitions' => 'required|array|min:1',
            'transitions.*.id' => 'nullable|integer|exists:ticket_status_transitions,id',
            'transitions.*.from_status' => 'required|string|max:50',
            'transitions.*.to_status' => 'required|string|max:50',
            'transitions.*.name' => 'required|string|max:255',
            'transitions.*.conditions' => 'nullable|array',
            'transitions.*.actions' => 'nullable|array',
            'transitions.*.required_role' => 'nullable|string|max:50',
            'transitions.*.is_automatic' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        DB::transaction(function () use ($request, $workflow) {
            // Update workflow
            $workflow->update($request->only([
                'name',
                'description',
                'category',
                'initial_status',
                'final_statuses',
                'auto_assign_rules',
                'global_conditions'
            ]) + [
                'is_active' => $request->boolean('is_active'),
            ]);

            // Handle transitions
            $existingTransitionIds = collect($request->transitions)
                                   ->pluck('id')
                                   ->filter()
                                   ->toArray();

            // Delete transitions not in the request
            $workflow->transitions()
                    ->whereNotIn('id', $existingTransitionIds)
                    ->delete();

            // Update or create transitions
            foreach ($request->transitions as $transitionData) {
                if (isset($transitionData['id'])) {
                    // Update existing transition
                    $transition = $workflow->transitions()->find($transitionData['id']);
                    if ($transition) {
                        $transition->update($transitionData);
                    }
                } else {
                    // Create new transition
                    $workflow->transitions()->create(array_merge($transitionData, [
                        'tenant_id' => auth()->user()->tenant_id,
                    ]));
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Workflow updated successfully',
                'workflow' => $workflow->load('transitions')
            ]);
        }

        return redirect()->route('tickets.workflows.index')
                        ->with('success', 'Workflow "' . $workflow->name . '" updated successfully.');
    }

    /**
     * Remove the specified workflow
     */
    public function destroy(TicketWorkflow $workflow)
    {
        $this->authorize('delete', $workflow);

        $workflowName = $workflow->name;

        // Check if workflow is being used by active tickets
        $activeTickets = $workflow->tickets()
                                 ->whereNotIn('status', $workflow->final_statuses)
                                 ->count();

        if ($activeTickets > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete workflow with active tickets'
                ], 422);
            }

            return redirect()->back()
                           ->with('error', 'Cannot delete workflow "' . $workflowName . '" because it has active tickets.');
        }

        $workflow->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Workflow deleted successfully'
            ]);
        }

        return redirect()->route('tickets.workflows.index')
                        ->with('success', 'Workflow "' . $workflowName . '" deleted successfully.');
    }

    /**
     * Execute workflow transition for a ticket
     */
    public function executeTransition(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'transition_id' => 'required|integer|exists:ticket_status_transitions,id',
            'notes' => 'nullable|string|max:500',
            'additional_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transition = TicketStatusTransition::findOrFail($request->transition_id);

        // Verify transition belongs to ticket's workflow
        if (!$ticket->workflow || $ticket->workflow_id !== $transition->workflow_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid transition for this ticket workflow'
            ], 422);
        }

        // Verify current status matches transition from_status
        if ($ticket->status !== $transition->from_status) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid transition from current status'
            ], 422);
        }

        try {
            DB::transaction(function () use ($ticket, $transition, $request) {
                // Execute the transition
                $result = $ticket->executeWorkflowTransition($transition, [
                    'notes' => $request->notes,
                    'additional_data' => $request->additional_data ?? [],
                    'executed_by' => auth()->id(),
                ]);

                if (!$result) {
                    throw new \Exception('Transition execution failed');
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Workflow transition executed successfully',
                'ticket' => $ticket->fresh(['workflow', 'assignee', 'client'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute transition: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available transitions for a ticket
     */
    public function getAvailableTransitions(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        if (!$ticket->workflow) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket has no workflow assigned'
            ], 422);
        }

        $availableTransitions = $ticket->getAvailableTransitions();

        return response()->json([
            'success' => true,
            'transitions' => $availableTransitions,
            'current_status' => $ticket->status,
        ]);
    }

    /**
     * Test workflow conditions
     */
    public function testConditions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conditions' => 'required|array',
            'ticket_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a temporary workflow instance for testing
        $tempWorkflow = new TicketWorkflow([
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $result = $tempWorkflow->evaluateConditions($request->conditions, $request->ticket_data);

        return response()->json([
            'success' => true,
            'result' => $result,
            'message' => $result ? 'Conditions passed' : 'Conditions failed'
        ]);
    }

    /**
     * Preview workflow actions
     */
    public function previewActions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'actions' => 'required|array',
            'ticket_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a temporary workflow instance for preview
        $tempWorkflow = new TicketWorkflow([
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $preview = $tempWorkflow->previewActions($request->actions, $request->ticket_data);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    /**
     * Duplicate workflow
     */
    public function duplicate(Request $request, TicketWorkflow $workflow)
    {
        $this->authorize('view', $workflow);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $duplicate = $workflow->duplicate($request->name);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Workflow duplicated successfully',
                'workflow' => $duplicate->load('transitions')
            ], 201);
        }

        return redirect()->route('tickets.workflows.edit', $duplicate)
                        ->with('success', 'Workflow duplicated successfully. Review and activate when ready.');
    }

    /**
     * Export workflow configuration
     */
    public function export(TicketWorkflow $workflow)
    {
        $this->authorize('view', $workflow);

        $workflow->load('transitions');

        $exportData = [
            'workflow' => $workflow->toArray(),
            'transitions' => $workflow->transitions->toArray(),
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()->name,
        ];

        $filename = 'workflow_' . $workflow->id . '_' . date('Y-m-d') . '.json';

        return response()->json($exportData)
               ->header('Content-Type', 'application/json')
               ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Import workflow configuration
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workflow_file' => 'required|file|mimes:json',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $content = file_get_contents($request->file('workflow_file')->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !isset($data['workflow']) || !isset($data['transitions'])) {
                throw new \Exception('Invalid workflow file format');
            }

            DB::transaction(function () use ($data, $request) {
                // Create new workflow
                $workflowData = $data['workflow'];
                unset($workflowData['id'], $workflowData['created_at'], $workflowData['updated_at']);
                
                $workflow = TicketWorkflow::create(array_merge($workflowData, [
                    'tenant_id' => auth()->user()->tenant_id,
                    'name' => $request->name,
                    'created_by' => auth()->id(),
                    'is_active' => false, // Start as inactive
                ]));

                // Create transitions
                foreach ($data['transitions'] as $transitionData) {
                    unset($transitionData['id'], $transitionData['workflow_id'], 
                          $transitionData['created_at'], $transitionData['updated_at']);
                    
                    $workflow->transitions()->create(array_merge($transitionData, [
                        'tenant_id' => auth()->user()->tenant_id,
                    ]));
                }
            });

            return redirect()->route('tickets.workflows.index')
                            ->with('success', 'Workflow imported successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to import workflow: ' . $e->getMessage());
        }
    }

    /**
     * Toggle workflow active status
     */
    public function toggleActive(TicketWorkflow $workflow)
    {
        $this->authorize('update', $workflow);

        $workflow->update([
            'is_active' => !$workflow->is_active,
        ]);

        $status = $workflow->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Workflow {$status} successfully",
            'is_active' => $workflow->is_active
        ]);
    }

    /**
     * Get available statuses for workflow configuration
     */
    private function getAvailableStatuses(): array
    {
        return [
            'new',
            'open',
            'in_progress',
            'pending',
            'waiting_for_customer',
            'waiting_for_vendor',
            'resolved',
            'closed',
            'cancelled',
            'on_hold',
            'escalated',
        ];
    }

    /**
     * Get available action types for workflow configuration
     */
    private function getActionTypes(): array
    {
        return [
            'assign_to_user',
            'assign_to_team',
            'set_priority',
            'add_tag',
            'remove_tag',
            'send_email',
            'create_task',
            'update_field',
            'add_note',
            'schedule_followup',
            'notify_client',
            'escalate',
        ];
    }

    /**
     * Get available condition types for workflow configuration
     */
    private function getConditionTypes(): array
    {
        return [
            'field_equals',
            'field_not_equals',
            'field_contains',
            'field_greater_than',
            'field_less_than',
            'has_tag',
            'missing_tag',
            'assigned_to',
            'created_by',
            'age_greater_than',
            'age_less_than',
            'priority_equals',
            'client_type',
            'business_hours',
        ];
    }
}