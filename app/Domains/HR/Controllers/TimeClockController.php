<?php

namespace App\Domains\HR\Controllers;

use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Services\TimeClockService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimeClockController extends Controller
{
    protected TimeClockService $timeClockService;

    public function __construct(TimeClockService $timeClockService)
    {
        $this->timeClockService = $timeClockService;
    }

    public function index()
    {
        $activeEntry = $this->timeClockService->getActiveEntry(auth()->user());

        return view('hr.time-clock.index', compact('activeEntry'));
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        try {
            $entry = $this->timeClockService->clockIn(auth()->user(), [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'shift_id' => $request->input('shift_id'),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully clocked in',
                    'entry' => $entry,
                ]);
            }

            return redirect()->route('hr.time-clock.index')->with('success', 'Successfully clocked in at ' . $entry->clock_in->format('g:i A'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('hr.time-clock.index')->with('error', $e->getMessage());
        }
    }

    public function clockOut(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
            'break_minutes' => 'nullable|integer|min:0|max:480',
        ]);

        try {
            $activeEntry = $this->timeClockService->getActiveEntry(auth()->user());

            if (! $activeEntry) {
                throw new \Exception('No active time entry found');
            }

            if ($request->filled('break_minutes')) {
                $activeEntry->break_minutes = (int) $request->input('break_minutes');
            }

            $entry = $this->timeClockService->clockOut($activeEntry, [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'notes' => $request->input('notes'),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully clocked out',
                    'entry' => $entry,
                    'hours' => $entry->getTotalHours(),
                ]);
            }

            return redirect()->route('hr.time-clock.index')->with('success', 'Successfully clocked out. Total hours: ' . $entry->getTotalHours());
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('hr.time-clock.index')->with('error', $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $query = EmployeeTimeEntry::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->with(['shift', 'payPeriod'])
            ->orderBy('clock_in', 'desc');

        if ($request->filled('start_date')) {
            $query->where('clock_in', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('clock_in', '<=', $request->input('end_date'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'entries' => $query->paginate(50),
            ]);
        }

        $entries = $query->paginate(50);

        return view('hr.time-clock.history', compact('entries'));
    }

    public function status(Request $request)
    {
        $activeEntry = $this->timeClockService->getActiveEntry(auth()->user());

        return response()->json([
            'is_clocked_in' => $activeEntry !== null,
            'active_entry' => $activeEntry ? [
                'id' => $activeEntry->id,
                'clock_in' => $activeEntry->clock_in->toISOString(),
                'elapsed_minutes' => $activeEntry->getElapsedMinutes(),
                'elapsed_hours' => $activeEntry->getElapsedHours(),
                'formatted_duration' => $activeEntry->getFormattedDuration(),
            ] : null,
        ]);
    }

    public function schedule(Request $request)
    {
        $schedules = \App\Domains\HR\Models\EmployeeSchedule::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->with('shift')
            ->orderBy('date', 'desc')
            ->paginate(30);

        return view('hr.time-clock.schedule', compact('schedules'));
    }
}
