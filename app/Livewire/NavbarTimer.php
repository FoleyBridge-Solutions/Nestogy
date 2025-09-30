<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Services\TimeTrackingService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class NavbarTimer extends Component
{
    // Timer data
    public $activeTimers = [];

    public $totalElapsedTime = '00:00:00';

    public $timerCount = 0;

    public $isMultipleTimers = false;

    // UI state
    public $showDropdown = false;

    public $showMultiTimerModal = false;

    public $pendingTicketId = null;

    public $pendingTicketNumber = null;

    // Display formats
    public $displayMode = 'compact'; // compact, expanded

    public $overtime = false;

    protected $listeners = [
        'timerStarted' => 'refreshTimers',
        'timerStopped' => 'refreshTimers',
        'timerPaused' => 'refreshTimers',
        'timerResumed' => 'refreshTimers',
        'refreshNavbarTimer' => 'refreshTimers',
        'timer:completion-confirmed' => 'handleTimerCompleted',
    ];

    public function mount()
    {
        $this->loadActiveTimers();
    }

    public function loadActiveTimers()
    {
        if (! Auth::check()) {
            $this->activeTimers = [];

            return;
        }

        // Cache key unique to user
        $cacheKey = 'navbar_timers_'.Auth::id();

        // Use cache with 1-second TTL for performance
        $this->activeTimers = Cache::remember($cacheKey, 1, function () {
            $timers = TicketTimeEntry::with('ticket')
                ->where('user_id', Auth::id())
                ->where('company_id', Auth::user()->company_id)
                ->where('entry_type', TicketTimeEntry::TYPE_TIMER)
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->orderBy('started_at', 'desc')
                ->get()
                ->map(function ($timer) {
                    return $this->formatTimerData($timer);
                })
                ->toArray();

            return $timers;
        });

        $this->timerCount = count($this->activeTimers);
        $this->isMultipleTimers = $this->timerCount > 1;

        if ($this->timerCount > 0) {
            $this->calculateTotalElapsedTime();
            $this->checkOvertime();
        }
    }

    protected function formatTimerData(TicketTimeEntry $timer)
    {
        $elapsed = $this->calculateElapsedTime($timer);
        $isPaused = $timer->metadata['paused'] ?? false;

        return [
            'id' => $timer->id,
            'ticket_id' => $timer->ticket_id,
            'ticket_number' => $timer->ticket->number ?? $timer->ticket_id,
            'ticket_subject' => $timer->ticket->subject ?? 'Untitled',
            'started_at' => $timer->started_at,
            'elapsed_seconds' => $elapsed['seconds'],
            'elapsed_display' => $elapsed['display'],
            'is_paused' => $isPaused,
            'work_type' => $timer->work_type,
            'description' => $timer->description,
            'billable' => $timer->billable,
            'hourly_rate' => $timer->hourly_rate,
            'live_amount' => $timer->billable ? round(($elapsed['seconds'] / 3600) * $timer->hourly_rate, 2) : 0,
        ];
    }

    protected function calculateElapsedTime(TicketTimeEntry $timer)
    {
        $start = Carbon::parse($timer->started_at);
        $now = now();

        // Account for paused duration
        $pausedMinutes = $timer->paused_duration ?? 0;
        $totalSeconds = $start->diffInSeconds($now) - ($pausedMinutes * 60);
        $totalSeconds = max(0, $totalSeconds); // Ensure non-negative

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return [
            'seconds' => $totalSeconds,
            'display' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
        ];
    }

    protected function calculateTotalElapsedTime()
    {
        $totalSeconds = collect($this->activeTimers)
            ->where('is_paused', false)
            ->sum('elapsed_seconds');

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $this->totalElapsedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    protected function checkOvertime()
    {
        // Check if any timer has been running for more than 8 hours
        $this->overtime = collect($this->activeTimers)
            ->filter(function ($timer) {
                return $timer['elapsed_seconds'] > (8 * 3600);
            })
            ->isNotEmpty();
    }

    public function pauseTimer($timerId)
    {
        try {
            $timer = TicketTimeEntry::find($timerId);
            if (! $timer) {
                throw new \Exception('Timer not found');
            }

            $service = app(TimeTrackingService::class);
            $service->pauseTracking($timer, 'Paused from navbar');

            $this->refreshTimers();

            Flux::toast(
                text: 'Timer paused'
            );
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to pause timer',
                variant: 'danger'
            );
        }
    }

    public function resumeTimer($timerId)
    {
        try {
            $timer = TicketTimeEntry::find($timerId);
            if (! $timer) {
                throw new \Exception('Timer not found');
            }

            $service = app(TimeTrackingService::class);
            $service->resumeTracking($timer);

            $this->refreshTimers();

            Flux::toast(
                text: 'Timer resumed',
                variant: 'success'
            );
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to resume timer',
                variant: 'danger'
            );
        }
    }

    public function stopTimer($timerId)
    {
        // Dispatch event to show completion modal instead of stopping directly
        $this->dispatch('timer:request-stop', timerId: $timerId, source: 'navbar');
    }

    public function stopAllTimers()
    {
        // Dispatch event to show batch completion modal
        $this->dispatch('timer:request-stop-all');
    }

    public function navigateToTicket($ticketId)
    {
        return redirect()->route('tickets.show', $ticketId);
    }

    #[On('attempt-start-timer')]
    public function handleTimerStartAttempt($ticketId)
    {
        // Check if user already has active timers
        if ($this->timerCount > 0) {
            // Get ticket info for confirmation modal
            $ticket = Ticket::find($ticketId);
            $this->pendingTicketId = $ticketId;
            $this->pendingTicketNumber = $ticket->number ?? $ticketId;
            $this->showMultiTimerModal = true;
        } else {
            // No active timers, proceed with starting
            $this->dispatch('confirmed-start-timer', ticketId: $ticketId);
        }
    }

    public function confirmMultipleTimers($action)
    {
        switch ($action) {
            case 'switch':
                // Stop all current timers and start new one
                $this->stopAllTimers();
                $this->dispatch('confirmed-start-timer', ticketId: $this->pendingTicketId);
                break;

            case 'both':
                // Allow both timers to run
                $this->dispatch('confirmed-start-timer', ticketId: $this->pendingTicketId);
                Flux::toast(
                    text: 'Multiple timers are now running'
                );
                break;

            case 'cancel':
                // Do nothing, just close modal
                break;
        }

        $this->showMultiTimerModal = false;
        $this->pendingTicketId = null;
        $this->pendingTicketNumber = null;
    }

    public function handleTimerCompleted($data)
    {
        // Refresh timers after successful completion
        $this->refreshTimers();

        // Show success message if provided
        if (isset($data['hours']) && isset($data['amount'])) {
            $message = "Timer stopped - {$data['hours']}h recorded";
            if ($data['amount'] > 0) {
                $message .= " (\${$data['amount']})";
            }

            Flux::toast(
                heading: 'Timer stopped',
                text: $message,
                variant: 'success'
            );
        }
    }

    public function refreshTimers()
    {
        // Clear cache to force reload
        Cache::forget('navbar_timers_'.Auth::id());
        $this->loadActiveTimers();
    }

    public function render()
    {
        // Refresh timers every second for live updates
        $this->refreshTimers();

        return view('livewire.navbar-timer');
    }
}
