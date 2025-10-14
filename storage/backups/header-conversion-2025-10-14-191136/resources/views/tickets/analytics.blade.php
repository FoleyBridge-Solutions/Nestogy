@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Ticket Analytics</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Performance metrics and insights</p>
    </div>

    <!-- Date Range Selector -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex gap-2">
                <button class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                    Today
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    This Week
                </button>
                <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    This Month
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    This Quarter
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    This Year
                </button>
            </div>
            <div class="flex gap-2">
                <input type="date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                <span class="text-gray-500 dark:text-gray-400 py-2">to</span>
                <input type="date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Apply
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Tickets -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="ticket" class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                    </div>
                    <h3 class="ml-3 text-sm font-medium text-gray-600 dark:text-gray-400">Total Tickets</h3>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['total_tickets']) }}</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                <flux:icon name="arrow-trending-up" class="h-4 w-4 inline"/> +12% from last period
            </p>
        </div>

        <!-- Open Tickets -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <flux:icon name="exclamation-circle" class="h-6 w-6 text-yellow-600 dark:text-yellow-400"/>
                    </div>
                    <h3 class="ml-3 text-sm font-medium text-gray-600 dark:text-gray-400">Open Tickets</h3>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['open_tickets']) }}</p>
            <div class="mt-2 flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">
                    {{ round(($metrics['open_tickets'] / max($metrics['total_tickets'], 1)) * 100, 1) }}% of total
                </span>
            </div>
        </div>

        <!-- Avg Resolution Time -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon name="clock" class="h-6 w-6 text-green-600 dark:text-green-400"/>
                    </div>
                    <h3 class="ml-3 text-sm font-medium text-gray-600 dark:text-gray-400">Avg Resolution</h3>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($metrics['avg_resolution_time'] ?? 0, 1) }}h
            </p>
            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                <flux:icon name="arrow-trending-down" class="h-4 w-4 inline"/> -8% improvement
            </p>
        </div>

        <!-- Customer Satisfaction -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <flux:icon name="face-smile" class="h-6 w-6 text-purple-600 dark:text-purple-400"/>
                    </div>
                    <h3 class="ml-3 text-sm font-medium text-gray-600 dark:text-gray-400">Satisfaction</h3>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">94.5%</p>
            <div class="mt-2 flex items-center">
                <div class="flex -space-x-1">
                    @for($i = 0; $i < 5; $i++)
                        <flux:icon name="star" class="h-4 w-4 {{ $i < 4 ? 'text-yellow-400' : 'text-gray-300' }}"/>
                    @endfor
                </div>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">4.7/5.0</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Tickets by Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tickets by Status</h3>
            <div class="space-y-4">
                @php
                    $statusColors = [
                        'open' => 'yellow',
                        'in_progress' => 'blue',
                        'waiting' => 'purple',
                        'on_hold' => 'gray',
                        'resolved' => 'green',
                        'closed' => 'slate'
                    ];
                    $totalStatusTickets = array_sum($metrics['tickets_by_status']->toArray());
                @endphp
                @foreach($metrics['tickets_by_status'] as $status => $count)
                    @php
                        $percentage = $totalStatusTickets > 0 ? ($count / $totalStatusTickets) * 100 : 0;
                        $color = $statusColors[$status] ?? 'gray';
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $count }} ({{ round($percentage, 1) }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="bg-{{ $color }}-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tickets by Priority -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tickets by Priority</h3>
            <div class="space-y-4">
                @php
                    $priorityColors = [
                        'critical' => 'red',
                        'high' => 'orange',
                        'medium' => 'yellow',
                        'low' => 'green'
                    ];
                    $totalPriorityTickets = array_sum($metrics['tickets_by_priority']->toArray());
                @endphp
                @foreach($metrics['tickets_by_priority'] as $priority => $count)
                    @php
                        $percentage = $totalPriorityTickets > 0 ? ($count / $totalPriorityTickets) * 100 : 0;
                        $color = $priorityColors[$priority] ?? 'gray';
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ ucfirst($priority) }}
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $count }} ({{ round($percentage, 1) }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="bg-{{ $color }}-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Response Time -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Response Time Targets</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">First Response</span>
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-green-600 dark:text-green-400 mr-2">98%</span>
                        <flux:icon name="check-circle" class="h-4 w-4 text-green-500"/>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Resolution SLA</span>
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mr-2">92%</span>
                        <flux:icon name="exclamation-triangle" class="h-4 w-4 text-yellow-500"/>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Customer Updates</span>
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-green-600 dark:text-green-400 mr-2">95%</span>
                        <flux:icon name="check-circle" class="h-4 w-4 text-green-500"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Agents -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Top Performers</h3>
            <div class="space-y-3">
                @php
                    $topAgents = [
                        ['name' => 'John Smith', 'tickets' => 142, 'rating' => 4.9],
                        ['name' => 'Sarah Johnson', 'tickets' => 128, 'rating' => 4.8],
                        ['name' => 'Mike Wilson', 'tickets' => 115, 'rating' => 4.7],
                    ];
                @endphp
                @foreach($topAgents as $agent)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                    {{ substr($agent['name'], 0, 2) }}
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $agent['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $agent['tickets'] }} tickets</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <flux:icon name="star" class="h-4 w-4 text-yellow-400"/>
                            <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">{{ $agent['rating'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity Trends</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">New Tickets Today</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">24</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Resolved Today</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">31</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Avg Response Time</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">18 min</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Escalations</span>
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">3</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="flex justify-end gap-2">
        <button class="px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
            <flux:icon name="printer" class="w-4 h-4 inline mr-1"/> Print Report
        </button>
        <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <flux:icon name="arrow-down-tray" class="w-4 h-4 inline mr-1"/> Export to CSV
        </button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <flux:icon name="document-chart-bar" class="w-4 h-4 inline mr-1"/> Generate PDF Report
        </button>
    </div>
</div>
@endsection