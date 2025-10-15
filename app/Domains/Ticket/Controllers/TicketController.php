<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Core\Controllers\Traits\UsesSelectedClient;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Ticket\Repositories\TicketRepository;
use App\Domains\Ticket\Requests\UpdateTicketRequest;
use App\Domains\Ticket\Services\TicketQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Client;
use App\Models\User;
use App\Traits\FiltersClientsByAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    use FiltersClientsByAssignment, UsesSelectedClient;

    public function __construct(
        private TicketQueryService $queryService,
        private TicketRepository $ticketRepository
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();

        $query = $this->applyClientFilter($query, 'client_id');
        $query->where('company_id', $user->company_id);

        $query = $this->queryService->applyBasicFilters($query, $request);
        $query = $this->queryService->applyDateFilters($query, $request);
        $query = $this->queryService->applyAdvancedFilters($query, $request);
        $query = $this->queryService->applySentimentFilters($query, $request);
        $query = $this->queryService->applySorting($query, $request);

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

        $filterOptions = $this->queryService->getFilterOptions($user->company_id);

        if ($request->wantsJson()) {
            return response()->json([
                'tickets' => $tickets,
                'filter_options' => $filterOptions,
            ]);
        }

        return view('tickets.index-livewire');
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $clients = Client::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $assignees = User::where('company_id', $user->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        $selectedClient = $this->getSelectedClient();
        if ($selectedClient && ! $clients->contains('id', $selectedClient->id)) {
            $selectedClient = null;
        }

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $statuses = ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'];

        return view('tickets.create', compact(
            'clients', 'assignees', 'selectedClient', 'priorities', 'statuses'
        ));
    }

    public function store(StoreTicketRequest $request)
    {
        try {
            $user = $request->user();
            $ticket = null;

            DB::transaction(function () use ($request, $user, &$ticket) {
                $ticketNumber = $this->generateTicketNumber($user->company_id);

                $ticket = Ticket::create([
                    'company_id' => $user->company_id,
                    'number' => $ticketNumber,
                    'client_id' => $request->client_id,
                    'contact_id' => $request->contact_id,
                    'subject' => $request->subject,
                    'details' => $request->details,
                    'priority' => $request->priority,
                    'status' => $request->status ?? 'new',
                    'assigned_to' => $request->assigned_to,
                    'created_by' => $user->id,
                    'scheduled_at' => $request->scheduled_at,
                    'estimated_hours' => $request->estimated_hours,
                    'tags' => $request->tags ?? [],
                    'custom_fields' => $request->custom_fields ?? [],
                ]);

                if ($request->workflow_id) {
                    $ticket->update(['workflow_id' => $request->workflow_id]);
                }

                TicketWatcher::create([
                    'company_id' => $user->company_id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
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
                'user_id' => $user->id,
            ]);

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
                'user_id' => $request->user()->id,
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
            'comments' => fn ($query) => $query->with(['author', 'timeEntry'])->orderBy('created_at', 'desc'),
            'resolver',
            'reopener',
        ]);

        $availableTransitions = $ticket->workflow ?
            $ticket->getAvailableTransitions() : collect();

        $recentActivity = $ticket->getRecentActivity(20);

        if ($request->wantsJson()) {
            return response()->json([
                'ticket' => $ticket,
                'available_transitions' => $availableTransitions,
                'recent_activity' => $recentActivity,
            ]);
        }

        return view('tickets.show-livewire', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = auth()->user();

        $clients = Client::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $assignees = User::where('company_id', $user->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $statuses = ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'];

        return view('tickets.edit', compact('ticket', 'clients', 'assignees', 'priorities', 'statuses'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        try {
            $ticket->update($request->only([
                'client_id',
                'contact_id',
                'subject',
                'details',
                'priority',
                'status',
                'assigned_to',
                'scheduled_at',
                'estimated_hours',
                'tags',
                'custom_fields',
            ]));

            Log::info('Ticket updated', [
                'ticket_id' => $ticket->id,
                'number' => $ticket->number,
                'user_id' => $request->user()->id,
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
                'user_id' => $request->user()->id,
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

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        try {
            $ticketNumber = $ticket->number;

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

    private function generateTicketNumber(int $companyId): int
    {
        return $this->ticketRepository->getNextTicketNumber($companyId);
    }
}
