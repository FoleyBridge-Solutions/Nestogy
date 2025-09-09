<?php

namespace App\Livewire;

use Livewire\Component;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TicketTimer extends Component
{
    public Ticket $ticket;
    public $activeTimer = null;
    public $elapsedTime = '00:00:00';
    public $timerStarted = false;
    public $timerDescription = '';
    public $todayMinutes = 0;
    public $totalMinutes = 0;
    
    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->checkActiveTimer();
        $this->loadTimeStats();
    }
    
    public function checkActiveTimer()
    {
        // Check if there's an active timer for this ticket and user
        $this->activeTimer = TicketTimeEntry::where('ticket_id', $this->ticket->id)
            ->where('user_id', Auth::id())
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('ended_at')
            ->first();
        
        if ($this->activeTimer) {
            $this->timerStarted = true;
            $this->timerDescription = $this->activeTimer->description ?? '';
            $this->updateElapsedTime();
        }
    }
    
    public function startTimer()
    {
        if ($this->timerStarted) {
            return;
        }
        
        // Prompt for description (optional)
        $this->timerDescription = $this->timerDescription ?: 'Working on ticket #' . $this->ticket->number;
        
        // Create a new time entry
        $this->activeTimer = TicketTimeEntry::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
            'description' => $this->timerDescription,
            'work_performed' => $this->timerDescription,
            'started_at' => now(),
            'work_date' => today(),
            'billable' => $this->ticket->billable ?? true,
            'entry_type' => 'timer',
            'status' => 'draft',
        ]);
        
        $this->timerStarted = true;
        $this->dispatch('timer-started');
    }
    
    public function stopTimer()
    {
        if (!$this->timerStarted || !$this->activeTimer) {
            return;
        }
        
        $endTime = now();
        $startTime = Carbon::parse($this->activeTimer->started_at);
        $minutes = $startTime->diffInMinutes($endTime);
        $hours = round($minutes / 60, 2);
        
        // Update the time entry
        $this->activeTimer->update([
            'ended_at' => $endTime,
            'minutes_worked' => $minutes,
            'hours_worked' => $hours,
            'hours_billed' => $hours,
            'status' => 'submitted',
            'submitted_at' => $endTime,
            'submitted_by' => Auth::id(),
        ]);
        
        // Update the time spent input field if it exists
        $this->dispatch('timer-stopped', minutes: $minutes);
        
        // Reset timer state
        $this->timerStarted = false;
        $this->activeTimer = null;
        $this->timerDescription = '';
        $this->elapsedTime = '00:00:00';
        
        // Reload time stats
        $this->loadTimeStats();
        
        // Show success message via Flux toast
        $this->dispatch('flux:toast', 
            text: "Timer stopped. {$minutes} minutes recorded.",
            variant: 'success'
        );
    }
    
    public function updateElapsedTime()
    {
        if (!$this->activeTimer) {
            return;
        }
        
        $startTime = Carbon::parse($this->activeTimer->started_at);
        $elapsed = $startTime->diffInSeconds(now());
        
        $hours = floor($elapsed / 3600);
        $minutes = floor(($elapsed % 3600) / 60);
        $seconds = $elapsed % 60;
        
        $this->elapsedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    
    public function loadTimeStats()
    {
        // Today's time for this ticket
        $this->todayMinutes = TicketTimeEntry::where('ticket_id', $this->ticket->id)
            ->where('company_id', Auth::user()->company_id)
            ->whereDate('work_date', today())
            ->sum('minutes_worked');
        
        // Total time for this ticket
        $this->totalMinutes = TicketTimeEntry::where('ticket_id', $this->ticket->id)
            ->where('company_id', Auth::user()->company_id)
            ->sum('minutes_worked');
    }
    
    public function formatMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }
    
    public function render()
    {
        // Update elapsed time every second when timer is running
        if ($this->timerStarted) {
            $this->updateElapsedTime();
        }
        
        return view('livewire.ticket-timer');
    }
}