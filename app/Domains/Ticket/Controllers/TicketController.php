<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Ticket\Services\CommentService;
use App\Domains\Ticket\Services\ResolutionService;
use App\Domains\Ticket\Services\TimeTrackingService;
use App\Domains\Ticket\Services\WorkTypeClassificationService;
use App\Http\Controllers\Controller;
use App\Domains\Core\Controllers\Traits\UsesSelectedClient;
use App\Traits\FiltersClientsByAssignment;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Domain Ticket Controller
 *
 * Comprehensive ticket management with enhanced domain features including
 * CRUD operations, assignments, status management, scheduling, and integrations
 * following the domain architecture pattern.
 */
class TicketController extends Controller
{
    use UsesSelectedClient, FiltersClientsByAssignment;

    /**
     * Display a listing of tickets
     */
    public function index(Request $request)
    {
        // Start with base query
        $query = Ticket::query();
        
        // Apply client-based filtering for technicians
        $query = $this->applyClientFilter($query, 'client_id');
        
        // Also ensure company scope
        $query->where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($assigneeId = $request->get('assigned_to')) {
            $query->where('assigned_to', $assigneeId);
        }

        // Apply client filter from session if client is selected
        $this->applyClientFilter($query);

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Date range filters
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Advanced filters
        if ($request->has('overdue')) {
            $query->where('scheduled_at', '<', now())
                ->whereNotIn('status', ['closed', 'resolved']);
        }

        if ($request->has('unassigned')) {
            $query->whereNull('assigned_to');
        }

        if ($request->has('watching')) {
            $query->whereHas('watchers', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        // Sentiment filters
        if ($sentiment = $request->get('sentiment')) {
            $query->where('sentiment_label', $sentiment);
        }

        if ($request->has('negative_sentiment_attention')) {
            $query->whereIn('sentiment_label', ['NEGATIVE', 'WEAK_NEGATIVE'])
                ->where('sentiment_confidence', '>', 0.6);
        }

        if ($request->has('with_sentiment_analysis')) {
            $query->whereNotNull('sentiment_analyzed_at');
        }

        if ($request->has('without_sentiment_analysis')) {
            $query->whereNull('sentiment_analyzed_at');
        }

        if ($sentimentScoreMin = $request->get('sentiment_score_min')) {
            $query->where('sentiment_score', '>=', $sentimentScoreMin);
        }

        if ($sentimentScoreMax = $request->get('sentiment_score_max')) {
            $query->where('sentiment_score', '<=', $sentimentScoreMax);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'updated_at', 'scheduled_at', 'priority', 'status', 'number', 'sentiment_score', 'sentiment_analyzed_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tickets = $query->with([
            'client',
            'contact',
            'assignee',
            'asset',
            'template',
            'workflow',
            'watchers',
        ])
            ->paginate($request->get('per_page', 25))
            ->appends($request->query());

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        if ($request->wantsJson()) {
            return response()->json([
                'tickets' => $tickets,
                'filter_options' => $filterOptions,
            ]);
        }

        return view('tickets.index-livewire');
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $assignees = User::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        // Pre-select client from session
        $selectedClient = $this->getSelectedClient();
        if ($selectedClient && ! $clients->contains('id', $selectedClient->id)) {
            // If selected client is not in the available clients list, add it
            $selectedClient = null;
        }

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $statuses = ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'];

        return view('tickets.create', compact(
            'clients', 'assignees', 'selectedClient', 'priorities', 'statuses'
        ));
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($request) {
                    $query->where('client_id', $request->client_id);
                }),
            ],
            'subject' => 'required|string|max:255',
            'details' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:new,open,in_progress,pending,resolved,closed',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'scheduled_at' => 'nullable|date|after:now',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::transaction(function () use ($request, &$ticket) {
                // Generate ticket number
                $ticketNumber = $this->generateTicketNumber();

                $ticket = Ticket::create([
                    'company_id' => auth()->user()->company_id,
                    'number' => $ticketNumber,
                    'client_id' => $request->client_id,
                    'contact_id' => $request->contact_id,
                    'subject' => $request->subject,
                    'details' => $request->details,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'assigned_to' => $request->assigned_to,
                    'created_by' => auth()->id(),
                    'scheduled_at' => $request->scheduled_at,
                    'estimated_hours' => $request->estimated_hours,
                    'tags' => $request->tags ?? [],
                    'custom_fields' => $request->custom_fields ?? [],
                ]);

                // Auto-assign to workflow if applicable
                if ($request->workflow_id) {
                    $ticket->update(['workflow_id' => $request->workflow_id]);
                }

                // Add creator as watcher by default
                TicketWatcher::create([
                    'company_id' => auth()->user()->company_id,
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'email' => auth()->user()->email,
                    'added_by' => auth()->id(),
                    'notification_preferences' => [
                        'status_changes' => true,
                        'new_comments' => true,
                        'assignments' => true,
                        'priority_changes' => true,
                    ],
                ]);
            });

            Log::info('Ticket created', [
                'ticket_id' => $ticket->id,
                'number' => $ticket->number,
                'client_id' => $request->client_id,
                'user_id' => auth()->id(),
            ]);

            // Queue sentiment analysis for the new ticket
            \App\Jobs\AnalyzeTicketSentiment::queueTicketAnalysis($ticket->company_id, $ticket->id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket' => $ticket->load(['client', 'assignee']),
                ], 201);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket #'.$ticket->number.' created successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['password']),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create ticket',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create ticket. Please try again.');
        }
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'client',
            'contact',
            'assignee',
            'asset',
            'template',
            'workflow.transitions',
            'calendarEvents',
            'timeEntries.user',
            'watchers.user',
            'comments' => function ($query) {
                $query->with(['author', 'timeEntry'])->orderBy('created_at', 'desc');
            },
            'resolver',
            'reopener',
        ]);

        // Get available workflow transitions
        $availableTransitions = $ticket->workflow ?
            $ticket->getAvailableTransitions() : collect();

        // Get recent activity
        $recentActivity = $ticket->getRecentActivity(20);

        // Track view for collision detection
        $this->trackTicketView($ticket);

        // Get other viewers
        $otherViewers = $this->getTicketViewers($ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'ticket' => $ticket,
                'available_transitions' => $availableTransitions,
                'recent_activity' => $recentActivity,
                'other_viewers' => $otherViewers,
            ]);
        }

        return view('tickets.show-livewire', compact('ticket'));
    }

    /**
     * Store a reply for the specified ticket
     */
    public function storeReply(Request $request, Ticket $ticket, CommentService $commentService)
    {
        $this->authorize('addReply', $ticket);

        $validated = $request->validate([
            'content' => 'required|string|min:1',
            'visibility' => 'required|in:public,internal',
            'time_minutes' => 'nullable|integer|min:1|max:480',
        ]);

        try {
            // Prepare options for comment
            $options = [];
            if (isset($validated['time_minutes'])) {
                $options['time_minutes'] = $validated['time_minutes'];
                $options['billable'] = $request->boolean('billable', true);
            }

            // Add comment using the service
            $comment = $commentService->addComment(
                $ticket,
                $validated['content'],
                $validated['visibility'],
                auth()->user(),
                TicketComment::SOURCE_MANUAL,
                $options
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Comment added successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add comment: ' . $e->getMessage());
        }
    }

    /**
     * Resolve a ticket
     */
    public function resolve(Request $request, Ticket $ticket, ResolutionService $resolutionService)
    {
        $this->authorize('resolve', $ticket);

        $validated = $request->validate([
            'resolution_summary' => 'required|string|min:10|max:1000',
            'allow_client_reopen' => 'boolean',
        ]);

        try {
            $resolutionService->resolveTicket(
                $ticket,
                auth()->user(),
                $validated['resolution_summary'],
                $request->boolean('allow_client_reopen', true)
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket has been resolved successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to resolve ticket: ' . $e->getMessage());
        }
    }

    /**
     * Reopen a resolved ticket
     */
    public function reopen(Request $request, Ticket $ticket, ResolutionService $resolutionService)
    {
        $this->authorize('reopen', $ticket);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $resolutionService->reopenTicket(
                $ticket,
                auth()->user(),
                $validated['reason'] ?? null
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket has been reopened');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reopen ticket: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for the specified ticket
     */
    public function generatePdf(Ticket $ticket)
    {
        $this->authorize('generatePdf', $ticket);

        // Load all necessary relationships for the PDF
        $ticket->load([
            'client',
            'contact',
            'assignee',
            'creator',
            'replies.user',
            'timeEntries.user',
            'watchers.user',
        ]);

        // Calculate some stats for the PDF
        $totalTimeWorked = $ticket->getTotalTimeWorked();
        $billableTimeWorked = $ticket->getBillableTimeWorked();

        // Create a simple HTML view for the PDF content
        $html = view('tickets.pdf', compact('ticket', 'totalTimeWorked', 'billableTimeWorked'))->render();

        // For now, return the HTML directly
        // In production, you would use a PDF library like dompdf or wkhtmltopdf
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="ticket-'.($ticket->number ?? $ticket->id).'.html"');
    }

    /**
     * Show the form for editing the specified ticket
     */
    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $assignees = User::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $statuses = ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'];

        return view('tickets.edit', compact('ticket', 'clients', 'assignees', 'priorities', 'statuses'));
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($request) {
                    $query->where('client_id', $request->client_id);
                }),
            ],
            'subject' => 'required|string|max:255',
            'details' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:new,open,in_progress,pending,resolved,closed',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'scheduled_at' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldData = $ticket->toArray();

            $ticket->update($request->only([
                'client_id',
                'contact_id',
                'subject',
                'description',
                'priority',
                'status',
                'assigned_to',
                'scheduled_at',
                'estimated_hours',
                'tags',
                'custom_fields',
            ]));

            // Track significant changes
            $this->trackTicketChanges($ticket, $oldData, $request->all());

            Log::info('Ticket updated', [
                'ticket_id' => $ticket->id,
                'number' => $ticket->number,
                'user_id' => auth()->id(),
                'changes' => $ticket->getChanges(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket updated successfully',
                    'ticket' => $ticket->fresh(['client', 'assignee']),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket #'.$ticket->number.' updated successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket update failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update ticket',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update ticket. Please try again.');
        }
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        try {
            $ticketNumber = $ticket->number;

            // Soft delete to maintain data integrity
            $ticket->delete();

            Log::warning('Ticket deleted', [
                'ticket_id' => $ticket->id,
                'number' => $ticketNumber,
                'user_id' => auth()->id(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket deleted successfully',
                ]);
            }

            return redirect()->route('tickets.index')
                ->with('success', 'Ticket #'.$ticketNumber.' deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket deletion failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete ticket',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete ticket. Please try again.');
        }
    }

    /**
     * Add reply to ticket
     */
    public function addReply(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'type' => 'required|in:public,private,internal',
            'attachments' => 'nullable|array',
            'time_spent' => 'nullable|integer|min:0', // minutes
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reply = $ticket->addReply($request->only([
                'content',
                'type',
                'attachments',
                'time_spent',
            ]) + [
                'user_id' => auth()->id(),
            ]);

            Log::info('Ticket reply added', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'type' => $request->type,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully',
                'reply' => $reply->load('user'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to add ticket reply', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply',
            ], 500);
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldStatus = $ticket->status;
            $newStatus = $request->status;

            $ticket->update([
                'status' => $newStatus,
                'closed_at' => strtolower($newStatus) === 'closed' ? now() : null,
                'closed_by' => strtolower($newStatus) === 'closed' ? auth()->id() : null,
                'resolved_at' => in_array(strtolower($newStatus), ['resolved', 'closed']) ? now() : null,
            ]);

            // Create a status change reply
            $statusChangeNote = "Status changed from {$oldStatus} to {$newStatus}";
            if ($request->filled('notes')) {
                $statusChangeNote .= "\n\nNotes: ".$request->notes;
            }

            \App\Models\TicketReply::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'reply' => $statusChangeNote,
                'type' => 'internal',
                'replied_by' => auth()->id(),
            ]);

            Log::info('Ticket status updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'ticket' => $ticket->fresh(),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Ticket status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Failed to update ticket status', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to update ticket status');
        }
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:Low,Medium,High,Critical',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldPriority = $ticket->priority;

            $ticket->update(['priority' => $request->priority]);

            // Add priority change note
            if ($request->filled('notes')) {
                $ticket->addNote($request->notes, 'priority_change');
            }

            $ticket->addNote("Priority changed from {$oldPriority} to {$request->priority}", 'priority_change');

            Log::info('Ticket priority updated', [
                'ticket_id' => $ticket->id,
                'old_priority' => $oldPriority,
                'new_priority' => $request->priority,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Priority updated successfully',
                'ticket' => $ticket->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update ticket priority', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update priority',
            ], 500);
        }
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id)
                        ->where('status', true);
                }),
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->route('tickets.show', $ticket)
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldAssignee = $ticket->assignee;

            $ticket->update(['assigned_to' => $request->assigned_to]);

            // Create assignment message
            if ($request->assigned_to) {
                $newAssignee = User::find($request->assigned_to);
                $message = $oldAssignee ?
                    "Ticket reassigned from {$oldAssignee->name} to {$newAssignee->name}" :
                    "Ticket assigned to {$newAssignee->name}";

                // Auto-add assignee as watcher
                TicketWatcher::firstOrCreate([
                    'company_id' => auth()->user()->company_id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $request->assigned_to,
                ], [
                    'email' => $newAssignee->email,
                    'notification_preferences' => [
                        'status_changes' => true,
                        'new_comments' => true,
                        'assignments' => true,
                        'priority_changes' => true,
                    ],
                    'is_active' => true,
                ]);
            } else {
                $message = 'Ticket unassigned';
            }

            // Add assignment note with optional user notes
            if ($request->filled('notes')) {
                $message .= "\n\nNotes: ".$request->notes;
            }

            // Create assignment reply
            \App\Models\TicketReply::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'reply' => $message,
                'type' => 'internal',
                'replied_by' => auth()->id(),
            ]);

            // Create assignment record
            \App\Domains\Ticket\Models\TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'assigned_to' => $request->assigned_to,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'notes' => $request->notes,
                'is_active' => true,
            ]);

            Log::info('Ticket assigned', [
                'ticket_id' => $ticket->id,
                'old_assignee' => $oldAssignee?->id,
                'new_assignee' => $request->assigned_to,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assignment updated successfully',
                    'ticket' => $ticket->fresh(['assignee']),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Assignment updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to assign ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'assigned_to' => $request->assigned_to,
                'request_data' => $request->all(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update assignment',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to update assignment');
        }
    }

    /**
     * Schedule ticket
     */
    public function schedule(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
            'duration' => 'nullable|integer|min:15|max:480', // 15 minutes to 8 hours
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'is_onsite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->route('tickets.show', $ticket)
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create calendar event
            $event = $ticket->calendarEvents()->create([
                'company_id' => auth()->user()->company_id,
                'title' => 'Scheduled: '.$ticket->subject,
                'description' => $request->notes,
                'start_time' => $request->scheduled_at,
                'end_time' => Carbon::parse($request->scheduled_at)->addMinutes($request->duration ?? 60),
                'location' => $request->location,
                'status' => 'scheduled',
                'is_onsite' => $request->boolean('is_onsite', false),
                'is_all_day' => false,
            ]);

            // Update ticket's scheduled_at field
            $ticket->update([
                'scheduled_at' => $request->scheduled_at,
            ]);

            // Create scheduling reply for audit trail
            $scheduleMessage = 'Ticket scheduled for '.Carbon::parse($request->scheduled_at)->format('M j, Y \\a\\t g:i A');
            if ($request->location) {
                $scheduleMessage .= " at {$request->location}";
            }
            if ($request->notes) {
                $scheduleMessage .= "\n\nScheduling Notes: ".$request->notes;
            }

            \App\Models\TicketReply::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'reply' => $scheduleMessage,
                'type' => 'internal',
                'replied_by' => auth()->id(),
            ]);

            Log::info('Ticket scheduled', [
                'ticket_id' => $ticket->id,
                'scheduled_at' => $request->scheduled_at,
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket scheduled successfully',
                    'event' => $event,
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket scheduled successfully');

        } catch (\Exception $e) {
            Log::error('Failed to schedule ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to schedule ticket',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to schedule ticket');
        }
    }

    /**
     * Merge ticket into another ticket
     */
    public function merge(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'merge_into_number' => 'required|string',
            'merge_comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->route('tickets.show', $ticket)
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Find the target ticket
            $targetTicketNumber = $request->merge_into_number;
            $targetTicket = Ticket::where('company_id', auth()->user()->company_id)
                ->where(function ($query) use ($targetTicketNumber) {
                    $query->where('number', $targetTicketNumber)
                        ->orWhere('number', $targetTicketNumber);
                })
                ->first();

            if (! $targetTicket) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Target ticket #{$targetTicketNumber} not found",
                    ], 404);
                }

                return redirect()->route('tickets.show', $ticket)
                    ->with('error', "Target ticket #{$targetTicketNumber} not found");
            }

            if ($targetTicket->id === $ticket->id) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot merge ticket into itself',
                    ], 422);
                }

                return redirect()->route('tickets.show', $ticket)
                    ->with('error', 'Cannot merge ticket into itself');
            }

            DB::transaction(function () use ($ticket, $targetTicket, $request) {
                // Move all replies to target ticket
                $ticket->replies()->update(['ticket_id' => $targetTicket->id]);

                // Move all time entries to target ticket
                $ticket->timeEntries()->update(['ticket_id' => $targetTicket->id]);

                // Move all calendar events to target ticket
                $ticket->calendarEvents()->update(['ticket_id' => $targetTicket->id]);

                // Move all assignments to target ticket
                $ticket->assignments()->update(['ticket_id' => $targetTicket->id]);

                // Create merge notification in target ticket
                $sourceTicketNumber = $ticket->number ?? $ticket->id;
                $mergeMessage = "Ticket #{$sourceTicketNumber} ({$ticket->subject}) was merged into this ticket";
                if ($request->merge_comment) {
                    $mergeMessage .= "\n\nMerge Comment: ".$request->merge_comment;
                }

                \App\Models\TicketReply::create([
                    'ticket_id' => $targetTicket->id,
                    'company_id' => auth()->user()->company_id,
                    'reply' => $mergeMessage,
                    'type' => 'internal',
                    'replied_by' => auth()->id(),
                ]);

                // Add original ticket details to target ticket
                $originalTicketDetails = "Original Ticket Details:\n";
                $originalTicketDetails .= "Subject: {$ticket->subject}\n";
                $originalTicketDetails .= "Priority: {$ticket->priority}\n";
                $originalTicketDetails .= "Status: {$ticket->status}\n";
                $originalTicketDetails .= "Description: {$ticket->details}\n";

                \App\Models\TicketReply::create([
                    'ticket_id' => $targetTicket->id,
                    'company_id' => auth()->user()->company_id,
                    'reply' => $originalTicketDetails,
                    'type' => 'internal',
                    'replied_by' => auth()->id(),
                ]);

                // Mark the original ticket as merged/closed
                $ticket->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                ]);

                // Add note to original ticket about merge
                $targetTicketNumber = $targetTicket->number ?? $targetTicket->id;
                \App\Models\TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'company_id' => auth()->user()->company_id,
                    'reply' => "This ticket was merged into Ticket #{$targetTicketNumber}",
                    'type' => 'internal',
                    'replied_by' => auth()->id(),
                ]);
            });

            Log::info('Ticket merged', [
                'source_ticket_id' => $ticket->id,
                'target_ticket_id' => $targetTicket->id,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket merged successfully',
                    'target_ticket_url' => route('tickets.show', $targetTicket),
                ]);
            }

            $targetTicketNum = $targetTicket->number ?? $targetTicket->id;

            return redirect()->route('tickets.show', $targetTicket)
                ->with('success', "Ticket merged successfully into #{$targetTicketNum}");

        } catch (\Exception $e) {
            Log::error('Failed to merge ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge ticket',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to merge ticket');
        }
    }

    /**
     * Get viewers currently viewing this ticket (for collision detection)
     */
    public function getViewers(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        // Update current user's view timestamp
        $this->trackTicketView($ticket);

        // Get other viewers
        $otherViewers = $this->getTicketViewers($ticket);

        return response()->json([
            'viewers' => $otherViewers,
            'message' => count($otherViewers) > 0
                ? 'Others currently viewing: '.collect($otherViewers)->pluck('name')->join(', ')
                : '',
        ]);
    }

    /**
     * Export tickets to CSV
     */
    public function export(Request $request)
    {
        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply client filter from session if client is selected
        $this->applyClientFilter($query);

        $tickets = $query->with(['client', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'tickets_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Ticket #',
                'Subject',
                'Client',
                'Status',
                'Priority',
                'Assignee',
                'Created Date',
                'Due Date',
                'Resolved Date',
            ]);

            // CSV data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->number,
                    $ticket->subject,
                    $ticket->client->name,
                    ucfirst($ticket->status),
                    $ticket->priority,
                    $ticket->assignee?->name ?? 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->scheduled_at?->format('Y-m-d H:i:s') ?? '',
                    $ticket->resolved_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $year = date('Y');

        // Get the last ticket number for this year
        $lastTicket = Ticket::where('company_id', auth()->user()->company_id)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract sequence number and increment
            $parts = explode('-', $lastTicket->number);
            $sequence = intval(end($parts)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Get filter options for the index page
     */
    private function getFilterOptions(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'statuses' => ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'],
            'priorities' => ['Low', 'Medium', 'High', 'Critical'],
            'clients' => Client::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'assignees' => User::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /**
     * Track ticket view for collision detection
     */
    private function trackTicketView(Ticket $ticket): void
    {
        $user = auth()->user();
        $cacheKey = "ticket_viewer_{$ticket->id}_{$user->id}";

        // Store viewer information for 5 minutes
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => session()->getId(),
        ], now()->addMinutes(5));
    }

    /**
     * Get other users viewing this ticket
     */
    private function getTicketViewers(Ticket $ticket): array
    {
        $currentUserId = auth()->id();
        $currentSessionId = session()->getId();
        $viewers = [];

        // Look for all cached viewers for this ticket
        $pattern = "ticket_viewer_{$ticket->id}_*";

        // Note: This is a simplified implementation
        // In production, you might want to use Redis or a more sophisticated cache pattern
        $cacheKeys = collect();

        // For Laravel cache, we'll need to check for known user IDs
        // This is a simplified approach - in production you'd use Redis SCAN or similar
        $companyUsers = User::where('company_id', auth()->user()->company_id)
            ->active()
            ->pluck('id');

        foreach ($companyUsers as $userId) {
            $cacheKey = "ticket_viewer_{$ticket->id}_{$userId}";
            if (Cache::has($cacheKey)) {
                $viewerData = Cache::get($cacheKey);

                // Only include other users (not current user) and recent views
                if ($viewerData['user_id'] != $currentUserId &&
                    $viewerData['session_id'] != $currentSessionId &&
                    Carbon::parse($viewerData['last_viewed'])->gt(now()->subMinutes(5))) {

                    $viewers[] = [
                        'id' => $viewerData['user_id'],
                        'name' => $viewerData['user_name'],
                        'last_viewed' => Carbon::parse($viewerData['last_viewed'])->diffForHumans(),
                    ];
                }
            }
        }

        return $viewers;
    }

    /**
     * Track significant ticket changes for audit log
     */
    private function trackTicketChanges(Ticket $ticket, array $oldData, array $newData): void
    {
        $changes = [];
        $significantFields = ['status', 'priority', 'assigned_to', 'scheduled_at'];

        foreach ($significantFields as $field) {
            if (isset($oldData[$field]) && isset($newData[$field]) &&
                $oldData[$field] !== $newData[$field]) {
                $changes[$field] = [
                    'old' => $oldData[$field],
                    'new' => $newData[$field],
                ];
            }
        }

        if (! empty($changes)) {
            // Log significant changes
            Log::info('Ticket changes tracked', [
                'ticket_id' => $ticket->id,
                'changes' => $changes,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // ===========================================
    // SMART TIME TRACKING ENDPOINTS
    // ===========================================

    /**
     * Get intelligent tracking information for a ticket
     */
    public function getSmartTrackingInfo(Ticket $ticket)
    {
        try {
            $timeTrackingService = new TimeTrackingService;
            $classificationService = new WorkTypeClassificationService;

            $trackingInfo = $timeTrackingService->startSmartTracking($ticket, auth()->user());
            $templates = $classificationService->getTemplateSuggestions($ticket, 5);

            return response()->json([
                'tracking_info' => $trackingInfo,
                'templates' => $templates->map(function ($suggestion) {
                    return [
                        'id' => $suggestion['template']->id,
                        'name' => $suggestion['template']->name,
                        'description' => $suggestion['template']->description,
                        'work_type' => $suggestion['template']->work_type,
                        'default_hours' => $suggestion['template']->default_hours,
                        'confidence' => $suggestion['confidence'],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            \Log::error('Smart tracking info error: '.$e->getMessage(), [
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error loading tracking information: '.$e->getMessage(),
                'tracking_info' => [
                    'error' => 'Unable to load tracking info',
                    'message' => $e->getMessage(),
                ],
                'templates' => [],
            ], 500);
        }
    }

    /**
     * Start smart timer for a ticket
     */
    public function startSmartTimer(Request $request, Ticket $ticket)
    {
        $timeTrackingService = new TimeTrackingService;

        try {
            $timeEntry = $timeTrackingService->startTracking($ticket, auth()->user(), [
                'work_type' => $request->input('work_type', 'general_support'),
                'description' => $request->input('description'),
                'auto_start' => true,
            ]);

            return response()->json([
                'success' => true,
                'time_entry' => $timeEntry,
                'message' => 'Timer started successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pause active timer for a ticket
     */
    public function pauseTimer(Request $request, Ticket $ticket)
    {
        $timeTrackingService = new TimeTrackingService;

        try {
            // Find the active timer entry for this ticket and user
            $activeEntry = TicketTimeEntry::where('ticket_id', $ticket->id)
                ->where('user_id', auth()->id())
                ->where('company_id', auth()->user()->company_id)
                ->where('entry_type', TicketTimeEntry::TYPE_TIMER)
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->first();

            if (! $activeEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active timer found for this ticket',
                ], 400);
            }

            $pausedEntry = $timeTrackingService->pauseTracking($activeEntry, $request->input('reason'));

            return response()->json([
                'success' => true,
                'time_entry' => $pausedEntry,
                'message' => 'Timer paused successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop active timer for a ticket
     */
    public function stopTimer(Request $request, Ticket $ticket)
    {
        try {
            // Find the active timer entry for this ticket and user
            $activeEntry = TicketTimeEntry::where('ticket_id', $ticket->id)
                ->where('user_id', auth()->id())
                ->where('company_id', auth()->user()->company_id)
                ->where('entry_type', TicketTimeEntry::TYPE_TIMER)
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->first();

            if (! $activeEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active timer found for this ticket',
                ], 400);
            }

            // Update description if provided
            if ($request->has('description')) {
                $activeEntry->description = $request->input('description');
            }

            // Update work performed if provided
            if ($request->has('work_performed')) {
                $activeEntry->work_performed = $request->input('work_performed');
            }

            // Stop the timer using the model's method
            $hoursWorked = $activeEntry->stopTimer();

            // Calculate amount if billable
            $amount = 0;
            if ($activeEntry->billable && $activeEntry->hourly_rate) {
                $amount = $hoursWorked * $activeEntry->hourly_rate;
                $activeEntry->amount = $amount;
                $activeEntry->save();
            }

            return response()->json([
                'success' => true,
                'time_entry' => $activeEntry->fresh(),
                'message' => 'Timer stopped and saved successfully',
                'hours_worked' => $hoursWorked,
                'amount' => $amount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create time entry from template
     */
    public function createTimeFromTemplate(Request $request, Ticket $ticket)
    {
        $request->validate([
            'template_id' => 'required|exists:time_entry_templates,id',
            'hours_worked' => 'nullable|numeric|min:0.01|max:24',
            'description' => 'nullable|string|max:1000',
            'billable' => 'nullable|boolean',
        ]);

        $timeTrackingService = new TimeTrackingService;

        try {
            $overrides = array_filter([
                'hours_worked' => $request->input('hours_worked'),
                'description' => $request->input('description'),
                'billable' => $request->input('billable'),
            ]);

            $timeEntry = $timeTrackingService->createFromTemplate(
                $request->input('template_id'),
                $ticket,
                auth()->user(),
                $overrides
            );

            return response()->json([
                'success' => true,
                'time_entry' => $timeEntry,
                'message' => 'Time entry created from template',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get billing dashboard for current user
     */
    public function getBillingDashboard(Request $request)
    {
        $timeTrackingService = new TimeTrackingService;
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : today();

        $dashboard = $timeTrackingService->getBillingDashboard(auth()->user(), $date);

        return response()->json($dashboard);
    }

    /**
     * Validate time entry data
     */
    public function validateTimeEntry(Request $request)
    {
        $timeTrackingService = new TimeTrackingService;

        $validation = $timeTrackingService->validateTimeEntry($request->all());

        return response()->json($validation);
    }

    /**
     * Get work type suggestions for a ticket
     */
    public function getWorkTypeSuggestions(Ticket $ticket)
    {
        $classificationService = new WorkTypeClassificationService;

        $suggestions = $classificationService->getWorkTypeSuggestions($ticket);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get current rate information
     */
    public function getCurrentRateInfo(Request $request)
    {
        $timeTrackingService = new TimeTrackingService;

        $time = $request->input('time') ? Carbon::parse($request->input('time')) : now();
        $context = $request->input('context', []);

        $rateInfo = $timeTrackingService->getSmartRateInfo($time, $context);

        return response()->json($rateInfo);
    }

    /**
     * Get time entry templates for company
     */
    public function getTimeTemplates(Request $request)
    {
        $query = \App\Domains\Ticket\Models\TimeEntryTemplate::where('company_id', auth()->user()->company_id)
            ->active();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($workType = $request->input('work_type')) {
            $query->where('work_type', $workType);
        }

        $templates = $query->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Search tickets for merge functionality
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $exclude = $request->get('exclude');

        if (strlen($query) < 2) {
            return response()->json(['tickets' => []]);
        }

        $ticketsQuery = Ticket::where('company_id', auth()->user()->company_id)
            ->with(['client:id,name', 'assignee:id,name'])
            ->where('status', '!=', 'closed'); // Don't allow merging into closed tickets

        // Exclude specific ticket (usually the current one)
        if ($exclude) {
            $ticketsQuery->where('id', '!=', $exclude);
        }

        // Search by number, subject, or client name
        $ticketsQuery->where(function ($q) use ($query) {
            $q->where('number', 'like', "%{$query}%")
                ->orWhere('subject', 'like', "%{$query}%")
                ->orWhereHas('client', function ($cq) use ($query) {
                    $cq->where('name', 'like', "%{$query}%");
                });
        });

        $tickets = $ticketsQuery->orderBy('number', 'desc')
            ->limit(10) // Limit results for performance
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'number' => $ticket->number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'created_at' => $ticket->created_at->toISOString(),
                    'client' => $ticket->client ? [
                        'id' => $ticket->client->id,
                        'name' => $ticket->client->name,
                    ] : null,
                    'assignee' => $ticket->assignee ? [
                        'id' => $ticket->assignee->id,
                        'name' => $ticket->assignee->name,
                    ] : null,
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }
    
    /**
     * Display active timers
     */
    public function activeTimers(Request $request)
    {
        $user = auth()->user();
        $query = \App\Domains\Ticket\Models\TicketTimeEntry::runningTimers()
            ->with(['ticket', 'user', 'ticket.client'])
            ->where('company_id', $user->company_id);
        
        // If not admin, only show user's own timers
        if (!$user->hasRole('admin') && !$user->hasPermission('tickets.view-all-timers')) {
            $query->where('user_id', $user->id);
        }
        
        $activeTimers = $query->orderBy('started_at', 'desc')->paginate(20);
        
        // Get timer statistics
        $statistics = [
            'total_active' => $activeTimers->total(),
            'total_time_today' => $this->getTodayTimeStatistics($user),
            'users_with_timers' => $activeTimers->pluck('user_id')->unique()->count(),
        ];
        
        return view('tickets.active-timers', compact('activeTimers', 'statistics'));
    }
    
    /**
     * Display SLA violations
     */
    public function slaViolations(Request $request)
    {
        // Redirect to tickets index with SLA violation filter
        return redirect()->route('tickets.index', [
            'filter' => 'sla_violation',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display SLA warning tickets
     */
    public function slaWarning(Request $request)
    {
        // Redirect to tickets index with SLA warning filter
        return redirect()->route('tickets.index', [
            'filter' => 'sla_warning',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display unassigned tickets
     */
    public function unassigned(Request $request)
    {
        // Redirect to tickets index with unassigned filter
        return redirect()->route('tickets.index', [
            'filter' => 'unassigned',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display tickets due today
     */
    public function dueToday(Request $request)
    {
        // Redirect to tickets index with due today filter
        return redirect()->route('tickets.index', [
            'filter' => 'due_today',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display team queue
     */
    public function teamQueue(Request $request)
    {
        // Redirect to tickets index with team filter
        return redirect()->route('tickets.index', [
            'filter' => 'team',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display tickets waiting for customer
     */
    public function customerWaiting(Request $request)
    {
        // Redirect to tickets index with waiting customer status
        return redirect()->route('tickets.index', [
            'selectedStatuses' => ['waiting_customer']
        ]);
    }
    
    /**
     * Display watched tickets
     */
    public function watched(Request $request)
    {
        // Redirect to tickets index with watched filter
        return redirect()->route('tickets.index', [
            'filter' => 'watched'
        ]);
    }
    
    /**
     * Display escalated tickets
     */
    public function escalated(Request $request)
    {
        // Redirect to tickets index with escalated filter
        return redirect()->route('tickets.index', [
            'filter' => 'escalated',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold']
        ]);
    }
    
    /**
     * Display merged tickets
     */
    public function merged(Request $request)
    {
        // Redirect to tickets index with merged filter
        return redirect()->route('tickets.index', [
            'filter' => 'merged'
        ]);
    }
    
    /**
     * Display archived tickets
     */
    public function archive(Request $request)
    {
        // Redirect to tickets index with archived filter
        return redirect()->route('tickets.index', [
            'filter' => 'archived'
        ]);
    }
    

    

    
    /**
     * Display time and billing view
     */
    public function timeBilling(Request $request)
    {
        $user = auth()->user();
        $timeEntries = \App\Domains\Ticket\Models\TicketTimeEntry::where('company_id', $user->company_id);
        
        if (!$user->hasRole('admin')) {
            $timeEntries->where('user_id', $user->id);
        }
        
        $timeEntries = $timeEntries->with(['ticket', 'user', 'ticket.client'])
            ->orderBy('work_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $statistics = [
            'total_hours' => $timeEntries->sum('hours_worked'),
            'billable_hours' => $timeEntries->where('billable', true)->sum('hours_worked'),
            'total_amount' => $timeEntries->sum('amount'),
        ];
        
        return view('tickets.time-billing', compact('timeEntries', 'statistics'));
    }
    
    /**
     * Display analytics dashboard
     */
    public function analytics(Request $request)
    {
        $company_id = auth()->user()->company_id;
        
        // Get various analytics metrics
        $metrics = [
            'total_tickets' => Ticket::where('company_id', $company_id)->count(),
            'open_tickets' => Ticket::where('company_id', $company_id)->whereIn('status', ['open', 'in_progress'])->count(),
            'avg_resolution_time' => Ticket::where('company_id', $company_id)
                ->whereNotNull('resolved_at')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600) as avg_hours")
                ->value('avg_hours'),
            'tickets_by_priority' => Ticket::where('company_id', $company_id)
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'tickets_by_status' => Ticket::where('company_id', $company_id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];
        
        return view('tickets.analytics', compact('metrics'));
    }
    
    /**
     * Display knowledge base integration
     */
    public function knowledgeBase(Request $request)
    {
        // This would integrate with knowledge base module
        $articles = [];
        $categories = [];
        
        return view('tickets.knowledge-base', compact('articles', 'categories'));
    }
    
    /**
     * Display automation rules
     */
    public function automationRules(Request $request)
    {
        $workflows = \App\Domains\Ticket\Models\TicketWorkflow::where('company_id', auth()->user()->company_id)
            ->with(['creator'])
            ->orderBy('name')
            ->paginate(20);
        
        return view('tickets.automation-rules', compact('workflows'));
    }
    
    /**
     * Get today's time statistics (helper method)
     */
    private function getTodayTimeStatistics($user)
    {
        $query = \App\Domains\Ticket\Models\TicketTimeEntry::where('company_id', $user->company_id)
            ->where('work_date', today());
            
        if (!$user->hasRole('admin') && !$user->hasPermission('tickets.view-all-timers')) {
            $query->where('user_id', $user->id);
        }
        
        return [
            'total_hours' => $query->sum('hours_worked'),
            'billable_hours' => $query->billable()->sum('hours_worked'),
            'entries_count' => $query->count()
        ];
    }
}
