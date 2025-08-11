<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nestogy ERP') }} - @yield('title', 'Welcome')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Simple Navigation for Guest Pages -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ url('/') }}">
                                <img src="{{ asset('assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="h-8 w-auto">
                            </a>
                        </div>
                    </div>

                    <!-- Guest Navigation Links -->
                    <div class="hidden sm:flex sm:items-center sm:space-x-8">
                        @guest
                            <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                                {{ __('Login') }}
                            </a>
                            @if (Route::has('signup.form'))
                                <a href="{{ route('signup.form') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    {{ __('Start Free Trial') }}
                                </a>
                            @endif
                        @endguest
                        
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                                {{ __('Dashboard') }}
                            </a>
                        @endauth
                    </div>

                    <!-- Mobile menu button -->
                    <div class="sm:hidden flex items-center">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">Open main menu</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="sm:hidden" id="mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    @guest
                        <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                            {{ __('Login') }}
                        </a>
                        @if (Route::has('signup.form'))
                            <a href="{{ route('signup.form') }}" class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                                {{ __('Start Free Trial') }}
                            </a>
                        @endif
                    @endguest
                    
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                            {{ __('Dashboard') }}
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Nestogy ERP') }}. All rights reserved.
                </div>
            </div>
        </footer>
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>