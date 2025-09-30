<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Services\TimeTrackingService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketTimeTracker extends Component
{
    public $ticketId;

    // Timer state (store ID only for Livewire hydration)
    public $activeTimerId = null;

    public $activeTimerStartedAt = null;

    public $isTimerRunning = false;

    public $isPaused = false;

    public $elapsedTime = '00:00:00';

    public $liveRevenue = 0;

    // Metrics
    public $todayMetrics = [];

    public $weekMetrics = [];

    public $monthMetrics = [];

    public $recentEntries = [];

    // Rate info
    public $currentRate = [];

    public $rateMultiplier = 1;

    public $rateBadge = 'Standard';

    public $rateColor = 'zinc';

    // Manual entry form
    public $showManualEntry = false;

    public $manualHours = '';

    public $manualWorkType = '';

    public $manualDescription = '';

    public $manualBillable = true;

    public $manualDate = '';

    // Quick timer form
    public $showQuickTimer = false;

    public $quickWorkType = '';

    public $quickDescription = '';

    public $quickBillable = true;

    public $suggestedWorkTypes = [];

    public $templates = [];

    protected $listeners = [
        'refreshTimer' => '$refresh',
        'confirmed-start-timer' => 'handleConfirmedStart',
        'timer:completion-confirmed' => 'handleTimerCompleted',
    ];

    public function mount(Ticket $ticket)
    {
        $this->ticketId = $ticket->id;
        $this->manualDate = now()->format('Y-m-d');

        // Initialize all properties properly
        $this->activeTimerId = null;
        $this->activeTimerStartedAt = null;
        $this->isTimerRunning = false;
        $this->isPaused = false;
        $this->elapsedTime = '00:00:00';
        $this->liveRevenue = 0;
        $this->rateMultiplier = 1;
        $this->rateBadge = 'Standard';
        $this->rateColor = 'zinc';

        // Initialize arrays
        $this->todayMetrics = [
            'total_hours' => 0,
            'billable_hours' => 0,
            'revenue' => 0,
            'entries_count' => 0,
        ];

        $this->weekMetrics = [
            'total_hours' => 0,
            'billable_hours' => 0,
            'revenue' => 0,
            'entries_count' => 0,
            'utilization' => 0,
        ];

        $this->monthMetrics = [
            'total_hours' => 0,
            'billable_hours' => 0,
            'revenue' => 0,
            'entries_count' => 0,
            'days_worked' => 0,
            'avg_daily_hours' => 0,
        ];

        $this->recentEntries = [];
        $this->suggestedWorkTypes = [];
        $this->templates = [];
        $this->currentRate = [];

        // Now load the data
        $this->loadTimerState();
        $this->loadMetrics();
        $this->loadRecentEntries();
        $this->loadSuggestions();
    }

    public function loadTimerState()
    {
        // Check for active timer
        $activeTimer = TicketTimeEntry::where('ticket_id', $this->ticketId)
            ->where('user_id', Auth::id())
            ->where('entry_type', 'timer')
            ->whereNull('ended_at')
            ->first();

        if ($activeTimer) {
            $this->activeTimerId = $activeTimer->id;
            $this->activeTimerStartedAt = $activeTimer->started_at;
            $this->isTimerRunning = true;
            $this->isPaused = $activeTimer->metadata['paused'] ?? false;
            $this->updateElapsedTime();
            $this->updateRateInfo();
        }
    }

    public function getTicketProperty()
    {
        return Ticket::find($this->ticketId);
    }

    public function getActiveTimerProperty()
    {
        if (! $this->activeTimerId) {
            return null;
        }

        return TicketTimeEntry::find($this->activeTimerId);
    }

    public function loadMetrics()
    {
        $service = app(TimeTrackingService::class);
        $dashboard = $service->getBillingDashboard(Auth::user());

        $this->todayMetrics = $dashboard['today'] ?? [];
        $this->weekMetrics = $dashboard['week'] ?? [];
        $this->monthMetrics = $dashboard['month'] ?? [];
    }

    public function loadRateInfo()
    {
        $service = app(TimeTrackingService::class);
        $rateInfo = $service->getSmartRateInfo();

        $this->currentRate = $rateInfo;
        $this->rateMultiplier = $rateInfo['multiplier'] ?? 1;
        $this->rateBadge = $rateInfo['visual_indicator']['badge'] ?? 'Standard';

        // Map rate colors
        $colorMap = [
            'orange' => 'amber',
            'red' => 'red',
            'green' => 'green',
            'blue' => 'blue',
            'purple' => 'purple',
        ];

        $this->rateColor = $colorMap[$rateInfo['visual_indicator']['color'] ?? 'zinc'] ?? 'zinc';
    }

    public function loadRecentEntries()
    {
        $this->recentEntries = TicketTimeEntry::where('ticket_id', $this->ticketId)
            ->where('user_id', Auth::id())
            ->whereDate('work_date', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'hours_worked' => $entry->hours_worked,
                    'description' => $entry->description,
                    'amount' => $entry->amount,
                    'billable' => $entry->billable,
                    'status' => $entry->status,
                    'work_date' => $entry->work_date,
                    'started_at' => $entry->started_at,
                    'work_type' => $entry->work_type,
                ];
            })
            ->toArray();
    }

    public function loadSuggestions()
    {
        $service = app(TimeTrackingService::class);
        $info = $service->startSmartTracking($this->ticket, Auth::user());

        $this->suggestedWorkTypes = $info['work_type_suggestions'] ?? [];
        // Templates would need to be loaded separately if needed
        $this->templates = [];
    }

    public function startTimer()
    {
        // Dispatch to navbar timer to check for existing timers
        $this->dispatch('attempt-start-timer', ticketId: $this->ticketId)->to('navbar-timer');
    }

    public function handleConfirmedStart($ticketId)
    {
        // Only proceed if this is for our ticket
        if ($ticketId == $this->ticketId) {
            $this->showQuickTimer = true;
        }
    }

    public function confirmStartTimer()
    {
        try {
            $service = app(TimeTrackingService::class);

            $newTimer = $service->startTracking(
                $this->ticket,
                Auth::user(),
                [
                    'work_type' => $this->quickWorkType ?: 'general_support',
                    'description' => $this->quickDescription,
                    'billable' => $this->quickBillable,
                ]
            );

            $this->activeTimerId = $newTimer->id;
            $this->activeTimerStartedAt = $newTimer->started_at;
            $this->isTimerRunning = true;
            $this->isPaused = false;
            $this->showQuickTimer = false;
            $this->quickWorkType = '';
            $this->quickDescription = '';
            $this->quickBillable = true;

            $this->loadTimerState();
            $this->loadMetrics();

            // Notify navbar timer to refresh
            $this->dispatch('timerStarted')->to('navbar-timer');

            Flux::toast(
                text: 'Timer started successfully',
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to start timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function pauseTimer()
    {
        if (! $this->activeTimerId) {
            return;
        }

        try {
            $service = app(TimeTrackingService::class);
            $service->pauseTracking($this->activeTimerId, Auth::user(), 'Manual pause');

            $this->isPaused = true;
            $this->loadTimerState();

            // Notify navbar timer
            $this->dispatch('timerPaused')->to('navbar-timer');

            Flux::toast(
                text: 'Timer paused'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to pause timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function resumeTimer()
    {
        if (! $this->activeTimerId) {
            return;
        }

        try {
            $service = app(TimeTrackingService::class);
            $service->resumeTracking($this->activeTimerId, Auth::user());

            $this->isPaused = false;
            $this->loadTimerState();

            // Notify navbar timer
            $this->dispatch('timerResumed')->to('navbar-timer');

            Flux::toast(
                text: 'Timer resumed',
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to resume timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function stopTimer()
    {
        if (! $this->activeTimerId) {
            return;
        }

        // Dispatch event to show completion modal instead of stopping directly
        $this->dispatch('timer:request-stop', timerId: $this->activeTimerId, source: 'ticket-page');
    }

    public function addManualEntry()
    {
        $this->validate([
            'manualHours' => 'required|numeric|min:0.01|max:24',
            'manualWorkType' => 'required|string',
            'manualDescription' => 'nullable|string|max:500',
            'manualBillable' => 'boolean',
            'manualDate' => 'required|date',
        ]);

        try {
            // Parse hours input (supports: 1.5, 1h30m, 90m)
            $hours = $this->parseTimeInput($this->manualHours);

            $entry = new TicketTimeEntry;
            $entry->ticket_id = $this->ticketId;
            $entry->user_id = Auth::id();
            $entry->company_id = Auth::user()->company_id;
            $entry->hours_worked = $hours;
            $entry->work_type = $this->manualWorkType;
            $entry->description = $this->manualDescription;
            $entry->billable = $this->manualBillable;
            $entry->work_date = $this->manualDate;
            $entry->entry_type = TicketTimeEntry::TYPE_MANUAL;
            $entry->started_at = now();
            $entry->ended_at = now();

            // Calculate billing
            $hourlyRate = Auth::user()->hourly_rate ?? 125;
            $entry->hourly_rate = $hourlyRate;
            $entry->hours_billed = $hours;
            $entry->amount = $this->manualBillable ? ($hours * $hourlyRate) : 0;

            $entry->save();

            $this->resetManualForm();
            $this->loadMetrics();
            $this->loadRecentEntries();

            Flux::toast(
                text: 'Time entry added successfully',
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to add entry: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function useTemplate($templateId)
    {
        try {
            $service = app(TimeTrackingService::class);
            $result = $service->createFromTemplate($templateId, $this->ticket, Auth::user());

            $this->loadMetrics();
            $this->loadRecentEntries();

            Flux::toast(
                text: 'Time entry created from template',
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to use template: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function deleteEntry($entryId)
    {
        try {
            $entry = TicketTimeEntry::where('id', $entryId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Don't allow deletion of approved entries
            if ($entry->status === 'approved') {
                Flux::toast(
                    text: 'Cannot delete approved entries',
                    variant: 'danger'
                );

                return;
            }

            $entry->delete();

            $this->loadMetrics();
            $this->loadRecentEntries();

            Flux::toast(
                text: 'Entry deleted',
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to delete entry: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    #[On('refresh-timer')]
    public function refreshTimer()
    {
        if ($this->isTimerRunning && ! $this->isPaused) {
            $this->updateElapsedTime();
            $this->updateLiveRevenue();
            $this->updateRateInfo();
        }
    }

    private function updateElapsedTime()
    {
        if (! $this->activeTimerId) {
            return;
        }

        $start = Carbon::parse($this->activeTimerStartedAt);
        $now = now();

        // Ensure we're working with the same timezone by converting both to UTC
        $start = $start->utc();
        $now = $now->utc();

        // Get active timer for paused duration
        $activeTimer = $this->activeTimer;
        $pausedMinutes = $activeTimer ? ($activeTimer->paused_duration ?? 0) : 0;

        // Calculate total seconds, ensuring positive value
        $totalSeconds = $start->diffInSeconds($now) - ($pausedMinutes * 60);
        $totalSeconds = max(0, $totalSeconds); // Ensure non-negative

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $this->elapsedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function updateLiveRevenue()
    {
        $activeTimer = $this->activeTimer;
        if (! $activeTimer || ! $activeTimer->billable) {
            $this->liveRevenue = 0;

            return;
        }

        $hours = $this->getElapsedHours();
        $this->liveRevenue = round($hours * $activeTimer->hourly_rate, 2);
    }

    private function getElapsedHours()
    {
        if (! $this->activeTimerId) {
            return 0;
        }

        $start = Carbon::parse($this->activeTimerStartedAt);
        $now = now();
        $pausedMinutes = $this->activeTimer->paused_duration ?? 0;
        $totalMinutes = $start->diffInMinutes($now) - $pausedMinutes;

        return round($totalMinutes / 60, 2);
    }

    private function parseTimeInput($input)
    {
        // Already a decimal (1.5)
        if (is_numeric($input)) {
            return (float) $input;
        }

        // Parse formats like "1h30m", "90m", "1h"
        if (preg_match('/^(\d+)h(?:(\d+)m)?$/', $input, $matches)) {
            $hours = (int) $matches[1];
            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;

            return round($hours + ($minutes / 60), 2);
        }

        if (preg_match('/^(\d+)m$/', $input, $matches)) {
            return round((int) $matches[1] / 60, 2);
        }

        throw new \Exception('Invalid time format. Use formats like: 1.5, 1h30m, 90m');
    }

    private function resetManualForm()
    {
        $this->showManualEntry = false;
        $this->manualHours = '';
        $this->manualWorkType = '';
        $this->manualDescription = '';
        $this->manualBillable = true;
        $this->manualDate = now()->format('Y-m-d');
    }

    private function updateRateInfo()
    {
        try {
            $service = app(TimeTrackingService::class);
            $this->currentRate = $service->getCurrentRateInfo();

            if (isset($this->currentRate['multiplier'])) {
                $this->rateMultiplier = $this->currentRate['multiplier'];
            }

            if (isset($this->currentRate['visual_indicator'])) {
                $this->rateBadge = $this->currentRate['visual_indicator']['badge'] ?? 'Standard';
                $this->rateColor = $this->currentRate['visual_indicator']['color'] ?? 'zinc';
            }
        } catch (\Exception $e) {
            // Default values if service fails
            $this->rateMultiplier = 1;
            $this->rateBadge = 'Standard';
            $this->rateColor = 'zinc';
        }
    }

    public function handleTimerCompleted($data)
    {
        // Reset timer state
        $this->isTimerRunning = false;
        $this->isPaused = false;
        $this->activeTimerId = null;
        $this->activeTimerStartedAt = null;
        $this->elapsedTime = '00:00:00';
        $this->liveRevenue = 0;

        // Reload all data
        $this->loadMetrics();
        $this->loadRecentEntries();

        // Show success message if provided
        if (isset($data['hours']) && isset($data['amount'])) {
            $message = "Timer stopped - {$data['hours']}h recorded";
            if ($data['amount'] > 0) {
                $message .= " (\${$data['amount']})";
            }

            Flux::toast(
                heading: 'Timer completed',
                text: $message,
                variant: 'success'
            );
        }
    }

    public function render()
    {
        return view('livewire.ticket-time-tracker');
    }
}
