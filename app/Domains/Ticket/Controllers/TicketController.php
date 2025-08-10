<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Models\User;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * Domain Ticket Controller
 * 
 * Comprehensive ticket management with enhanced domain features including
 * CRUD operations, assignments, status management, scheduling, and integrations
 * following the domain architecture pattern.
 */
class TicketController extends Controller
{
    /**
     * Display a listing of tickets
     */
    public function index(Request $request)
    {
        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
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

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

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
            $query->where('due_date', '<', now())
                  ->whereNotIn('status', ['closed', 'resolved']);
        }

        if ($request->has('unassigned')) {
            $query->whereNull('assigned_to');
        }

        if ($request->has('watching')) {
            $query->whereHas('watchers', function($q) {
                $q->where('user_id', auth()->id());
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'updated_at', 'due_date', 'priority', 'status', 'ticket_number'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tickets = $query->with([
                'client', 
                'contact', 
                'assignee', 
                'asset', 
                'category',
                'template',
                'workflow',
                'priorityQueue',
                'watchers' => function($q) {
                    $q->limit(5);
                }
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

        return view('tickets.index', compact('tickets', 'filterOptions'));
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
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->get();

        // Pre-select client if provided
        $selectedClient = null;
        if ($clientId = $request->get('client_id')) {
            $selectedClient = $clients->firstWhere('id', $clientId);
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
            'description' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:new,open,in_progress,pending,resolved,closed',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'due_date' => 'nullable|date|after:now',
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
                    'ticket_number' => $ticketNumber,
                    'client_id' => $request->client_id,
                    'contact_id' => $request->contact_id,
                    'subject' => $request->subject,
                    'description' => $request->description,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'assigned_to' => $request->assigned_to,
                    'created_by' => auth()->id(),
                    'due_date' => $request->due_date,
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
                'ticket_number' => $ticket->ticket_number,
                'client_id' => $request->client_id,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket' => $ticket->load(['client', 'assignee'])
                ], 201);
            }

            return redirect()->route('tickets.show', $ticket)
                            ->with('success', 'Ticket #' . $ticket->ticket_number . ' created successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['password']),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create ticket'
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
            'category',
            'template',
            'workflow.transitions',
            'priorityQueue',
            'calendarEvents',
            'timeEntries.user',
            'watchers.user',
            'replies' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            }
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

        return view('tickets.show', compact(
            'ticket', 'availableTransitions', 'recentActivity', 'otherViewers'
        ));
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
                        ->where('status', 'active')
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
            'description' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:new,open,in_progress,pending,resolved,closed',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'due_date' => 'nullable|date',
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
                'due_date',
                'estimated_hours',
                'tags',
                'custom_fields',
            ]));

            // Track significant changes
            $this->trackTicketChanges($ticket, $oldData, $request->all());

            Log::info('Ticket updated', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id(),
                'changes' => $ticket->getChanges(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket updated successfully',
                    'ticket' => $ticket->fresh(['client', 'assignee'])
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                            ->with('success', 'Ticket #' . $ticket->ticket_number . ' updated successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket update failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update ticket'
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
            $ticketNumber = $ticket->ticket_number;
            
            // Soft delete to maintain data integrity
            $ticket->delete();

            Log::warning('Ticket deleted', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticketNumber,
                'user_id' => auth()->id(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket deleted successfully'
                ]);
            }

            return redirect()->route('tickets.index')
                            ->with('success', 'Ticket #' . $ticketNumber . ' deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Ticket deletion failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete ticket'
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
                'errors' => $validator->errors()
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
                'reply' => $reply->load('user')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to add ticket reply', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply'
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
            'status' => 'required|in:new,open,in_progress,pending,resolved,closed',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldStatus = $ticket->status;
            
            $ticket->update([
                'status' => $request->status,
                'resolved_at' => in_array($request->status, ['resolved', 'closed']) ? now() : null,
            ]);

            // Add status change note
            if ($request->filled('notes')) {
                $ticket->addNote($request->notes, 'status_change');
            }

            $ticket->addNote("Status changed from {$oldStatus} to {$request->status}", 'status_change');

            Log::info('Ticket status updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'ticket' => $ticket->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update ticket status', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
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
                'errors' => $validator->errors()
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
                'ticket' => $ticket->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update ticket priority', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update priority'
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
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldAssignee = $ticket->assignee;
            
            $ticket->update(['assigned_to' => $request->assigned_to]);

            // Add assignment note
            if ($request->filled('notes')) {
                $ticket->addNote($request->notes, 'assignment');
            }

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
                    'added_by' => auth()->id(),
                    'notification_preferences' => [
                        'status_changes' => true,
                        'new_comments' => true,
                        'assignments' => true,
                        'priority_changes' => true,
                    ],
                ]);
            } else {
                $message = 'Ticket unassigned';
            }

            $ticket->addNote($message, 'assignment');

            Log::info('Ticket assigned', [
                'ticket_id' => $ticket->id,
                'old_assignee' => $oldAssignee?->id,
                'new_assignee' => $request->assigned_to,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'ticket' => $ticket->fresh(['assignee'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment'
            ], 500);
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create calendar event
            $event = $ticket->calendarEvents()->create([
                'company_id' => auth()->user()->company_id,
                'title' => 'Scheduled: ' . $ticket->subject,
                'description' => $request->notes,
                'event_type' => 'maintenance',
                'starts_at' => $request->scheduled_at,
                'ends_at' => Carbon::parse($request->scheduled_at)->addMinutes($request->duration ?? 60),
                'assigned_to' => $ticket->assigned_to ?: auth()->id(),
                'created_by' => auth()->id(),
                'location' => $request->location,
                'status' => 'scheduled',
            ]);

            $ticket->addNote(
                "Ticket scheduled for " . Carbon::parse($request->scheduled_at)->format('M j, Y \a\t g:i A') . 
                ($request->location ? " at {$request->location}" : ""),
                'schedule'
            );

            Log::info('Ticket scheduled', [
                'ticket_id' => $ticket->id,
                'scheduled_at' => $request->scheduled_at,
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket scheduled successfully',
                'event' => $event
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to schedule ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule ticket'
            ], 500);
        }
    }

    /**
     * Export tickets to CSV
     */
    public function export(Request $request)
    {
        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $tickets = $query->with(['client', 'assignee'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        $filename = 'tickets_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($tickets) {
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
                'Resolved Date'
            ]);

            // CSV data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->ticket_number,
                    $ticket->subject,
                    $ticket->client->name,
                    ucfirst($ticket->status),
                    $ticket->priority,
                    $ticket->assignee?->name ?? 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->due_date?->format('Y-m-d H:i:s') ?? '',
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
                           ->where('ticket_number', 'like', "{$prefix}-{$year}-%")
                           ->orderBy('ticket_number', 'desc')
                           ->first();

        if ($lastTicket) {
            // Extract sequence number and increment
            $parts = explode('-', $lastTicket->ticket_number);
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
                              ->where('status', 'active')
                              ->orderBy('name')
                              ->get(['id', 'name']),
        ];
    }

    /**
     * Track ticket view for collision detection
     */
    private function trackTicketView(Ticket $ticket): void
    {
        // Implementation for tracking who is viewing the ticket
        // This would typically use cache or session storage
    }

    /**
     * Get other users viewing this ticket
     */
    private function getTicketViewers(Ticket $ticket): array
    {
        // Implementation for getting other viewers
        // This would typically check cache or session storage
        return [];
    }

    /**
     * Track significant ticket changes for audit log
     */
    private function trackTicketChanges(Ticket $ticket, array $oldData, array $newData): void
    {
        $changes = [];
        $significantFields = ['status', 'priority', 'assigned_to', 'due_date'];

        foreach ($significantFields as $field) {
            if (isset($oldData[$field]) && isset($newData[$field]) &&
                $oldData[$field] !== $newData[$field]) {
                $changes[$field] = [
                    'old' => $oldData[$field],
                    'new' => $newData[$field],
                ];
            }
        }

        if (!empty($changes)) {
            // Log significant changes
            Log::info('Ticket changes tracked', [
                'ticket_id' => $ticket->id,
                'changes' => $changes,
                'user_id' => auth()->id(),
            ]);
        }
    }
}