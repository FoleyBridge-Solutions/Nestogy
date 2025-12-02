<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>🚨 ADMIN LAYOUT IS WORKING 🚨 - Platform Admin - {{ config('app.name', 'Nestogy') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Flux Appearance -->
    @fluxAppearance
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="h-screen overflow-hidden bg-zinc-50 dark:bg-zinc-900">
    <!-- Admin Header -->
    <flux:header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-50">
        <flux:navbar class="w-full px-4">
            <!-- Platform Admin Branding -->
            <div class="flex items-center gap-3">
                <flux:icon.building-office-2 class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">Platform Admin</flux:heading>
            </div>
            
            <flux:spacer />
            
            <!-- Admin Navigation -->
            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item 
                    icon="chart-bar" 
                    href="{{ route('admin.dashboard') }}"
                    :current="request()->routeIs('admin.dashboard')">
                    Dashboard
                </flux:navbar.item>
                
                <flux:navbar.item 
                    icon="building-office-2" 
                    href="{{ route('admin.companies.index') }}"
                    :current="request()->routeIs('admin.companies.*')">
                    Companies
                </flux:navbar.item>
                
                <flux:navbar.item 
                    icon="credit-card" 
                    href="{{ route('admin.billing.index') }}"
                    :current="request()->routeIs('admin.billing.*')">
                    Billing
                </flux:navbar.item>
                
                <flux:navbar.item 
                    icon="chart-pie" 
                    href="{{ route('admin.analytics.index') }}"
                    :current="request()->routeIs('admin.analytics.*')">
                    Analytics
                </flux:navbar.item>
            </flux:navbar>
            
            <flux:spacer />
            
            <!-- Back to Main App -->
            <flux:navbar.item 
                icon="arrow-left" 
                href="{{ route('dashboard') }}"
                aria-label="Back to Main App">
                Back to App
            </flux:navbar.item>
            
            <!-- Profile Dropdown -->
            @auth
            <flux:dropdown align="end">
                <flux:profile name="{{ auth()->user()->name }}" />
                
                <flux:navmenu class="max-w-[12rem]">
                    <div class="px-2 py-1.5">
                        <flux:text size="sm">Signed in as</flux:text>
                        <flux:heading class="mt-1! truncate">{{ auth()->user()->email }}</flux:heading>
                    </div>
                    
                    <flux:navmenu.separator />
                    
                    <flux:navmenu.item href="{{ route('dashboard') }}" icon="home" class="text-zinc-800 dark:text-white">Main Dashboard</flux:navmenu.item>
                    <flux:navmenu.item href="{{ route('settings.index') }}" icon="cog-6-tooth" class="text-zinc-800 dark:text-white">Settings</flux:navmenu.item>
                    
                    <flux:navmenu.separator />
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:navmenu.item type="submit" icon="arrow-right-start-on-rectangle" class="text-zinc-800 dark:text-white">
                            Sign out
                        </flux:navmenu.item>
                    </form>
                </flux:navmenu>
            </flux:dropdown>
            @endauth
        </flux:navbar>
    </flux:header>

    <!-- Main Content Area (No Sidebar) -->
    <flux:main class="overflow-y-auto h-[calc(100vh-64px)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </div>
    </flux:main>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
