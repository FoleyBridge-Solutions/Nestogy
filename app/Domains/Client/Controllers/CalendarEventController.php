<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCalendarEvent;
use App\Services\NavigationService;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CalendarEventController extends Controller
{
    use UsesSelectedClient;
    /**
     * Display a listing of calendar events for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (!$client) {
            return redirect()->route('clients.select-screen');
        }

        $query = $client->calendarEvents()->with(['client', 'creator']);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply event type filter
        if ($type = $request->get('event_type')) {
            $query->where('event_type', $type);
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Client filter not needed - using selected client

        // Apply priority filter
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        // Apply date range filters
        if ($request->get('upcoming_only')) {
            $query->upcoming();
        } elseif ($request->get('past_only')) {
            $query->past();
        } elseif ($startDate = $request->get('start_date')) {
            $endDate = $request->get('end_date') ?: $startDate;
            $query->dateRange($startDate, $endDate);
        }

        $events = $query->orderBy('start_datetime', 'asc')
                        ->paginate(20)
                        ->appends($request->query());

        $types = ClientCalendarEvent::getTypes();
        $statuses = ClientCalendarEvent::getStatuses();
        $priorities = ClientCalendarEvent::getPriorities();

        return view('clients.calendar-events.index', compact('events', 'client', 'types', 'statuses', 'priorities'));
    }

    /**
     * Show the form for creating a new calendar event
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $types = ClientCalendarEvent::getTypes();
        $statuses = ClientCalendarEvent::getStatuses();
        $priorities = ClientCalendarEvent::getPriorities();
        $reminderOptions = ClientCalendarEvent::getReminderOptions();

        return view('clients.calendar-events.create', compact('clients', 'selectedClientId', 'types', 'statuses', 'priorities', 'reminderOptions'));
    }

    /**
     * Store a newly created calendar event
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getTypes())),
            'location' => 'nullable|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'all_day' => 'boolean',
            'status' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getStatuses())),
            'priority' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getPriorities())),
            'attendees' => 'nullable|string',
            'reminder_minutes' => 'nullable|in:' . implode(',', array_keys(ClientCalendarEvent::getReminderOptions())),
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process attendees
        $attendees = [];
        if ($request->attendees) {
            $attendees = array_map('trim', explode(',', $request->attendees));
            $attendees = array_filter($attendees);
        }

        $event = new ClientCalendarEvent([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'event_type' => $request->event_type,
            'location' => $request->location,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'all_day' => $request->has('all_day'),
            'status' => $request->status,
            'priority' => $request->priority,
            'attendees' => $attendees,
            'reminder_minutes' => $request->reminder_minutes ?: 0,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);
        
        $event->company_id = auth()->user()->company_id;
        $event->save();

        return redirect()->route('clients.calendar-events.standalone.index')
                        ->with('success', 'Calendar event created successfully.');
    }

    /**
     * Display the specified calendar event
     */
    public function show(ClientCalendarEvent $calendarEvent)
    {
        $this->authorize('view', $calendarEvent);

        $calendarEvent->load('client', 'creator');
        
        // Update access timestamp
        $calendarEvent->update(['accessed_at' => now()]);

        return view('clients.calendar-events.show', compact('calendarEvent'));
    }

    /**
     * Show the form for editing the specified calendar event
     */
    public function edit(ClientCalendarEvent $calendarEvent)
    {
        $this->authorize('update', $calendarEvent);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $types = ClientCalendarEvent::getTypes();
        $statuses = ClientCalendarEvent::getStatuses();
        $priorities = ClientCalendarEvent::getPriorities();
        $reminderOptions = ClientCalendarEvent::getReminderOptions();

        return view('clients.calendar-events.edit', compact('calendarEvent', 'clients', 'types', 'statuses', 'priorities', 'reminderOptions'));
    }

    /**
     * Update the specified calendar event
     */
    public function update(Request $request, ClientCalendarEvent $calendarEvent)
    {
        $this->authorize('update', $calendarEvent);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getTypes())),
            'location' => 'nullable|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'all_day' => 'boolean',
            'status' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getStatuses())),
            'priority' => 'required|in:' . implode(',', array_keys(ClientCalendarEvent::getPriorities())),
            'attendees' => 'nullable|string',
            'reminder_minutes' => 'nullable|in:' . implode(',', array_keys(ClientCalendarEvent::getReminderOptions())),
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process attendees
        $attendees = [];
        if ($request->attendees) {
            $attendees = array_map('trim', explode(',', $request->attendees));
            $attendees = array_filter($attendees);
        }

        $calendarEvent->fill([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'event_type' => $request->event_type,
            'location' => $request->location,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'all_day' => $request->has('all_day'),
            'status' => $request->status,
            'priority' => $request->priority,
            'attendees' => $attendees,
            'reminder_minutes' => $request->reminder_minutes ?: 0,
            'notes' => $request->notes,
        ]);

        $calendarEvent->save();

        return redirect()->route('clients.calendar-events.standalone.index')
                        ->with('success', 'Calendar event updated successfully.');
    }

    /**
     * Remove the specified calendar event
     */
    public function destroy(ClientCalendarEvent $calendarEvent)
    {
        $this->authorize('delete', $calendarEvent);

        $calendarEvent->delete();

        return redirect()->route('clients.calendar-events.standalone.index')
                        ->with('success', 'Calendar event deleted successfully.');
    }

    /**
     * Export calendar events to CSV
     */
    public function export(Request $request)
    {
        $query = ClientCalendarEvent::with(['client', 'creator'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('event_type')) {
            $query->where('event_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $events = $query->orderBy('start_datetime', 'asc')->get();

        $filename = 'calendar_events_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Event Title',
                'Type',
                'Client Name',
                'Start DateTime',
                'End DateTime',
                'Duration',
                'Location',
                'Status',
                'Priority',
                'Attendees',
                'All Day',
                'Reminder',
                'Created By',
                'Created At'
            ]);

            // CSV data
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->title,
                    $event->event_type,
                    $event->client->display_name,
                    $event->start_datetime->format('Y-m-d H:i:s'),
                    $event->end_datetime->format('Y-m-d H:i:s'),
                    $event->duration_human,
                    $event->location,
                    $event->status,
                    $event->priority,
                    is_array($event->attendees) ? implode('; ', $event->attendees) : '',
                    $event->all_day ? 'Yes' : 'No',
                    $event->reminder_minutes ? ClientCalendarEvent::getReminderOptions()[$event->reminder_minutes] : 'None',
                    $event->creator ? $event->creator->name : '',
                    $event->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}