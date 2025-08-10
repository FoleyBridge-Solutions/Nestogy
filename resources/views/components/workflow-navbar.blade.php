@props(['activeDomain' => null])

@php
// Get selected client and workflow context
$selectedClient = \App\Services\NavigationService::getSelectedClient();
$currentWorkflow = $selectedClient ? \App\Services\NavigationService::getWorkflowContext($selectedClient) : null;
$urgentItems = \App\Services\NavigationService::getUrgentItems();
$todaysWork = \App\Services\NavigationService::getTodaysWork();

// Workflow-based navigation structure
$workflowNavigation = [
    'urgent' => [
        'name' => 'Urgent',
        'icon' => '🔥',
        'count' => $urgentItems['total'] ?? 0,
        'route' => 'dashboard',
        'params' => ['view' => 'urgent'],
        'color' => 'red',
        'description' => 'Critical items requiring immediate attention'
    ],
    'today' => [
        'name' => "Today's Work",
        'icon' => '⚡',
        'count' => $todaysWork['total'] ?? 0,
        'route' => 'dashboard',
        'params' => ['view' => 'today'],
        'color' => 'blue',
        'description' => 'Scheduled tasks and appointments for today'
    ],
    'scheduled' => [
        'name' => 'Scheduled',
        'icon' => '📋',
        'count' => $todaysWork['upcoming'] ?? 0,
        'route' => 'tickets.calendar.index',
        'params' => [],
        'color' => 'indigo',
        'description' => 'Upcoming work and appointments'
    ],
    'financial' => [
        'name' => 'Financial',
        'icon' => '💰',
        'count' => $urgentItems['financial'] ?? 0,
        'route' => 'financial.invoices.index',
        'params' => [],
        'color' => 'green',
        'description' => 'Payments, invoices, and financial matters'
    ],
    'reports' => [
        'name' => 'Reports',
        'icon' => '📊',
        'count' => 0,
        'route' => 'reports.index',
        'params' => [],
        'color' => 'purple',
        'description' => 'Analytics and business insights'
    ]
];

// Client workflow status detection
$workflowStatus = 'idle';
$statusColor = 'gray';
$statusText = 'Ready to work';

if ($selectedClient) {
    if ($urgentItems['client'][$selectedClient->id]['critical'] ?? 0 > 0) {
        $workflowStatus = 'critical';
        $statusColor = 'red';
        $statusText = 'Critical issues need attention';
    } elseif ($urgentItems['client'][$selectedClient->id]['urgent'] ?? 0 > 0) {
        $workflowStatus = 'urgent';
        $statusColor = 'orange';
        $statusText = 'Urgent items pending';
    } elseif ($todaysWork['client'][$selectedClient->id] ?? 0 > 0) {
        $workflowStatus = 'active';
        $statusColor = 'green';
        $statusText = 'Active work in progress';
    } elseif ($todaysWork['scheduled'][$selectedClient->id] ?? 0 > 0) {
        $workflowStatus = 'scheduled';
        $statusColor = 'blue';
        $statusText = 'Scheduled work upcoming';
    }
}
@endphp

<nav class="bg-gradient-to-r from-white via-slate-50 to-white backdrop-blur-md border-b border-gray-200/30 shadow-sm fixed w-full top-0 z-50" 
     x-data="modernNavbar()" x-init="init()">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16 gap-4">
            <!-- Left Section: Logo & Workflow Context -->
            <div class="flex items-center space-x-3 flex-shrink-0 min-w-0">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center group">
                        <x-application-logo class="block h-8 w-auto fill-current text-gray-800 group-hover:text-indigo-600 transition duration-150" />
                        <span class="ml-2 text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition duration-150 hidden sm:block">{{ config('app.name', 'Nestogy') }}</span>
                    </a>
                </div>

                <!-- Modern Client Switcher -->
                <div class="hidden xl:flex items-center">
                    <x-client-switcher :currentClient="$selectedClient" 
                                      placement="bottom-start"
                                      class="mr-4" />
                </div>
            </div>

            <!-- Center Section: Workflow Navigation -->
            <div class="hidden lg:flex bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 rounded-xl p-1.5 flex-1 items-center justify-center min-w-0 max-w-3xl mx-auto border border-blue-100/50 backdrop-blur-sm shadow-inner">
                <div class="flex items-center space-x-1 overflow-x-auto scrollbar-none">
                @foreach($workflowNavigation as $key => $workflow)
                @php
                $isActive = $activeDomain === $key;
                $hasItems = $workflow['count'] > 0;
                $tabClasses = $isActive
                    ? ($workflow['color'] === 'red' ? 'bg-red-50 text-red-700 border-red-200' :
                       ($workflow['color'] === 'orange' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                        ($workflow['color'] === 'blue' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                         ($workflow['color'] === 'indigo' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                          ($workflow['color'] === 'green' ? 'bg-green-50 text-green-700 border-green-200' :
                           ($workflow['color'] === 'purple' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                            'bg-gray-50 text-gray-700 border-gray-200'))))))
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-transparent';
                @endphp
                
                <a href="{{ route($workflow['route'], $workflow['params']) }}"
                   class="relative group flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border {{ $tabClasses }} hover:shadow-sm whitespace-nowrap flex-shrink-0"
                   title="{{ $workflow['description'] }}"
                    
                    <!-- Icon -->
                    <span class="text-sm mr-1.5">{{ $workflow['icon'] }}</span>
                    
                    <!-- Label -->
                    <span class="hidden sm:inline">{{ $workflow['name'] }}</span>
                    
                    <!-- Count Badge -->
                    @if($hasItems)
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                {{ $workflow['color'] === 'red' ? 'bg-red-100 text-red-800' :
                                   ($workflow['color'] === 'orange' ? 'bg-orange-100 text-orange-800' :
                                    ($workflow['color'] === 'blue' ? 'bg-blue-100 text-blue-800' :
                                     ($workflow['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-800' :
                                      ($workflow['color'] === 'green' ? 'bg-green-100 text-green-800' :
                                       ($workflow['color'] === 'purple' ? 'bg-purple-100 text-purple-800' :
                                        'bg-gray-100 text-gray-800'))))) }}">
                        {{ $workflow['count'] }}
                    </span>
                    @endif

                    <!-- Simplified Tooltip -->
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                        {{ $workflow['description'] }}
                    </div>
                </a>
                @endforeach
                </div>
            </div>

            <!-- Right Section: Search, Notifications, User Menu -->
            <div class="flex items-center space-x-2 flex-shrink-0">
                <!-- Modern Smart Search -->
                <div class="relative hidden md:block"
                     x-data="smartSearch()"
                     x-init="init()"
                     @click.away="closeResults()">
                    <div class="relative">
                        <input type="text"
                               placeholder="{{ $selectedClient ? 'Search...' : 'Search...' }}"
                               class="w-32 lg:w-48 pl-9 pr-3 py-2 border border-gray-200/60 rounded-lg text-sm bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 transition-all duration-200 placeholder-gray-400"
                               x-model="query"
                               @input.debounce.300ms="search()"
                               @focus="showResults = true; loadSuggestions(); searchFocused = true"
                               @blur="searchFocused = false"
                               @keydown.escape="closeResults()"
                               @keydown.arrow-down.prevent="navigateDown()"
                               @keydown.arrow-up.prevent="navigateUp()"
                               @keydown.enter.prevent="selectResult()">
                        
                        <!-- Enhanced Search Icon / Loading Indicator -->
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <div class="relative">
                                <svg x-show="!loading" class="h-5 w-5 transition-colors duration-200" :class="searchFocused ? 'text-indigo-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg x-show="loading" class="h-5 w-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Search Context Indicator -->
                        @if($selectedClient)
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <span class="text-xs text-indigo-600 bg-gradient-to-r from-indigo-50 to-indigo-100 px-2 py-1 rounded-full hidden lg:inline font-medium border border-indigo-200/50">Client</span>
                            <div class="w-2 h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full lg:hidden animate-pulse"></div>
                        </div>
                        @endif
                    </div>

                    <!-- Enhanced Search Results Dropdown -->
                    <div x-show="showResults && (results.length > 0 || suggestions.length > 0 || query.length > 0)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute top-full left-0 right-0 mt-2 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-gray-200/50 py-2 z-50 max-h-96 overflow-y-auto"
                         style="display: none;">
                        
                        <!-- Search Results -->
                        <template x-if="query.length > 0 && results.length > 0">
                            <div>
                                <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                    Search Results
                                </div>
                                <template x-for="(result, index) in results" :key="result.type + '-' + index">
                                    <a :href="result.url"
                                       class="block px-3 py-2 hover:bg-gray-50 cursor-pointer transition duration-150"
                                       :class="{ 'bg-indigo-50': selectedIndex === index }"
                                       @click="selectResult(result)">
                                        <div class="flex items-center">
                                            <span class="text-lg mr-3" x-text="result.icon"></span>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="result.title"></div>
                                                <div class="text-xs text-gray-500 truncate" x-text="result.subtitle"></div>
                                            </div>
                                            <template x-if="result.meta && result.meta.status">
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-red-100 text-red-800': result.meta.priority === 'critical',
                                                          'bg-orange-100 text-orange-800': result.meta.priority === 'urgent',
                                                          'bg-green-100 text-green-800': result.meta.status === 'completed',
                                                          'bg-blue-100 text-blue-800': result.meta.status === 'active',
                                                          'bg-gray-100 text-gray-800': true
                                                      }"
                                                      x-text="result.meta.status"></span>
                                            </template>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <!-- Suggestions (when no query or no results) -->
                        <template x-if="(query.length === 0 || results.length === 0) && suggestions.length > 0">
                            <div>
                                <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                    <template x-if="query.length === 0">Quick Actions</template>
                                    <template x-if="query.length > 0 && results.length === 0">No Results - Try These</template>
                                </div>
                                <template x-for="(suggestion, index) in suggestions" :key="suggestion.type + '-' + index">
                                    <a :href="suggestion.url"
                                       class="block px-3 py-2 hover:bg-gray-50 cursor-pointer transition duration-150"
                                       :class="{ 'bg-indigo-50': selectedIndex === (results.length + index) }"
                                       @click="selectResult(suggestion)">
                                        <div class="flex items-center">
                                            <span class="text-lg mr-3" x-text="suggestion.icon"></span>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-900" x-text="suggestion.title"></div>
                                                <template x-if="suggestion.context">
                                                    <div class="text-xs text-indigo-600" x-text="suggestion.context + ' context'"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <!-- No Results State -->
                        <template x-if="query.length > 0 && results.length === 0 && suggestions.length === 0 && !loading">
                            <div class="px-3 py-4 text-center text-sm text-gray-500">
                                <div class="mb-2">🔍</div>
                                <div>No results found for "<span x-text="query"></span>"</div>
                                <div class="text-xs text-gray-400 mt-1">Try a different search term</div>
                            </div>
                        </template>

                        <!-- Loading State -->
                        <template x-if="loading">
                            <div class="px-3 py-4 text-center text-sm text-gray-500">
                                <div class="flex items-center justify-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Searching...
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Enhanced Smart Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="relative p-2.5 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-all duration-200 ease-in-out rounded-xl hover:bg-gradient-to-br hover:from-gray-50 hover:to-gray-100 hover:shadow-sm transform hover:scale-105">
                        <svg class="h-6 w-6 transition-transform duration-200 hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5V9a7 7 0 00-14 0v3l-5 5h5a7 7 0 1014 0z"></path>
                        </svg>
                        
                        <!-- Notification Badge -->
                        @if($urgentItems['notifications'] ?? 0 > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold leading-none text-white bg-gradient-to-r from-red-500 to-red-600 rounded-full animate-pulse shadow-lg ring-2 ring-red-100">
                            {{ min($urgentItems['notifications'], 9) }}@if($urgentItems['notifications'] > 9)+@endif
                        </span>
                        @endif
                    </button>

                    <!-- Enhanced Notifications Dropdown -->
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 top-full mt-3 w-80 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-gray-200/50 py-3 z-50"
                         style="display: none;">
                        
                        <div class="px-4 py-3 border-b border-gray-100/60">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full animate-pulse"></div>
                                <h3 class="text-sm font-semibold text-gray-900">Smart Notifications</h3>
                            </div>
                        </div>
                        
                        <div class="max-h-64 overflow-y-auto">
                            <!-- Placeholder notifications -->
                            <div class="px-4 py-3 hover:bg-gradient-to-r hover:from-red-50 hover:to-red-50/30 cursor-pointer border-l-4 border-red-400 transition-all duration-200 rounded-r-lg mx-1">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-3 h-3 bg-gradient-to-r from-red-400 to-red-500 rounded-full mt-1.5 animate-pulse"></div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-semibold text-gray-900">SLA Breach Alert</p>
                                        <p class="text-xs text-gray-600 mt-1">Ticket #1234 exceeds response time</p>
                                        <p class="text-xs text-gray-400 mt-1 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            2 minutes ago
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-4 py-2 text-center text-sm text-gray-500">
                                Smart notifications will appear here...
                            </div>
                        </div>
                        
                        <div class="px-4 py-3 border-t border-gray-100/60">
                            <a href="#" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-200 flex items-center group">
                                View all notifications
                                <svg class="w-3 h-3 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Profile Menu -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-xl text-gray-600 bg-white/80 backdrop-blur-sm hover:text-gray-800 hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-100 focus:outline-none transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105">
                            <div class="flex items-center">
                                <img class="h-8 w-8 rounded-full mr-2 ring-2 ring-white/60 shadow-sm"
                                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF"
                                     alt="{{ Auth::user()->name }}">
                                <div class="hidden lg:block text-sm truncate max-w-24">{{ Str::limit(Auth::user()->name, 15) }}</div>
                            </div>

                            <div class="ml-1 hidden lg:block">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('dashboard')">
                            {{ __('Dashboard') }}
                        </x-dropdown-link>
                        
                        <x-dropdown-link :href="route('users.profile')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @can('access', 'settings')
                            <x-dropdown-link :href="route('settings.index')">
                                {{ __('Settings') }}
                            </x-dropdown-link>
                        @endcan

                        <div class="border-t border-gray-100"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

                <!-- Mobile Menu Button -->
                <div class="lg:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" 
                            class="inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="xl:hidden bg-white border-t border-gray-200"
         style="display: none;">
        
        <!-- Mobile Client Switcher -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <x-client-switcher :currentClient="$selectedClient" 
                              placement="bottom-start" />
        </div>

        <!-- Mobile Workflow Navigation -->
        <div class="px-4 py-3 space-y-2">
            @foreach($workflowNavigation as $key => $workflow)
            <a href="{{ route($workflow['route'], $workflow['params']) }}"
               class="flex items-center justify-between w-full px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition duration-150">
                <div class="flex items-center">
                    <span class="text-lg mr-3">{{ $workflow['icon'] }}</span>
                    <span>{{ $workflow['name'] }}</span>
                </div>
                @if($workflow['count'] > 0)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $workflow['color'] === 'red' ? 'bg-red-100 text-red-800' : ($workflow['color'] === 'orange' ? 'bg-orange-100 text-orange-800' : ($workflow['color'] === 'blue' ? 'bg-blue-100 text-blue-800' : ($workflow['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-800' : ($workflow['color'] === 'green' ? 'bg-green-100 text-green-800' : ($workflow['color'] === 'purple' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))))) }}">
                    {{ $workflow['count'] }}
                </span>
                @endif
            </a>
            @endforeach
        </div>

        <!-- Mobile User Menu -->
        <div class="border-t border-gray-200 px-4 py-3">
            <div class="flex items-center mb-3">
                <img class="h-10 w-10 rounded-full" 
                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" 
                     alt="{{ Auth::user()->name }}">
                <div class="ml-3">
                    <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>
            
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition duration-150">
                    Dashboard
                </a>
                <a href="{{ route('users.profile') }}" class="block px-3 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition duration-150">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left block px-3 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition duration-150">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Workflow Navigation (hidden on desktop) -->
    <div class="lg:hidden bg-white border-b border-gray-200/60 px-4 py-2">
        <div class="flex items-center space-x-2 overflow-x-auto scrollbar-none">
            @foreach($workflowNavigation as $key => $workflow)
            @php
            $isActive = $activeDomain === $key;
            $hasItems = $workflow['count'] > 0;
            $mobileClasses = $isActive
                ? 'bg-indigo-100 text-indigo-700 border-indigo-200'
                : 'text-gray-600 hover:bg-gray-100 border-transparent';
            @endphp
            
            <a href="{{ route($workflow['route'], $workflow['params']) }}"
               class="flex items-center px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 border whitespace-nowrap flex-shrink-0 {{ $mobileClasses }}">
                <span class="text-sm mr-1.5">{{ $workflow['icon'] }}</span>
                <span>{{ $workflow['name'] }}</span>
                @if($hasItems)
                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $workflow['color'] === 'red' ? 'bg-red-100 text-red-800' : ($workflow['color'] === 'orange' ? 'bg-orange-100 text-orange-800' : ($workflow['color'] === 'blue' ? 'bg-blue-100 text-blue-800' : ($workflow['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-800' : ($workflow['color'] === 'green' ? 'bg-green-100 text-green-800' : ($workflow['color'] === 'purple' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))))) }}">
                    {{ $workflow['count'] }}
                </span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</nav>

<!-- Modern Navbar Alpine.js Component -->
<script>
function modernNavbar() {
    return {
        searchFocused: false,
        darkMode: localStorage.getItem('darkMode') === 'true' || false,
        
        init() {
            this.applyTheme();
            this.setupKeyboardShortcuts();
        },
        
        applyTheme() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            this.applyTheme();
            
            // Emit theme change event
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { darkMode: this.darkMode }
            }));
        },
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + K for search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    const searchInput = document.querySelector('input[type="text"]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
            });
        }
    };
}

function smartSearch() {
    return {
        query: '',
        results: [],
        suggestions: [],
        showResults: false,
        loading: false,
        selectedIndex: -1,
        selectedClient: @json($selectedClient ? $selectedClient->id : null),
        
        init() {
            // Initialize search functionality
            this.$nextTick(() => {
                this.loadSuggestions();
            });
        },
        
        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.showResults = this.suggestions.length > 0;
                return;
            }
            
            this.loading = true;
            this.showResults = true;
            
            try {
                const params = new URLSearchParams({
                    query: this.query,
                    context: this.selectedClient ? 'client' : 'global',
                    client_id: this.selectedClient || '',
                    domain: this.getCurrentDomain()
                });
                
                const response = await fetch(`/api/search/query?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.results = data.results || [];
                    this.selectedIndex = -1;
                } else {
                    console.error('Search request failed:', response.statusText);
                    this.results = [];
                }
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        async loadSuggestions() {
            // Disable search suggestions for now
            this.suggestions = [
                {
                    title: 'Create New Ticket',
                    url: '/tickets/create',
                    icon: '🎫',
                    type: 'action'
                },
                {
                    title: 'View Invoices',
                    url: '/financial/invoices',
                    icon: '💰',
                    type: 'action'
                },
                {
                    title: 'Client List',
                    url: '/clients',
                    icon: '👥',
                    type: 'action'
                }
            ];
        },
        
        getCurrentDomain() {
            // Detect current domain from URL or route
            const path = window.location.pathname;
            if (path.includes('/tickets')) return 'tickets';
            if (path.includes('/clients')) return 'clients';
            if (path.includes('/assets')) return 'assets';
            if (path.includes('/financial')) return 'financial';
            if (path.includes('/projects')) return 'projects';
            if (path.includes('/reports')) return 'reports';
            return 'global';
        },
        
        navigateDown() {
            const totalItems = this.results.length + this.suggestions.length;
            if (totalItems > 0) {
                this.selectedIndex = Math.min(this.selectedIndex + 1, totalItems - 1);
            }
        },
        
        navigateUp() {
            if (this.selectedIndex > -1) {
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            }
        },
        
        selectResult(result = null) {
            if (result) {
                // Direct result selection
                window.location.href = result.url;
                return;
            }
            
            // Keyboard selection
            const totalResults = this.results.length;
            let selectedResult = null;
            
            if (this.selectedIndex >= 0 && this.selectedIndex < totalResults) {
                selectedResult = this.results[this.selectedIndex];
            } else if (this.selectedIndex >= totalResults) {
                const suggestionIndex = this.selectedIndex - totalResults;
                if (suggestionIndex < this.suggestions.length) {
                    selectedResult = this.suggestions[suggestionIndex];
                }
            }
            
            if (selectedResult) {
                window.location.href = selectedResult.url;
            }
        },
        
        closeResults() {
            this.showResults = false;
            this.selectedIndex = -1;
        },
        
        handleResultClick(result) {
            // Track search analytics (placeholder)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'search_result_click', {
                    'search_term': this.query,
                    'result_type': result.type,
                    'result_title': result.title
                });
            }
            
            window.location.href = result.url;
        }
    }
}
</script>