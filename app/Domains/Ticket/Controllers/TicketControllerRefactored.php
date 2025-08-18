<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Ticket;
use App\Models\Client;
use App\Models\User;
use App\Domains\Ticket\Services\TicketService;
use App\Domains\Ticket\Requests\StoreTicketRequest;
use App\Domains\Ticket\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;

class TicketControllerRefactored extends BaseController
{
    protected function initializeController(): void
    {
        $this->modelClass = Ticket::class;
        $this->serviceClass = TicketService::class;
        $this->resourceName = 'tickets';
        $this->viewPrefix = 'tickets';
        $this->eagerLoadRelations = ['client', 'assignedUser', 'category'];
    }

    protected function getFilters(Request $request): array
    {
        return $request->only([
            'search', 'status', 'priority', 'category_id', 'client_id', 
            'assigned_user_id', 'date_from', 'date_to'
        ]);
    }

    protected function applyCustomFilters($query, Request $request)
    {
        // Apply priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        // Apply category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Apply client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        // Apply assigned user filter
        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->get('assigned_user_id'));
        }

        // Apply date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        return $query;
    }

    protected function getIndexViewData(Request $request): array
    {
        $service = app($this->serviceClass);
        
        return [
            'clients' => Client::where('company_id', auth()->user()->company_id)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::where('company_id', auth()->user()->company_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => $service->getTicketCategories(),
            'statistics' => $service->getStatistics(),
        ];
    }

    protected function getShowViewData(\Illuminate\Database\Eloquent\Model $model): array
    {
        $ticket = $model;
        
        $ticket->load([
            'client.primaryContact',
            'assignedUser',
            'category',
            'replies.user',
            'timeEntries.user',
            'attachments'
        ]);

        $service = app($this->serviceClass);

        return [
            'ticket' => $ticket,
            'relatedTickets' => $service->getRelatedTickets($ticket),
            'timeSpent' => $ticket->timeEntries->sum('hours'),
            'canEdit' => in_array($ticket->status, ['Open', 'In Progress']),
            'canClose' => in_array($ticket->status, ['Open', 'In Progress', 'Waiting']),
        ];
    }

    protected function getCreateViewData(): array
    {
        $service = app($this->serviceClass);
        
        return [
            'clients' => Client::where('company_id', auth()->user()->company_id)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::where('company_id', auth()->user()->company_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => $service->getTicketCategories(),
            'priorities' => Ticket::getPriorities(),
        ];
    }

    // Custom ticket-specific methods

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'assigned_user_id' => 'required|exists:users,id'
        ]);

        try {
            $service = app($this->serviceClass);
            $service->assignTicket($ticket, $request->assigned_user_id);
            
            $this->logActivity($ticket, 'assigned', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket assigned successfully'
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Ticket assigned successfully');

        } catch (\Exception $e) {
            $this->logError('assignment', $e, $request, $ticket);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign ticket'
                ], 500);
            }

            return back()->with('error', 'Failed to assign ticket');
        }
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'required|in:' . implode(',', Ticket::getStatuses()),
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $service = app($this->serviceClass);
            $service->updateStatus($ticket, $request->status, $request->reason);
            
            $this->logActivity($ticket, 'status_changed', $request);

            return response()->json([
                'success' => true,
                'message' => 'Ticket status updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->logError('status_update', $e, $request, $ticket);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket status'
            ], 500);
        }
    }

    public function addReply(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240'
        ]);

        try {
            $service = app($this->serviceClass);
            $reply = $service->addReply($ticket, $request->all());
            
            $this->logActivity($ticket, 'reply_added', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reply added successfully',
                    'reply' => $reply
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Reply added successfully');

        } catch (\Exception $e) {
            $this->logError('add_reply', $e, $request, $ticket);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add reply'
                ], 500);
            }

            return back()->with('error', 'Failed to add reply');
        }
    }

    public function addTimeEntry(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'hours' => 'required|numeric|min:0.1|max:24',
            'description' => 'required|string|max:500',
            'billable' => 'boolean'
        ]);

        try {
            $service = app($this->serviceClass);
            $timeEntry = $service->addTimeEntry($ticket, $request->all());
            
            $this->logActivity($ticket, 'time_entry_added', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Time entry added successfully',
                    'time_entry' => $timeEntry
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Time entry added successfully');

        } catch (\Exception $e) {
            $this->logError('add_time_entry', $e, $request, $ticket);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add time entry'
                ], 500);
            }

            return back()->with('error', 'Failed to add time entry');
        }
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:assign,close,archive,delete',
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:tickets,id',
            'assigned_user_id' => 'required_if:action,assign|exists:users,id'
        ]);

        $tickets = Ticket::whereIn('id', $request->ticket_ids)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        $processed = 0;
        $service = app($this->serviceClass);

        foreach ($tickets as $ticket) {
            try {
                if (!auth()->user()->can('update', $ticket)) {
                    continue;
                }

                switch ($request->action) {
                    case 'assign':
                        $service->assignTicket($ticket, $request->assigned_user_id);
                        break;
                    case 'close':
                        $service->updateStatus($ticket, 'Closed');
                        break;
                    case 'archive':
                        $service->archive($ticket);
                        break;
                    case 'delete':
                        if (auth()->user()->can('delete', $ticket)) {
                            $service->delete($ticket);
                        }
                        break;
                }
                $processed++;
            } catch (\Exception $e) {
                // Log error but continue processing
                $this->logError('bulk_action', $e, $request, $ticket);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed {$processed} tickets"
        ]);
    }
}