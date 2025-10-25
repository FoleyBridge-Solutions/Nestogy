@extends('layouts.app')

@section('title', 'Time Clock History')

@php
$pageTitle = 'Time Clock History';
$pageSubtitle = 'View your clock in/out history';
$sidebarContext = 'hr';
$pageActions = [
    ['label' => 'Time Clock', 'href' => route('hr.time-clock.index'), 'icon' => 'clock', 'variant' => 'ghost']
];
@endphp

@section('content')
    <div class="container mx-auto max-w-6xl py-6">
        <flux:card>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Your Time History</flux:heading>
                    <div class="flex gap-2">
                        <form method="GET" action="{{ route('hr.time-clock.history') }}" class="flex gap-2">
                            <flux:input 
                                type="date" 
                                name="start_date" 
                                value="{{ request('start_date') }}"
                                placeholder="Start Date" />
                            <flux:input 
                                type="date" 
                                name="end_date" 
                                value="{{ request('end_date') }}"
                                placeholder="End Date" />
                            <flux:button type="submit" variant="primary" icon="magnifying-glass">Filter</flux:button>
                        </form>
                    </div>
                </div>

                @if($entries->isEmpty())
                    <div class="text-center py-12">
                        <flux:icon.clock class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <flux:heading size="lg" class="mb-2">No Time Entries</flux:heading>
                        <flux:subheading>You haven't clocked in yet</flux:subheading>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">Clock In</th>
                                    <th class="px-4 py-3 font-semibold">Clock Out</th>
                                    <th class="px-4 py-3 font-semibold">Break</th>
                                    <th class="px-4 py-3 font-semibold">Total Hours</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-4 py-3">
                                            {{ $entry->clock_in->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $entry->clock_in->format('g:i A') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($entry->clock_out)
                                                {{ $entry->clock_out->format('g:i A') }}
                                            @else
                                                <flux:badge color="green">Clocked In</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $entry->break_minutes ?? 0 }} min
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($entry->clock_out)
                                                <strong>{{ $entry->getTotalHours() }}</strong> hrs
                                                @if($entry->getOvertimeHours() > 0)
                                                    <flux:badge color="orange" size="sm">{{ $entry->getOvertimeHours() }} OT</flux:badge>
                                                @endif
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'in_progress' => 'green',
                                                    'completed' => 'blue',
                                                    'approved' => 'green',
                                                    'rejected' => 'red',
                                                    'paid' => 'purple',
                                                ];
                                                $statusColor = $statusColors[$entry->status] ?? 'gray';
                                            @endphp
                                            <flux:badge :color="$statusColor">{{ ucfirst(str_replace('_', ' ', $entry->status)) }}</flux:badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs text-gray-500">{{ ucfirst($entry->entry_type) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </flux:card>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <flux:heading size="sm">Total Hours This Period</flux:heading>
                <div class="text-3xl font-bold mt-2">
                    {{ round($entries->sum(fn($e) => $e->total_minutes ?? 0) / 60, 1) }}
                </div>
                <flux:subheading>Regular + Overtime</flux:subheading>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">Overtime Hours</flux:heading>
                <div class="text-3xl font-bold mt-2 text-orange-500">
                    {{ round($entries->sum(fn($e) => $e->overtime_minutes ?? 0) / 60, 1) }}
                </div>
                <flux:subheading>Time and a half</flux:subheading>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">Pending Approval</flux:heading>
                <div class="text-3xl font-bold mt-2 text-yellow-500">
                    {{ $entries->whereIn('status', ['in_progress', 'completed'])->count() }}
                </div>
                <flux:subheading>Waiting for manager</flux:subheading>
            </flux:card>
        </div>
    </div>
@endsection
