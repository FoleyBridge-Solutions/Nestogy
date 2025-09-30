@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Automation Rules</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Automate ticket workflows and actions</p>
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <flux:icon name="plus" class="w-4 h-4 inline mr-1"/> Create Rule
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Active Rules</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $workflows->where('is_active', true)->count() }}</p>
                </div>
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="bolt" class="h-6 w-6 text-green-600 dark:text-green-400"/>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Executions Today</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">342</p>
                </div>
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="play" class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Time Saved</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">18.5h</p>
                </div>
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="clock" class="h-6 w-6 text-purple-600 dark:text-purple-400"/>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Success Rate</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">98.2%</p>
                </div>
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <flux:icon name="check-circle" class="h-6 w-6 text-yellow-600 dark:text-yellow-400"/>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 rounded-t-lg">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button class="py-3 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600 dark:text-blue-400">
                All Rules ({{ $workflows->count() }})
            </button>
            <button class="py-3 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300">
                Active ({{ $workflows->where('is_active', true)->count() }})
            </button>
            <button class="py-3 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300">
                Inactive ({{ $workflows->where('is_active', false)->count() }})
            </button>
            <button class="py-3 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300">
                Templates
            </button>
        </nav>
    </div>

    <!-- Rules List -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-b-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Rule Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Trigger
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Conditions
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Last Run
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Executions
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($workflows as $workflow)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       {{ $workflow->is_active ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $workflow->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $workflow->description }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ ucfirst(str_replace('_', ' ', $workflow->trigger_type ?? 'ticket_created')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $conditions = [
                                    'Priority = High',
                                    'Client VIP',
                                    'Category = Network'
                                ];
                            @endphp
                            <div class="text-xs space-y-1">
                                @foreach(array_slice($conditions, 0, 2) as $condition)
                                    <div class="text-gray-600 dark:text-gray-400">• {{ $condition }}</div>
                                @endforeach
                                @if(count($conditions) > 2)
                                    <div class="text-gray-500 dark:text-gray-500">+{{ count($conditions) - 2 }} more</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $actions = [
                                    'Assign to Team',
                                    'Set Priority',
                                    'Send Email'
                                ];
                            @endphp
                            <div class="text-xs space-y-1">
                                @foreach(array_slice($actions, 0, 2) as $action)
                                    <div class="text-gray-600 dark:text-gray-400">→ {{ $action }}</div>
                                @endforeach
                                @if(count($actions) > 2)
                                    <div class="text-gray-500 dark:text-gray-500">+{{ count($actions) - 2 }} more</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $workflow->last_run_at ? \Carbon\Carbon::parse($workflow->last_run_at)->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ rand(10, 500) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">This month</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Edit
                            </button>
                            <button class="ml-3 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                                Clone
                            </button>
                            <button class="ml-3 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <flux:icon name="bolt" class="h-12 w-12 text-gray-400 mb-3"/>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No Automation Rules</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Create your first automation rule to streamline ticket workflows
                                </p>
                                <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <flux:icon name="plus" class="w-4 h-4 inline mr-1"/> Create First Rule
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($workflows->hasPages())
        <div class="mt-6">
            {{ $workflows->links() }}
        </div>
    @endif

    <!-- Rule Templates -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Popular Templates</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $templates = [
                    [
                        'name' => 'Auto-assign VIP Tickets',
                        'description' => 'Automatically assign high-priority tickets from VIP clients to senior technicians',
                        'icon' => 'star',
                        'color' => 'yellow'
                    ],
                    [
                        'name' => 'SLA Escalation',
                        'description' => 'Escalate tickets approaching SLA breach to management',
                        'icon' => 'exclamation-triangle',
                        'color' => 'red'
                    ],
                    [
                        'name' => 'After-hours Routing',
                        'description' => 'Route tickets created after business hours to on-call technicians',
                        'icon' => 'moon',
                        'color' => 'purple'
                    ]
                ];
            @endphp
            @foreach($templates as $template)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:border-{{ $template['color'] }}-300 dark:hover:border-{{ $template['color'] }}-600 cursor-pointer">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-{{ $template['color'] }}-100 dark:bg-{{ $template['color'] }}-900 rounded-lg">
                                <flux:icon name="{{ $template['icon'] }}" class="h-6 w-6 text-{{ $template['color'] }}-600 dark:text-{{ $template['color'] }}-400"/>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $template['description'] }}</p>
                            <button class="mt-3 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                Use Template →
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection