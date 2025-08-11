<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Assignment Controller
 * 
 * Manages ticket assignments, bulk operations, watcher functionality,
 * and notification preferences following the domain architecture pattern.
 */
class AssignmentController extends Controller
{
    /**
     * Display assignment dashboard
     */
    public function index(Request $request)
    {
        $view = $request->get('view', 'my-tickets'); // my-tickets, team-tickets, unassigned, watchers

        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply view-specific filters
        switch ($view) {
            case 'my-tickets':
                $query->where('assigned_to', auth()->id());
                break;
            case 'team-tickets':
                // Get team members based on user's teams/departments
                $teamMemberIds = $this->getTeamMemberIds();
                $query->whereIn('assigned_to', $teamMemberIds);
                break;
            case 'unassigned':
                $query->whereNull('assigned_to');
                break;
            case 'watchers':
                $query->whereHas('watchers', function($q) {
                    $q->where('user_id', auth()->id());
                });
                break;
        }

        // Apply additional filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $tickets = $query->with(['client', 'assignee', 'watchers.user'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(20)
                        ->appends($request->query());

        // Get filter options
        $assignees = User::where('company_id', auth()->user()->company_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();

        $statuses = ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'];
        $priorities = ['Low', 'Medium', 'High', 'Critical'];

        // Get assignment statistics
        $stats = $this->getAssignmentStats();

        if ($request->wantsJson()) {
            return response()->json([
                'tickets' => $tickets,
                'assignees' => $assignees,
                'statuses' => $statuses,
                'priorities' => $priorities,
                'stats' => $stats,
                'view' => $view,
            ]);
        }

        return view('tickets.assignments.index', compact(
            'tickets', 'assignees', 'statuses', 'priorities', 'stats', 'view'
        ));
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
            'notify_assignee' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $previousAssignee = $ticket->assignee;

        $ticket->update([
            'assigned_to' => $request->assigned_to,
        ]);

        // Add assignment note if provided
        if ($request->filled('notes')) {
            $ticket->addNote($request->notes, 'assignment');
        }

        // Add automatic assignment note
        if ($request->assigned_to) {
            $assignee = User::find($request->assigned_to);
            $note = "Ticket assigned to {$assignee->name}";
            if ($previousAssignee) {
                $note = "Ticket reassigned from {$previousAssignee->name} to {$assignee->name}";
            }
            $ticket->addNote($note, 'assignment');
        } else {
            $ticket->addNote('Ticket unassigned', 'assignment');
        }

        // Send notifications if enabled
        if ($request->boolean('notify_assignee', true) && $request->assigned_to) {
            // TODO: Implement notification sending
            // $ticket->sendAssignmentNotification();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully',
            'ticket' => $ticket->fresh(['assignee', 'client'])
        ]);
    }

    /**
     * Bulk assign tickets
     */
    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'integer|exists:tickets,id',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'notes' => 'nullable|string|max:500',
            'notify_assignees' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tickets = Ticket::whereIn('id', $request->ticket_ids)
                        ->where('company_id', auth()->user()->company_id)
                        ->get();

        $assignedCount = 0;

        DB::transaction(function () use ($tickets, $request, &$assignedCount) {
            foreach ($tickets as $ticket) {
                if (auth()->user()->can('update', $ticket)) {
                    $ticket->update([
                        'assigned_to' => $request->assigned_to,
                    ]);

                    // Add assignment note
                    if ($request->filled('notes')) {
                        $ticket->addNote($request->notes, 'assignment');
                    }

                    if ($request->assigned_to) {
                        $assignee = User::find($request->assigned_to);
                        $ticket->addNote("Ticket assigned to {$assignee->name} (bulk operation)", 'assignment');
                    } else {
                        $ticket->addNote('Ticket unassigned (bulk operation)', 'assignment');
                    }

                    $assignedCount++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$assignedCount} tickets"
        ]);
    }

    /**
     * Add watcher to ticket
     */
    public function addWatcher(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $validator = Validator::make($request->all(), [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
                Rule::unique('ticket_watchers')->where(function ($query) use ($ticket) {
                    return $query->where('ticket_id', $ticket->id)
                                 ->where('company_id', auth()->user()->company_id);
                }),
            ],
            'notification_preferences' => 'nullable|array',
            'notification_preferences.status_changes' => 'boolean',
            'notification_preferences.new_comments' => 'boolean',
            'notification_preferences.assignments' => 'boolean',
            'notification_preferences.priority_changes' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $watcher = TicketWatcher::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $ticket->id,
            'user_id' => $request->user_id,
            'added_by' => auth()->id(),
            'notification_preferences' => $request->notification_preferences ?? [
                'status_changes' => true,
                'new_comments' => true,
                'assignments' => true,
                'priority_changes' => true,
            ],
        ]);

        // Add note to ticket
        $user = User::find($request->user_id);
        $ticket->addNote("{$user->name} added as watcher", 'watcher');

        return response()->json([
            'success' => true,
            'message' => 'Watcher added successfully',
            'watcher' => $watcher->load('user')
        ]);
    }

    /**
     * Remove watcher from ticket
     */
    public function removeWatcher(Ticket $ticket, TicketWatcher $watcher)
    {
        $this->authorize('view', $ticket);
        $this->authorize('delete', $watcher);

        $userName = $watcher->user->name;
        $watcher->delete();

        // Add note to ticket
        $ticket->addNote("{$userName} removed as watcher", 'watcher');

        return response()->json([
            'success' => true,
            'message' => 'Watcher removed successfully'
        ]);
    }

    /**
     * Update watcher notification preferences
     */
    public function updateWatcherPreferences(Request $request, Ticket $ticket, TicketWatcher $watcher)
    {
        $this->authorize('update', $watcher);

        $validator = Validator::make($request->all(), [
            'notification_preferences' => 'required|array',
            'notification_preferences.status_changes' => 'boolean',
            'notification_preferences.new_comments' => 'boolean',
            'notification_preferences.assignments' => 'boolean',
            'notification_preferences.priority_changes' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $watcher->update([
            'notification_preferences' => $request->notification_preferences,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'watcher' => $watcher->fresh()
        ]);
    }

    /**
     * Bulk add watchers to tickets
     */
    public function bulkAddWatchers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'integer|exists:tickets,id',
            'user_ids' => 'required|array',
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'notification_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tickets = Ticket::whereIn('id', $request->ticket_ids)
                        ->where('company_id', auth()->user()->company_id)
                        ->get();

        $addedCount = 0;

        DB::transaction(function () use ($tickets, $request, &$addedCount) {
            foreach ($tickets as $ticket) {
                if (auth()->user()->can('view', $ticket)) {
                    foreach ($request->user_ids as $userId) {
                        // Check if watcher already exists
                        $exists = TicketWatcher::where('ticket_id', $ticket->id)
                                             ->where('user_id', $userId)
                                             ->exists();

                        if (!$exists) {
                            TicketWatcher::create([
                                'company_id' => auth()->user()->company_id,
                                'ticket_id' => $ticket->id,
                                'user_id' => $userId,
                                'added_by' => auth()->id(),
                                'notification_preferences' => $request->notification_preferences ?? [
                                    'status_changes' => true,
                                    'new_comments' => true,
                                    'assignments' => true,
                                    'priority_changes' => true,
                                ],
                            ]);

                            $addedCount++;
                        }
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully added {$addedCount} watchers"
        ]);
    }

    /**
     * Get assignment workload report
     */
    public function getWorkloadReport(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, quarter
        $assigneeId = $request->get('assignee_id');

        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply period filter
        switch ($period) {
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'quarter':
                $query->where('created_at', '>=', now()->startOfQuarter());
                break;
            case 'month':
            default:
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
        }

        // Apply assignee filter
        if ($assigneeId) {
            $query->where('assigned_to', $assigneeId);
        }

        $tickets = $query->with('assignee')->get();

        // Group by assignee
        $workloadData = $tickets->groupBy('assigned_to')->map(function($tickets, $assigneeId) {
            $assignee = $tickets->first()->assignee ?? null;
            
            return [
                'assignee' => $assignee ? $assignee->name : 'Unassigned',
                'total_tickets' => $tickets->count(),
                'open_tickets' => $tickets->whereIn('status', ['new', 'open', 'in_progress'])->count(),
                'closed_tickets' => $tickets->where('status', 'closed')->count(),
                'avg_resolution_time' => $this->calculateAvgResolutionTime($tickets),
                'priority_breakdown' => $tickets->groupBy('priority')->map->count(),
                'status_breakdown' => $tickets->groupBy('status')->map->count(),
            ];
        })->sortByDesc('total_tickets');

        return response()->json([
            'success' => true,
            'workload_data' => $workloadData->values(),
            'period' => $period,
            'total_tickets' => $tickets->count(),
            'summary' => [
                'total_assignees' => $workloadData->count(),
                'avg_tickets_per_assignee' => round($tickets->count() / max($workloadData->count(), 1), 2),
                'unassigned_tickets' => $tickets->whereNull('assigned_to')->count(),
            ]
        ]);
    }

    /**
     * Get team assignment overview
     */
    public function getTeamOverview(Request $request)
    {
        $teamMembers = User::where('company_id', auth()->user()->company_id)
                          ->where('is_active', true)
                          ->withCount([
                              'assignedTickets',
                              'assignedTickets as open_tickets_count' => function($q) {
                                  $q->whereIn('status', ['new', 'open', 'in_progress']);
                              },
                              'assignedTickets as overdue_tickets_count' => function($q) {
                                  $q->where('due_date', '<', now())
                                    ->whereNotIn('status', ['closed', 'resolved']);
                              }
                          ])
                          ->get();

        $overview = $teamMembers->map(function($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'total_tickets' => $member->assigned_tickets_count,
                'open_tickets' => $member->open_tickets_count,
                'overdue_tickets' => $member->overdue_tickets_count,
                'workload_level' => $this->calculateWorkloadLevel($member->open_tickets_count),
                'last_activity' => $member->last_seen_at,
            ];
        });

        return response()->json([
            'success' => true,
            'team_overview' => $overview,
            'totals' => [
                'team_members' => $teamMembers->count(),
                'total_tickets' => $teamMembers->sum('assigned_tickets_count'),
                'total_open' => $teamMembers->sum('open_tickets_count'),
                'total_overdue' => $teamMembers->sum('overdue_tickets_count'),
            ]
        ]);
    }

    /**
     * Auto-assign tickets based on rules
     */
    public function autoAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|in:round_robin,workload_balance,skill_based',
            'ticket_ids' => 'nullable|array',
            'ticket_ids.*' => 'integer|exists:tickets,id',
            'assignee_pool' => 'nullable|array',
            'assignee_pool.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get tickets to assign
        $query = Ticket::where('company_id', auth()->user()->company_id)
                      ->whereNull('assigned_to');

        if ($request->has('ticket_ids')) {
            $query->whereIn('id', $request->ticket_ids);
        }

        $tickets = $query->get();

        // Get available assignees
        $assignees = User::where('company_id', auth()->user()->company_id)
                        ->where('is_active', true);

        if ($request->has('assignee_pool')) {
            $assignees->whereIn('id', $request->assignee_pool);
        }

        $assignees = $assignees->get();

        if ($assignees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No available assignees found'
            ], 422);
        }

        $assignedCount = 0;

        DB::transaction(function () use ($tickets, $assignees, $request, &$assignedCount) {
            foreach ($tickets as $ticket) {
                if (auth()->user()->can('update', $ticket)) {
                    $assignee = $this->selectAssignee($ticket, $assignees, $request->method);
                    
                    if ($assignee) {
                        $ticket->update(['assigned_to' => $assignee->id]);
                        $ticket->addNote("Auto-assigned to {$assignee->name} using {$request->method} method", 'assignment');
                        $assignedCount++;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Auto-assigned {$assignedCount} tickets using {$request->method} method"
        ]);
    }

    /**
     * Export assignment report to CSV
     */
    public function exportAssignments(Request $request)
    {
        $query = Ticket::where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($assigneeId = $request->get('assignee_id')) {
            $query->where('assigned_to', $assigneeId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tickets = $query->with(['client', 'assignee', 'watchers.user'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        $filename = 'ticket-assignments_' . date('Y-m-d_H-i-s') . '.csv';

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
                'Assignee',
                'Status',
                'Priority',
                'Watchers',
                'Created Date',
                'Due Date'
            ]);

            // CSV data
            foreach ($tickets as $ticket) {
                $watchers = $ticket->watchers->pluck('user.name')->join(', ');
                
                fputcsv($file, [
                    $ticket->ticket_number,
                    $ticket->subject,
                    $ticket->client->name,
                    $ticket->assignee?->name ?? 'Unassigned',
                    ucfirst($ticket->status),
                    $ticket->priority,
                    $watchers,
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->due_date?->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get assignment statistics
     */
    private function getAssignmentStats(): array
    {
        $query = Ticket::where('company_id', auth()->user()->company_id);

        $total = $query->count();
        $assigned = $query->whereNotNull('assigned_to')->count();
        $unassigned = $total - $assigned;
        $myTickets = $query->where('assigned_to', auth()->id())->count();
        $watchingTickets = Ticket::whereHas('watchers', function($q) {
            $q->where('user_id', auth()->id());
        })->count();

        return [
            'total_tickets' => $total,
            'assigned_tickets' => $assigned,
            'unassigned_tickets' => $unassigned,
            'my_tickets' => $myTickets,
            'watching_tickets' => $watchingTickets,
            'assignment_rate' => $total > 0 ? round(($assigned / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get team member IDs for current user
     */
    private function getTeamMemberIds(): array
    {
        // This would typically get team members based on user's department/team
        // For now, return all active users in the tenant
        return User::where('company_id', auth()->user()->company_id)
                  ->where('is_active', true)
                  ->pluck('id')
                  ->toArray();
    }

    /**
     * Calculate average resolution time for tickets
     */
    private function calculateAvgResolutionTime($tickets)
    {
        $resolvedTickets = $tickets->whereIn('status', ['resolved', 'closed'])
                                  ->filter(function($ticket) {
                                      return $ticket->resolved_at;
                                  });

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        $totalHours = $resolvedTickets->sum(function($ticket) {
            return $ticket->created_at->diffInHours($ticket->resolved_at);
        });

        return round($totalHours / $resolvedTickets->count(), 2);
    }

    /**
     * Calculate workload level based on open tickets count
     */
    private function calculateWorkloadLevel(int $openTickets): string
    {
        if ($openTickets <= 5) return 'low';
        if ($openTickets <= 15) return 'medium';
        if ($openTickets <= 25) return 'high';
        return 'critical';
    }

    /**
     * Select assignee based on assignment method
     */
    private function selectAssignee(Ticket $ticket, $assignees, string $method): ?User
    {
        switch ($method) {
            case 'round_robin':
                return $assignees->sortBy('last_assigned_at')->first();

            case 'workload_balance':
                return $assignees->loadCount('assignedTickets')
                                ->sortBy('assigned_tickets_count')
                                ->first();

            case 'skill_based':
                // Simple skill-based assignment based on ticket category/type
                // In a real implementation, this would use skill matching
                return $assignees->random();

            default:
                return $assignees->first();
        }
    }
}