<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class MyTickets extends Component
{
    public Collection $tickets;
    public bool $loading = true;
    public string $filter = 'assigned'; // assigned, created, watching
    public string $status = 'active'; // active, all, closed
    public int $limit = 10;
    
    public function mount()
    {
        $this->tickets = collect();
        $this->loadTickets();
    }
    
    #[On('refresh-my-tickets')]
    public function loadTickets()
    {
        $this->loading = true;
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $query = Ticket::where('company_id', $companyId);
        
        // Filter by relationship to user
        switch ($this->filter) {
            case 'assigned':
                $query->where('assigned_to', $user->id);
                break;
            case 'created':
                $query->where('created_by', $user->id);
                break;
            case 'watching':
                // This would require a pivot table for watchers
                $query->where('assigned_to', $user->id); // Fallback to assigned
                break;
        }
        
        // Filter by status
        switch ($this->status) {
            case 'active':
                $query->whereIn('status', ['open', 'in-progress', 'waiting']);
                break;
            case 'closed':
                $query->whereIn('status', ['resolved', 'closed']);
                break;
            // 'all' doesn't add any filter
        }
        
        $this->tickets = $query->with(['client', 'creator'])
            ->orderByRaw("CASE 
                WHEN priority = 'critical' THEN 1 
                WHEN priority = 'high' THEN 2 
                WHEN priority = 'medium' THEN 3 
                WHEN priority = 'low' THEN 4 
                ELSE 5 END")
            ->orderBy('created_at', 'desc')
            ->limit($this->limit)
            ->get();
            
        $this->loading = false;
    }
    
    public function setFilter($filter)
    {
        if (in_array($filter, ['assigned', 'created', 'watching'])) {
            $this->filter = $filter;
            $this->loadTickets();
        }
    }
    
    public function setStatus($status)
    {
        if (in_array($status, ['active', 'all', 'closed'])) {
            $this->status = $status;
            $this->loadTickets();
        }
    }
    
    public function takeTicket($ticketId)
    {
        $ticket = Ticket::find($ticketId);
        if ($ticket && $ticket->company_id === Auth::user()->company_id) {
            $ticket->update([
                'assigned_to' => Auth::id(),
                'status' => 'In Progress'
            ]);
            
            $this->loadTickets();
            $this->dispatch('ticket-taken', ['ticket_id' => $ticketId]);
        }
    }
    
    public function updateStatus($ticketId, $newStatus)
    {
        $ticket = Ticket::find($ticketId);
        if ($ticket && $ticket->assigned_to === Auth::id()) {
            $ticket->update(['status' => $newStatus]);
            $this->loadTickets();
            $this->dispatch('ticket-status-updated', [
                'ticket_id' => $ticketId, 
                'status' => $newStatus
            ]);
        }
    }
    
    public function loadMore()
    {
        $this->limit += 10;
        $this->loadTickets();
    }
    
    protected function getSafeRoute($name, $parameters = [])
    {
        try {
            return route($name, $parameters);
        } catch (\Exception $e) {
            return '#';
        }
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.my-tickets');
    }
}