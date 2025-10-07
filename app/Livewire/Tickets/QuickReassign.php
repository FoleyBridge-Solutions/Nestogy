<?php

namespace App\Livewire\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Livewire\Component;

class QuickReassign extends Component
{
    public $show = false;
    public $tickets = [];
    public $selectedTickets = [];
    public $newAssigneeId = null;
    public $reassignReason = '';
    public $technicians = [];
    public $techWorkload = [];

    protected $listeners = ['open-reassign-modal' => 'openModal'];

    public function mount($techId = null)
    {
        if ($techId) {
            $this->loadTicketsForTech($techId);
        }
        $this->loadTechnicians();
    }

    public function openModal($techId = null)
    {
        if ($techId) {
            $this->loadTicketsForTech($techId);
        }
        $this->show = true;
    }

    public function loadTicketsForTech($techId)
    {
        $this->tickets = Ticket::where('assigned_to', $techId)
            ->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer'])
            ->with('client:id,name')
            ->get(['id', 'number', 'subject', 'priority', 'status', 'client_id', 'created_at'])
            ->toArray();
    }

    public function loadTechnicians()
    {
        $companyId = auth()->user()->company_id;

        $this->technicians = User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['technician', 'admin']);
            })
            ->withCount([
                'assignedTickets as active_count' => function ($q) {
                    $q->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer']);
                }
            ])
            ->get(['id', 'name', 'email'])
            ->map(function ($tech) {
                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'email' => $tech->email,
                    'active_count' => $tech->active_count,
                ];
            })
            ->toArray();
    }

    public function toggleTicket($ticketId)
    {
        if (in_array($ticketId, $this->selectedTickets)) {
            $this->selectedTickets = array_diff($this->selectedTickets, [$ticketId]);
        } else {
            $this->selectedTickets[] = $ticketId;
        }
    }

    public function selectAll()
    {
        $this->selectedTickets = array_column($this->tickets, 'id');
    }

    public function clearSelection()
    {
        $this->selectedTickets = [];
    }

    public function reassign()
    {
        $this->validate([
            'newAssigneeId' => 'required|exists:users,id',
            'selectedTickets' => 'required|array|min:1',
            'reassignReason' => 'nullable|string|max:500',
        ], [
            'newAssigneeId.required' => 'Please select a technician to assign to',
            'selectedTickets.required' => 'Please select at least one ticket',
        ]);

        $newAssignee = User::find($this->newAssigneeId);
        $count = 0;

        foreach ($this->selectedTickets as $ticketId) {
            $ticket = Ticket::find($ticketId);
            
            if (!$ticket) {
                continue;
            }

            $oldAssignee = $ticket->assignee;
            
            // Update assignment
            $ticket->update(['assigned_to' => $this->newAssigneeId]);

            // Add internal comment
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'company_id' => $ticket->company_id,
                'content' => "Ticket reassigned from " . ($oldAssignee?->name ?? 'Unassigned') .
                           " to " . $newAssignee->name . 
                           ($this->reassignReason ? "\n\nReason: " . $this->reassignReason : ''),
                'visibility' => 'internal',
                'source' => 'system',
                'author_type' => 'user',
                'author_id' => auth()->id(),
            ]);

            // Notify new assignee
            $newAssignee->notify(new TicketNotification(
                ticket: $ticket,
                type: 'ticket_assigned',
                title: 'Ticket Assigned to You',
                message: "Ticket #{$ticket->number} has been assigned to you",
                actionUrl: route('tickets.show', $ticket->id),
                actionText: 'View Ticket'
            ));

            $count++;
        }

        session()->flash('success', "{$count} ticket(s) reassigned to {$newAssignee->name}");
        
        $this->reset(['show', 'selectedTickets', 'newAssigneeId', 'reassignReason']);
        $this->dispatch('tickets-reassigned');
    }

    public function close()
    {
        $this->show = false;
        $this->reset(['selectedTickets', 'newAssigneeId', 'reassignReason']);
    }

    public function render()
    {
        return view('livewire.tickets.quick-reassign');
    }
}
