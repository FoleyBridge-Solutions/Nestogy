<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nestogy ERP') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-50 via-white to-gray-50 min-h-screen" 
      x-data="modernLayout()" x-init="init()">
    
    <!-- Command Palette -->
    <x-command-palette />
    
    <!-- Preload CSS to prevent flash -->
    <style>
        /* Prevent sidebar flash on load */
        [x-cloak] { display: none !important; }
        
        /* Initial sidebar state for SSR */
        aside[data-sidebar] {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (max-width: 768px) {
            aside[data-sidebar] { width: 3rem; /* w-12 */ }
        }
        
        @media (min-width: 768px) and (max-width: 1279px) {
            aside[data-sidebar] { width: 4rem; /* w-16 */ }
        }
        
        @media (min-width: 1280px) {
            aside[data-sidebar] { width: 13rem; /* w-52 */ }
        }
    </style>
    <div class="min-h-screen relative overflow-x-hidden" x-data="layoutManager()" x-init="init()">
        <!-- Workflow Navigation Bar -->
        <x-workflow-navbar :active-domain="$activeDomain" />

        <div class="flex h-screen pt-16 lg:pt-16 transition-all duration-300 ease-in-out navbar-padding" x-data="{ sidebarMode: 'expanded' }" @sidebar-mode-changed.window="sidebarMode = $event.detail.mode"> 
            <!-- Responsive padding for navbar -->
            <!-- Domain Sidebar -->
            @if($activeDomain)
                <x-domain-sidebar
                    :active-domain="$activeDomain"
                    :active-item="$activeItem"
                    x-show="!sidebarOpen || window.innerWidth >= 1024"
                    class="hidden lg:block transform transition-all duration-300 ease-in-out"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="-translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="-translate-x-full opacity-0"
                />
            @endif

            <!-- Dynamic Main Content Area -->
            <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 ease-in-out"
                 :class="{
                     'lg:ml-0': sidebarMode === 'expanded',
                     'lg:ml-0': sidebarMode === 'compact',
                     'lg:ml-0': sidebarMode === 'mini'
                 }">
                <!-- Modern Page Header with Breadcrumbs -->
                @if(!empty($breadcrumbs) || isset($header))
                    <header class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200/60">
                        <div class="max-w-full mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            @if(!empty($breadcrumbs))
                                <nav class="flex mb-3" aria-label="Breadcrumb">
                                    <ol class="inline-flex items-center space-x-1 md:space-x-3 breadcrumbs">
                                        @foreach($breadcrumbs as $index => $breadcrumb)
                                            @if($loop->first)
                                                <li class="inline-flex items-center">
                                                    <a href="{{ route($breadcrumb['route']) }}" class="inline-flex items-center text-sm font-semibold text-gray-700 hover:text-indigo-600 transition-colors duration-200 group">
                                                        <svg class="w-4 h-4 mr-2 transition-transform duration-200 group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                                        </svg>
                                                        {{ $breadcrumb['name'] }}
                                                    </a>
                                                </li>
                                            @else
                                                <li>
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        @if(isset($breadcrumb['route']) && !($breadcrumb['active'] ?? false))
                                                            <a href="{{ route($breadcrumb['route']) }}" class="text-sm font-semibold text-gray-700 hover:text-indigo-600 transition-colors duration-200">
                                                                {{ $breadcrumb['name'] }}
                                                            </a>
                                                        @else
                                                            <span class="text-sm font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">
                                                                {{ $breadcrumb['name'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ol>
                                </nav>
                            @endif
                            
                            @if (isset($header))
                                <div>{{ $header }}</div>
                            @endif
                        </div>
                    </header>
                @endif

                <!-- Dynamic Page Content -->
                <main class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <div class="max-w-full mx-auto py-6 px-4 sm:px-6 lg:px-8 flash-messages-container">
                        <!-- Modern Flash Messages -->
                        <div class="flash-messages mb-6">
                            @if (session('success'))
                                <div class="flash-message relative border-l-4 border-green-400 bg-green-50 p-4 mb-4 rounded-r-lg shadow-sm animate-slide-in" role="alert">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                        </div>
                                        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-600 p-1.5 hover:bg-green-200 transition-colors duration-200" onclick="this.parentElement.parentElement.remove()">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="flash-message relative border-l-4 border-red-400 bg-red-50 p-4 mb-4 rounded-r-lg shadow-sm animate-slide-in" role="alert">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                        </div>
                                        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-600 p-1.5 hover:bg-red-200 transition-colors duration-200" onclick="this.parentElement.parentElement.remove()">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="flash-message relative border-l-4 border-red-400 bg-red-50 p-4 mb-4 rounded-r-lg shadow-sm animate-slide-in" role="alert">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h3 class="text-sm font-medium text-red-800 mb-2">Please correct the following errors:</h3>
                                            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-600 p-1.5 hover:bg-red-200 transition-colors duration-200" onclick="this.parentElement.parentElement.remove()">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <!-- Enhanced Mobile Sidebar Overlay -->
        @if($activeDomain)
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-all ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-all ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 lg:hidden"
                 style="display: none;">
                
                <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="closeSidebar()"></div>
                
                <div class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-white shadow-2xl"
                     x-transition:enter="transition-transform ease-out duration-300"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition-transform ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full">
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button @click="closeSidebar()"
                                class="ml-1 flex items-center justify-center h-10 w-10 rounded-full bg-white/10 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-white transition-all duration-200 hover:bg-white/20">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <x-domain-sidebar :active-domain="$activeDomain" :active-item="$activeItem" />
                </div>
            </div>
        @endif
    </div>

    <!-- Enhanced Mobile Sidebar Toggle Button -->
    @if($activeDomain)
        <!-- Adaptive Mobile Sidebar Toggle -->
        <button @click="toggleSidebar()"
                class="fixed bottom-6 left-6 lg:hidden z-50 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white p-4 rounded-full shadow-xl transform hover:scale-110 transition-all duration-200 ring-4 ring-white/20 backdrop-blur-sm"
                x-data="{ currentMode: 'mini' }" 
                @sidebar-mode-changed.window="currentMode = $event.detail.mode">
            <svg class="h-6 w-6 transition-transform duration-200" :class="sidebarOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    @endif

    <!-- Layout Manager Alpine.js Component -->
    <script>
    function layoutManager() {
        return {
            sidebarMode: 'expanded',
            
            init() {
                // Listen for sidebar mode changes
                window.addEventListener('sidebar-mode-changed', (e) => {
                    this.sidebarMode = e.detail.mode;
                    this.adjustLayout();
                });
                
                // Initial layout adjustment
                this.adjustLayout();
            },
            
            adjustLayout() {
                // Adjust main content margins and spacing based on sidebar mode
                const mainContent = document.querySelector('main');
                if (mainContent) {
                    // Content adjustments can be made here if needed
                    // Currently handled via CSS classes
                }
            },
            
            getSidebarWidth() {
                switch (this.sidebarMode) {
                    case 'expanded': return '256px';
                    case 'compact': return '80px';
                    case 'mini': return '56px';
                    default: return '256px';
                }
            }
        };
    }
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>