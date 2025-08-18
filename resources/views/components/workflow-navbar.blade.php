@props(['activeDomain' => null])

@php
// Get navigation context
$selectedClient = \App\Services\NavigationService::getSelectedClient();
$currentWorkflow = \App\Services\NavigationService::getWorkflowContext(); 
$workflowHighlights = \App\Services\NavigationService::getWorkflowNavigationHighlights($currentWorkflow);

// Prepare badge counts
$badges = [
    'urgent' => $workflowHighlights['badges']['urgent'] ?? 0,
    'today' => $workflowHighlights['badges']['today'] ?? 0,
    'scheduled' => $workflowHighlights['badges']['scheduled'] ?? 0,
    'financial' => $workflowHighlights['badges']['financial'] ?? 0,
];

// Recent clients query removed - now handled by client-switcher component

// Workflow items configuration
$workflowItems = [
    [
        'key' => 'urgent',
        'label' => 'Urgent',
        'icon' => '🔥',
        'color' => 'red',
        'count' => $badges['urgent'],
        'route' => route('dashboard', ['view' => 'urgent']),
        'description' => 'Critical items requiring immediate attention'
    ],
    [
        'key' => 'today',
        'label' => "Today",
        'icon' => '⚡',
        'color' => 'blue',
        'count' => $badges['today'],
        'route' => route('dashboard', ['view' => 'today']),
        'description' => 'Scheduled tasks for today'
    ],
    [
        'key' => 'scheduled',
        'label' => 'Scheduled',
        'icon' => '📋',
        'color' => 'indigo',
        'count' => $badges['scheduled'],
        'route' => route('dashboard', ['view' => 'scheduled']),
        'description' => 'Upcoming work and appointments'
    ],
    [
        'key' => 'financial',
        'label' => 'Financial',
        'icon' => '💰',
        'color' => 'green',
        'count' => $badges['financial'],
        'route' => route('dashboard', ['view' => 'financial']),
        'description' => 'Invoices and payments'
    ],
];
@endphp

<!-- Main Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm"
     x-data="{ 
         mobileMenuOpen: false,
         userMenuOpen: false,
         notificationsOpen: false,
         clientSwitcherOpen: false
     }">
    
    <!-- Primary Navigation Bar -->
    <div class="mx-auto max-w-full">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
            
            <!-- Left Section: Mobile Menu, Logo & Client Context -->
            <div class="flex items-center space-x-3">
                <!-- Mobile Sidebar Toggle -->
                @if($activeDomain ?? false)
                    <button @click="$dispatch('toggle-mobile-sidebar')"
                            class="lg:hidden p-3 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors touch-manipulation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                @endif
                
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center group">
                    <x-application-logo class="h-8 w-auto text-gray-800 group-hover:text-indigo-600 transition-colors" />
                    <span class="ml-2 text-lg font-semibold text-gray-900 hidden sm:block">
                        {{ Auth::user()?->company?->name ?? config('app.name', 'Nestogy') }}
                    </span>
                </a>
                
                <!-- Divider -->
                <div class="hidden lg:block h-6 w-px bg-gray-300"></div>
                
                <!-- Client Switcher Component -->
                <div class="hidden lg:block">
                    <x-client-switcher :current-client="$selectedClient" />
                </div>
            </div>
            
            <!-- Center Section: Workflow Pills -->
            <div class="hidden lg:flex items-center space-x-2">
                @foreach($workflowItems as $item)
                <a href="{{ $item['route'] }}"
                   class="group relative flex items-center space-x-2 px-4 py-3 min-h-[44px] rounded-lg transition-all duration-200 touch-manipulation
                          {{ $currentWorkflow === $item['key'] 
                             ? 'bg-' . $item['color'] . '-50 text-' . $item['color'] . '-700 ring-1 ring-' . $item['color'] . '-200' 
                             : 'text-gray-600 hover:bg-gray-50' }}">
                    <span class="text-lg">{{ $item['icon'] }}</span>
                    <span class="font-medium text-base">{{ $item['label'] }}</span>
                    @if($item['count'] > 0)
                    <span class="ml-1 inline-flex items-center justify-center min-w-[24px] min-h-[24px] px-2 py-1 text-sm font-bold rounded-full
                                bg-{{ $item['color'] }}-100 text-{{ $item['color'] }}-800">
                        {{ $item['count'] > 99 ? '99+' : $item['count'] }}
                    </span>
                    @endif
                    
                    <!-- Tooltip -->
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                        {{ $item['description'] }}
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                            <div class="border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            
            <!-- Right Section: Actions -->
            <div class="flex items-center space-x-3">
                <!-- Command Palette Button -->
                <button onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                        class="hidden md:flex items-center space-x-2 px-4 py-3 min-h-[44px] bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors group touch-manipulation">
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span class="text-base text-gray-600 group-hover:text-gray-900">Search</span>
                    <kbd class="hidden lg:inline-flex items-center px-2 py-1 text-sm text-gray-500 bg-white border border-gray-300 rounded">
                        Ctrl+/
                    </kbd>
                </button>
                
                <!-- Notifications -->
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen"
                            class="relative p-3 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors touch-manipulation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @if($badges['urgent'] > 0)
                        <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[20px] min-h-[20px] text-xs font-bold text-white bg-red-500 rounded-full">
                            {{ min($badges['urgent'], 9) }}{{ $badges['urgent'] > 9 ? '+' : '' }}
                        </span>
                        @endif
                    </button>
                    
                    <!-- Notifications Dropdown -->
                    <div x-show="notificationsOpen"
                     @click.away="notificationsOpen = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 top-full mt-2 w-96 max-h-[70vh] bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                     x-cloak>
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-sm text-gray-500 text-center">
                            No new notifications
                        </div>
                    </div>
                    <div class="p-3 border-t border-gray-200">
                        <a href="#" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            View all notifications →
                        </a>
                    </div>
                </div>
                </div>
                
                <!-- User Menu -->
                <div class="relative">
                    @auth
                    <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center space-x-2 p-3 min-h-[44px] rounded-lg hover:bg-gray-50 transition-colors touch-manipulation">
                        <img class="h-8 w-8 rounded-full ring-2 ring-white shadow-sm"
                             src="{{ Auth::user()->getAvatarUrl() }}"
                             alt="{{ Auth::user()->name }}">
                        <svg class="hidden lg:block w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div x-show="userMenuOpen"
                         @click.away="userMenuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-64 max-h-[60vh] overflow-y-auto bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                         x-cloak>
                        <div class="px-4 py-2 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                        </div>
                        <a href="{{ route('users.profile') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profile
                            </div>
                        </a>
                        <a href="{{ route('settings.index') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Settings
                            </div>
                        </a>
                        @if(auth()->id() === 1)
                            <a href="{{ route('admin.console') }}" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Admin Console
                                </div>
                            </a>
                        @endif
                        <div class="border-t border-gray-200 my-2"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sign out
                                </div>
                            </button>
                        </form>
                    </div>
                    @else
                    <!-- Guest user - show login button -->
                    <a href="{{ route('login') }}" class="flex items-center space-x-2 p-3 min-h-[44px] rounded-lg hover:bg-gray-50 transition-colors touch-manipulation">
                        <span class="text-sm font-medium text-gray-700">Sign In</span>
                    </a>
                    @endauth
                </div>
                
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-3 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors touch-manipulation">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Workflow Pills -->
    <div class="lg:hidden border-t border-gray-200 bg-gray-50">
        <div class="flex overflow-x-auto px-4 py-2 space-x-2">
            @foreach($workflowItems as $item)
            <a href="{{ $item['route'] }}"
               class="flex-shrink-0 flex items-center space-x-2 px-4 py-3 min-h-[44px] rounded-lg text-base font-medium whitespace-nowrap touch-manipulation
                      {{ $currentWorkflow === $item['key'] 
                         ? 'bg-' . $item['color'] . '-100 text-' . $item['color'] . '-700' 
                         : 'text-gray-600 bg-white' }}">
                <span class="text-lg">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
                @if($item['count'] > 0)
                <span class="ml-1 inline-flex items-center justify-center min-w-[20px] min-h-[20px] px-1 text-sm font-bold rounded-full bg-current bg-opacity-20">{{ $item['count'] }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden border-t border-gray-200 bg-white"
         x-cloak>
        <div class="px-4 py-3 space-y-3">
            <!-- Mobile Search -->
            <button onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                    class="w-full flex items-center justify-center space-x-2 px-4 py-3 min-h-[48px] bg-indigo-600 text-white rounded-lg touch-manipulation">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="text-base font-medium">Quick Search</span>
            </button>
            
            <!-- Mobile Client Switcher -->
            <div class="border rounded-lg p-3 {{ $selectedClient ? 'bg-indigo-50' : 'bg-gray-50' }}">
                @if($selectedClient)
                    <p class="text-xs text-gray-500 mb-1">Current Client</p>
                    <p class="font-medium text-gray-900">{{ $selectedClient->name }}</p>
                    <a href="{{ route('clients.clear-selection') }}" class="text-xs text-indigo-600 hover:text-indigo-800">
                        Clear Selection →
                    </a>
                @else
                    <p class="text-xs text-gray-500 mb-2">No Client Selected</p>
                @endif
                <a href="{{ route('clients.index') }}" 
                   class="block mt-2 text-center px-4 py-3 min-h-[44px] bg-white border border-gray-300 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-50 touch-manipulation flex items-center justify-center">
                    {{ $selectedClient ? 'Change Client' : 'Select Client' }}
                </a>
            </div>
            
            <!-- Mobile Navigation Links -->
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 min-h-[48px] text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg touch-manipulation flex items-center">
                    Dashboard
                </a>
                <a href="{{ route('tickets.index') }}" class="block px-4 py-3 min-h-[48px] text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg touch-manipulation flex items-center">
                    Tickets
                </a>
                <a href="{{ route('clients.index') }}" class="block px-4 py-3 min-h-[48px] text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg touch-manipulation flex items-center">
                    Clients
                </a>
                <a href="{{ route('financial.invoices.index') }}" class="block px-4 py-3 min-h-[48px] text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg touch-manipulation flex items-center">
                    Billing
                </a>
            </nav>
        </div>
    </div>
</nav>

<style>
/* Hide x-cloak elements until Alpine loads */
[x-cloak] { display: none !important; }

/* Fix for Tailwind color classes that might be purged */
.bg-red-50 { background-color: rgb(254 242 242); }
.text-red-700 { color: rgb(185 28 28); }
.ring-red-200 { --tw-ring-color: rgb(254 202 202); }
.bg-red-100 { background-color: rgb(254 226 226); }
.text-red-800 { color: rgb(153 27 27); }

.bg-blue-50 { background-color: rgb(239 246 255); }
.text-blue-700 { color: rgb(29 78 216); }
.ring-blue-200 { --tw-ring-color: rgb(191 219 254); }
.bg-blue-100 { background-color: rgb(219 234 254); }
.text-blue-800 { color: rgb(30 64 175); }

.bg-indigo-50 { background-color: rgb(238 242 255); }
.text-indigo-700 { color: rgb(67 56 202); }
.ring-indigo-200 { --tw-ring-color: rgb(199 210 254); }
.bg-indigo-100 { background-color: rgb(224 231 255); }
.text-indigo-800 { color: rgb(55 48 163); }

.bg-green-50 { background-color: rgb(240 253 244); }
.text-green-700 { color: rgb(21 128 61); }
.ring-green-200 { --tw-ring-color: rgb(187 247 208); }
.bg-green-100 { background-color: rgb(220 252 231); }
.text-green-800 { color: rgb(22 101 52); }
</style>