<?php

namespace App\Livewire\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Client\Models\Client;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class TicketIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $priority = '';
    public $assignedTo = '';
    public $clientId = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;
    
    public $selectedTickets = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'priority' => ['except' => ''],
        'assignedTo' => ['except' => ''],
        'clientId' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25]
    ];

    public function mount()
    {
        // Get client from session if available
        $selectedClient = app(\App\Services\NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $this->clientId = $selectedClient;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPriority()
    {
        $this->resetPage();
    }

    public function updatingClientId()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTickets = $this->getTickets()->pluck('id')->toArray();
        } else {
            $this->selectedTickets = [];
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

    public function getTickets()
    {
        return Ticket::query()
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('ticket_number', 'like', '%' . $this->search . '%')
                      ->orWhere('subject', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->priority, function ($query) {
                $query->where('priority', $this->priority);
            })
            ->when($this->assignedTo, function ($query) {
                $query->where('assigned_to', $this->assignedTo);
            })
            ->when($this->clientId, function ($query) {
                $query->where('client_id', $this->clientId);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->with(['client', 'assignedTo', 'requester'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        $tickets = $this->getTickets();
        
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
            
        $users = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('livewire.tickets.ticket-index', [
            'tickets' => $tickets,
            'clients' => $clients,
            'users' => $users,
            'statuses' => ['open', 'in_progress', 'pending', 'resolved', 'closed'],
            'priorities' => ['low', 'medium', 'high', 'urgent']
        ]);
    }
}