<?php

namespace App\Livewire\Tickets;

use App\Domains\Client\Models\Client;
use App\Domains\Ticket\Models\Ticket;
use App\Livewire\BaseIndexComponent;
use App\Domains\Core\Models\User;
use Illuminate\Support\Facades\Auth;

class TicketIndex extends BaseIndexComponent
{
    protected $casts = [
        'selectedStatuses' => 'array',
        'selectedPriorities' => 'array',
        'selectedAssignees' => 'array',
        'selectedClients' => 'array',
    ];

    public $selectedStatuses = [];

    public $selectedPriorities = [];

    public $selectedAssignees = [];

    public $selectedClients = [];

    public $dateFrom = '';

    public $dateTo = '';

    public $viewMode = 'cards';

    public $filter = '';

    public $selectedTickets = [];

    public function mount()
    {
        parent::mount();

        $this->viewMode = session('ticket_view_mode', 'cards');

        if (request()->has('filter')) {
            $this->filter = request()->get('filter');
        }

        if (! $this->filter) {
            $this->selectedStatuses = $this->selectedStatuses ?: ['open', 'in_progress', 'waiting', 'on_hold'];
            $this->selectedPriorities = $this->selectedPriorities ?: [];
            $this->selectedAssignees = $this->selectedAssignees ?: [];
            $this->selectedClients = $this->selectedClients ?: [];
        }
    }

    protected function getDefaultSort(): array
    {
        return [
            'field' => 'created_at',
            'direction' => 'desc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'subject',
            'details',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'selectedStatuses' => ['except' => []],
            'selectedPriorities' => ['except' => []],
            'selectedAssignees' => ['except' => []],
            'selectedClients' => ['except' => []],
            'dateFrom' => ['except' => ''],
            'dateTo' => ['except' => ''],
            'sortField' => ['except' => 'created_at'],
            'sortDirection' => ['except' => 'desc'],
            'perPage' => ['except' => 25],
            'viewMode' => ['except' => 'cards'],
            'filter' => ['except' => ''],
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Ticket::with(['client', 'assignedTo', 'requester']);
    }

    protected function applySearch($query)
    {
        if (! $this->search) {
            return $query;
        }

        return $query->where(function ($q) {
            $q->whereRaw('CAST(number AS TEXT) LIKE ?', ['%'.$this->search.'%'])
                ->orWhere('subject', 'like', '%'.$this->search.'%')
                ->orWhere('details', 'like', '%'.$this->search.'%');
        });
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTickets = $this->getItems()->pluck('id')->toArray();
            $this->selected = $this->selectedTickets;
        } else {
            $this->selectedTickets = [];
            $this->selected = [];
        }
    }

    public function bulkDelete()
    {
        $count = count($this->selectedTickets);

        Ticket::whereIn('id', $this->selectedTickets)
            ->where('company_id', Auth::user()->company_id)
            ->update(['archived_at' => now()]);

        $this->selectedTickets = [];
        $this->selectAll = false;

        session()->flash('message', "$count tickets have been archived.");
    }

    public function bulkUpdateStatus($status)
    {
        $count = count($this->selectedTickets);

        Ticket::whereIn('id', $this->selectedTickets)
            ->where('company_id', Auth::user()->company_id)
            ->update(['status' => $status]);

        $this->selectedTickets = [];
        $this->selectAll = false;

        session()->flash('message', "$count tickets have been updated to $status status.");
    }

    public function deleteTicket($ticketId)
    {
        $ticket = Ticket::where('id', $ticketId)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if ($ticket) {
            $ticket->update(['archived_at' => now()]);
            session()->flash('message', "Ticket #{$ticket->ticket_number} has been archived.");
        }
    }

    protected function applyCustomFilters($query)
    {
        $user = Auth::user();

        // Apply special filters
        switch ($this->filter) {
            case 'my':
                $query->where('created_by', $user->id);
                break;

            case 'sla_violation':
                $query->whereHas('priorityQueue', function ($q) {
                    $q->where('sla_deadline', '<', now());
                })
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'sla_warning':
                // SLA warning - approaching deadline (within 2 hours)
                $query->whereHas('priorityQueue', function ($q) {
                    $q->where('sla_deadline', '>', now())
                        ->where('sla_deadline', '<', now()->addHours(2));
                })
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'unassigned':
                $query->whereNull('assigned_to')
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'due_today':
                $query->whereDate('scheduled_at', today())
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'team':
                if ($user->department_id) {
                    $query->whereHas('assignee', function ($q) use ($user) {
                        $q->where('department_id', $user->department_id);
                    });
                }
                $query->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'watched':
                $query->whereHas('watchers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                break;

            case 'escalated':
                // For now, show high/critical priority tickets as "escalated"
                $query->whereIn('priority', ['high', 'critical'])
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;

            case 'merged':
                // This feature needs to be implemented with a proper relationship
                // For now, return empty result
                $query->whereRaw('1=0');
                break;

            case 'archived':
                $query->whereNotNull('archived_at');
                break;

            default:
                // For non-archived tickets by default
                if ($this->filter !== 'archived') {
                    $query->whereNull('archived_at');
                }
                break;
        }

        if (! empty($this->selectedStatuses)) {
            $query->whereIn('status', $this->selectedStatuses);
        } elseif (! $this->filter) {
            $query->whereIn('status', Ticket::ACTIVE_STATUSES);
        }

        if (! empty($this->selectedPriorities)) {
            $query->whereIn('priority', $this->selectedPriorities);
        }

        if (! empty($this->selectedAssignees)) {
            $query->whereIn('assigned_to', $this->selectedAssignees);
        }

        if (! empty($this->selectedClients)) {
            $query->whereIn('client_id', $this->selectedClients);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query;
    }

    protected function applySorting($query)
    {
        if ($this->filter === 'sla_violation' || $this->filter === 'sla_warning') {
            return $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
        } elseif ($this->filter === 'due_today') {
            return $query->orderBy('due_date', 'asc')->orderBy('priority', 'desc');
        }

        return parent::applySorting($query);
    }

    public function render()
    {
        $tickets = $this->getItems();

        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $users = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        // All available statuses
        $statuses = ['open', 'in_progress', 'waiting', 'on_hold', 'resolved', 'closed', 'cancelled'];

        // Use model constants for priorities
        $priorities = [
            Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_MEDIUM,
            Ticket::PRIORITY_HIGH,
            Ticket::PRIORITY_CRITICAL,
        ];

        return view('livewire.tickets.ticket-index', [
            'tickets' => $tickets,
            'clients' => $clients,
            'users' => $users,
            'statuses' => $statuses,
            'priorities' => $priorities,
        ]);
    }

    public function toggleView($mode)
    {
        $this->viewMode = $mode;
        session(['ticket_view_mode' => $mode]); // Save preference to session
        $this->resetPage();
    }

    public function updateStatus($ticketId, $status)
    {
        $this->dispatch('ticket-updating', ['id' => $ticketId]);

        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $ticket->status = $status;
            $ticket->save();
            session()->flash('message', 'Ticket status updated successfully.');
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedStatuses = [];
        $this->selectedPriorities = [];
        $this->selectedAssignees = [];
        $this->selectedClients = [];
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function showAllTickets()
    {
        // Select all statuses to show all tickets
        $this->selectedStatuses = ['open', 'in_progress', 'waiting', 'on_hold', 'resolved', 'closed', 'cancelled'];
        $this->resetPage();
    }
}
