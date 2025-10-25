<?php

namespace App\Livewire\HR;

use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Services\TimeClockService;
use Livewire\Component;

class TimeClock extends Component
{
    public $activeEntry = null;

    public $elapsedTime = '';

    public $latitude = null;

    public $longitude = null;

    public $notes = '';

    public $breakMinutes = 0;

    public $requireGPS = false;

    public $isProcessing = false;

    public $showBreakModal = false;

    public $selectedBreakDuration = null;

    public $showClockOutModal = false;

    protected TimeClockService $timeClockService;

    public function boot(TimeClockService $timeClockService)
    {
        $this->timeClockService = $timeClockService;
    }

    public function mount()
    {
        $this->loadActiveEntry();
        $this->loadSettings();
    }

    public function loadActiveEntry()
    {
        $this->activeEntry = $this->timeClockService->getActiveEntry(auth()->user());

        if ($this->activeEntry) {
            $this->updateElapsedTime();
        }
    }

    public function loadSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettings($settings);
        
        $resolvedSettings = $hrSettings->resolveForUser(auth()->user());
        
        $this->requireGPS = $resolvedSettings['require_gps'] ?? false;
    }

    public function requestLocation()
    {
        // Just trigger the browser to request location
        // The actual clock in will happen after location is captured
        $this->dispatch('requestGeoLocation');
    }

    public function clockIn()
    {
        $this->isProcessing = true;

        try {
            if ($this->requireGPS && (! $this->latitude || ! $this->longitude)) {
                $this->dispatch('error', message: 'GPS location is required to clock in');
                $this->isProcessing = false;

                return;
            }

            $this->activeEntry = $this->timeClockService->clockIn(auth()->user(), [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);

            $this->dispatch('success', message: 'Successfully clocked in at ' . $this->activeEntry->clock_in->format('g:i A'));
            $this->notes = '';
            $this->breakMinutes = 0;
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function openClockOutModal()
    {
        $this->showClockOutModal = true;
    }

    public function clockOut()
    {
        $this->isProcessing = true;

        try {
            if (! $this->activeEntry) {
                throw new \Exception('No active time entry found');
            }

            $entry = $this->timeClockService->clockOut($this->activeEntry, [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'notes' => $this->notes,
            ]);

            $this->dispatch('success', message: 'Successfully clocked out. Total hours: ' . $entry->getTotalHours());

            $this->activeEntry = null;
            $this->notes = '';
            $this->breakMinutes = 0;
            $this->showClockOutModal = false;
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function updateLocation($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function updateElapsedTime()
    {
        if ($this->activeEntry) {
            $this->elapsedTime = $this->activeEntry->getFormattedDuration();
        }
    }

    public function openBreakModal()
    {
        $this->showBreakModal = true;
    }

    public function takeBreak()
    {
        $this->isProcessing = true;

        try {
            if (! $this->activeEntry) {
                throw new \Exception('No active time entry found');
            }

            if (! $this->selectedBreakDuration) {
                throw new \Exception('Please select a break duration');
            }

            $entry = $this->timeClockService->clockOut($this->activeEntry, [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'notes' => 'Break: ' . $this->selectedBreakDuration . ' minutes',
                'is_break' => true,
                'break_duration' => $this->selectedBreakDuration,
            ]);

            $this->dispatch('success', message: 'Break started. Enjoy your ' . $this->selectedBreakDuration . ' minute break!');

            $this->activeEntry = null;
            $this->showBreakModal = false;
            $this->selectedBreakDuration = null;
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function isOnBreak()
    {
        $lastEntry = EmployeeTimeEntry::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->whereNotNull('clock_out')
            ->orderBy('clock_out', 'desc')
            ->first();

        if (! $lastEntry) {
            return false;
        }

        $metadata = $lastEntry->metadata ?? [];
        $isBreak = $metadata['is_break'] ?? false;
        $breakDuration = $metadata['break_duration'] ?? 0;

        if (! $isBreak || ! $breakDuration) {
            return false;
        }

        $breakEndTime = $lastEntry->clock_out->copy()->addMinutes($breakDuration);

        return now()->lessThan($breakEndTime);
    }

    public function getBreakEndTime()
    {
        $lastEntry = EmployeeTimeEntry::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->whereNotNull('clock_out')
            ->orderBy('clock_out', 'desc')
            ->first();

        if (! $lastEntry) {
            return null;
        }

        $metadata = $lastEntry->metadata ?? [];
        $breakDuration = $metadata['break_duration'] ?? 0;

        return $lastEntry->clock_out->copy()
            ->addMinutes($breakDuration)
            ->timezone(auth()->user()->company->getTimezone());
    }

    public function render()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettings($settings);

        $resolvedSettings = $hrSettings->resolveForUser(auth()->user());
        
        $availableBreakDurations = $resolvedSettings['available_break_durations'] ?? [15, 30, 45, 60];
        $allowCustomDuration = $resolvedSettings['allow_custom_break_duration'] ?? false;

        $recentEntries = EmployeeTimeEntry::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('clock_in', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.hr.time-clock', [
            'recentEntries' => $recentEntries,
            'isOnBreak' => $this->isOnBreak(),
            'breakEndTime' => $this->getBreakEndTime(),
            'availableBreakDurations' => $availableBreakDurations,
            'allowCustomDuration' => $allowCustomDuration,
        ]);
    }
}
