<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketCalendarEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Calendar Controller
 *
 * Manages ticket calendar events with scheduling, conflict detection,
 * recurring events, and comprehensive calendar views following the domain architecture pattern.
 */
class CalendarController extends Controller
{
    /**
     * Display calendar view with events
     */
    public function index(Request $request)
    {
        $view = $request->get('view', 'month'); // month, week, day
        $date = $request->get('date', now()->toDateString());

        $currentDate = Carbon::parse($date);

        // Calculate date range based on view
        switch ($view) {
            case 'week':
                $startDate = $currentDate->copy()->startOfWeek();
                $endDate = $currentDate->copy()->endOfWeek();
                break;
            case 'day':
                $startDate = $currentDate->copy()->startOfDay();
                $endDate = $currentDate->copy()->endOfDay();
                break;
            case 'month':
            default:
                $startDate = $currentDate->copy()->startOfMonth()->startOfWeek();
                $endDate = $currentDate->copy()->endOfMonth()->endOfWeek();
                break;
        }

        $query = TicketCalendarEvent::where('company_id', auth()->user()->company_id)
            ->whereBetween('starts_at', [$startDate, $endDate]);

        // Apply filters
        if ($userId = $request->get('user_id')) {
            $query->where('assigned_to', $userId);
        }

        if ($type = $request->get('type')) {
            $query->where('event_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $events = $query->with(['ticket.client', 'assignee', 'createdBy'])
            ->orderBy('starts_at')
            ->get();

        // Get filter options
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $eventTypes = ['meeting', 'maintenance', 'deployment', 'review', 'training', 'other'];
        $eventStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];

        if ($request->wantsJson()) {
            return response()->json([
                'events' => $events,
                'view' => $view,
                'current_date' => $currentDate->toDateString(),
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'users' => $users,
                'event_types' => $eventTypes,
                'event_statuses' => $eventStatuses,
            ]);
        }

        return view('tickets.calendar.index', compact(
            'events', 'view', 'currentDate', 'startDate', 'endDate',
            'users', 'eventTypes', 'eventStatuses'
        ));
    }

    /**
     * Show the form for creating a new event
     */
    public function create(Request $request)
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('company_id', auth()->user()->company_id)
            ->where('status', '!=', 'closed')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        $eventTypes = ['meeting', 'maintenance', 'deployment', 'review', 'training', 'other'];

        // Pre-fill date/time if provided
        $defaultStart = $request->get('start_time', now()->addHour()->format('Y-m-d H:i'));
        $defaultEnd = $request->get('end_time', now()->addHours(2)->format('Y-m-d H:i'));

        return view('tickets.calendar.create', compact(
            'users', 'tickets', 'eventTypes', 'defaultStart', 'defaultEnd'
        ));
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'nullable',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:meeting,maintenance,deployment,review,training,other',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'location' => 'nullable|string|max:255',
            'is_all_day' => 'boolean',
            'send_notifications' => 'boolean',
            'attendees' => 'nullable|array',
            'attendees.*' => 'email',
            'recurring_pattern' => 'nullable|string|in:daily,weekly,monthly',
            'recurring_end_date' => 'nullable|date|after:starts_at',
            'reminder_minutes' => 'nullable|integer|min:0|max:10080', // Max 1 week
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for conflicts
        $conflicts = $this->checkForConflicts(
            $request->assigned_to ?: auth()->id(),
            $request->starts_at,
            $request->ends_at
        );

        if ($conflicts->isNotEmpty() && ! $request->boolean('ignore_conflicts')) {
            return redirect()->back()
                ->withErrors(['conflicts' => 'This event conflicts with existing events'])
                ->withInput()
                ->with('conflicts', $conflicts);
        }

        $event = TicketCalendarEvent::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $request->ticket_id,
            'title' => $request->title,
            'description' => $request->description,
            'event_type' => $request->event_type,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'assigned_to' => $request->assigned_to ?: auth()->id(),
            'created_by' => auth()->id(),
            'location' => $request->location,
            'is_all_day' => $request->boolean('is_all_day'),
            'status' => 'scheduled',
            'attendees' => $request->attendees ?? [],
            'recurring_pattern' => $request->recurring_pattern,
            'recurring_end_date' => $request->recurring_end_date,
            'reminder_minutes' => $request->reminder_minutes,
            'send_notifications' => $request->boolean('send_notifications', true),
        ]);

        // Create recurring events if pattern is specified
        if ($event->recurring_pattern && $event->recurring_end_date) {
            $event->generateRecurringEvents();
        }

        // Send notifications if enabled
        if ($event->send_notifications) {
            // TODO: Implement notification sending
            // $event->sendNotifications();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'event' => $event->load(['ticket.client', 'assignee']),
            ], 201);
        }

        return redirect()->route('tickets.calendar.index')
            ->with('success', 'Event "'.$event->title.'" created successfully.');
    }

    /**
     * Display the specified event
     */
    public function show(TicketCalendarEvent $event)
    {
        $this->authorize('view', $event);

        $event->load(['ticket.client', 'assignee', 'createdBy']);

        if (request()->wantsJson()) {
            return response()->json([
                'event' => $event,
                'can_edit' => auth()->user()->can('update', $event),
                'can_delete' => auth()->user()->can('delete', $event),
            ]);
        }

        return view('tickets.calendar.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event
     */
    public function edit(TicketCalendarEvent $event)
    {
        $this->authorize('update', $event);

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('company_id', auth()->user()->company_id)
            ->where('status', '!=', 'closed')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        $eventTypes = ['meeting', 'maintenance', 'deployment', 'review', 'training', 'other'];

        return view('tickets.calendar.edit', compact('event', 'users', 'tickets', 'eventTypes'));
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, TicketCalendarEvent $event)
    {
        $this->authorize('update', $event);

        $validator = Validator::make($request->all(), [
            'ticket_id' => [
                'nullable',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:meeting,maintenance,deployment,review,training,other',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'location' => 'nullable|string|max:255',
            'is_all_day' => 'boolean',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'attendees' => 'nullable|array',
            'attendees.*' => 'email',
            'reminder_minutes' => 'nullable|integer|min:0|max:10080',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for conflicts if time or assignee changed
        $timeChanged = $event->starts_at != $request->starts_at || $event->ends_at != $request->ends_at;
        $assigneeChanged = $event->assigned_to != $request->assigned_to;

        if (($timeChanged || $assigneeChanged) && ! $request->boolean('ignore_conflicts')) {
            $conflicts = $this->checkForConflicts(
                $request->assigned_to ?: $event->assigned_to,
                $request->starts_at,
                $request->ends_at,
                $event->id
            );

            if ($conflicts->isNotEmpty()) {
                return redirect()->back()
                    ->withErrors(['conflicts' => 'This event conflicts with existing events'])
                    ->withInput()
                    ->with('conflicts', $conflicts);
            }
        }

        $event->update($request->only([
            'ticket_id',
            'title',
            'description',
            'event_type',
            'starts_at',
            'ends_at',
            'assigned_to',
            'location',
            'status',
            'attendees',
            'reminder_minutes',
        ]) + [
            'is_all_day' => $request->boolean('is_all_day'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'event' => $event->load(['ticket.client', 'assignee']),
            ]);
        }

        return redirect()->route('tickets.calendar.index')
            ->with('success', 'Event "'.$event->title.'" updated successfully.');
    }

    /**
     * Remove the specified event
     */
    public function destroy(TicketCalendarEvent $event)
    {
        $this->authorize('delete', $event);

        $eventTitle = $event->title;
        $event->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);
        }

        return redirect()->route('tickets.calendar.index')
            ->with('success', 'Event "'.$eventTitle.'" deleted successfully.');
    }

    /**
     * Update event status
     */
    public function updateStatus(Request $request, TicketCalendarEvent $event)
    {
        $this->authorize('update', $event);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->update([
            'status' => $request->status,
            'status_notes' => $request->notes,
            'status_updated_at' => now(),
            'status_updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event status updated successfully',
            'event' => $event->fresh(),
        ]);
    }

    /**
     * Move/resize event (drag and drop)
     */
    public function move(Request $request, TicketCalendarEvent $event)
    {
        $this->authorize('update', $event);

        $validator = Validator::make($request->all(), [
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for conflicts
        $conflicts = $this->checkForConflicts(
            $event->assigned_to,
            $request->starts_at,
            $request->ends_at,
            $event->id
        );

        if ($conflicts->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move event due to conflicts',
                'conflicts' => $conflicts,
            ], 422);
        }

        $event->update([
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event moved successfully',
            'event' => $event->fresh(),
        ]);
    }

    /**
     * Get events for specific date range (AJAX)
     */
    public function getEvents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $events = TicketCalendarEvent::where('company_id', auth()->user()->company_id)
            ->whereBetween('starts_at', [$request->start, $request->end])
            ->with(['ticket.client', 'assignee'])
            ->get();

        return response()->json($events);
    }

    /**
     * Check availability for a user in a time range
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $conflicts = $this->checkForConflicts(
            $request->user_id,
            $request->start,
            $request->end
        );

        return response()->json([
            'available' => $conflicts->isEmpty(),
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Export calendar to ICS format
     */
    public function exportIcs(Request $request)
    {
        $startDate = $request->get('start', now()->startOfMonth());
        $endDate = $request->get('end', now()->endOfMonth());

        $events = TicketCalendarEvent::where('company_id', auth()->user()->company_id)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->with(['ticket.client', 'assignee'])
            ->get();

        // TODO: Implement ICS generation
        return response()->json(['message' => 'ICS export - implementation pending']);
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkForConflicts($userId, $startTime, $endTime, $excludeEventId = null)
    {
        $query = TicketCalendarEvent::where('company_id', auth()->user()->company_id)
            ->where('assigned_to', $userId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('starts_at', [$startTime, $endTime])
                    ->orWhereBetween('ends_at', [$startTime, $endTime])
                    ->orWhere(function ($subQ) use ($startTime, $endTime) {
                        $subQ->where('starts_at', '<=', $startTime)
                            ->where('ends_at', '>=', $endTime);
                    });
            });

        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->get();
    }
}
