@php
    $userTheme = optional(optional(auth()->user())->userSetting)->theme ?? 'auto';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $currentCompany?->name ?? config('app.name', 'Nestogy ERP') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Flux Appearance -->
    @fluxAppearance
    
    <!-- Current User Context for JavaScript -->
    <script>
        window.CURRENT_USER = {!! json_encode([
            'id' => auth()->id(),
            'company_id' => optional(auth()->user())->company_id,
            'name' => optional(auth()->user())->name,
            'theme' => optional(optional(auth()->user())->userSetting)->theme ?? 'auto',
            'selected_client_id' => session('selected_client_id'),
            'selected_client' => session('selected_client_id') ? optional(\App\Models\Client::where('company_id', optional(auth()->user())->company_id)->find(session('selected_client_id')))->only(['id', 'name', 'company_name', 'email']) : null
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>
    
    <!-- Company Customization Styles -->
    @if(auth()->check() && auth()->user()->company_id)
        @php
            $settingsService = app(\App\Services\SettingsService::class);
            $companyCss = $settingsService->generateCompanyCss(auth()->user()->company);
        @endphp
        <style>
            {!! $companyCss !!}
        </style>
    @endif
    
    <!-- Additional Styles -->
    @stack('styles')
    
    <!-- Minimal styles for proper scrolling -->
    <style>
        /* Ensure the body doesn't scroll, only the main content area */
        body {
            height: 100vh;
            overflow: hidden;
        }
        
        /* Make main content scrollable */
        [data-flux-main] {
            height: calc(100vh - 64px); /* Subtract header height */
            overflow-y: auto;
        }
        
        /* Remove padding from flux sidebar container */
        [data-flux-sidebar] {
            padding: 0 !important;
        }
        
        /* Remove padding from main grid area */
        [data-flux-main] {
            padding: 0 !important;
        }
        
        /* Remove margins from breadcrumbs */
        [data-flux-breadcrumbs],
        [data-flux-breadcrumbs] * {
            margin: 0 !important;
        }
    </style>
    

</head>
<body class="h-screen overflow-hidden bg-white dark:bg-zinc-800">
    <!-- Flux UI Layout Structure -->
    <flux:header class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-50">
        <!-- Single Row Navigation with Everything -->
        <flux:navbar class="w-full px-4">
            <!-- Brand (Far Left) -->
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" 
                        name="{{ Auth::user()?->company?->name ?? config('app.name', 'Nestogy') }}" 
                        class="dark:hidden" />
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" 
                        name="{{ Auth::user()?->company?->name ?? config('app.name', 'Nestogy') }}" 
                        class="hidden dark:flex" />
            
            <!-- Mobile Toggle -->
            @if($sidebarContext ?? $activeDomain ?? null)
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />
            @endif
            
            <!-- Main Navigation - App name serves as primary dashboard link -->
            
            <flux:spacer />
            <!-- Client Switcher -->
            @auth
                @livewire('client-switcher')
            @endauth
            
            <!-- Navbar Timer -->
            @auth
                @livewire('navbar-timer')
            @endauth

            <!-- Command Palette -->
            @auth
                @livewire('command-palette')
            @endauth
            
            {{-- Global keyboard shortcut for command palette --}}
            <script>
                document.addEventListener('keydown', function(e) {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        Livewire.dispatch('openCommandPalette', { 
                            currentRoute: '{{ Route::currentRouteName() }}' 
                        });
                    }
                });
            </script>
            <flux:navbar.item icon="envelope" 
                            href="{{ route('email.inbox.index') }}" 
                            class="max-lg:hidden"
                            aria-label="Email" />
            <flux:navbar.item icon="paper-airplane" 
                            href="{{ route('mail.index') }}" 
                            class="max-lg:hidden"
                            aria-label="Physical Mail" />
            <flux:navbar.item icon="cog-6-tooth" 
                            href="{{ route('settings.index') }}" 
                            class="max-lg:hidden"
                            aria-label="Settings" />
            <flux:navbar.item icon="information-circle" 
                            href="#" 
                            class="max-lg:hidden"
                            aria-label="Help" />
            
            <!-- Profile Dropdown -->
            @auth
            <flux:dropdown align="end">
                <flux:profile 
                    name="{{ auth()->user()->name }}"
                />
                
                <flux:navmenu class="max-w-[12rem]">
                    <div class="px-2 py-1.5">
                        <flux:text size="sm">Signed in as</flux:text>
                        <flux:heading class="mt-1! truncate">{{ auth()->user()->email }}</flux:heading>
                    </div>
                    
                    <flux:navmenu.separator />
                    
                    <flux:navmenu.item href="{{ route('users.profile') }}" icon="user" class="text-zinc-800 dark:text-white">Profile</flux:navmenu.item>
                    <flux:navmenu.item href="{{ route('settings.index') }}" icon="cog-6-tooth" class="text-zinc-800 dark:text-white">Settings</flux:navmenu.item>
                    
                    <flux:navmenu.separator />
                    
                    <div class="px-2 py-1.5">
                        <flux:text size="sm" class="pl-7">Theme</flux:text>
                    </div>
                    <flux:navmenu.item onclick="setTheme('auto')" class="text-zinc-800 dark:text-white">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="computer-desktop" class="w-4 h-4" />
                            <span>System</span>
                            <span class="theme-indicator ml-auto" data-theme="auto" style="display: none;">✓</span>
                        </div>
                    </flux:navmenu.item>
                    <flux:navmenu.item onclick="setTheme('light')" class="text-zinc-800 dark:text-white">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="sun" class="w-4 h-4" />
                            <span>Light</span>
                            <span class="theme-indicator ml-auto" data-theme="light" style="display: none;">✓</span>
                        </div>
                    </flux:navmenu.item>
                    <flux:navmenu.item onclick="setTheme('dark')" class="text-zinc-800 dark:text-white">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="moon" class="w-4 h-4" />
                            <span>Dark</span>
                            <span class="theme-indicator ml-auto" data-theme="dark" style="display: none;">✓</span>
                        </div>
                    </flux:navmenu.item>

                    <flux:navmenu.separator />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:navmenu.item href="#" onclick="this.closest('form').submit()" icon="arrow-right-start-on-rectangle" class="text-zinc-800 dark:text-white">Sign out</flux:navmenu.item>
                    </form>
                </flux:navmenu>
            </flux:dropdown>
            @endauth
        </flux:navbar>
    </flux:header>

    <!-- Mobile Sidebar for navigation -->
    @php
        // Map old domain variable to new sidebar context for backward compatibility
        $sidebarContext = $activeDomain ?? $sidebarContext ?? null;
        $activeSection = $activeItem ?? $activeSection ?? null;
    @endphp
    
    @if($sidebarContext)
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden bg-white dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" 
                        name="{{ Auth::user()?->company?->name ?? config('app.name', 'Nestogy') }}" 
                        class="px-2 py-2 dark:hidden" />
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" 
                        name="{{ Auth::user()?->company?->name ?? config('app.name', 'Nestogy') }}" 
                        class="px-2 py-2 hidden dark:flex" />
            
            <x-flux-sidebar
                :sidebar-context="$sidebarContext"
                :active-section="$activeSection"
                :mobile="true"
            />
        </flux:sidebar>
    @endif

    <!-- Desktop Sidebar (only render when there's sidebar content) -->
    @if($sidebarContext)
        <flux:sidebar collapsible sticky class="hidden lg:block bg-white dark:bg-zinc-900">
            <x-flux-sidebar
                :sidebar-context="$sidebarContext"
                :active-section="$activeSection"
                :mobile="false"
            />
        </flux:sidebar>
    @endif

    <!-- Flux Main Content Area -->
    <flux:main class="{{ !($sidebarContext ?? $activeDomain ?? null) ? 'ml-0' : '' }}">
        <!-- Sticky Breadcrumbs Container -->
        @if(!empty($breadcrumbs))
            <div class="sticky top-0 z-40">
                <div class="px-4 py-1 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:breadcrumbs>
                        @foreach($breadcrumbs as $breadcrumb)
                            @if($loop->last || ($breadcrumb['active'] ?? false))
                                <flux:breadcrumbs.item>{{ $breadcrumb['name'] }}</flux:breadcrumbs.item>
                            @else
                                @php
                                    // Build the URL with params if provided
                                    if (isset($breadcrumb['route'])) {
                                        $url = isset($breadcrumb['params']) 
                                            ? route($breadcrumb['route'], $breadcrumb['params']) 
                                            : route($breadcrumb['route']);
                                    } else {
                                        $url = '#';
                                    }
                                @endphp
                                <flux:breadcrumbs.item href="{{ $url }}">
                                    {{ $breadcrumb['name'] }}
                                </flux:breadcrumbs.item>
                            @endif
                        @endforeach
                    </flux:breadcrumbs>
                </div>
            </div>
        @endif

        <!-- Flash Messages using Flux UI -->
        @if (session('success'))
            <flux:toast variant="success" class="m-4">
                {{ session('success') }}
            </flux:toast>
        @endif

        @if (session('error'))
            <flux:toast variant="danger" class="m-4">
                {{ session('error') }}
            </flux:toast>
        @endif

        @if ($errors->any())
            <flux:toast variant="danger" class="m-4">
                <div>
                    <div class="font-medium mb-2">Please correct the following errors:</div>
                    <ul class="text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </flux:toast>
        @endif

        <!-- Page Content -->
        <div class="p-4 lg:p-6 h-full">
            @yield('content')
        </div>
    </flux:main>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Flux Scripts -->
    @fluxScripts

    <!-- Defer Flux component initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure all components are rendered
            setTimeout(function() {
                if (window.Flux && window.Flux.init) {
                    window.Flux.init();
                }
            }, 100);
        });
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')
    
    <!-- Flux Toast Component -->
    <flux:toast />
    
    <!-- Alpine.js removed - using Flux/Livewire components -->
    <script>
        // Global notification listener for Livewire components
        window.addEventListener('notify', event => {
            console.log('Notification received:', event.detail);
            const detail = event.detail;
            const type = detail.type || detail[0]?.type || 'info';
            const message = detail.message || detail[0]?.message || 'Notification';
            
            // Create a toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
                type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
                type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                'bg-blue-100 text-blue-800 border border-blue-200'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${type === 'success' ? 
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
                          type === 'error' ?
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>' :
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
                        }
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-current opacity-50 hover:opacity-75">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentElement) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        });

        // Print invoice listener
        window.addEventListener('print-invoice', event => {
            const { url } = event.detail;
            // Open PDF in new tab - browser will handle PDF viewing and user can print from there
            window.open(url, '_blank');
        });

        // Theme handling for Flux UI
        document.addEventListener('DOMContentLoaded', function() {
            // Show current theme indicator
            const currentTheme = '{{ $userTheme }}';
            const indicator = document.querySelector(`[data-theme="${currentTheme}"]`);
            if (indicator) {
                indicator.style.display = 'inline';
            }
        });
        
        // Theme setter function
        function setTheme(theme) {
            // Apply theme immediately
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else if (theme === 'auto') {
                localStorage.removeItem('theme');
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            // Update indicators
            document.querySelectorAll('.theme-indicator').forEach(el => el.style.display = 'none');
            const indicator = document.querySelector(`[data-theme="${theme}"]`);
            if (indicator) {
                indicator.style.display = 'inline';
            }
            
            // Save to user preferences via AJAX
            fetch('/settings/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ theme: theme })
            });
        }
    </script>

    {{-- Timer Modals (Global) --}}
    @livewire('timer-completion-modal')
    @livewire('timer-batch-completion-modal')
</body>
</html>
