<?php

namespace App\Livewire\HR\Reports;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Carbon\Carbon;
use Livewire\Component;

class Overtime extends Component
{
    public $startDate;
    public $endDate;
    public $selectedUser = null;
    public $threshold = 40;

    public function mount()
    {
        $this->authorize('manage-hr');
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function getOvertimeDataProperty()
    {
        $query = EmployeeTimeEntry::where('company_id', auth()->user()->company_id)
            ->with(['user'])
            ->whereBetween('clock_in', [$this->startDate, $this->endDate])
            ->where('overtime_minutes', '>', 0);

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        $entries = $query->get();

        return $entries->groupBy('user_id')->map(function ($userEntries) {
            $weeklyData = $userEntries->groupBy(fn($entry) => $entry->clock_in->format('Y-W'))
                ->map(function ($weekEntries) {
                    return [
                        'week_start' => Carbon::parse($weekEntries->first()->clock_in)->startOfWeek(),
                        'total_minutes' => $weekEntries->sum('total_minutes'),
                        'overtime_minutes' => $weekEntries->sum('overtime_minutes'),
                        'entry_count' => $weekEntries->count(),
                    ];
                });

            return [
                'user' => $userEntries->first()->user,
                'total_overtime_minutes' => $userEntries->sum('overtime_minutes'),
                'total_overtime_hours' => round($userEntries->sum('overtime_minutes') / 60, 2),
                'weeks_with_overtime' => $weeklyData->count(),
                'weekly_breakdown' => $weeklyData,
                'total_minutes' => $userEntries->sum('total_minutes'),
                'total_hours' => round($userEntries->sum('total_minutes') / 60, 2),
            ];
        })->sortByDesc('total_overtime_minutes');
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
        $data = $this->overtimeData;

        return [
            'total_overtime_hours' => round($data->sum('total_overtime_minutes') / 60, 2),
            'average_overtime_per_employee' => $data->count() > 0 ? round($data->sum('total_overtime_minutes') / $data->count() / 60, 2) : 0,
            'employees_with_overtime' => $data->count(),
            'max_overtime_hours' => $data->count() > 0 ? round($data->max('total_overtime_minutes') / 60, 2) : 0,
            'total_cost_estimate' => round($data->sum('total_overtime_minutes') / 60 * 1.5 * 25, 2),
        ];
    }

    public function render()
    {
        return view('livewire.hr.reports.overtime')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
