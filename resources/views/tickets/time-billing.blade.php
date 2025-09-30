@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header with Statistics -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Time & Billing</h1>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Hours</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($statistics['total_hours'], 2) }}
                        </p>
                    </div>
                    <flux:icon name="clock" class="h-8 w-8 text-gray-400"/>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Billable Hours</p>
                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($statistics['billable_hours'], 2) }}
                        </p>
                    </div>
                    <flux:icon name="currency-dollar" class="h-8 w-8 text-green-400"/>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Amount</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($statistics['total_amount'], 2) }}
                        </p>
                    </div>
                    <flux:icon name="banknotes" class="h-8 w-8 text-blue-400"/>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Rate</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            ${{ $statistics['billable_hours'] > 0 ? number_format($statistics['total_amount'] / $statistics['billable_hours'], 2) : '0.00' }}/hr
                        </p>
                    </div>
                    <flux:icon name="chart-bar" class="h-8 w-8 text-purple-400"/>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Range</label>
                    <div class="flex gap-2">
                        <input type="date" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" 
                               wire:model="dateFrom" placeholder="From">
                        <input type="date" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" 
                               wire:model="dateTo" placeholder="To">
                    </div>
                </div>
                
                <div class="min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="invoiced">Invoiced</option>
                    </select>
                </div>
                
                <div class="min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">All</option>
                        <option value="billable">Billable</option>
                        <option value="non-billable">Non-Billable</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <flux:icon name="funnel" class="w-4 h-4 inline mr-1"/> Filter
                    </button>
                    <button class="ml-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <flux:icon name="arrow-down-tray" class="w-4 h-4 inline mr-1"/> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Entries Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Ticket
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Client
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Hours
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Rate
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Amount
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($timeEntries as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $entry->work_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('tickets.show', $entry->ticket_id) }}" 
                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                #{{ $entry->ticket->ticket_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $entry->ticket->client ? $entry->ticket->client->name : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $entry->user->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <span class="truncate block max-w-xs" title="{{ $entry->description }}">
                                {{ Str::limit($entry->description, 50) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <span class="font-medium {{ $entry->billable ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                {{ number_format($entry->hours_worked, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                            ${{ number_format($entry->hourly_rate, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($entry->billable)
                                <span class="text-green-600 dark:text-green-400">
                                    ${{ number_format($entry->amount, 2) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $statusColors = [
                                    'draft' => 'gray',
                                    'submitted' => 'blue',
                                    'approved' => 'green',
                                    'invoiced' => 'purple',
                                    'completed' => 'green'
                                ];
                                $color = $statusColors[$entry->status ?? 'draft'] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900 dark:text-{{ $color }}-200">
                                {{ ucfirst($entry->status ?? 'draft') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Edit
                            </button>
                            @if($entry->status === 'draft')
                                <button class="ml-2 text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Submit
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <flux:icon name="clock" class="h-12 w-12 text-gray-400 mb-3"/>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No Time Entries</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Start tracking time on tickets to see entries here
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($timeEntries->hasPages())
        <div class="mt-6">
            {{ $timeEntries->links() }}
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing {{ $timeEntries->firstItem() ?? 0 }} to {{ $timeEntries->lastItem() ?? 0 }} of {{ $timeEntries->total() }} entries
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <flux:icon name="plus" class="w-4 h-4 inline mr-1"/> Add Manual Entry
            </button>
            <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                <flux:icon name="document-text" class="w-4 h-4 inline mr-1"/> Generate Invoice
            </button>
        </div>
    </div>
</div>
@endsection