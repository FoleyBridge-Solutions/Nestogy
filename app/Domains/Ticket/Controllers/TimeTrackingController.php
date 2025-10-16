<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Http\Controllers\Controller;
use App\Domains\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Time Tracking Controller
 *
 * Manages time entries with timer functionality, reporting, approval workflows,
 * and comprehensive time tracking analytics following the domain architecture pattern.
 */
class TimeTrackingController extends Controller
{
    /**
     * Display a listing of time entries
     */
    public function index(Request $request)
    {
        // Get view type from request, default to 'entries'
        $view = $request->get('view', 'entries');

        $query = TicketTimeEntry::where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('ticket', function ($tq) use ($search) {
                        $tq->where('subject', 'like', "%{$search}%")
                            ->orWhere('ticket_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filter
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('started_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('started_at', '<=', $endDate);
        }

        // Apply user filter
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        // Apply ticket filter
        if ($ticketId = $request->get('ticket_id')) {
            $query->where('ticket_id', $ticketId);
        }

        // Apply status filter
        if ($request->has('billable')) {
            $query->where('is_billable', $request->boolean('billable'));
        }

        if ($request->has('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }

        // Apply timer status filter
        if ($request->has('timer_running')) {
            if ($request->boolean('timer_running')) {
                $query->whereNull('ended_at');
            } else {
                $query->whereNotNull('ended_at');
            }
        }

        $timeEntries = $query->with(['ticket', 'user', 'approvedBy'])
            ->orderBy('started_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Get filter options
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('company_id', auth()->user()->company_id)
            ->where('status', '!=', 'closed')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get active timers
        $activeTimers = TicketTimeEntry::where('company_id', auth()->user()->company_id)
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->where('entry_type', TicketTimeEntry::TYPE_TIMER)
            ->with(['ticket', 'user'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Calculate summary statistics
        $timeStats = $this->calculateSummaryStats($request);

        if ($request->wantsJson()) {
            return response()->json([
                'time_entries' => $timeEntries,
                'users' => $users,
                'tickets' => $tickets,
                'summary_stats' => $timeStats,
                'active_timers' => $activeTimers,
            ]);
        }

        return view('tickets.time-tracking.index', compact(
            'timeEntries', 'users', 'tickets', 'timeStats', 'view', 'activeTimers'
        ));
    }

    /**
     * Show the form for creating a new time entry
     */
    public function create(Request $request)
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('company_id', auth()->user()->company_id)
            ->where('status', '!=', 'closed')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        // Pre-select ticket if provided
        $selectedTicket = null;
        if ($ticketId = $request->get('ticket_id')) {
            $selectedTicket = $tickets->firstWhere('id', $ticketId);
        }

        return view('tickets.time-tracking.create', compact('users', 'tickets', 'selectedTicket'));
    }

    /**
     * Store a newly created time entry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'description' => 'required|string|max:500',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after:started_at',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:999.99',
            'task_category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Calculate duration if not provided
        $durationMinutes = $request->duration_minutes;
        if (! $durationMinutes && $request->started_at && $request->ended_at) {
            $start = Carbon::parse($request->started_at);
            $end = Carbon::parse($request->ended_at);
            $durationMinutes = $end->diffInMinutes($start);
        }

        $timeEntry = TicketTimeEntry::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $request->ticket_id,
            'user_id' => $request->user_id,
            'description' => $request->description,
            'started_at' => $request->started_at,
            'ended_at' => $request->ended_at,
            'duration_minutes' => $durationMinutes,
            'is_billable' => $request->boolean('is_billable', true),
            'hourly_rate' => $request->hourly_rate,
            'task_category' => $request->task_category,
            'is_approved' => false, // Requires approval by default
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry created successfully',
                'time_entry' => $timeEntry->load(['ticket', 'user']),
            ], 201);
        }

        return redirect()->route('tickets.time-tracking.index')
            ->with('success', 'Time entry created successfully.');
    }

    /**
     * Display the specified time entry
     */
    public function show(TicketTimeEntry $timeEntry)
    {
        $this->authorize('view', $timeEntry);

        $timeEntry->load(['ticket.client', 'user', 'approvedBy']);

        if (request()->wantsJson()) {
            return response()->json([
                'time_entry' => $timeEntry,
                'cost_calculation' => $timeEntry->cost_calculation,
            ]);
        }

        return view('tickets.time-tracking.show', compact('timeEntry'));
    }

    /**
     * Show the form for editing the specified time entry
     */
    public function edit(TicketTimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('company_id', auth()->user()->company_id)
            ->where('status', '!=', 'closed')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tickets.time-tracking.edit', compact('timeEntry', 'users', 'tickets'));
    }

    /**
     * Update the specified time entry
     */
    public function update(Request $request, TicketTimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'description' => 'required|string|max:500',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after:started_at',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:999.99',
            'task_category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Recalculate duration if dates changed
        $durationMinutes = $request->duration_minutes;
        if (! $durationMinutes && $request->started_at && $request->ended_at) {
            $start = Carbon::parse($request->started_at);
            $end = Carbon::parse($request->ended_at);
            $durationMinutes = $end->diffInMinutes($start);
        }

        $timeEntry->update($request->only([
            'ticket_id',
            'user_id',
            'description',
            'started_at',
            'ended_at',
            'hourly_rate',
            'task_category',
        ]) + [
            'duration_minutes' => $durationMinutes,
            'is_billable' => $request->boolean('is_billable'),
            'is_approved' => false, // Reset approval status on edit
            'approved_by' => null,
            'approved_at' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry updated successfully',
                'time_entry' => $timeEntry->load(['ticket', 'user']),
            ]);
        }

        return redirect()->route('tickets.time-tracking.index')
            ->with('success', 'Time entry updated successfully.');
    }

    /**
     * Remove the specified time entry
     */
    public function destroy(TicketTimeEntry $timeEntry)
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry deleted successfully',
            ]);
        }

        return redirect()->route('tickets.time-tracking.index')
            ->with('success', 'Time entry deleted successfully.');
    }

    /**
     * Start a new timer
     */
    public function startTimer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'description' => 'required|string|max:500',
            'task_category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for existing running timer
        $existingTimer = TicketTimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->first();

        if ($existingTimer) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a running timer. Please stop it first.',
                'existing_timer' => $existingTimer->load('ticket'),
            ], 409);
        }

        $timeEntry = TicketTimeEntry::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $request->ticket_id,
            'user_id' => auth()->id(),
            'description' => $request->description,
            'task_category' => $request->task_category,
            'started_at' => now(),
            'entry_type' => TicketTimeEntry::TYPE_TIMER,
            'status' => 'draft',
            'work_date' => now()->toDateString(),
            'billable' => true,
            'rate_type' => 'after_hours', // or determine based on time/settings
            'hourly_rate' => 225, // or get from user profile/settings
            'work_type' => 'general_support', // or get from request
            'metadata' => [
                'auto_started' => true,
                'location' => 'remote',
                'client_visible' => true,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timer started successfully',
            'time_entry' => $timeEntry->load('ticket'),
        ], 201);
    }

    /**
     * Stop the current timer
     */
    public function stopTimer(Request $request, ?TicketTimeEntry $timeEntry = null)
    {
        // If specific time entry ID provided in request, use that
        if ($request->has('time_entry_id')) {
            $timeEntry = TicketTimeEntry::where('id', $request->time_entry_id)
                ->where('company_id', auth()->user()->company_id)
                ->first();

            if (! $timeEntry) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Time entry not found',
                    ], 404);
                }

                return redirect()->back()->with('error', 'Time entry not found');
            }
        }
        // If no specific time entry provided, find the user's active timer
        elseif (! $timeEntry) {
            $timeEntry = TicketTimeEntry::where('user_id', auth()->id())
                ->where('company_id', auth()->user()->company_id)
                ->whereNull('ended_at')
                ->first();

            if (! $timeEntry) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active timer found',
                    ], 404);
                }

                return redirect()->back()->with('error', 'No active timer found');
            }
        }

        $this->authorize('update', $timeEntry);

        if ($timeEntry->ended_at || $timeEntry->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Timer is not running or does not belong to you',
            ], 422);
        }

        $timeEntry->stopTimer();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Timer stopped successfully',
                'time_entry' => $timeEntry->fresh()->load('ticket'),
                'duration' => $timeEntry->formatted_duration,
            ]);
        }

        return redirect()->back()->with('success', 'Timer stopped successfully');
    }

    /**
     * Get current running timer for user
     */
    public function getCurrentTimer()
    {
        $timer = TicketTimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->with('ticket')
            ->first();

        return response()->json([
            'timer' => $timer,
            'elapsed_time' => $timer ? $timer->elapsed_time : null,
        ]);
    }

    /**
     * Approve time entries
     */
    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time_entry_ids' => 'required|array',
            'time_entry_ids.*' => 'integer|exists:ticket_time_entries,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = TicketTimeEntry::whereIn('id', $request->time_entry_ids)
            ->where('company_id', auth()->user()->company_id)
            ->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Approved {$count} time entries successfully",
        ]);
    }

    /**
     * Reject time entries
     */
    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time_entry_ids' => 'required|array',
            'time_entry_ids.*' => 'integer|exists:ticket_time_entries,id',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $timeEntries = TicketTimeEntry::whereIn('id', $request->time_entry_ids)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        foreach ($timeEntries as $entry) {
            $entry->update([
                'is_approved' => false,
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => $request->rejection_reason,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Rejected {$timeEntries->count()} time entries successfully",
        ]);
    }

    /**
     * Generate time tracking report
     */
    public function report(Request $request)
    {
        $query = TicketTimeEntry::where('company_id', auth()->user()->company_id);

        // Apply date range (required for reports)
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query->whereBetween('started_at', [$startDate, $endDate]);

        // Apply filters
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($request->has('billable')) {
            $query->where('is_billable', $request->boolean('billable'));
        }

        if ($request->has('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }

        $timeEntries = $query->with(['ticket.client', 'user'])->get();

        // Generate report data
        $reportData = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_entries' => $timeEntries->count(),
                'total_hours' => round($timeEntries->sum('duration_minutes') / 60, 2),
                'billable_hours' => round($timeEntries->where('is_billable', true)->sum('duration_minutes') / 60, 2),
                'total_cost' => $timeEntries->sum('cost'),
                'approved_hours' => round($timeEntries->where('is_approved', true)->sum('duration_minutes') / 60, 2),
            ],
            'by_user' => $timeEntries->groupBy('user.name')->map(function ($entries) {
                return [
                    'total_hours' => round($entries->sum('duration_minutes') / 60, 2),
                    'billable_hours' => round($entries->where('is_billable', true)->sum('duration_minutes') / 60, 2),
                    'total_cost' => $entries->sum('cost'),
                    'entries_count' => $entries->count(),
                ];
            }),
            'by_ticket' => $timeEntries->groupBy('ticket.subject')->map(function ($entries) {
                return [
                    'total_hours' => round($entries->sum('duration_minutes') / 60, 2),
                    'total_cost' => $entries->sum('cost'),
                    'entries_count' => $entries->count(),
                ];
            }),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report' => $reportData,
                'time_entries' => $timeEntries,
            ]);
        }

        return view('tickets.time-tracking.report', compact('reportData', 'timeEntries', 'startDate', 'endDate'));
    }

    /**
     * Export time entries to CSV
     */
    public function export(Request $request)
    {
        $query = TicketTimeEntry::where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('started_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('started_at', '<=', $endDate);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        $timeEntries = $query->with(['ticket.client', 'user', 'approvedBy'])
            ->orderBy('started_at', 'desc')
            ->get();

        $filename = 'time-entries_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($timeEntries) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date',
                'User',
                'Ticket',
                'Client',
                'Description',
                'Duration (Hours)',
                'Billable',
                'Rate',
                'Cost',
                'Category',
                'Approved',
                'Approved By',
            ]);

            // CSV data
            foreach ($timeEntries as $entry) {
                fputcsv($file, [
                    $entry->started_at->format('Y-m-d'),
                    $entry->user->name,
                    $entry->ticket->subject,
                    $entry->ticket->client->name ?? '',
                    $entry->description,
                    round($entry->duration_minutes / 60, 2),
                    $entry->is_billable ? 'Yes' : 'No',
                    $entry->hourly_rate ?? '',
                    $entry->cost ?? '',
                    $entry->task_category ?? '',
                    $entry->is_approved ? 'Yes' : 'No',
                    $entry->approvedBy->name ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate summary statistics for the current filters
     */
    private function calculateSummaryStats(Request $request): array
    {
        $query = TicketTimeEntry::where('company_id', auth()->user()->company_id);

        // Apply same filters as main query
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('started_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('started_at', '<=', $endDate);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($ticketId = $request->get('ticket_id')) {
            $query->where('ticket_id', $ticketId);
        }

        $entries = $query->get();

        $totalHours = $entries->sum('hours_worked') ?: 0;
        $billableEntries = $entries->where('billable', true);
        $billableHours = $billableEntries->sum('hours_worked') ?: 0;
        $totalRevenue = $billableEntries->sum('amount') ?: 0;
        $avgHourlyRate = $billableHours > 0 ? ($totalRevenue / $billableHours) : 0;

        return [
            'total_entries' => $entries->count(),
            'total_hours' => round($totalHours, 2),
            'billable_hours' => round($billableHours, 2),
            'total_revenue' => round($totalRevenue, 2),
            'avg_hourly_rate' => round($avgHourlyRate, 2),
            'approved_entries' => $entries->whereNotNull('approved_at')->count(),
            'pending_approval' => $entries->whereNull('approved_at')->whereNotNull('submitted_at')->count(),
        ];
    }
}
