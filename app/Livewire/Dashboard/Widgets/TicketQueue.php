<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TicketQueue extends Component
{
    public Collection $tickets;
    public bool $loading = true;
    public string $priority = 'all'; // all, critical, high, medium, low
    public string $status = 'open'; // open, in_progress, waiting, all
    public string $sortBy = 'priority'; // priority, created_at, updated_at
    public int $limit = 15;
    public array $expandedPriorities = []; // Track which priorities are expanded
    
    public function mount()
    {
        $this->tickets = collect();
        $this->loadQueue();
    }
    
    #[On('refresh-ticket-queue')]
    public function loadQueue()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        $query = Ticket::where('company_id', $companyId);
        
        // Filter by priority
        if ($this->priority !== 'all') {
            $query->where('priority', $this->priority);
        }
        
        // Filter by status
        switch ($this->status) {
            case 'open':
                $query->where('status', 'open');
                break;
            case 'in_progress':
                $query->where('status', 'in-progress');
                break;
            case 'waiting':
                $query->where('status', 'waiting');
                break;
            case 'all':
                $query->whereNotIn('status', ['resolved', 'closed']);
                break;
        }
        
        // Sort
        switch ($this->sortBy) {
            case 'priority':
                $query->orderByRaw("CASE 
                    WHEN priority = 'Critical' THEN 1 
                    WHEN priority = 'High' THEN 2 
                    WHEN priority = 'Medium' THEN 3 
                    WHEN priority = 'Low' THEN 4 
                    ELSE 5 END")
                    ->orderBy('created_at', 'asc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'asc');
                break;
            case 'updated_at':
                $query->orderBy('updated_at', 'desc');
                break;
        }
        
        $this->tickets = $query->with(['client', 'assignee'])
            ->limit($this->limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'client' => $ticket->client?->name ?? 'Unknown',
                    'priority' => ucfirst($ticket->priority),
                    'status' => $ticket->status,
                    'assigned_to' => $ticket->assignee?->name ?? 'Unassigned',
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'time_in_queue' => $this->calculateTimeInQueue($ticket),
                    'sla_status' => $this->calculateSlaStatus($ticket),
                ];
            });
            
        $this->loading = false;
    }
    
    protected function calculateTimeInQueue($ticket)
    {
        $start = $ticket->created_at;
        $end = in_array($ticket->status, ['Resolved', 'Closed']) 
            ? $ticket->updated_at 
            : Carbon::now();
            
        $hours = $start->diffInHours($end);
        
        if ($hours < 1) {
            return $start->diffInMinutes($end) . 'm';
        } elseif ($hours < 24) {
            return $hours . 'h';
        } else {
            return $start->diffInDays($end) . 'd';
        }
    }
    
    protected function calculateSlaStatus($ticket)
    {
        // Simple SLA calculation based on priority
        $slaHours = match($ticket->priority) {
            'critical' => 4,
            'high' => 8,
            'medium' => 24,
            'low' => 48,
            default => 72
        };
        
        $hoursOpen = $ticket->created_at->diffInHours(Carbon::now());
        
        if ($hoursOpen > $slaHours) {
            return 'overdue';
        } elseif ($hoursOpen > ($slaHours * 0.8)) {
            return 'warning';
        } else {
            return 'ok';
        }
    }
    
    public function setPriority($priority)
    {
        if (in_array($priority, ['all', 'critical', 'high', 'medium', 'low'])) {
            $this->priority = $priority;
            $this->loadQueue();
        }
    }
    
    public function setStatus($status)
    {
        if (in_array($status, ['open', 'in_progress', 'waiting', 'all'])) {
            $this->status = $status;
            $this->loadQueue();
        }
    }
    
    public function setSortBy($sortBy)
    {
        if (in_array($sortBy, ['priority', 'created_at', 'updated_at'])) {
            $this->sortBy = $sortBy;
            $this->loadQueue();
        }
    }
    
    public function assignTicket($ticketId)
    {
        $ticket = Ticket::find($ticketId);
        if ($ticket && $ticket->company_id === Auth::user()->company_id) {
            $ticket->update([
                'assigned_to' => Auth::id(),
                'status' => 'In Progress'
            ]);
            
            $this->loadQueue();
            $this->dispatch('ticket-assigned', ['ticket_id' => $ticketId]);
        }
    }
    
    public function viewTicket($ticketId)
    {
        return redirect()->route('tickets.show', $ticketId);
    }
    
    public function loadMore()
    {
        $this->limit += 15;
        $this->loadQueue();
    }
    
    public function loadMoreForPriority($priority)
    {
        // Toggle expanded state for this priority
        if (in_array($priority, $this->expandedPriorities)) {
            // Collapse - remove from expanded array
            $this->expandedPriorities = array_diff($this->expandedPriorities, [$priority]);
        } else {
            // Expand - add to expanded array
            $this->expandedPriorities[] = $priority;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.ticket-queue');
    }
}