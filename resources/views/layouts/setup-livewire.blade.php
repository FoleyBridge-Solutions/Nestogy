<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Apply theme IMMEDIATELY before any CSS loads -->
    <script>
        const guestTheme = localStorage.getItem('guest_theme') || 'auto';
        if (guestTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (guestTheme === 'light') {
            document.documentElement.classList.remove('dark');
        } else if (guestTheme === 'auto') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    </script>

    <title>{{ config('app.name', 'Nestogy ERP') }} - @yield('title', 'Setup')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Guest Theme Context for JavaScript -->
    <script>
        window.GUEST_THEME = localStorage.getItem('guest_theme') || 'auto';
    </script>
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col">
        <!-- Simplified Setup Navigation -->
        <flux:header class="px-4! w-full bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <flux:brand>
                <x-slot name="logo">
                    <div class="flex items-center">
                        <img src="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="h-8 w-auto">
                        <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">Setup Wizard</span>
                    </div>
                </x-slot>
            </flux:brand>
            <flux:spacer />
            <div class="flex items-center">
                <flux:text class="text-sm">
                    Initial System Setup
                </flux:text>
            </div>
        </flux:header>

        <!-- Page Content -->
        <main class="flex-grow">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <flux:callout variant="success" icon="check-circle">
                        <p>{!! session('success') !!}</p>
                    </flux:callout>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <flux:callout variant="danger" icon="x-circle">
                        <p>{!! session('error') !!}</p>
                    </flux:callout>
                </div>
            @endif

            @if ($errors->any())
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </flux:callout>
                </div>
            @endif

            {{ $slot }}
        </main>

        <!-- Simplified Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Nestogy ERP') }}. All rights reserved.
                    <div class="mt-1 text-xs">
                        Setting up your MSP business management platform
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
