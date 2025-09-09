<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Services\TicketService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TicketsController extends Controller
{
    protected TicketService $ticketService;
    protected NotificationService $notificationService;

    public function __construct(
        TicketService $ticketService,
        NotificationService $notificationService
    ) {
        $this->ticketService = $ticketService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of tickets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['client', 'assignee', 'category', 'replies.user'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Include SLA information
        $tickets = $query->paginate($request->get('per_page', 25));

        // Calculate SLA status for each ticket
        $tickets->getCollection()->transform(function ($ticket) {
            $slaInfo = $this->ticketService->calculateSlaDeadlines($ticket);
            $ticket->sla_response_deadline = $slaInfo['response_deadline'];
            $ticket->sla_resolution_deadline = $slaInfo['resolution_deadline'];
            $ticket->is_sla_breached = $this->ticketService->isSlaBreached($ticket);
            return $ticket;
        });

        return response()->json([
            'success' => true,
            'data' => $tickets,
            'message' => 'Tickets retrieved successfully'
        ]);
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category_id' => 'nullable|exists:ticket_categories,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'assigned_to' => 'nullable|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        try {
            DB::beginTransaction();

            // Create ticket
            $ticket = Ticket::create([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'contact_id' => $validated['contact_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'ticket_number' => $this->generateTicketNumber(),
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'created_by' => auth()->id(),
                'assigned_to' => $validated['assigned_to'] ?? null,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $ticket->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $file->store('tickets/' . $ticket->id, 'private'),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            // Calculate and set SLA deadlines
            $slaInfo = $this->ticketService->calculateSlaDeadlines($ticket);
            $ticket->update([
                'response_deadline' => $slaInfo['response_deadline'],
                'resolution_deadline' => $slaInfo['resolution_deadline'],
            ]);

            // Auto-assign if not already assigned
            if (!$ticket->assigned_to) {
                $assignedUser = $this->ticketService->autoAssignTicket($ticket);
                if ($assignedUser) {
                    $ticket->update(['assigned_to' => $assignedUser->id]);
                    
                    // Send notification to assigned technician
                    $this->notificationService->notifyTicketAssigned($ticket, $assignedUser);
                }
            }

            // Send creation notification
            $this->notificationService->notifyNewTicket($ticket);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ticket->load(['client', 'assignee', 'category', 'attachments']),
                'message' => 'Ticket created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create ticket', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified ticket
     */
    public function show(Ticket $ticket): JsonResponse
    {
        // Ensure user has access to this ticket
        if ($ticket->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $ticket->load([
            'client',
            'contact',
            'assignee',
            'category',
            'replies.user',
            'attachments',
            'watchers',
            'timeEntries.user'
        ]);

        // Calculate SLA status
        $slaInfo = $this->ticketService->calculateSlaDeadlines($ticket);
        $ticket->sla_response_deadline = $slaInfo['response_deadline'];
        $ticket->sla_resolution_deadline = $slaInfo['resolution_deadline'];
        $ticket->is_sla_breached = $this->ticketService->isSlaBreached($ticket);

        // Mark as viewed
        $ticket->viewers()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['viewed_at' => now()]
        );

        return response()->json([
            'success' => true,
            'data' => $ticket,
            'message' => 'Ticket retrieved successfully'
        ]);
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        // Ensure user has access to this ticket
        if ($ticket->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'on_hold', 'resolved', 'closed'])],
            'category_id' => 'nullable|exists:ticket_categories,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $ticket->status;
            $oldAssignee = $ticket->assigned_to;

            $ticket->update($validated);

            // Recalculate SLA if priority changed
            if (isset($validated['priority'])) {
                $slaInfo = $this->ticketService->calculateSlaDeadlines($ticket);
                $ticket->update([
                    'response_deadline' => $slaInfo['response_deadline'],
                    'resolution_deadline' => $slaInfo['resolution_deadline'],
                ]);
            }

            // Track status changes
            if (isset($validated['status']) && $oldStatus !== $validated['status']) {
                // Update response/resolution times
                if ($validated['status'] === 'in_progress' && !$ticket->first_response_at) {
                    $ticket->update(['first_response_at' => now()]);
                }
                
                if (in_array($validated['status'], ['resolved', 'closed']) && !$ticket->resolved_at) {
                    $ticket->update(['resolved_at' => now()]);
                }

                // Send status change notification
                $this->notificationService->notifyTicketStatusChanged($ticket, $oldStatus);
            }

            // Send assignment notification if assignee changed
            if (isset($validated['assigned_to']) && $oldAssignee !== $validated['assigned_to']) {
                if ($validated['assigned_to']) {
                    $this->notificationService->notifyTicketAssigned(
                        $ticket,
                        $ticket->assignee
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ticket->fresh(['client', 'assignee', 'category']),
                'message' => 'Ticket updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update ticket', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        // Ensure user has access to this ticket
        if ($ticket->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $ticket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete ticket', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign tickets
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:tickets,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $tickets = Ticket::whereIn('id', $validated['ticket_ids'])
                ->where('company_id', auth()->user()->company_id)
                ->get();

            $result = $this->ticketService->bulkAssignTickets(
                $tickets,
                $validated['assigned_to']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Tickets assigned successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk assign tickets', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update ticket status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:tickets,id',
            'status' => ['required', Rule::in(['open', 'in_progress', 'on_hold', 'resolved', 'closed'])],
        ]);

        try {
            $tickets = Ticket::whereIn('id', $validated['ticket_ids'])
                ->where('company_id', auth()->user()->company_id)
                ->get();

            $result = $this->ticketService->bulkUpdateStatus(
                $tickets,
                $validated['status']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Ticket statuses updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk update ticket status', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SLA performance report
     */
    public function slaPerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        try {
            $report = $this->ticketService->generateSlaPerformanceReport(
                $validated['start_date'],
                $validated['end_date'],
                $validated['client_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'SLA performance report generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate SLA report', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate SLA report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for tickets needing escalation
     */
    public function checkEscalations(): JsonResponse
    {
        try {
            $escalated = $this->ticketService->checkAndTriggerEscalations();

            return response()->json([
                'success' => true,
                'data' => [
                    'escalated_count' => $escalated->count(),
                    'tickets' => $escalated
                ],
                'message' => 'Escalation check completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check escalations', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check escalations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a unique ticket number
     */
    private function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $year = date('Y');
        $lastTicket = Ticket::where('company_id', auth()->user()->company_id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTicket && preg_match('/TKT-\d{4}-(\d+)/', $lastTicket->ticket_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $nextNumber);
    }
}