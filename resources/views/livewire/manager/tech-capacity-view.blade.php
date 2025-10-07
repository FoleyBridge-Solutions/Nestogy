<div>
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">Technician Capacity</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm sm:text-base">Monitor team workload and available capacity</p>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <button
                    wire:click="setViewMode('grid')"
                    class="px-3 py-2 rounded {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow' : '' }}">
                    <i class="fas fa-th"></i>
                </button>
                <button 
                    wire:click="setViewMode('list')" 
                    class="px-3 py-2 rounded {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow' : '' }}">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            
            <flux:button wire:click="refresh" variant="ghost" size="sm" class="hidden sm:inline-flex">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </flux:button>
            <flux:button wire:click="refresh" variant="ghost" size="sm" class="sm:hidden w-full">
                <i class="fas fa-sync-alt"></i>
            </flux:button>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-2 sm:gap-3">
        <flux:button 
            wire:click="setSortBy('workload')" 
            variant="{{ $sortBy === 'workload' ? 'primary' : 'ghost' }}" 
            size="sm">
            Workload Score
            @if($sortBy === 'workload')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-2"></i>
            @endif
        </flux:button>
        
        <flux:button 
            wire:click="setSortBy('capacity')" 
            variant="{{ $sortBy === 'capacity' ? 'primary' : 'ghost' }}" 
            size="sm">
            Capacity %
            @if($sortBy === 'capacity')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-2"></i>
            @endif
        </flux:button>
        
        <flux:button 
            wire:click="setSortBy('active')" 
            variant="{{ $sortBy === 'active' ? 'primary' : 'ghost' }}" 
            size="sm">
            Active Tickets
            @if($sortBy === 'active')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-2"></i>
            @endif
        </flux:button>
        
        <flux:button 
            wire:click="setSortBy('overdue')" 
            variant="{{ $sortBy === 'overdue' ? 'primary' : 'ghost' }}" 
            size="sm">
            Overdue
            @if($sortBy === 'overdue')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-2"></i>
            @endif
        </flux:button>
        
        <flux:button 
            wire:click="setSortBy('name')" 
            variant="{{ $sortBy === 'name' ? 'primary' : 'ghost' }}" 
            size="sm">
            Name
            @if($sortBy === 'name')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-2"></i>
            @endif
        </flux:button>
    </div>

    @if($viewMode === 'grid')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($technicians as $tech)
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 dark:text-gray-300 text-xl"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $tech['user']->name }}</p>
                                <p class="text-xs text-gray-500">{{ $tech['user']->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Capacity</span>
                            <span class="text-sm font-semibold text-{{ $tech['status']['color'] }}-600">
                                {{ $tech['capacity_percentage'] }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-{{ $tech['status']['color'] }}-600 h-3 rounded-full" 
                                 style="width: {{ $tech['capacity_percentage'] }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <i class="{{ $tech['status']['icon'] }} text-{{ $tech['status']['color'] }}-600 text-sm"></i>
                            <span class="text-xs font-semibold text-{{ $tech['status']['color'] }}-600">
                                {{ $tech['status']['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <p class="text-2xl font-bold text-blue-600">{{ $tech['total_active'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Active</p>
                        </div>
                        <div class="text-center p-2 bg-red-50 dark:bg-red-900/20 rounded">
                            <p class="text-2xl font-bold text-red-600">{{ $tech['overdue_count'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Overdue</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Critical:</span>
                            <span class="font-semibold text-red-600">{{ $tech['critical_count'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">High:</span>
                            <span class="font-semibold text-orange-600">{{ $tech['high_count'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Resolved (7d):</span>
                            <span class="font-semibold text-green-600">{{ $tech['resolved_last_7_days'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Avg Resolution:</span>
                            <span class="font-semibold">{{ $tech['avg_resolution_time'] }}h</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Time Logged (week):</span>
                            <span class="font-semibold">{{ $tech['time_logged_this_week'] }}h</span>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <flux:card>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Technician</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Capacity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Active</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Overdue</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Critical</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">High</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Resolved (7d)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Avg Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($technicians as $tech)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600 dark:text-gray-300"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $tech['user']->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $tech['user']->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $tech['status']['color'] }}-100 text-{{ $tech['status']['color'] }}-800">
                                        {{ $tech['status']['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="w-24">
                                        <div class="text-sm font-semibold text-{{ $tech['status']['color'] }}-600 mb-1">
                                            {{ $tech['capacity_percentage'] }}%
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-{{ $tech['status']['color'] }}-600 h-2 rounded-full" 
                                                 style="width: {{ $tech['capacity_percentage'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-blue-600">{{ $tech['total_active'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-red-600">{{ $tech['overdue_count'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-red-600">{{ $tech['critical_count'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-orange-600">{{ $tech['high_count'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-green-600">{{ $tech['resolved_last_7_days'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm">{{ $tech['avg_resolution_time'] }}h</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>
