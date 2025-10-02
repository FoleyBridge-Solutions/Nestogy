<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use Carbon\Carbon;
use Livewire\Component;
use App\Traits\HasFluxToasts;

class MobileTimeTracker extends Component
{
    use HasFluxToasts;
    public $ticketId;

    public $ticket;

    public $isRunning = false;

    public $startTime;

    public $elapsedSeconds = 0;

    public $description = '';

    public $billable = true;

    public $recentEntries = [];

    protected $listeners = ['sync-time-entry' => 'syncPendingEntry'];

    public function mount($ticketId = null)
    {
        if ($ticketId) {
            $this->ticketId = $ticketId;
            $this->ticket = Ticket::with('client')->find($ticketId);
        }

        $this->loadRecentEntries();
        $this->checkPendingTimer();
    }

    protected function checkPendingTimer()
    {
        $pendingEntry = TicketTimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->first();

        if ($pendingEntry) {
            $this->isRunning = true;
            $this->startTime = $pendingEntry->started_at->toIso8601String();
            $this->elapsedSeconds = $pendingEntry->started_at->diffInSeconds(now());
            $this->ticketId = $pendingEntry->ticket_id;
            $this->ticket = $pendingEntry->ticket;
            $this->description = $pendingEntry->description ?? '';
            $this->billable = (bool) $pendingEntry->billable;
        }
    }

    public function startTimer()
    {
        if (! $this->ticketId) {
            $this->dispatch('error', message: 'Please select a ticket first');
            return;
        }

        $timeEntry = TicketTimeEntry::create([
            'company_id' => auth()->user()->company_id,
            'ticket_id' => $this->ticketId,
            'user_id' => auth()->id(),
            'started_at' => now(),
            'description' => $this->description,
            'billable' => $this->billable,
        ]);

        $this->isRunning = true;
        $this->startTime = $timeEntry->started_at->toIso8601String();
        $this->elapsedSeconds = 0;

        $this->dispatch('timer-started', timeEntryId: $timeEntry->id);
    }

    public function stopTimer()
    {
        $timeEntry = TicketTimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->first();

        if (! $timeEntry) {
            $this->dispatch('error', message: 'No active timer found');
            return;
        }

        $timeEntry->update([
            'ended_at' => now(),
            'hours_worked' => $timeEntry->started_at->diffInHours(now(), true),
            'description' => $this->description,
            'billable' => $this->billable,
        ]);

        $this->isRunning = false;
        $this->startTime = null;
        $this->elapsedSeconds = 0;
        $this->description = '';

        $this->loadRecentEntries();

        $this->dispatch('timer-stopped');
        $this->dispatch('success', message: sprintf('Time entry saved: %.2f hours', $timeEntry->hours_worked));
    }

    public function syncPendingEntry()
    {
        if (! $this->isRunning) {
            return;
        }

        $timeEntry = TicketTimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->first();

        if ($timeEntry) {
            $timeEntry->update([
                'description' => $this->description,
                'billable' => $this->billable,
            ]);
        }
    }

    protected function loadRecentEntries()
    {
        $this->recentEntries = TicketTimeEntry::where('user_id', auth()->id())
            ->with('ticket')
            ->whereNotNull('ended_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function deleteEntry($entryId)
    {
        $entry = TicketTimeEntry::find($entryId);

        if ($entry && $entry->user_id === auth()->id()) {
            $entry->delete();
            $this->loadRecentEntries();
            $this->dispatch('success', message: 'Time entry deleted');
        }
    }

    public function render()
    {
        return view('livewire.mobile-time-tracker');
    }
}
