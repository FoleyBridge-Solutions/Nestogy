<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name') }} Client Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/client-portal.css', 'resources/js/app.js'])
    
    <!-- Client Portal Theme Context for JavaScript -->
    <script>
        window.CLIENT_THEME = localStorage.getItem('client_theme') || 'auto';
        // TODO: In future, fetch from client user preferences
    </script>
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans bg-gray-50 dark:bg-gray-900 antialiased" 
      x-data="{ 
          theme: localStorage.getItem('client_theme') || 'auto',
          init() {
              this.applyTheme();
          },
          applyTheme() {
              document.body.classList.remove('dark', 'light');
              if (this.theme === 'dark') {
                  document.body.classList.add('dark');
              } else if (this.theme === 'light') {
                  document.body.classList.add('light');
              }
              // For 'auto', let CSS media query handle it
          }
      }" 
      x-init="init()"
    <!-- Sidebar -->
    <nav class="fixed top-0 left-0 h-screen w-64 bg-gradient-to-b from-indigo-600 to-indigo-800 dark:from-gray-800 dark:to-gray-900 overflow-y-auto z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" 
         id="sidebar"
         x-data="{ sidebarOpen: false }"
         :class="{ 'translate-x-0': sidebarOpen }">
        <div class="p-6">
            <!-- Brand -->
            <div class="text-center mb-6">
                <h4 class="text-white text-xl font-bold">{{ config('app.name') }}</h4>
                <p class="text-indigo-200 text-sm">Client Portal</p>
            </div>

            <!-- User Info -->
            @auth('client')
            <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <h6 class="text-white font-medium mb-1">{{ auth('client')->user()->name }}</h6>
                    <p class="text-indigo-200 text-sm">{{ auth('client')->user()->email }}</p>
                </div>
            </div>
            @endauth

            <!-- Navigation -->
            <nav class="space-y-1">
                <!-- Dashboard - Always visible -->
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.dashboard') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ route('client.dashboard') }}">
                    <i class="fas fa-tachometer-alt w-5 text-center mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                @php
                    $contact = auth('client')->user();
                    $permissions = $contact->portal_permissions ?? [];
                @endphp

                <!-- Contracts - Only if user has permission -->
                @if(in_array('can_view_contracts', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.contracts*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ route('client.contracts') }}">
                    <i class="fas fa-file-contract w-5 text-center mr-3"></i>
                    <span class="font-medium">Contracts</span>
                </a>
                @endif

                <!-- Invoices - Only if user has permission -->
                @if(in_array('can_view_invoices', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.invoices*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ route('client.invoices') }}">
                    <i class="fas fa-file-invoice-dollar w-5 text-center mr-3"></i>
                    <span class="font-medium">Invoices</span>
                </a>
                @endif

                <!-- Support Tickets - Only if user has permission -->
                @if(in_array('can_view_tickets', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.tickets*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ route('client.tickets') }}">
                    <i class="fas fa-ticket-alt w-5 text-center mr-3"></i>
                    <span class="font-medium">Support Tickets</span>
                </a>
                @endif

                <!-- Quotes - Only if user has permission -->
                @if(in_array('can_approve_quotes', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.quotes*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ Route::has('client.quotes') ? route('client.quotes') : '#' }}">
                    <i class="fas fa-file-signature w-5 text-center mr-3"></i>
                    <span class="font-medium">Quotes</span>
                </a>
                @endif

                <!-- IT Assets - Only if user has permission -->
                @if(in_array('can_view_assets', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.assets*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ route('client.assets') }}">
                    <i class="fas fa-server w-5 text-center mr-3"></i>
                    <span class="font-medium">IT Assets</span>
                </a>
                @endif

                <!-- Projects - Only if user has permission -->
                @if(in_array('can_view_projects', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.projects*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ Route::has('client.projects') ? route('client.projects') : '#' }}">
                    <i class="fas fa-project-diagram w-5 text-center mr-3"></i>
                    <span class="font-medium">Projects</span>
                </a>
                @endif

                <!-- Reports - Only if user has permission -->
                @if(in_array('can_view_reports', $permissions))
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.reports*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ Route::has('client.reports') ? route('client.reports') : '#' }}">
                    <i class="fas fa-chart-bar w-5 text-center mr-3"></i>
                    <span class="font-medium">Reports</span>
                </a>
                @endif

                <!-- Profile & Settings - Always visible -->
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-colors duration-200 {{ request()->routeIs('client.profile*') ? 'bg-white bg-opacity-20' : '' }}" 
                   href="{{ Route::has('client.profile') ? route('client.profile') : '#' }}">
                    <i class="fas fa-user-cog w-5 text-center mr-3"></i>
                    <span class="font-medium">Profile & Settings</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-6">
            <hr class="border-indigo-400 mb-4">
            <div class="text-center">
                <form action="{{ route('client.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-transparent border border-white text-white rounded-lg hover:bg-white hover:text-indigo-600 transition-colors duration-200 touch-manipulation">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Navigation -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm" x-data="{ notificationsOpen: false }">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors touch-manipulation" 
                            type="button" 
                            @click="$refs.sidebar.classList.toggle('translate-x-0')">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            @yield('title', 'Dashboard')
                        </h1>
                    </div>

                    <!-- Top Right Items -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors relative touch-manipulation" 
                                    type="button" 
                                    @click="notificationsOpen = !notificationsOpen">
                                <i class="fas fa-bell"></i>
                                @if(isset($notifications) && $notifications->where('is_read', false)->count() > 0)
                                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                                    {{ $notifications->where('is_read', false)->count() }}
                                </span>
                                @endif
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div x-show="notificationsOpen"
                                 @click.away="notificationsOpen = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
                                 style="display: none;">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h6 class="font-semibold text-gray-900 dark:text-white">Notifications</h6>
                                </div>
                                <div class="p-4">
                                    @if(isset($notifications) && $notifications->count() > 0)
                                        @foreach($notifications->take(5) as $notification)
                                        <a class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors touch-manipulation {{ $notification->is_read ? '' : 'bg-blue-50 dark:bg-blue-900/20' }}" 
                                           href="{{ $notification->action_url ?: '#' }}"
                                           data-notification-id="{{ $notification->id }}">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <i class="fas {{ $notification->getIcon() }} text-{{ $notification->getPriorityColor() }}-500"></i>
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $notification->title }}</div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $notification->getExcerpt(60) }}</p>
                                                    @if($notification->created_at)
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                                    @endif
                                                </div>
                                                @if(!$notification->is_read)
                                                <div class="flex-shrink-0">
                                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                </div>
                                                @endif
                                            </div>
                                        </a>
                                        @endforeach
                                    @else
                                        <div class="text-center py-4">
                                            <div class="text-gray-500 dark:text-gray-400 text-sm">
                                                <i class="fas fa-bell-slash mb-2"></i>
                                                <p>No new notifications</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                                    <a class="text-center block text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium" href="#">View all notifications</a>
                                </div>
                            </div>
                        </div>

                        <!-- Current Time -->
                        <span class="text-sm text-gray-500 dark:text-gray-400 hidden sm:block">{{ now()->format('M j, Y g:i A') }}</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="p-4">
            <!-- Flash Messages -->
            @if(session('success'))
            <div class="portal-alert portal-alert-success portal-alert-dismissible portal-fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            @endif

            @if(session('error'))
            <div class="portal-alert portal-alert-danger portal-alert-dismissible portal-fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            @endif

            @if(session('warning'))
            <div class="portal-alert portal-alert-warning portal-alert-dismissible portal-fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            @endif

            @if(session('info'))
            <div class="portal-alert portal-alert-info portal-alert-dismissible portal-fade show" role="alert">
                <i class="fas fa-info-circle"></i> {{ session('info') }}
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-5 py-4 bg-gray-100 dark:bg-gray-800">
            <div class="w-full px-4">
                <div class="portal-row">
                    <div class="portal-col-6">
                        <small class="text-gray-600 dark:text-gray-400">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </small>
                    </div>
                    <div class="portal-col-6 portal-md-text-end">
                        <small class="text-gray-600 dark:text-gray-400">
                            Need help? <a href="mailto:support@example.com" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">Contact Support</a>
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Custom Portal Scripts -->

    <!-- Custom Scripts -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.portal-alert-dismissible');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);

        // CSRF Token setup for fetch requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Handle notification clicks to mark as read
        document.addEventListener('click', function(e) {
            const notificationLink = e.target.closest('[data-notification-id]');
            if (notificationLink) {
                const notificationId = notificationLink.dataset.notificationId;
                if (notificationId && !notificationLink.classList.contains('read')) {
                    // Mark as read via AJAX (optional - implement if needed)
                    fetch(`/client-portal/notifications/${notificationId}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    }).catch(err => console.log('Failed to mark notification as read', err));
                    
                    // Update UI immediately
                    notificationLink.classList.remove('bg-blue-50');
                    const unreadDot = notificationLink.querySelector('.bg-blue-500');
                    if (unreadDot) unreadDot.remove();
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>