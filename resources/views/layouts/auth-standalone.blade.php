<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @PwaHead

    @fluxAppearance

    <!-- Apply theme IMMEDIATELY before any CSS loads -->
    <script>
        const authTheme = localStorage.getItem('auth_theme') || 'auto';
        if (authTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (authTheme === 'light') {
            document.documentElement.classList.remove('dark');
        } else if (authTheme === 'auto') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    </script>

    <title>{{ config('app.name', 'Nestogy ERP') }} - @yield('title', 'Authentication')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Auth Theme Context for JavaScript -->
    <script>
        window.AUTH_THEME = localStorage.getItem('auth_theme') || 'auto';
    </script>
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="flex min-h-screen flex-col-span-12 justify-center py-12 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mx-auto w-full max-w-sm mb-8">
                <flux:toast variant="success" class="w-full">
                    <p>{!! session('success') !!}</p>
                </flux:toast>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto w-full max-w-sm mb-8">
                <flux:toast variant="danger" class="w-full">
                    <p>{!! session('error') !!}</p>
                </flux:toast>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto w-full max-w-sm mb-8">
                <flux:toast variant="danger" class="w-full">
                    <div class="font-medium mb-2">Please correct the following errors:</div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:toast>
            </div>
        @endif

        <!-- Logo and Header -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <flux:brand 
                href="/" 
                logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" 
                name="{{ config('app.name', 'Nestogy ERP') }}"
                class="mx-auto flex justify-center" />
            
            <flux:heading size="lg" class="mt-6 text-center text-gray-900 dark:text-white">
                @yield('heading', 'Welcome Back')
            </flux:heading>
            
            @hasSection('subheading')
            <flux:text class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                @yield('subheading')
            </flux:text>
            @endif
        </div>

        <!-- Main Content -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <flux:card class="bg-white dark:bg-zinc-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                @yield('content')
            </flux:card>
        </div>

        <!-- Footer -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Nestogy ERP') }}. All rights reserved.
                </flux:text>
                
                <div class="mt-2">
                    <flux:button variant="ghost" size="sm" href="mailto:support@nestogy.com">
                        Need help? Contact Support
                    </flux:button>
                </div>
                
                <!-- Theme Toggle -->
                <div class="mt-4 flex justify-center space-x-2">
                    <flux:button variant="ghost" size="sm" onclick="toggleAuthTheme('light')" 
                               class="text-xs" id="light-theme-btn">
                        ☀️ Light
                    </flux:button>
                    <flux:button variant="ghost" size="sm" onclick="toggleAuthTheme('dark')" 
                               class="text-xs" id="dark-theme-btn">
                        🌙 Dark
                    </flux:button>
                    <flux:button variant="ghost" size="sm" onclick="toggleAuthTheme('auto')" 
                               class="text-xs" id="auto-theme-btn">
                        💻 Auto
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')
    
    @fluxScripts
    
    <!-- Theme Toggle Script -->
    <script>
        function toggleAuthTheme(theme) {
            localStorage.setItem('auth_theme', theme);
            
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else if (theme === 'auto') {
                localStorage.removeItem('auth_theme');
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            // Update button states
            updateThemeButtons(theme);
        }
        
        function updateThemeButtons(activeTheme) {
            const buttons = ['light-theme-btn', 'dark-theme-btn', 'auto-theme-btn'];
            buttons.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.classList.remove('bg-zinc-100', 'dark:bg-zinc-800');
                    if ((id === 'light-theme-btn' && activeTheme === 'light') ||
                        (id === 'dark-theme-btn' && activeTheme === 'dark') ||
                        (id === 'auto-theme-btn' && (activeTheme === 'auto' || !activeTheme))) {
                        btn.classList.add('bg-zinc-100', 'dark:bg-zinc-800');
                    }
                }
            });
        }
        
        // Initialize theme buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = localStorage.getItem('auth_theme') || 'auto';
            updateThemeButtons(currentTheme);
        });
    </script>

    @RegisterServiceWorkerScript
</body>
</html>
