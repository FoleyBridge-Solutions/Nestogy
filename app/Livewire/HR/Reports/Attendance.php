<?php

namespace App\Livewire\HR\Reports;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Carbon\Carbon;
use Livewire\Component;

class Attendance extends Component
{
    public $startDate;
    public $endDate;
    public $selectedUser = null;
    public $viewMode = 'summary';

    public function mount()
    {
        $this->authorize('manage-hr');
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function getAttendanceDataProperty()
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', true);

        if ($this->selectedUser) {
            $users->where('id', $this->selectedUser);
        }

        $users = $users->get();

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $totalWorkdays = $this->calculateWorkdays($startDate, $endDate);

        return $users->map(function ($user) use ($startDate, $endDate, $totalWorkdays) {
            $entries = EmployeeTimeEntry::where('user_id', $user->id)
                ->where('company_id', auth()->user()->company_id)
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->get();

            $daysWorked = $entries->pluck('clock_in')->map(fn($date) => $date->format('Y-m-d'))->unique()->count();
            $lateEntries = $entries->filter(fn($entry) => $entry->clock_in->format('H:i') > '09:00')->count();
            $earlyDepartures = $entries->filter(fn($entry) => $entry->clock_out && $entry->clock_out->format('H:i') < '17:00')->count();

            return [
                'user' => $user,
                'days_worked' => $daysWorked,
                'days_absent' => $totalWorkdays - $daysWorked,
                'attendance_rate' => $totalWorkdays > 0 ? round(($daysWorked / $totalWorkdays) * 100, 1) : 0,
                'late_count' => $lateEntries,
                'early_departure_count' => $earlyDepartures,
                'total_hours' => round($entries->sum('total_minutes') / 60, 2),
                'average_daily_hours' => $daysWorked > 0 ? round($entries->sum('total_minutes') / 60 / $daysWorked, 2) : 0,
            ];
        })->sortByDesc('attendance_rate');
    }

    public function getDailyAttendanceProperty()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $dates = [];
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isWeekday()) {
                $dates[] = $date->copy();
            }
        }

        return collect($dates)->map(function ($date) {
            $entries = EmployeeTimeEntry::where('company_id', auth()->user()->company_id)
                ->whereDate('clock_in', $date)
                ->with('user')
                ->get();

            $totalEmployees = User::where('company_id', auth()->user()->company_id)
                ->where('status', true)
                ->count();

            $presentCount = $entries->pluck('user_id')->unique()->count();

            return [
                'date' => $date,
                'present_count' => $presentCount,
                'absent_count' => $totalEmployees - $presentCount,
                'attendance_rate' => $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100, 1) : 0,
                'total_hours' => round($entries->sum('total_minutes') / 60, 2),
            ];
        });
    }

    public function getUsersProperty()
    {
        return User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    public function getSummaryProperty()
    {
        $data = $this->attendanceData;

        return [
            'average_attendance_rate' => $data->count() > 0 ? round($data->avg('attendance_rate'), 1) : 0,
            'total_days_worked' => $data->sum('days_worked'),
            'total_days_absent' => $data->sum('days_absent'),
            'total_late_incidents' => $data->sum('late_count'),
            'perfect_attendance_count' => $data->filter(fn($item) => $item['attendance_rate'] >= 100)->count(),
        ];
    }

    protected function calculateWorkdays(Carbon $start, Carbon $end): int
    {
        $workdays = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $workdays++;
            }
        }
        return $workdays;
    }

    public function render()
    {
        return view('livewire.hr.reports.attendance')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
