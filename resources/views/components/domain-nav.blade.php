@props(['activeDomain' => null])

@php
// Get selected client for client-aware navigation
$selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();

$domains = [
    'clients' => [
        'name' => $selectedClient ? $selectedClient->name : 'Clients',
        'route' => $selectedClient ? 'clients.show' : 'clients.index',
        'params' => $selectedClient ? [$selectedClient] : [],
        'icon' => 'users',
        'subtitle' => $selectedClient ? 'Selected Client' : null
    ],
    'tickets' => [
        'name' => 'Tickets',
        'route' => 'tickets.index',
        'params' => [],
        'icon' => 'ticket'
    ],
    'assets' => [
        'name' => 'Assets',
        'route' => 'assets.index',
        'params' => [],
        'icon' => 'computer-desktop'
    ],
    'financial' => [
        'name' => 'Financial',
        'route' => 'financial.invoices.index',
        'params' => [],
        'icon' => 'currency-dollar'
    ],
    'projects' => [
        'name' => 'Projects',
        'route' => 'projects.index',
        'params' => [],
        'icon' => 'folder'
    ],
    'reports' => [
        'name' => 'Reports',
        'route' => 'reports.index',
        'params' => [],
        'icon' => 'chart-bar'
    ],
    'products' => [
        'name' => 'Products',
        'route' => 'products.index',
        'params' => [],
        'icon' => 'cube'
    ],
    'leads' => [
        'name' => 'Leads',
        'route' => 'leads.index',
        'params' => [],
        'icon' => 'user-plus'
    ],
    'marketing' => [
        'name' => 'Marketing',
        'route' => 'marketing.campaigns.index',
        'params' => [],
        'icon' => 'megaphone'
    ]
];
@endphp

<nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                        <span class="ml-2 text-xl font-semibold text-gray-900">{{ config('app.name', 'Nestogy ERP') }}</span>
                    </a>
                </div>

                <!-- Domain Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    @foreach ($domains as $domainKey => $domain)
                        @php
                        $isActive = $activeDomain === $domainKey;
                        $classes = $isActive
                            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-medium leading-5 text-indigo-600 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
                            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
                        @endphp
                        
                        @php
                        $routeUrl = !empty($domain['params'])
                            ? route($domain['route'], $domain['params'])
                            : route($domain['route']);
                        @endphp
                        
                        <a href="{{ $routeUrl }}" class="{{ $classes }}" @if($domainKey === 'clients' && $selectedClient) title="Currently working with {{ $selectedClient->name }}" @endif>
                            <div class="flex flex-flex-1 px-6 items-center">
                                <span>{{ $domain['name'] }}</span>
                                @if(isset($domain['subtitle']) && $domain['subtitle'])
                                    <span class="text-xs text-indigo-400">{{ $domain['subtitle'] }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- User Navigation -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- Client Context Indicator -->
                @if($selectedClient)
                    <div class="mr-4 px-6 py-1 bg-blue-50 border border-blue-200 rounded-md">
                        <div class="flex items-center text-sm">
                            <div class="h-2 w-2 bg-blue-500 rounded-full mr-2"></div>
                            <span class="text-blue-700 font-medium">{{ Str::limit($selectedClient->name, 20) }}</span>
                            <a href="{{ route('clients.switch') }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">Switch</a>
                        </div>
                    </div>
                @endif
                <!-- Quick Search -->
                <div class="relative mr-4">
                    <input type="text"
                           placeholder="Quick search..."
                           class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           x-data="{ open: false }"
                           @focus="open = true"
                           @blur="open = false">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Notifications -->
                <button class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition duration-150 ease-in-out mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5V9a7 7 0 00-14 0v3l-5 5h5a7 7 0 1014 0z"></path>
                    </svg>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full ring-2 ring-white bg-red-400"></span>
                </button>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-6 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center">
                                <img class="h-8 w-8 rounded-full mr-2" 
                                     src="{{ Auth::user()->getAvatarUrl() }}" 
                                     alt="{{ Auth::user()->name }}">
                                <div>{{ Auth::user()->name }}</div>
                            </div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('users.profile')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @can('access', 'settings')
                            <x-dropdown-link :href="route('settings.index')">
                                {{ __('Settings') }}
                            </x-dropdown-link>
                        @endcan


                        <div class="border-t border-gray-100"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Mobile menu button -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" x-data="{ open: false }">
        <!-- Client Context for Mobile -->
        @if($selectedClient)
            <div class="px-6 py-6 bg-blue-50 border-b border-blue-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-2 w-2 bg-blue-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium text-blue-700">{{ $selectedClient->name }}</span>
                    </div>
                    <a href="{{ route('clients.switch') }}" class="text-xs text-blue-600 hover:text-blue-800">Switch Client</a>
                </div>
            </div>
        @endif

        <div class="pt-2 pb-3 space-y-1 bg-gray-50">
            @foreach ($domains as $domainKey => $domain)
                @php
                $isActive = $activeDomain === $domainKey;
                $classes = $isActive
                    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium'
                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium';
                @endphp
                
                @php
                $routeUrl = !empty($domain['params'])
                    ? route($domain['route'], $domain['params'])
                    : route($domain['route']);
                @endphp
                
                <a href="{{ $routeUrl }}" class="{{ $classes }}">
                    {{ $domain['name'] }}
                    @if(isset($domain['subtitle']) && $domain['subtitle'])
                        <span class="text-xs text-indigo-400 block">{{ $domain['subtitle'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Mobile User Menu -->
        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            <div class="px-6">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-6 space-y-1">
                <x-responsive-nav-link :href="route('users.profile')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @can('access', 'settings')
                    <x-responsive-nav-link :href="route('settings.index')">
                        {{ __('Settings') }}
                    </x-responsive-nav-link>
                @endcan


                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
