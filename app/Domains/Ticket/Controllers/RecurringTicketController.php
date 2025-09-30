<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\RecurringTicket;
use App\Domains\Ticket\Models\TicketTemplate;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Recurring Ticket Controller
 *
 * Manages recurring ticket schedules with complex frequency patterns,
 * preview functionality, and automated ticket generation following the domain architecture pattern.
 */
class RecurringTicketController extends Controller
{
    /**
     * Display a listing of recurring tickets
     */
    public function index(Request $request)
    {
        $query = RecurringTicket::where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('template', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Apply frequency filter
        if ($frequency = $request->get('frequency')) {
            $query->where('frequency', $frequency);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply template filter
        if ($templateId = $request->get('template_id')) {
            $query->where('template_id', $templateId);
        }

        $recurringTickets = $query->with(['template', 'client', 'assignee', 'createdBy'])
            ->withCount('generatedTickets')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Get filter options
        $templates = TicketTemplate::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $frequencies = ['daily', 'weekly', 'monthly', 'yearly'];

        if ($request->wantsJson()) {
            return response()->json([
                'recurring_tickets' => $recurringTickets,
                'templates' => $templates,
                'clients' => $clients,
                'frequencies' => $frequencies,
            ]);
        }

        return view('tickets.recurring.index', compact(
            'recurringTickets', 'templates', 'clients', 'frequencies'
        ));
    }

    /**
     * Show the form for creating a new recurring ticket
     */
    public function create()
    {
        $templates = TicketTemplate::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $frequencies = ['daily', 'weekly', 'monthly', 'yearly'];
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('tickets.recurring.create', compact(
            'templates', 'clients', 'users', 'frequencies', 'weekdays'
        ));
    }

    /**
     * Store a newly created recurring ticket
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => [
                'required',
                'integer',
                Rule::exists('ticket_templates', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval_value' => 'required|integer|min:1|max:365',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'is_active' => 'boolean',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'month_day' => 'nullable|integer|min:1|max:31',
            'month_week' => 'nullable|integer|min:1|max:4',
            'month_weekday' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'time_of_day' => 'nullable|date_format:H:i',
            'template_variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Build frequency config based on frequency type
        $frequencyConfig = $this->buildFrequencyConfig($request);

        $recurringTicket = RecurringTicket::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
            'template_id' => $request->template_id,
            'client_id' => $request->client_id,
            'frequency' => $request->frequency,
            'interval_value' => $request->interval_value,
            'frequency_config' => $frequencyConfig,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'is_active' => $request->boolean('is_active', true),
            'time_of_day' => $request->time_of_day,
            'template_variables' => $request->template_variables ?? [],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recurring ticket created successfully',
                'recurring_ticket' => $recurringTicket->load(['template', 'client', 'assignee']),
            ], 201);
        }

        return redirect()->route('tickets.recurring.index')
            ->with('success', 'Recurring ticket "'.$recurringTicket->name.'" created successfully.');
    }

    /**
     * Display the specified recurring ticket
     */
    public function show(RecurringTicket $recurringTicket)
    {
        $this->authorize('view', $recurringTicket);

        $recurringTicket->load([
            'template',
            'client',
            'assignee',
            'createdBy',
            'generatedTickets' => function ($query) {
                $query->latest()->limit(20);
            },
        ]);

        // Get next scheduled dates
        $nextDates = $recurringTicket->getNextScheduledDates(10);

        // Get generation statistics
        $stats = [
            'total_generated' => $recurringTicket->tickets_generated,
            'last_generated' => $recurringTicket->last_generated_at,
            'next_due' => $recurringTicket->next_due_date,
            'days_until_next' => $recurringTicket->next_due_date ?
                now()->diffInDays($recurringTicket->next_due_date, false) : null,
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'recurring_ticket' => $recurringTicket,
                'next_dates' => $nextDates,
                'stats' => $stats,
            ]);
        }

        return view('tickets.recurring.show', compact('recurringTicket', 'nextDates', 'stats'));
    }

    /**
     * Show the form for editing the specified recurring ticket
     */
    public function edit(RecurringTicket $recurringTicket)
    {
        $this->authorize('update', $recurringTicket);

        $templates = TicketTemplate::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $frequencies = ['daily', 'weekly', 'monthly', 'yearly'];
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('tickets.recurring.edit', compact(
            'recurringTicket', 'templates', 'clients', 'users', 'frequencies', 'weekdays'
        ));
    }

    /**
     * Update the specified recurring ticket
     */
    public function update(Request $request, RecurringTicket $recurringTicket)
    {
        $this->authorize('update', $recurringTicket);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => [
                'required',
                'integer',
                Rule::exists('ticket_templates', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval_value' => 'required|integer|min:1|max:365',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'is_active' => 'boolean',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'month_day' => 'nullable|integer|min:1|max:31',
            'month_week' => 'nullable|integer|min:1|max:4',
            'month_weekday' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'time_of_day' => 'nullable|date_format:H:i',
            'template_variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Build frequency config based on frequency type
        $frequencyConfig = $this->buildFrequencyConfig($request);

        // Check if frequency settings changed - if so, recalculate next due date
        $frequencyChanged = (
            $recurringTicket->frequency !== $request->frequency ||
            $recurringTicket->interval_value !== $request->interval_value ||
            $recurringTicket->frequency_config !== $frequencyConfig
        );

        $updateData = $request->only([
            'name',
            'description',
            'template_id',
            'client_id',
            'frequency',
            'interval_value',
            'start_date',
            'end_date',
            'assigned_to',
            'time_of_day',
            'template_variables',
        ]) + [
            'frequency_config' => $frequencyConfig,
            'is_active' => $request->boolean('is_active'),
        ];

        // Recalculate next due date if frequency changed
        if ($frequencyChanged) {
            $updateData['next_due_date'] = $recurringTicket->calculateNextDueDate();
        }

        $recurringTicket->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recurring ticket updated successfully',
                'recurring_ticket' => $recurringTicket->load(['template', 'client', 'assignee']),
            ]);
        }

        return redirect()->route('tickets.recurring.index')
            ->with('success', 'Recurring ticket "'.$recurringTicket->name.'" updated successfully.');
    }

    /**
     * Remove the specified recurring ticket
     */
    public function destroy(RecurringTicket $recurringTicket)
    {
        $this->authorize('delete', $recurringTicket);

        $recurringTicketName = $recurringTicket->name;
        $recurringTicket->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recurring ticket deleted successfully',
            ]);
        }

        return redirect()->route('tickets.recurring.index')
            ->with('success', 'Recurring ticket "'.$recurringTicketName.'" deleted successfully.');
    }

    /**
     * Preview upcoming ticket generation dates
     */
    public function preview(Request $request, RecurringTicket $recurringTicket)
    {
        $this->authorize('view', $recurringTicket);

        $count = $request->get('count', 10);
        $count = min($count, 50); // Limit to prevent performance issues

        $dates = $recurringTicket->getNextScheduledDates($count);

        return response()->json([
            'success' => true,
            'recurring_ticket' => $recurringTicket->name,
            'preview_dates' => $dates->map(function ($date) {
                return [
                    'date' => $date->toDateString(),
                    'time' => $date->format('H:i'),
                    'formatted' => $date->format('M j, Y \a\t g:i A'),
                    'days_from_now' => now()->diffInDays($date, false),
                ];
            }),
        ]);
    }

    /**
     * Manually generate ticket now
     */
    public function generateNow(Request $request, RecurringTicket $recurringTicket)
    {
        $this->authorize('update', $recurringTicket);

        if (! $recurringTicket->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot generate tickets from inactive recurring schedule',
            ], 422);
        }

        try {
            $variables = $request->get('template_variables', []);
            $ticket = $recurringTicket->generateTicket($variables);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket generated successfully',
                    'ticket' => $ticket->load('client'),
                ], 201);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket generated successfully from recurring schedule.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate ticket: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to generate ticket: '.$e->getMessage());
        }
    }

    /**
     * Pause/Resume recurring ticket
     */
    public function toggleActive(RecurringTicket $recurringTicket)
    {
        $this->authorize('update', $recurringTicket);

        $recurringTicket->update([
            'is_active' => ! $recurringTicket->is_active,
        ]);

        $status = $recurringTicket->is_active ? 'activated' : 'paused';

        return response()->json([
            'success' => true,
            'message' => "Recurring ticket {$status} successfully",
            'is_active' => $recurringTicket->is_active,
        ]);
    }

    /**
     * Get recurring schedule preview for AJAX
     */
    public function getSchedulePreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval_value' => 'required|integer|min:1|max:365',
            'start_date' => 'required|date|after_or_equal:today',
            'weekdays' => 'nullable|array',
            'month_day' => 'nullable|integer|min:1|max:31',
            'month_week' => 'nullable|integer|min:1|max:4',
            'month_weekday' => 'nullable|string',
            'time_of_day' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create a temporary recurring ticket instance to calculate dates
        $temp = new RecurringTicket([
            'frequency' => $request->frequency,
            'interval_value' => $request->interval_value,
            'frequency_config' => $this->buildFrequencyConfig($request),
            'start_date' => $request->start_date,
            'time_of_day' => $request->time_of_day,
        ]);

        $dates = $temp->getNextScheduledDates(10);

        return response()->json([
            'success' => true,
            'preview_dates' => $dates->map(function ($date) {
                return [
                    'date' => $date->toDateString(),
                    'time' => $date->format('H:i'),
                    'formatted' => $date->format('M j, Y \a\t g:i A'),
                    'days_from_now' => now()->diffInDays($date, false),
                ];
            }),
        ]);
    }

    /**
     * Export recurring tickets to CSV
     */
    public function export(Request $request)
    {
        $query = RecurringTicket::where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($frequency = $request->get('frequency')) {
            $query->where('frequency', $frequency);
        }

        $recurringTickets = $query->with(['template', 'client', 'assignee'])
            ->orderBy('name')
            ->get();

        $filename = 'recurring-tickets_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($recurringTickets) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Name',
                'Template',
                'Client',
                'Frequency',
                'Interval',
                'Start Date',
                'End Date',
                'Assignee',
                'Active',
                'Tickets Generated',
                'Next Due',
                'Created Date',
            ]);

            // CSV data
            foreach ($recurringTickets as $recurring) {
                fputcsv($file, [
                    $recurring->name,
                    $recurring->template->name,
                    $recurring->client->name,
                    ucfirst($recurring->frequency),
                    $recurring->interval_value,
                    $recurring->start_date?->format('Y-m-d'),
                    $recurring->end_date?->format('Y-m-d'),
                    $recurring->assignee?->name,
                    $recurring->is_active ? 'Yes' : 'No',
                    $recurring->tickets_generated,
                    $recurring->next_due_date?->format('Y-m-d H:i'),
                    $recurring->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Build frequency configuration array based on request data
     */
    private function buildFrequencyConfig(Request $request): array
    {
        $config = [];

        switch ($request->frequency) {
            case 'weekly':
                if ($request->has('weekdays')) {
                    $config['weekdays'] = $request->weekdays;
                }
                break;

            case 'monthly':
                if ($request->filled('month_day')) {
                    $config['day'] = $request->month_day;
                } elseif ($request->filled('month_week') && $request->filled('month_weekday')) {
                    $config['week'] = $request->month_week;
                    $config['weekday'] = $request->month_weekday;
                }
                break;

            case 'yearly':
                if ($request->filled('month_day')) {
                    $config['month'] = Carbon::parse($request->start_date)->month;
                    $config['day'] = $request->month_day;
                }
                break;
        }

        return $config;
    }
}
