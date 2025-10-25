<?php

namespace App\Livewire\HR;

use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Services\TimeClockService;
use Carbon\Carbon;
use Livewire\Component;

class HRDashboard extends Component
{
    public $activeEntry = null;
    public $canManageHR = false;

    public function mount()
    {
        $this->activeEntry = app(TimeClockService::class)->getActiveEntry(auth()->user());
        $this->canManageHR = auth()->user()->can('manage-hr');
    }

    public function clockIn()
    {
        try {
            $timeClockService = app(TimeClockService::class);
            $this->activeEntry = $timeClockService->clockIn(auth()->user(), [
                'ip' => request()->ip(),
            ]);
            
            $this->dispatch('success', message: 'Clocked in at ' . $this->activeEntry->clock_in->format('g:i A'));
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function clockOut()
    {
        try {
            if (!$this->activeEntry) {
                throw new \Exception('No active time entry found');
            }

            $timeClockService = app(TimeClockService::class);
            $entry = $timeClockService->clockOut($this->activeEntry, [
                'ip' => request()->ip(),
            ]);
            
            $this->activeEntry = null;
            $this->dispatch('success', message: 'Clocked out. Total: ' . $entry->getTotalHours() . ' hours');
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function getMyStatsProperty()
    {
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;
        
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $weekEntries = EmployeeTimeEntry::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereBetween('clock_in', [$weekStart, $weekEnd])
            ->get();

        $monthEntries = EmployeeTimeEntry::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereBetween('clock_in', [$monthStart, $monthEnd])
            ->get();

        $pendingCount = EmployeeTimeEntry::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereIn('status', ['in_progress', 'completed'])
            ->count();

        return [
            'week_total_minutes' => $weekEntries->sum('total_minutes'),
            'week_regular_minutes' => $weekEntries->sum('regular_minutes'),
            'week_overtime_minutes' => $weekEntries->sum('overtime_minutes'),
            'month_total_minutes' => $monthEntries->sum('total_minutes'),
            'pending_entries' => $pendingCount,
        ];
    }

    public function getTeamStatsProperty()
    {
        if (!$this->canManageHR) {
            return null;
        }

        $companyId = auth()->user()->company_id;
        $now = Carbon::now();
        
        $clockedInCount = EmployeeTimeEntry::where('company_id', $companyId)
            ->where('status', 'in_progress')
            ->whereDate('clock_in', $now->toDateString())
            ->distinct('user_id')
            ->count('user_id');

        $totalEmployees = \App\Domains\Core\Models\User::where('company_id', $companyId)
            ->where('status', true)
            ->count();

        $pendingApprovals = EmployeeTimeEntry::where('company_id', $companyId)
            ->where('status', 'completed')
            ->count();

        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $weekEntries = EmployeeTimeEntry::where('company_id', $companyId)
            ->whereBetween('clock_in', [$weekStart, $weekEnd])
            ->get();

        $overtimeAlerts = $weekEntries->groupBy('user_id')
            ->filter(fn($userEntries) => $userEntries->sum('overtime_minutes') > 0)
            ->count();

        return [
            'clocked_in' => $clockedInCount,
            'total_employees' => $totalEmployees,
            'pending_approvals' => $pendingApprovals,
            'team_week_minutes' => $weekEntries->sum('total_minutes'),
            'overtime_alerts' => $overtimeAlerts,
        ];
    }

    public function getRecentEntriesProperty()
    {
        return EmployeeTimeEntry::where('user_id', auth()->id())
            ->where('company_id', auth()->user()->company_id)
            ->with('user')
            ->orderBy('clock_in', 'desc')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.hr.hr-dashboard')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
