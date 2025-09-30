<?php

namespace App\Livewire\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class TicketIndex extends Component
{
    use WithPagination;
    
    protected $casts = [
        'selectedStatuses' => 'array',
        'selectedPriorities' => 'array',
        'selectedAssignees' => 'array',
        'selectedClients' => 'array',
    ];

    public $search = '';
    public $selectedStatuses = [];
    public $selectedPriorities = [];
    public $selectedAssignees = [];
    public $selectedClients = [];
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $viewMode = 'cards'; // New property for view mode
    public $filter = ''; // Special filter mode
    
    public $selectedTickets = [];
    public $selectAll = false;

    protected $queryString = [
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
        'filter' => ['except' => '']
    ];

    public function mount()
    {
        // Get client from session if available
        $selectedClient = app(\App\Domains\Core\Services\NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            // Extract the ID if it's an object, otherwise use the value directly
            $this->selectedClients = [is_object($selectedClient) ? $selectedClient->id : $selectedClient];
        }
        
        // Load saved view mode preference from session
        $this->viewMode = session('ticket_view_mode', 'cards');
        
        // Handle special filter parameter from route
        if (request()->has('filter')) {
            $this->filter = request()->get('filter');
        }
        
        // Initialize with active statuses selected by default (unless overridden by filter)
        if (!$this->filter) {
            $this->selectedStatuses = $this->selectedStatuses ?: ['open', 'in_progress', 'waiting', 'on_hold'];
            $this->selectedPriorities = $this->selectedPriorities ?: [];
            $this->selectedAssignees = $this->selectedAssignees ?: [];
            $this->selectedClients = $this->selectedClients ?: [];
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
        $user = Auth::user();
        $query = Ticket::query()
            ->where('company_id', $user->company_id);
        
        // Apply special filters
        switch ($this->filter) {
            case 'my':
                $query->where('created_by', $user->id);
                break;
                
            case 'sla_violation':
                $query->whereHas('priorityQueue', function($q) {
                        $q->where('sla_deadline', '<', now());
                    })
                    ->whereNotIn('status', ['closed', 'resolved']);
                break;
                
            case 'sla_warning':
                // SLA warning - approaching deadline (within 2 hours)
                $query->whereHas('priorityQueue', function($q) {
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
                    $query->whereHas('assignee', function($q) use ($user) {
                        $q->where('department_id', $user->department_id);
                    });
                }
                $query->whereNotIn('status', ['closed', 'resolved']);
                break;
                
            case 'watched':
                $query->whereHas('watchers', function($q) use ($user) {
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
        
        // Apply standard filters
        $query->when(!empty($this->selectedStatuses), function ($query) {
                // Filter by selected statuses
                $query->whereIn('status', $this->selectedStatuses);
            })
            ->when(empty($this->selectedStatuses) && !$this->filter, function ($query) {
                // This shouldn't happen since we initialize with active statuses, but just in case
                $query->whereIn('status', Ticket::ACTIVE_STATUSES);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('ticket_number', 'like', '%' . $this->search . '%')
                      ->orWhere('subject', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when(!empty($this->selectedPriorities), function ($query) {
                $query->whereIn('priority', $this->selectedPriorities);
            })
            ->when(!empty($this->selectedAssignees), function ($query) {
                $query->whereIn('assigned_to', $this->selectedAssignees);
            })
            ->when(!empty($this->selectedClients), function ($query) {
                $query->whereIn('client_id', $this->selectedClients);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->with(['client', 'assignedTo', 'requester']);
        
        // Special sorting for certain filters
        if ($this->filter === 'sla_violation' || $this->filter === 'sla_warning') {
            $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
        } elseif ($this->filter === 'due_today') {
            $query->orderBy('due_date', 'asc')->orderBy('priority', 'desc');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
        
        return $query->paginate($this->perPage);
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
        
        // All available statuses
        $statuses = ['open', 'in_progress', 'waiting', 'on_hold', 'resolved', 'closed', 'cancelled'];
        
        // Use model constants for priorities
        $priorities = [
            Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_MEDIUM,
            Ticket::PRIORITY_HIGH,
            Ticket::PRIORITY_CRITICAL
        ];

        return view('livewire.tickets.ticket-index', [
            'tickets' => $tickets,
            'clients' => $clients,
            'users' => $users,
            'statuses' => $statuses,
            'priorities' => $priorities
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