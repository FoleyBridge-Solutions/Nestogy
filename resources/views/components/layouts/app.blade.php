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

    <!-- Favicon -->
    @php
        $faviconUrl = ($currentCompany?->branding['favicon_url'] ?? null) ?: asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">

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
    
    <!-- Company Branding Styles -->
    @if(auth()->check() && auth()->user()->company)
        @php
            $company = auth()->user()->company;
            $branding = $company->branding ?? [];
            $accentColor = $branding['accent_color'] ?? '#3b82f6';
            $accentContentColor = $branding['accent_content_color'] ?? '#2563eb';
            $accentForegroundColor = $branding['accent_foreground_color'] ?? '#ffffff';
            $baseScheme = $branding['base_color_scheme'] ?? 'zinc';
        @endphp
        <style>
            @layer theme {
                :root {
                    --color-accent: {{ $accentColor }};
                    --color-accent-content: {{ $accentContentColor }};
                    --color-accent-foreground: {{ $accentForegroundColor }};
                    
                    @if($baseScheme !== 'zinc')
                    /* Override base color scheme to {{ $baseScheme }} */
                    --color-zinc-50: var(--color-{{ $baseScheme }}-50);
                    --color-zinc-100: var(--color-{{ $baseScheme }}-100);
                    --color-zinc-200: var(--color-{{ $baseScheme }}-200);
                    --color-zinc-300: var(--color-{{ $baseScheme }}-300);
                    --color-zinc-400: var(--color-{{ $baseScheme }}-400);
                    --color-zinc-500: var(--color-{{ $baseScheme }}-500);
                    --color-zinc-600: var(--color-{{ $baseScheme }}-600);
                    --color-zinc-700: var(--color-{{ $baseScheme }}-700);
                    --color-zinc-800: var(--color-{{ $baseScheme }}-800);
                    --color-zinc-900: var(--color-{{ $baseScheme }}-900);
                    --color-zinc-950: var(--color-{{ $baseScheme }}-950);
                    @endif
                }
                
                .dark {
                    --color-accent: {{ $accentColor }};
                    --color-accent-content: {{ $accentContentColor }};
                    --color-accent-foreground: {{ $accentForegroundColor }};
                }
            }
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
            @php
                $company = Auth::user()?->company;
                $companyBranding = $company?->branding ?? [];
                $logoLight = $companyBranding['logo_url'] ?? asset('static-assets/img/branding/nestogy-logo.png');
                $logoDark = $companyBranding['logo_dark_url'] ?? $logoLight;
                $companyName = $company?->name ?? config('app.name', 'Nestogy');
            @endphp
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ $logoLight }}" 
                        name="{{ $companyName }}" 
                        class="dark:hidden" />
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ $logoDark }}" 
                        name="{{ $companyName }}" 
                        class="hidden dark:flex" />
            
            <!-- Mobile Toggle -->
            @if($sidebarContext ?? $activeDomain ?? null)
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />
            @endif
            
            <!-- Main Navigation - App name serves as primary dashboard link -->
            
            <flux:spacer />
            <!-- Client Switcher -->
            @livewire('client-switcher')
            
            <!-- Navbar Timer -->
            @livewire('navbar-timer')
            
            <!-- Command Palette loaded in main layout -->
            
            {{-- Global keyboard shortcut for command palette --}}
            <script>
                document.addEventListener('keydown', function(e) {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        Livewire.dispatch('openCommandPalette');
                    }
                });
            </script>
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
                        logo="{{ $logoLight }}" 
                        name="{{ $companyName }}" 
                        class="px-2 py-2 dark:hidden" />
            <flux:brand href="{{ route('dashboard') }}" 
                        logo="{{ $logoDark }}" 
                        name="{{ $companyName }}"
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
        <div class="p-4 lg:p-6">
            @yield('content')
        </div>
    </flux:main>

    <!-- Additional Scripts -->
    @stack('scripts')
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Flux Scripts -->
    @fluxScripts
    
    <!-- Flux Toast Component -->
    <flux:toast />
    
    <!-- Global Toast Handler -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('flux-toast', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                Flux.toast({
                    text: data.text,
                    variant: data.variant || 'info',
                    duration: data.duration || 3000
                });
            });
        });
    </script>
    
    <!-- Alpine.js removed - using Flux/Livewire components -->
    <script>

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
</body>
</html>
