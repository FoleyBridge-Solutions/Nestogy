<?php

namespace App\Livewire\HR;

use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use App\Livewire\BaseIndexComponent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Flux\Flux;

class EmployeeTimeEntryIndex extends BaseIndexComponent
{
    public $statusFilter = '';

    public $userFilter = '';

    public $payPeriodFilter = '';

    public $startDate = '';

    public $endDate = '';

    public $selectedEntries = [];



    public function mount()
    {
        parent::mount();

        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    protected function getDefaultSort(): array
    {
        return ['field' => 'clock_in', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['notes', 'rejection_reason'];
    }

    protected function getColumns(): array
    {
        return [
            'user.name' => [
                'label' => 'Employee',
                'sortable' => true,
                'filterable' => false,
            ],
            'clock_in' => [
                'label' => 'Clock In',
                'sortable' => true,
                'type' => 'datetime',
            ],
            'clock_out' => [
                'label' => 'Clock Out',
                'sortable' => true,
                'type' => 'datetime',
            ],
            'total_hours' => [
                'label' => 'Total Hours',
                'sortable' => false,
                'type' => 'custom',
            ],
            'overtime_hours' => [
                'label' => 'Overtime',
                'sortable' => false,
                'type' => 'custom',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'badge',
                'badgeColor' => function ($item) {
                    return match ($item->status) {
                        'in_progress' => 'blue',
                        'completed' => 'yellow',
                        'approved' => 'green',
                        'rejected' => 'red',
                        'paid' => 'purple',
                        default => 'zinc',
                    };
                },
                'options' => [
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'paid' => 'Paid',
                ],
            ],
            'entry_type' => [
                'label' => 'Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'badge',
                'badgeColor' => function ($item) {
                    return match ($item->entry_type) {
                        'clock' => 'sky',
                        'manual' => 'orange',
                        'imported' => 'violet',
                        'adjusted' => 'amber',
                        default => 'zinc',
                    };
                },
                'options' => [
                    'clock' => 'Clock',
                    'manual' => 'Manual',
                    'imported' => 'Imported',
                    'adjusted' => 'Adjusted',
                ],
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = EmployeeTimeEntry::where('company_id', $this->companyId)
            ->whereBetween('clock_in', [
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate),
            ]);

        $totalMinutes = (clone $baseQuery)->sum('total_minutes');
        $overtimeMinutes = (clone $baseQuery)->sum('overtime_minutes');
        $pendingCount = (clone $baseQuery)->whereIn('status', ['in_progress', 'completed'])->count();

        return [
            [
                'label' => 'Total Hours',
                'value' => round($totalMinutes / 60, 1),
                'icon' => 'clock',
                'iconBg' => 'bg-blue-500',
            ],
            [
                'label' => 'Overtime Hours',
                'value' => round($overtimeMinutes / 60, 1),
                'icon' => 'fire',
                'iconBg' => 'bg-orange-500',
            ],
            [
                'label' => 'Pending Approval',
                'value' => $pendingCount,
                'icon' => 'exclamation-circle',
                'iconBg' => 'bg-yellow-500',
            ],
            [
                'label' => 'Unique Employees',
                'value' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
                'icon' => 'users',
                'iconBg' => 'bg-green-500',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Time Entries',
            'message' => 'No time entries found for the selected period.',
            'icon' => 'clock',
            'action' => null,
            'actionLabel' => null,
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return EmployeeTimeEntry::where('company_id', $this->companyId)
            ->with(['user', 'shift', 'payPeriod', 'approvedBy']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        if ($this->payPeriodFilter) {
            $query->where('pay_period_id', $this->payPeriodFilter);
        }

        if ($this->startDate) {
            $query->where('clock_in', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->where('clock_in', '<=', Carbon::parse($this->endDate)->endOfDay());
        }

        return $query;
    }

    protected function getRowActions($item)
    {
        $actions = [
            ['label' => 'View', 'href' => route('hr.time-entries.show', $item->id), 'icon' => 'eye'],
        ];

        if (! $item->exported_to_payroll && auth()->user()->can('update', $item)) {
            $actions[] = ['label' => 'Edit', 'href' => route('hr.time-entries.edit', $item->id), 'icon' => 'pencil'];
        }

        if ($item->status === 'completed' && auth()->user()->can('approve', $item)) {
            $actions[] = ['label' => 'Approve', 'wire:click' => "approveEntry({$item->id})", 'icon' => 'check', 'variant' => 'success'];
            $actions[] = ['label' => 'Reject', 'wire:click' => "rejectEntry({$item->id})", 'icon' => 'x-mark', 'variant' => 'danger'];
        }

        if (! $item->exported_to_payroll && auth()->user()->can('delete', $item)) {
            $actions[] = ['label' => 'Delete', 'wire:click' => "deleteEntry({$item->id})", 'wire:confirm' => 'Are you sure you want to delete this time entry?', 'icon' => 'trash', 'variant' => 'danger'];
        }

        return $actions;
    }

    protected function getBulkActions()
    {
        return [
            ['label' => 'Approve Selected', 'method' => 'bulkApprove', 'variant' => 'success'],
            ['label' => 'Export to Payroll', 'method' => 'exportToPayroll', 'variant' => 'primary'],
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'statusFilter' => ['except' => ''],
            'userFilter' => ['except' => ''],
            'payPeriodFilter' => ['except' => ''],
            'startDate' => ['except' => ''],
            'endDate' => ['except' => ''],
            'sortField' => ['except' => 'clock_in'],
            'sortDirection' => ['except' => 'desc'],
        ];
    }

    public function approveEntry($entryId)
    {
        $entry = EmployeeTimeEntry::findOrFail($entryId);
        $this->authorize('approve', $entry);

        $entry->status = EmployeeTimeEntry::STATUS_APPROVED;
        $entry->approved_by = auth()->id();
        $entry->approved_at = now();
        $entry->save();

        $this->dispatch('success', message: 'Time entry approved');
    }

    public function rejectEntry($entryId)
    {
        $entry = EmployeeTimeEntry::findOrFail($entryId);
        $this->authorize('approve', $entry);

        $this->dispatch('prompt-rejection', entryId: $entryId);
    }

    public function confirmRejection($entryId, $reason)
    {
        $entry = EmployeeTimeEntry::findOrFail($entryId);
        $this->authorize('approve', $entry);

        $entry->status = EmployeeTimeEntry::STATUS_REJECTED;
        $entry->rejected_by = auth()->id();
        $entry->rejected_at = now();
        $entry->rejection_reason = $reason;
        $entry->save();

        $this->dispatch('success', message: 'Time entry rejected');
    }

    public function deleteEntry($entryId)
    {
        $entry = EmployeeTimeEntry::findOrFail($entryId);
        $this->authorize('delete', $entry);

        if ($entry->exported_to_payroll) {
            $this->dispatch('error', message: 'Cannot delete exported time entries');

            return;
        }

        $entry->delete();
        $this->dispatch('success', message: 'Time entry deleted');
    }

    public function bulkApprove()
    {
        $entries = EmployeeTimeEntry::whereIn('id', $this->selected)
            ->where('company_id', $this->companyId)
            ->get();

        foreach ($entries as $entry) {
            if (auth()->user()->can('approve', $entry)) {
                $entry->status = EmployeeTimeEntry::STATUS_APPROVED;
                $entry->approved_by = auth()->id();
                $entry->approved_at = now();
                $entry->save();
            }
        }

        $this->selected = [];
        $this->dispatch('success', message: count($entries) . ' time entries approved');
    }

    public function exportToPayroll()
    {
        $this->dispatch('show-payroll-export-modal', entryIds: $this->selected);
    }

    public function getPayPeriods()
    {
        return PayPeriod::where('company_id', $this->companyId)
            ->orderBy('start_date', 'desc')
            ->limit(12)
            ->get()
            ->map(fn ($period) => [
                'id' => $period->id,
                'label' => $period->getLabel(),
            ]);
    }



    public function render()
    {
        return parent::render()->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
