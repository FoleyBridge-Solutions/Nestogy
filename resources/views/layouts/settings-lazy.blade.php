@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="settingsLazyLoader()" x-init="init()"> 
    <div class="container mx-auto px-4 mx-auto px-4 mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white" x-text="currentTitle">@yield('settings-title', 'Settings')</h1>
                        <p class="text-gray-600 dark:text-gray-300 mt-1 text-sm lg:text-base" x-text="currentDescription">@yield('settings-description', 'Configure your system preferences')</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    @yield('settings-actions')
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:flex lg:gap-6">
            <!-- Mobile Sidebar Overlay -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition-all ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-all ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 lg:hidden"
                 style="display: none;"
                 @click.away="closeMobileMenu()">
                
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="closeMobileMenu()"></div>
                
                <!-- Mobile Sidebar -->
                <div class="relative flex flex-col w-80 max-w-[85vw] h-full bg-white dark:bg-gray-800 shadow-2xl"
                     x-transition:enter="transition-transform ease-out duration-300"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition-transform ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full">
                    
                    <!-- Mobile Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Settings Menu</h2>
                        <button @click="closeMobileMenu()"
                                class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Mobile Navigation -->
                    <div class="flex-1 overflow-y-auto">
                        @include('settings.partials.navigation', ['mobile' => true])
                    </div>
                </div>
            </div>

            <!-- Desktop Sidebar Navigation -->
            <div class="hidden lg:block w-64 flex-shrink-0">
                @include('settings.partials.navigation', ['mobile' => false])
            </div>

            <!-- Settings Content -->
            <div class="flex-1 mt-6 lg:mt-0">
                <!-- Success/Error Messages -->
                @if (session('success'))
                    <div class="mb-4 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Dynamic Content Container -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <!-- Loading State -->
                    <div x-show="loading" class="p-6">
                        <x-settings.skeleton-loader :type="loadingType" />
                    </div>
                    
                    <!-- Error State -->
                    <div x-show="error && !loading" class="p-6 text-center">
                        <div class="text-red-600 mb-4">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Failed to Load Content</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4" x-text="errorMessage"></p>
                            <button @click="loadContent(currentSection, true)" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Retry
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div x-show="!loading && !error" x-html="content" class="settings-content">
                        @yield('settings-content')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div x-data="notifications" 
     @notify.window="addNotification($event.detail)"
     class="fixed top-4 right-4 z-50 space-y-2">
    <template x-for="notification in notifications" :key="notification.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             :class="{ 'bg-green-100 border-green-400 text-green-700': notification.type === 'success', 'bg-red-100 border-red-400 text-red-700': notification.type === 'error', 'bg-yellow-100 border-yellow-400 text-yellow-700': notification.type === 'warning', 'bg-blue-100 border-blue-400 text-blue-700': notification.type === 'info' }"
             class="border px-4 py-3 rounded relative shadow-md min-w-[300px] max-w-md">
            <span x-text="notification.message"></span>
            <button @click="removeNotification(notification.id)" 
                    class="absolute top-0 right-0 p-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </template>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Settings Lazy Loader Component
    Alpine.data('settingsLazyLoader', () => ({
        mobileMenuOpen: false,
        loading: false,
        error: false,
        errorMessage: '',
        content: '',
        currentSection: 'general',
        currentTitle: 'General Settings',
        currentDescription: 'Configure your company information and system preferences',
        loadingType: 'form',
        cache: new Map(),
        
        init() {
            // Load initial content based on current URL
            this.detectCurrentSection();
            this.loadContent(this.currentSection);
            
            // Listen for navigation events
            window.addEventListener('settingsNavigate', (event) => {
                this.navigateToSection(event.detail.section);
            });
            
            // Listen for popstate events (browser back/forward)
            window.addEventListener('popstate', (event) => {
                this.detectCurrentSection();
                this.loadContent(this.currentSection);
            });
        },
        
        detectCurrentSection() {
            const path = window.location.pathname;
            const sectionMatch = path.match(/\/settings\/([^\/]+)/);
            this.currentSection = sectionMatch ? sectionMatch[1] : 'general';
        },
        
        async navigateToSection(section, skipHistory = false) {
            this.currentSection = section;
            
            // Update URL without page reload
            if (!skipHistory) {
                const newUrl = `/settings/${section}`;
                window.history.pushState({ section }, '', newUrl);
            }
            
            // Close mobile menu
            this.closeMobileMenu();
            
            // Load content
            await this.loadContent(section);
        },
        
        async loadContent(section, forceReload = false) {
            // Check cache first
            if (!forceReload && this.cache.has(section)) {
                const cached = this.cache.get(section);
                this.content = cached.html;
                this.currentTitle = cached.title;
                this.updateLoadingType(section);
                return;
            }
            
            this.loading = true;
            this.error = false;
            this.updateLoadingType(section);
            
            try {
                const response = await fetch(`/settings/api/content/${section}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.content = data.html;
                    this.currentTitle = data.title;
                    this.currentDescription = `Configure your ${data.title.toLowerCase()}`;
                    
                    // Cache the result
                    this.cache.set(section, {
                        html: data.html,
                        title: data.title,
                        timestamp: Date.now()
                    });
                    
                    // Execute any scripts in the loaded content
                    this.$nextTick(() => {
                        this.executeScripts();
                    });
                } else {
                    throw new Error(data.error || 'Failed to load content');
                }
            } catch (error) {
                console.error('Error loading content:', error);
                this.error = true;
                this.errorMessage = error.message || 'An unexpected error occurred';
                
                this.$dispatch('notify', {
                    type: 'error',
                    message: `Failed to load ${section} settings: ${error.message}`
                });
            } finally {
                this.loading = false;
            }
        },
        
        updateLoadingType(section) {
            // Set appropriate skeleton type based on section
            const sectionTypes = {
                'general': 'tabs',
                'security': 'form',
                'email': 'form',
                'user-management': 'table',
                'billing-financial': 'form',
                'accounting': 'form',
                'payment-gateways': 'cards',
                'ticketing-service-desk': 'table',
                'project-management': 'form',
                'asset-inventory': 'table',
                'client-portal': 'form',
                'rmm-monitoring': 'form',
                'integrations': 'cards',
                'automation-workflows': 'table',
                'api-webhooks': 'form',
                'compliance-audit': 'form',
                'backup-recovery': 'form',
                'data-management': 'table',
                'performance-optimization': 'form',
                'reporting-analytics': 'cards',
                'notifications-alerts': 'form',
                'mobile-remote': 'form',
                'training-documentation': 'form',
                'knowledge-base': 'form'
            };
            
            this.loadingType = sectionTypes[section] || 'form';
        },
        
        executeScripts() {
            // Execute any Alpine.js components in the loaded content
            const contentElement = document.querySelector('.settings-content');
            if (contentElement) {
                Alpine.initTree(contentElement);
            }
        },
        
        closeMobileMenu() { 
            this.mobileMenuOpen = false; 
        },
        
        clearCache() {
            this.cache.clear();
            this.$dispatch('notify', {
                type: 'success',
                message: 'Content cache cleared'
            });
        }
    }));
    
    // Notification system
    Alpine.data('notifications', () => ({
        notifications: [],
        
        init() {
            this.$el.addEventListener('notify', (event) => {
                this.addNotification(event.detail);
            });
        },
        
        addNotification(notification) {
            const id = Date.now();
            this.notifications.push({
                id,
                ...notification
            });
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                this.removeNotification(id);
            }, 5000);
        },
        
        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }));
});
</script>
@endpush
@endsection