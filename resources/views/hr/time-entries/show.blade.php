@extends('layouts.app')

@section('title', 'Time Entry Details')

@php
$pageTitle = 'Time Entry Details';
$pageSubtitle = $entry->user->name . ' - ' . $entry->clock_in->format('M d, Y');
$pageActions = [
    ['label' => 'Back to List', 'href' => route('hr.time-entries.index'), 'icon' => 'arrow-left', 'variant' => 'ghost']
];

if (!$entry->exported_to_payroll && auth()->user()->can('update', $entry)) {
    $pageActions[] = ['label' => 'Edit', 'href' => route('hr.time-entries.edit', $entry), 'icon' => 'pencil', 'variant' => 'primary'];
}
@endphp

@section('content')
    <div class="max-w-4xl mx-auto py-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Employee</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $entry->user->name }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                        <div class="mt-1">
                            @if($entry->status === 'approved')
                                <flux:badge variant="success">Approved</flux:badge>
                            @elseif($entry->status === 'completed')
                                <flux:badge variant="warning">Pending Approval</flux:badge>
                            @elseif($entry->status === 'rejected')
                                <flux:badge variant="danger">Rejected</flux:badge>
                            @elseif($entry->status === 'in_progress')
                                <flux:badge variant="info">In Progress</flux:badge>
                            @elseif($entry->status === 'paid')
                                <flux:badge variant="primary">Paid</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Clock In</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $entry->clock_in->format('M d, Y g:i A') }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Clock Out</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $entry->clock_out?->format('M d, Y g:i A') ?? 'In Progress' }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Hours</h3>
                        <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $entry->getTotalHours() }} hours
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Regular / Overtime</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $entry->getRegularHours() }}h / 
                            <span class="text-orange-600 dark:text-orange-400">{{ $entry->getOvertimeHours() }}h</span>
                        </p>
                    </div>

                    @if($entry->break_minutes > 0)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Break Time</h3>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $entry->break_minutes }} minutes
                            </p>
                        </div>
                    @endif

                    @if($entry->shift)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Shift</h3>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $entry->shift->name }}
                            </p>
                        </div>
                    @endif

                    @if($entry->notes)
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</h3>
                            <p class="mt-1 text-gray-900 dark:text-white">
                                {{ $entry->notes }}
                            </p>
                        </div>
                    @endif

                    @if($entry->rejection_reason)
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-red-500">Rejection Reason</h3>
                            <p class="mt-1 text-red-900 dark:text-red-100 bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                {{ $entry->rejection_reason }}
                            </p>
                        </div>
                    @endif

                    @if($entry->approved_at)
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Approval Information</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Approved by {{ $entry->approvedBy->name ?? 'System' }} on 
                                {{ $entry->approved_at->format('M d, Y g:i A') }}
                            </p>
                        </div>
                    @endif

                    @if($entry->exported_to_payroll)
                        <div class="col-span-2">
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                                    <div>
                                        <p class="font-medium text-green-900 dark:text-green-100">Exported to Payroll</p>
                                        <p class="text-sm text-green-700 dark:text-green-300">
                                            Exported on {{ $entry->exported_at->format('M d, Y g:i A') }}
                                            @if($entry->payroll_batch_id)
                                                (Batch: {{ $entry->payroll_batch_id }})
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @if($entry->status === 'completed' && auth()->user()->can('approve', $entry))
                        <form method="POST" action="{{ route('hr.time-entries.approve', $entry) }}">
                            @csrf
                            <flux:button type="submit" variant="primary" color="green">
                                <flux:icon.check class="w-4 h-4" />
                                Approve
                            </flux:button>
                        </form>

                        <flux:button variant="danger" wire:click="$dispatch('prompt-rejection')">
                            <flux:icon.x-mark class="w-4 h-4" />
                            Reject
                        </flux:button>
                    @endif

                    @if(!$entry->exported_to_payroll && auth()->user()->can('delete', $entry))
                        <form method="POST" action="{{ route('hr.time-entries.destroy', $entry) }}" 
                              onsubmit="return confirm('Are you sure you want to delete this time entry?')">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger">
                                <flux:icon.trash class="w-4 h-4" />
                                Delete
                            </flux:button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
