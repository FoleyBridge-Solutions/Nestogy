@extends('layouts.app')

@section('title', 'My Schedule')

@php
$pageTitle = 'My Schedule';
$pageSubtitle = 'View your upcoming work schedules';
$activeDomain = 'hr';
$sidebarContext = 'hr';
$pageActions = [
    ['label' => 'Time Clock', 'href' => route('hr.time-clock.index'), 'icon' => 'clock', 'variant' => 'ghost']
];
@endphp

@section('content')
    <div class="container mx-auto max-w-6xl py-6">
        <flux:card>
            <div class="space-y-6">
                <flux:heading size="lg">Your Work Schedule</flux:heading>

                @if($schedules->isEmpty())
                    <div class="text-center py-12">
                        <flux:icon.calendar class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <flux:heading size="lg" class="mb-2">No Schedules</flux:heading>
                        <flux:subheading>No schedules assigned yet</flux:subheading>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">Start Time</th>
                                    <th class="px-4 py-3 font-semibold">End Time</th>
                                    <th class="px-4 py-3 font-semibold">Duration</th>
                                    <th class="px-4 py-3 font-semibold">Shift</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-4 py-3">
                                            {{ $schedule->scheduled_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ round($schedule->getDurationMinutes() / 60, 2) }}h
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($schedule->shift)
                                                <span class="text-sm">{{ $schedule->shift->name }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <flux:badge color="{{ $schedule->status === 'confirmed' ? 'green' : 'zinc' }}">
                                                {{ ucfirst($schedule->status) }}
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $schedules->links() }}
                    </div>
                @endif
            </div>
        </flux:card>
    </div>
@endsection
