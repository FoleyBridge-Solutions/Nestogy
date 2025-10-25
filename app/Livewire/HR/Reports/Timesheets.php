<?php

namespace App\Livewire\HR\Reports;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Livewire\Component;

class Timesheets extends Component
{
    public $startDate;
    public $endDate;
    public $selectedUser = null;
    public $selectedPayPeriod = null;
    public $groupBy = 'user';

    public function mount()
    {
        $this->authorize('manage-hr');
        $this->startDate = Carbon::now()->startOfWeek()->toDateString();
        $this->endDate = Carbon::now()->endOfWeek()->toDateString();
    }

    public function updatedSelectedPayPeriod($payPeriodId)
    {
        if ($payPeriodId) {
            $payPeriod = PayPeriod::find($payPeriodId);
            if ($payPeriod) {
                $this->startDate = $payPeriod->start_date->toDateString();
                $this->endDate = $payPeriod->end_date->toDateString();
            }
        }
    }

    public function getEntriesProperty()
    {
        $query = EmployeeTimeEntry::where('company_id', auth()->user()->company_id)
            ->with(['user'])
            ->whereBetween('clock_in', [$this->startDate, $this->endDate]);

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        $entries = $query->orderBy('clock_in')->get();

        if ($this->groupBy === 'user') {
            return $entries->groupBy('user_id')->map(function ($userEntries) {
                return [
                    'user' => $userEntries->first()->user,
                    'entries' => $userEntries,
                    'total_minutes' => $userEntries->sum('total_minutes'),
                    'regular_minutes' => $userEntries->sum('regular_minutes'),
                    'overtime_minutes' => $userEntries->sum('overtime_minutes'),
                    'total_hours' => round($userEntries->sum('total_minutes') / 60, 2),
                ];
            });
        } elseif ($this->groupBy === 'day') {
            return $entries->groupBy(fn($entry) => $entry->clock_in->format('Y-m-d'))->map(function ($dayEntries, $date) {
                return [
                    'date' => Carbon::parse($date),
                    'entries' => $dayEntries,
                    'total_minutes' => $dayEntries->sum('total_minutes'),
                    'user_count' => $dayEntries->pluck('user_id')->unique()->count(),
                ];
            });
        }

        return $entries;
    }

    public function getPayPeriodsProperty()
    {
        return PayPeriod::where('company_id', auth()->user()->company_id)
            ->orderBy('start_date', 'desc')
            ->limit(12)
            ->get();
    }

    public function getUsersProperty()
    {
        return User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    public function getTotalsProperty()
    {
        $entries = EmployeeTimeEntry::where('company_id', auth()->user()->company_id)
            ->whereBetween('clock_in', [$this->startDate, $this->endDate]);

        if ($this->selectedUser) {
            $entries->where('user_id', $this->selectedUser);
        }

        $entries = $entries->get();

        return [
            'total_minutes' => $entries->sum('total_minutes'),
            'regular_minutes' => $entries->sum('regular_minutes'),
            'overtime_minutes' => $entries->sum('overtime_minutes'),
            'total_hours' => round($entries->sum('total_minutes') / 60, 2),
            'regular_hours' => round($entries->sum('regular_minutes') / 60, 2),
            'overtime_hours' => round($entries->sum('overtime_minutes') / 60, 2),
            'employee_count' => $entries->pluck('user_id')->unique()->count(),
            'entry_count' => $entries->count(),
        ];
    }

    public function exportCsv()
    {
        $entries = $this->entries;
        
        $filename = 'timesheets_' . $this->startDate . '_to_' . $this->endDate . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($entries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee', 'Date', 'Clock In', 'Clock Out', 'Total Hours', 'Regular Hours', 'Overtime Hours', 'Status']);

            foreach ($entries as $group) {
                if ($this->groupBy === 'user') {
                    foreach ($group['entries'] as $entry) {
                        fputcsv($file, [
                            $entry->user->name,
                            $entry->clock_in->format('Y-m-d'),
                            $entry->clock_in->format('H:i'),
                            $entry->clock_out?->format('H:i') ?? 'In Progress',
                            round($entry->total_minutes / 60, 2),
                            round($entry->regular_minutes / 60, 2),
                            round($entry->overtime_minutes / 60, 2),
                            ucfirst($entry->status),
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr.reports.timesheets')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
