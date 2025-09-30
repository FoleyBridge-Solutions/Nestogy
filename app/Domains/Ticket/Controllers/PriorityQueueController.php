<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\TicketPriorityQueue;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * Priority Queue Controller
 * 
 * Manages ticket priority queue with escalation rules, SLA tracking,
 * drag-and-drop reordering, and priority scoring algorithms following the domain architecture pattern.
 */
class PriorityQueueController extends Controller
{
    /**
     * Display the priority queue dashboard
     */
    public function index(Request $request)
    {
        $view = $request->get('view', 'queue'); // queue, matrix, analytics

        $query = TicketPriorityQueue::where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($search = $request->get('search')) {
            $query->whereHas('ticket', function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($userId = $request->get('assignee_id')) {
            $query->whereHas('ticket', function($q) use ($userId) {
                $q->where('assigned_to', $userId);
            });
        }

        if ($priority = $request->get('priority_level')) {
            $query->whereHas('ticket', function($q) use ($priority) {
                $q->where('priority', $priority);
            });
        }

        if ($request->has('sla_breached')) {
            if ($request->boolean('sla_breached')) {
                $query->where('sla_deadline', '<', now());
            } else {
                $query->where('sla_deadline', '>=', now());
            }
        }

        if ($request->has('escalated')) {
            if ($request->boolean('escalated')) {
                $query->whereNotNull('escalated_at');
            } else {
                $query->whereNull('escalated_at');
            }
        }

        // Order by queue position for queue view, by priority score for other views
        if ($view === 'queue') {
            $query->orderBy('queue_position');
        } else {
            $query->orderByDesc('priority_score')->orderBy('created_at');
        }

        $queueItems = $query->with(['ticket.client', 'ticket.assignee', 'escalationRule'])
                           ->paginate($view === 'queue' ? 50 : 20)
                           ->appends($request->query());

        // Get filter options
        $assignees = User::where('company_id', auth()->user()->company_id)
                        ->whereNull('archived_at')
                        ->orderBy('name')
                        ->get();

        $priorityLevels = ['Critical', 'High', 'Medium', 'Low'];

        // Calculate queue statistics
        $queueStats = $this->calculateQueueStats();

        if ($request->wantsJson()) {
            return response()->json([
                'queue_items' => $queueItems,
                'assignees' => $assignees,
                'priority_levels' => $priorityLevels,
                'queue_stats' => $queueStats,
                'view' => $view,
            ]);
        }

        return view('tickets.priority-queue.index', compact(
            'queueItems', 'assignees', 'priorityLevels', 'queueStats', 'view'
        ));
    }

    /**
     * Show the form for adding a ticket to priority queue
     */
    public function create(Request $request)
    {
        // Get tickets not currently in priority queue
        $tickets = Ticket::where('company_id', auth()->user()->company_id)
                        ->where('status', '!=', 'closed')
                        ->whereDoesntHave('priorityQueue')
                        ->with(['client', 'assignee'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        $priorityLevels = ['Critical', 'High', 'Medium', 'Low'];

        // Pre-select ticket if provided
        $selectedTicket = null;
        if ($ticketId = $request->get('ticket_id')) {
            $selectedTicket = $tickets->firstWhere('id', $ticketId);
        }

        return view('tickets.priority-queue.create', compact('tickets', 'priorityLevels', 'selectedTicket'));
    }

    /**
     * Store a newly created priority queue item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id)
                          ->where('status', '!=', 'closed');
                }),
                Rule::unique('ticket_priority_queue')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'priority_level' => 'required|in:Critical,High,Medium,Low',
            'sla_hours' => 'nullable|integer|min:1|max:8760', // Max 1 year
            'escalation_rule_config' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $ticket = Ticket::findOrFail($request->ticket_id);

        // Calculate SLA due date
        $slaDueAt = null;
        if ($request->filled('sla_hours')) {
            $slaDueAt = now()->addHours($request->sla_hours);
        }

        // Get next queue position
        $nextPosition = TicketPriorityQueue::where('company_id', auth()->user()->company_id)
                                          ->max('queue_position') + 1;

        $queueItem = TicketPriorityQueue::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $request->ticket_id,
            'queue_position' => $nextPosition,
            'sla_deadline' => $slaDueAt,
            'escalation_rules' => $request->escalation_rule_config ?? [],
            'added_by' => auth()->id(),
        ]);

        // Calculate initial priority score
        $queueItem->calculatePriorityScore();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ticket added to priority queue successfully',
                'queue_item' => $queueItem->load(['ticket.client', 'ticket.assignee'])
            ], 201);
        }

        return redirect()->route('tickets.priority-queue.index')
                        ->with('success', 'Ticket added to priority queue successfully.');
    }

    /**
     * Display the specified priority queue item
     */
    public function show(TicketPriorityQueue $queueItem)
    {
        $this->authorize('view', $queueItem);

        $queueItem->load([
            'ticket.client', 
            'ticket.assignee', 
            'escalationRule',
            'ticket.timeEntries' => function($query) {
                $query->latest()->limit(5);
            }
        ]);

        // Get priority score breakdown
        $scoreBreakdown = $queueItem->getPriorityScoreBreakdown();

        // Get escalation history
        $escalationHistory = $queueItem->escalation_history ?? [];

        if (request()->wantsJson()) {
            return response()->json([
                'queue_item' => $queueItem,
                'score_breakdown' => $scoreBreakdown,
                'escalation_history' => $escalationHistory,
            ]);
        }

        return view('tickets.priority-queue.show', compact('queueItem', 'scoreBreakdown', 'escalationHistory'));
    }

    /**
     * Update the specified priority queue item
     */
    public function update(Request $request, TicketPriorityQueue $queueItem)
    {
        $this->authorize('update', $queueItem);

        $validator = Validator::make($request->all(), [
            'priority_level' => 'required|in:Critical,High,Medium,Low',
            'sla_hours' => 'nullable|integer|min:1|max:8760',
            'escalation_rule_config' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate new SLA due date if hours changed
        $slaDueAt = $queueItem->sla_deadline;
        if ($request->filled('sla_hours')) {
            $slaDueAt = now()->addHours($request->sla_hours);
        }

        $queueItem->update([
            'sla_deadline' => $slaDueAt,
            'escalation_rules' => $request->escalation_rule_config ?? [],
        ]);

        // Recalculate priority score
        $queueItem->calculatePriorityScore();

        return response()->json([
            'success' => true,
            'message' => 'Priority queue item updated successfully',
            'queue_item' => $queueItem->fresh()
        ]);
    }

    /**
     * Remove the specified item from priority queue
     */
    public function destroy(TicketPriorityQueue $queueItem)
    {
        $this->authorize('delete', $queueItem);

        $queueItem->delete();

        // Reorder remaining items
        $this->reorderQueue();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ticket removed from priority queue successfully'
            ]);
        }

        return redirect()->route('tickets.priority-queue.index')
                        ->with('success', 'Ticket removed from priority queue successfully.');
    }

    /**
     * Update queue positions (drag and drop reordering)
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:ticket_priority_queue,id',
            'items.*.position' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                TicketPriorityQueue::where('id', $item['id'])
                                  ->where('company_id', auth()->user()->company_id)
                                  ->update(['queue_position' => $item['position']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Queue reordered successfully'
        ]);
    }

    /**
     * Escalate priority queue items
     */
    public function escalate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'queue_item_ids' => 'required|array',
            'queue_item_ids.*' => 'integer|exists:ticket_priority_queue,id',
            'escalation_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $escalatedCount = 0;

        foreach ($request->queue_item_ids as $id) {
            $queueItem = TicketPriorityQueue::where('id', $id)
                                           ->where('company_id', auth()->user()->company_id)
                                           ->first();

            if ($queueItem && !$queueItem->escalated_at) {
                $queueItem->escalate($request->escalation_reason);
                $escalatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Escalated {$escalatedCount} items successfully"
        ]);
    }

    /**
     * Auto-prioritize queue based on algorithms
     */
    public function autoPrioritize(Request $request)
    {
        $method = $request->get('method', 'score'); // score, sla, age

        $queueItems = TicketPriorityQueue::where('company_id', auth()->user()->company_id)
                                        ->with('ticket')
                                        ->get();

        DB::transaction(function () use ($queueItems, $method) {
            $position = 1;

            // Sort based on method
            switch ($method) {
                case 'sla':
                    $sorted = $queueItems->sortBy('sla_deadline');
                    break;
                case 'age':
                    $sorted = $queueItems->sortBy('created_at');
                    break;
                case 'score':
                default:
                    // Recalculate all scores first
                    foreach ($queueItems as $item) {
                        $item->calculatePriorityScore();
                    }
                    $sorted = $queueItems->sortByDesc('priority_score');
                    break;
            }

            // Update positions
            foreach ($sorted as $item) {
                $item->update(['queue_position' => $position++]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Queue auto-prioritized successfully',
            'method' => $method
        ]);
    }

    /**
     * Bulk update queue items
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'queue_item_ids' => 'required|array',
            'queue_item_ids.*' => 'integer|exists:ticket_priority_queue,id',
            'action' => 'required|in:escalate,change_priority,set_sla,remove',
            'priority_level' => 'required_if:action,change_priority|in:Critical,High,Medium,Low',
            'sla_hours' => 'required_if:action,set_sla|integer|min:1|max:8760',
            'escalation_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $queueItems = TicketPriorityQueue::whereIn('id', $request->queue_item_ids)
                                        ->where('company_id', auth()->user()->company_id)
                                        ->get();

        $count = 0;

        DB::transaction(function () use ($queueItems, $request, &$count) {
            foreach ($queueItems as $item) {
                switch ($request->action) {
                    case 'escalate':
                        if (!$item->escalated_at) {
                            $item->escalate($request->escalation_reason);
                            $count++;
                        }
                        break;

                    case 'change_priority':
                        // Update priority on the ticket itself
                        $item->ticket->update(['priority' => $request->priority_level]);
                        $item->calculatePriorityScore();
                        $count++;
                        break;

                    case 'set_sla':
                        $item->update([
                            'sla_deadline' => now()->addHours($request->sla_hours)
                        ]);
                        $count++;
                        break;

                    case 'remove':
                        $item->delete();
                        $count++;
                        break;
                }
            }
        });

        // Reorder queue if items were removed
        if ($request->action === 'remove') {
            $this->reorderQueue();
        }

        $actionName = [
            'escalate' => 'escalated',
            'change_priority' => 'updated',
            'set_sla' => 'updated',
            'remove' => 'removed'
        ][$request->action];

        return response()->json([
            'success' => true,
            'message' => "Successfully {$actionName} {$count} queue items"
        ]);
    }

    /**
     * Get priority matrix data
     */
    public function getPriorityMatrix(Request $request)
    {
        $queueItems = TicketPriorityQueue::where('company_id', auth()->user()->company_id)
                                        ->with('ticket.client')
                                        ->get();

        // Group by priority level and urgency
        $matrix = [
            'Critical' => ['High' => [], 'Medium' => [], 'Low' => []],
            'High' => ['High' => [], 'Medium' => [], 'Low' => []],
            'Medium' => ['High' => [], 'Medium' => [], 'Low' => []],
            'Low' => ['High' => [], 'Medium' => [], 'Low' => []],
        ];

        foreach ($queueItems as $item) {
            $urgency = $this->calculateUrgency($item);
            $matrix[$item->ticket->priority ?? 'medium'][$urgency][] = $item;
        }

        return response()->json([
            'success' => true,
            'matrix' => $matrix
        ]);
    }

    /**
     * Export priority queue to CSV
     */
    public function export(Request $request)
    {
        $query = TicketPriorityQueue::where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->whereHas('ticket', function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        if ($userId = $request->get('assignee_id')) {
            $query->whereHas('ticket', function($q) use ($userId) {
                $q->where('assigned_to', $userId);
            });
        }

        $queueItems = $query->with(['ticket.client', 'ticket.assignee'])
                           ->orderBy('queue_position')
                           ->get();

        $filename = 'priority-queue_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($queueItems) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Position',
                'Ticket #',
                'Subject',
                'Client',
                'Priority',
                'Priority Score',
                'Assignee',
                'SLA Due',
                'Is Escalated',
                'Days in Queue',
                'Created Date'
            ]);

            // CSV data
            foreach ($queueItems as $item) {
                fputcsv($file, [
                    $item->queue_position,
                    $item->ticket->ticket_number,
                    $item->ticket->subject,
                    $item->ticket->client->name,
                    $item->ticket->priority,
                    $item->priority_score,
                    $item->ticket->assignee?->name,
                    $item->sla_deadline?->format('Y-m-d H:i'),
                    $item->escalated_at ? 'Yes' : 'No',
                    $item->created_at->diffInDays(now()),
                    $item->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate queue statistics
     */
    private function calculateQueueStats(): array
    {
        $query = TicketPriorityQueue::where('company_id', auth()->user()->company_id);

        $totalItems = $query->count();
        $escalatedItems = $query->whereNotNull('escalated_at')->count();
        $slaBreach = $query->where('sla_deadline', '<', now())->count();
        
        // PostgreSQL compatible date difference calculation
        $avgWaitTime = $query->selectRaw("AVG(EXTRACT(EPOCH FROM (NOW() - created_at)) / 86400) as avg_days")
                            ->value('avg_days');

        // Get priority breakdown from tickets
        $priorityBreakdown = TicketPriorityQueue::where('company_id', auth()->user()->company_id)
                                  ->whereHas('ticket')
                                  ->with('ticket:id,priority')
                                  ->get()
                                  ->groupBy('ticket.priority')
                                  ->map->count()
                                  ->toArray();

        return [
            'total_items' => $totalItems,
            'escalated_items' => $escalatedItems,
            'sla_breached' => $slaBreach,
            'avg_wait_days' => round($avgWaitTime ?? 0, 1),
            'priority_breakdown' => $priorityBreakdown,
        ];
    }

    /**
     * Reorder queue positions after deletions
     */
    private function reorderQueue(): void
    {
        $items = TicketPriorityQueue::where('company_id', auth()->user()->company_id)
                                  ->orderBy('queue_position')
                                  ->get();

        $position = 1;
        foreach ($items as $item) {
            $item->update(['queue_position' => $position++]);
        }
    }

    /**
     * Calculate urgency level for priority matrix
     */
    private function calculateUrgency(TicketPriorityQueue $item): string
    {
        if ($item->sla_deadline && $item->sla_deadline < now()) {
            return 'High';
        }

        if ($item->sla_deadline && $item->sla_deadline < now()->addHours(24)) {
            return 'Medium';
        }

        return 'Low';
    }
}