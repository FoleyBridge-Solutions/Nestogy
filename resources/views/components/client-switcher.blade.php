@props([
    'currentClient' => null,
    'placement' => 'bottom-start'
])

<div x-data="clientSwitcher()" 
     x-init="init()"
     @keydown.window="onKeyDown($event)"
     class="relative"
     {{ $attributes }}>
     
    <!-- Hidden data for JavaScript -->
    @if($currentClient)
        <div data-current-client="{{ json_encode($currentClient) }}" class="hidden"></div>
    @endif
    
    <!-- Trigger Button -->
    <button @click="toggle()" 
            type="button"
            class="group flex items-center space-x-3 px-4 py-2 rounded-xl bg-white/80 hover:bg-white shadow-sm hover:shadow-md border border-gray-200/60 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 backdrop-blur-sm"
            :class="{ 'ring-2 ring-indigo-500/20 border-indigo-300': open }">
        
        @if($currentClient)
            <!-- Current Client Avatar -->
            <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-semibold shadow-sm">
                    {{ substr($currentClient->name ?? '', 0, 2) }}
                </div>
            </div>
            
            <!-- Current Client Info -->
            <div class="flex-1 min-w-0 text-left">
                <div class="text-sm font-semibold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">
                    {{ $currentClient->name }}
                </div>
                @if($currentClient->company_name && $currentClient->company_name !== $currentClient->name)
                    <div class="text-xs text-gray-500 truncate">
                        {{ $currentClient->company_name }}
                    </div>
                @endif
            </div>
            
            <!-- Status Indicator -->
            <div class="flex items-center space-x-2">
                <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse shadow-sm"></div>
                <svg class="h-4 w-4 text-gray-400 group-hover:text-indigo-500 transition-all duration-200" 
                     :class="{ 'rotate-180': open }" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                </svg>
            </div>
        @else
            <!-- No Client Selected State -->
            <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-600">Select Client</span>
                <svg class="h-4 w-4 text-gray-400" 
                     :class="{ 'rotate-180': open }" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                </svg>
            </div>
        @endif
    </button>
    
    <!-- Dropdown Panel -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-1 scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-1 scale-100" 
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 mt-2 w-80 bg-white rounded-2xl shadow-xl ring-1 ring-black/5 backdrop-blur-sm border border-gray-100"
         :class="{
             'right-0': '{{ $placement }}' === 'bottom-end',
             'left-0': '{{ $placement }}' === 'bottom-start'
         }"
         style="display: none;">
        
        <div class="p-4">
            <!-- Search Input -->
            <div class="relative mb-3">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"></path>
                </svg>
                <input type="search" 
                       x-model="searchQuery"
                       @input="onSearch()"
                       placeholder="Search clients..."
                       class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200 bg-gray-50/50">
                
                <!-- Clear Search -->
                <button x-show="searchQuery.length > 0" 
                        @click="searchQuery = ''; onSearch()" 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2 text-gray-500">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm">Loading clients...</span>
                </div>
            </div>
            
            <!-- Dropdown Content -->
            <div x-show="!loading" 
                 data-dropdown-content
                 class="max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                
                <!-- Recent Clients Section -->
                <template x-if="hasRecentClients">
                    <div class="mb-2">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-2">
                            Recent Clients
                        </div>
                        <template x-for="(client, index) in recentClients.filter(c => !currentClient || c.id !== currentClient.id)" :key="client.id">
                            <button @click="selectClient(client)" 
                                    data-client-item
                                    class="w-full flex items-center space-x-3 px-3 py-2.5 text-left rounded-lg hover:bg-gray-50 transition-all duration-150 group"
                                    :class="{ 'bg-indigo-50 hover:bg-indigo-100': isItemSelected(index) }">
                                <div class="flex-shrink-0">
                                    <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-semibold">
                                        <span x-text="getClientInitials(client)"></span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate group-hover:text-indigo-600" x-text="client.name"></div>
                                    <template x-if="client.company_name && client.company_name !== client.name">
                                        <div class="text-xs text-gray-500 truncate" x-text="client.company_name"></div>
                                    </template>
                                </div>
                                <div class="text-xs text-indigo-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    Recent
                                </div>
                            </button>
                        </template>
                        
                        <!-- Divider -->
                        <div class="my-3 border-t border-gray-100"></div>
                    </div>
                </template>
                
                <!-- All Clients / Search Results -->
                <template x-if="!hasRecentClients && filteredClients.length > 0">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-2">
                        <span x-text="searchQuery ? 'Search Results' : 'All Clients'"></span>
                    </div>
                </template>
                
                <!-- Client List -->
                <template x-for="(client, index) in filteredClients" :key="client.id">
                    <button @click="selectClient(client)" 
                            data-client-item
                            class="w-full flex items-center space-x-3 px-3 py-2.5 text-left rounded-lg hover:bg-gray-50 transition-all duration-150 group"
                            :class="{ 'bg-indigo-50 hover:bg-indigo-100': isItemSelected(hasRecentClients ? recentClients.length + index : index) }">
                        <div class="flex-shrink-0">
                            <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center text-white text-xs font-semibold">
                                <span x-text="getClientInitials(client)"></span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate group-hover:text-indigo-600" x-text="client.name"></div>
                            <template x-if="client.company_name && client.company_name !== client.name">
                                <div class="text-xs text-gray-500 truncate" x-text="client.company_name"></div>
                            </template>
                        </div>
                        <div class="text-xs text-gray-400 group-hover:text-indigo-500 transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </button>
                </template>
                
                <!-- No Results -->
                <div x-show="showNoResults" class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div class="text-sm text-gray-500">
                        <p class="font-medium">No clients found</p>
                        <p>Try adjusting your search terms</p>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div x-show="showEmpty" class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <div class="text-sm text-gray-500">
                        <p class="font-medium">No clients available</p>
                        <p>Add your first client to get started</p>
                    </div>
                    <a href="{{ route('clients.create') }}" class="inline-flex items-center px-3 py-2 mt-4 text-sm font-medium text-indigo-600 bg-indigo-50 border border-transparent rounded-lg hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Client
                    </a>
                </div>
            </div>
            
            <!-- Footer Actions -->
            @if($currentClient)
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('clients.index') }}" 
                       class="text-xs text-gray-500 hover:text-indigo-600 font-medium transition-colors">
                        View All Clients
                    </a>
                    <button @click="clearSelection()" 
                            class="text-xs text-red-600 hover:text-red-700 font-medium transition-colors">
                        Clear Selection
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>