@props(['activeDomain' => null, 'activeItem' => null])

@php
$sidebarConfig = [
    'clients' => [
        'title' => 'Client Dashboard',
        'items' => [
            // PRIMARY ENTRY POINT
            [
                'name' => 'Client Dashboard',
                'route' => 'clients.show',
                'icon' => 'chart-pie',
                'key' => 'dashboard',
                'params' => ['client' => 'current'],
                'description' => 'Central hub with client health, alerts, and quick actions'
            ],

            // IMMEDIATE ACTIONS SECTION
            [
                'type' => 'section',
                'title' => 'IMMEDIATE ACTIONS',
                'priority' => true,
                'color' => 'red'
            ],
            [
                'name' => 'Open Tickets',
                'route' => 'tickets.index',
                'icon' => 'exclamation-triangle',
                'key' => 'open-tickets',
                'params' => ['client_id' => 'current', 'status' => 'open'],
                'badge_type' => 'urgent',
                'show_if' => 'has_open_tickets'
            ],
            [
                'name' => 'Pending Items',
                'route' => 'clients.show',
                'icon' => 'clock',
                'key' => 'pending',
                'params' => ['client' => 'current', 'section' => 'pending'],
                'badge_type' => 'warning',
                'show_if' => 'has_pending_items'
            ],

            // CLIENT COMMUNICATION SECTION
            [
                'type' => 'section',
                'title' => 'COMMUNICATION'
            ],
            [
                'name' => 'Contacts',
                'route' => 'clients.contacts.index',
                'icon' => 'users',
                'key' => 'contacts',
                'params' => ['client' => 'current']
            ],
            [
                'name' => 'Locations',
                'route' => 'clients.locations.index',
                'icon' => 'map-pin',
                'key' => 'locations',
                'params' => ['client' => 'current']
            ],
            [
                'name' => 'Communication Log',
                'route' => 'clients.show',
                'icon' => 'chat-bubble-left-right',
                'key' => 'communication',
                'params' => ['client' => 'current', 'section' => 'communication']
            ],

            // SERVICE MANAGEMENT SECTION
            [
                'type' => 'section',
                'title' => 'SERVICE MANAGEMENT'
            ],
            [
                'name' => 'Support Tickets',
                'route' => 'tickets.index',
                'icon' => 'ticket',
                'key' => 'tickets',
                'params' => ['client_id' => 'current']
            ],
            [
                'name' => 'Assets & Equipment',
                'route' => 'assets.index',
                'icon' => 'computer-desktop',
                'key' => 'assets',
                'params' => ['client_id' => 'current']
            ],
            [
                'name' => 'Projects',
                'route' => 'projects.index',
                'icon' => 'folder',
                'key' => 'projects',
                'params' => ['client_id' => 'current']
            ],
            [
                'name' => 'Service Plans',
                'route' => 'clients.show',
                'icon' => 'cog-6-tooth',
                'key' => 'services',
                'params' => ['client' => 'current', 'section' => 'services']
            ],

            // FINANCIAL & BILLING SECTION
            [
                'type' => 'section',
                'title' => 'BILLING & FINANCE'
            ],
            [
                'name' => 'Invoices',
                'route' => 'financial.invoices.index',
                'icon' => 'document-text',
                'key' => 'invoices',
                'params' => ['client_id' => 'current']
            ],
            [
                'name' => 'Payments',
                'route' => 'financial.payments.index',
                'icon' => 'credit-card',
                'key' => 'payments',
                'params' => ['client_id' => 'current']
            ],
            [
                'name' => 'Financial Summary',
                'route' => 'clients.show',
                'icon' => 'currency-dollar',
                'key' => 'financial',
                'params' => ['client' => 'current', 'section' => 'financial']
            ],

            // DOCUMENTATION SECTION (COLLAPSED BY DEFAULT)
            [
                'type' => 'section',
                'title' => 'DOCUMENTATION',
                'collapsible' => true,
                'default_collapsed' => true
            ],
            [
                'name' => 'Client Files',
                'route' => 'clients.files.index',
                'icon' => 'folder',
                'key' => 'files',
                'params' => ['client' => 'current'],
                'parent_section' => 'documentation'
            ],
            [
                'name' => 'Documents',
                'route' => 'clients.documents.index',
                'icon' => 'document-duplicate',
                'key' => 'documents',
                'params' => ['client' => 'current'],
                'parent_section' => 'documentation'
            ],
            [
                'name' => 'Configuration',
                'route' => 'clients.show',
                'icon' => 'cog',
                'key' => 'configuration',
                'params' => ['client' => 'current', 'section' => 'configuration'],
                'parent_section' => 'documentation'
            ]
        ]
    ],
    'tickets' => [
        'title' => 'Ticket Management',
        'items' => [
            [
                'name' => 'Overview',
                'route' => 'tickets.index',
                'icon' => 'home',
                'key' => 'overview'
            ],
            [
                'name' => 'All Tickets',
                'route' => 'tickets.index',
                'icon' => 'ticket',
                'key' => 'index'
            ],
            [
                'name' => 'Create Ticket',
                'route' => 'tickets.create',
                'icon' => 'plus',
                'key' => 'create'
            ],
            [
                'type' => 'section',
                'title' => 'MY WORK'
            ],
            [
                'name' => 'My Tickets',
                'route' => 'tickets.index',
                'icon' => 'user',
                'key' => 'my-tickets',
                'params' => ['filter' => 'my']
            ],
            [
                'name' => 'Assigned to Me',
                'route' => 'tickets.index',
                'icon' => 'user-circle',
                'key' => 'assigned',
                'params' => ['assignee' => 'me']
            ],
            [
                'name' => 'Watching',
                'route' => 'tickets.index',
                'icon' => 'eye',
                'key' => 'watching',
                'params' => ['filter' => 'watching']
            ],
            [
                'type' => 'section',
                'title' => 'STATUS VIEWS'
            ],
            [
                'name' => 'Open Tickets',
                'route' => 'tickets.index',
                'icon' => 'exclamation-circle',
                'key' => 'open',
                'params' => ['status' => 'open']
            ],
            [
                'name' => 'In Progress',
                'route' => 'tickets.index',
                'icon' => 'arrow-right',
                'key' => 'in-progress',
                'params' => ['status' => 'in-progress']
            ],
            [
                'name' => 'Waiting for Response',
                'route' => 'tickets.index',
                'icon' => 'clock',
                'key' => 'waiting',
                'params' => ['status' => 'waiting']
            ],
            [
                'name' => 'Closed Tickets',
                'route' => 'tickets.index',
                'icon' => 'check-circle',
                'key' => 'closed',
                'params' => ['status' => 'closed']
            ],
            [
                'type' => 'section',
                'title' => 'SCHEDULING'
            ],
            [
                'name' => 'Scheduled Tickets',
                'route' => 'tickets.index',
                'icon' => 'calendar',
                'key' => 'scheduled',
                'params' => ['filter' => 'scheduled']
            ],
            [
                'name' => 'Calendar View',
                'route' => 'tickets.calendar.index',
                'icon' => 'calendar-days',
                'key' => 'calendar'
            ],
            [
                'name' => 'Time Tracking',
                'route' => 'tickets.time-tracking.index',
                'icon' => 'clock',
                'key' => 'time-tracking'
            ],
            [
                'type' => 'section',
                'title' => 'MANAGEMENT'
            ],
            [
                'name' => 'Priority Queue',
                'route' => 'tickets.priority-queue.index',
                'icon' => 'fire',
                'key' => 'priority-queue'
            ],
            [
                'name' => 'Recurring Tickets',
                'route' => 'tickets.recurring.index',
                'icon' => 'arrow-path',
                'key' => 'recurring'
            ],
            [
                'name' => 'Templates',
                'route' => 'tickets.templates.index',
                'icon' => 'document-duplicate',
                'key' => 'templates'
            ],
            [
                'name' => 'Workflows',
                'route' => 'tickets.workflows.index',
                'icon' => 'cog-6-tooth',
                'key' => 'workflows'
            ],
            [
                'name' => 'Assignments',
                'route' => 'tickets.assignments.index',
                'icon' => 'user-group',
                'key' => 'assignments'
            ],
            [
                'name' => 'Export Tickets',
                'route' => 'tickets.export.csv',
                'icon' => 'arrow-up-tray',
                'key' => 'export'
            ]
        ]
    ],
    'assets' => [
        'title' => 'Asset Management',
        'items' => [
            [
                'name' => 'Overview',
                'route' => 'assets.index',
                'icon' => 'home',
                'key' => 'overview'
            ],
            [
                'name' => 'All Assets',
                'route' => 'assets.index',
                'icon' => 'computer-desktop',
                'key' => 'index'
            ],
            [
                'name' => 'Add New Asset',
                'route' => 'assets.create',
                'icon' => 'plus',
                'key' => 'create'
            ],
            [
                'type' => 'section',
                'title' => 'ASSET CATEGORIES'
            ],
            [
                'name' => 'Hardware',
                'route' => 'assets.index',
                'icon' => 'computer-desktop',
                'key' => 'hardware',
                'params' => ['category' => 'hardware']
            ],
            [
                'name' => 'Software',
                'route' => 'assets.index',
                'icon' => 'code-bracket',
                'key' => 'software',
                'params' => ['category' => 'software']
            ],
            [
                'name' => 'Licenses',
                'route' => 'assets.index',
                'icon' => 'key',
                'key' => 'licenses',
                'params' => ['category' => 'licenses']
            ],
            [
                'name' => 'Mobile Devices',
                'route' => 'assets.index',
                'icon' => 'device-phone-mobile',
                'key' => 'mobile',
                'params' => ['category' => 'mobile']
            ],
            [
                'type' => 'section',
                'title' => 'MANAGEMENT'
            ],
            [
                'name' => 'Check In/Out',
                'route' => 'assets.checkinout',
                'icon' => 'arrow-right-circle',
                'key' => 'checkinout'
            ],
            [
                'name' => 'Maintenance',
                'route' => 'assets.maintenance',
                'icon' => 'wrench-screwdriver',
                'key' => 'maintenance'
            ],
            [
                'name' => 'Warranties',
                'route' => 'assets.warranties',
                'icon' => 'shield-check',
                'key' => 'warranties'
            ],
            [
                'name' => 'Depreciation',
                'route' => 'assets.depreciation',
                'icon' => 'chart-line',
                'key' => 'depreciation'
            ],
            [
                'type' => 'section',
                'title' => 'TOOLS'
            ],
            [
                'name' => 'QR Code Generator',
                'route' => 'assets.index',
                'icon' => 'qr-code',
                'key' => 'qr-codes',
                'params' => ['view' => 'qr']
            ],
            [
                'name' => 'Print Labels',
                'route' => 'assets.index',
                'icon' => 'printer',
                'key' => 'labels',
                'params' => ['view' => 'labels']
            ],
            [
                'name' => 'Bulk Actions',
                'route' => 'assets.bulk',
                'icon' => 'squares-plus',
                'key' => 'bulk'
            ],
            [
                'type' => 'section',
                'title' => 'DATA MANAGEMENT'
            ],
            [
                'name' => 'Import Assets',
                'route' => 'assets.import.form',
                'icon' => 'arrow-down-tray',
                'key' => 'import'
            ],
            [
                'name' => 'Export Assets',
                'route' => 'assets.export',
                'icon' => 'arrow-up-tray',
                'key' => 'export'
            ],
            [
                'name' => 'Download Template',
                'route' => 'assets.template.download',
                'icon' => 'document-arrow-down',
                'key' => 'template'
            ],
            [
                'name' => 'Reports',
                'route' => 'reports.assets',
                'icon' => 'chart-bar',
                'key' => 'reports'
            ]
        ]
    ],
    'financial' => [
        'title' => 'Financial Management',
        'items' => [
            [
                'name' => 'Dashboard',
                'route' => 'financial.invoices.index',
                'icon' => 'chart-pie',
                'key' => 'dashboard'
            ],
            [
                'type' => 'section',
                'title' => 'Invoicing'
            ],
            [
                'name' => 'All Invoices',
                'route' => 'financial.invoices.index',
                'icon' => 'document-text',
                'key' => 'invoices'
            ],
            [
                'name' => 'Create Invoice',
                'route' => 'financial.invoices.create',
                'icon' => 'plus',
                'key' => 'create-invoice'
            ],
            [
                'name' => 'Export Invoices',
                'route' => 'financial.invoices.export.csv',
                'icon' => 'arrow-up-tray',
                'key' => 'export-invoices'
            ],
            [
                'type' => 'section',
                'title' => 'Payments'
            ],
            [
                'name' => 'All Payments',
                'route' => 'financial.payments.index',
                'icon' => 'credit-card',
                'key' => 'payments'
            ],
            [
                'name' => 'Record Payment',
                'route' => 'financial.payments.create',
                'icon' => 'plus-circle',
                'key' => 'create-payment'
            ],
            [
                'type' => 'section',
                'title' => 'Expenses'
            ],
            [
                'name' => 'All Expenses',
                'route' => 'financial.expenses.index',
                'icon' => 'receipt-percent',
                'key' => 'expenses'
            ],
            [
                'name' => 'Add Expense',
                'route' => 'financial.expenses.create',
                'icon' => 'plus',
                'key' => 'create-expense'
            ]
        ]
    ],
    'projects' => [
        'title' => 'Project Management',
        'items' => [
            [
                'name' => 'All Projects',
                'route' => 'projects.index',
                'icon' => 'folder',
                'key' => 'index'
            ],
            [
                'name' => 'Create Project',
                'route' => 'projects.create',
                'icon' => 'plus',
                'key' => 'create'
            ],
            [
                'type' => 'divider'
            ],
            [
                'name' => 'Active Projects',
                'route' => 'projects.index',
                'icon' => 'play',
                'key' => 'active',
                'params' => ['status' => 'active']
            ],
            [
                'name' => 'Completed Projects',
                'route' => 'projects.index',
                'icon' => 'check-circle',
                'key' => 'completed',
                'params' => ['status' => 'completed']
            ],
            [
                'type' => 'divider'
            ],
            [
                'name' => 'Project Timeline',
                'route' => 'projects.index',
                'icon' => 'calendar-days',
                'key' => 'timeline',
                'params' => ['view' => 'timeline']
            ]
        ]
    ],
    'reports' => [
        'title' => 'Reports & Analytics',
        'items' => [
            [
                'name' => 'Reports Dashboard',
                'route' => 'reports.index',
                'icon' => 'chart-bar',
                'key' => 'index'
            ],
            [
                'type' => 'section',
                'title' => 'Financial Reports'
            ],
            [
                'name' => 'Financial Overview',
                'route' => 'reports.financial',
                'icon' => 'currency-dollar',
                'key' => 'financial'
            ],
            [
                'name' => 'Invoice Reports',
                'route' => 'reports.financial',
                'icon' => 'document-text',
                'key' => 'invoices',
                'params' => ['type' => 'invoices']
            ],
            [
                'name' => 'Payment Reports',
                'route' => 'reports.financial',
                'icon' => 'credit-card',
                'key' => 'payments',
                'params' => ['type' => 'payments']
            ],
            [
                'type' => 'section',
                'title' => 'Operational Reports'
            ],
            [
                'name' => 'Ticket Reports',
                'route' => 'reports.tickets',
                'icon' => 'ticket',
                'key' => 'tickets'
            ],
            [
                'name' => 'Asset Reports',
                'route' => 'reports.assets',
                'icon' => 'computer-desktop',
                'key' => 'assets'
            ],
            [
                'name' => 'Client Reports',
                'route' => 'reports.clients',
                'icon' => 'users',
                'key' => 'clients'
            ],
            [
                'name' => 'Project Reports',
                'route' => 'reports.projects',
                'icon' => 'folder',
                'key' => 'projects'
            ],
            [
                'name' => 'User Reports',
                'route' => 'reports.users',
                'icon' => 'user-group',
                'key' => 'users'
            ]
        ]
    ]
];

$currentSidebar = $sidebarConfig[$activeDomain] ?? null;
@endphp

@if($currentSidebar)
<aside x-data="sidebarManager()" 
       x-init="init()"
       x-cloak
       data-sidebar
       :class="{
           'w-52': mode === 'expanded',
           'w-16': mode === 'compact', 
           'w-12': mode === 'mini'
       }"
       class="sidebar-loading bg-gradient-to-b from-white via-slate-50/30 to-white shadow-xl border-r border-gray-200/50 backdrop-blur-sm flex-shrink-0 transition-all duration-300 ease-in-out"
       style="min-width: 48px;">
    <div class="h-full flex flex-col">
        <!-- Ultra-Compact Sidebar Header -->
        <div class="border-b border-gray-200/60 bg-gradient-to-r from-indigo-50 via-blue-50 to-purple-50 transition-all duration-300"
             :class="{
                 'px-3 py-2': mode === 'expanded',
                 'px-2 py-1.5': mode === 'compact',
                 'px-1 py-1': mode === 'mini'
             }">
            <div class="flex items-center" :class="mode === 'expanded' ? 'space-x-2' : 'justify-center'">
                <div class="w-1.5 h-1.5 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full animate-pulse flex-shrink-0"></div>
                <h2 x-show="mode === 'expanded'" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-x-2"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-2"
                    class="text-sm font-semibold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent truncate">
                    {{ $currentSidebar['title'] }}
                </h2>
                <!-- Compact title tooltip -->
                <div x-show="mode !== 'expanded'" class="group relative">
                    <div class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                        {{ $currentSidebar['title'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Ultra-Compact Sidebar Navigation -->
        <nav class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 transition-all duration-300"
             :class="{
                 'px-2 py-2 space-y-0.5': mode === 'expanded',
                 'px-1 py-1 space-y-0': mode === 'compact',
                 'px-0.5 py-1 space-y-0': mode === 'mini'
             }">
            @foreach($currentSidebar['items'] as $item)
                @if(($item['type'] ?? null) === 'divider')
                    <hr class="my-3 border-gray-200">
                @elseif(($item['type'] ?? null) === 'section')
                    @php
                    $sectionClasses = 'px-4 py-3 relative';
                    $titleClasses = 'text-xs font-bold uppercase tracking-wider';
                    
                    // Priority section styling with gradients
                    if (($item['priority'] ?? false)) {
                        $titleClasses .= ' text-red-600';
                        $sectionClasses .= ' bg-gradient-to-r from-red-50 to-red-50/30 border-l-4 border-red-400 rounded-r-lg';
                    } elseif (($item['color'] ?? '') === 'red') {
                        $titleClasses .= ' text-red-500';
                    } else {
                        $titleClasses .= ' text-gray-600';
                    }
                    
                    // Collapsible section handling
                    $isCollapsible = ($item['collapsible'] ?? false);
                    $isDefaultCollapsed = ($item['default_collapsed'] ?? false);
                    @endphp
                    
                    <div class="transition-all duration-300 mb-1" 
                         :class="{
                             'mx-0.5 {{ $sectionClasses }}': mode === 'expanded',
                             'mx-0 px-1 py-0.5 text-center': mode !== 'expanded'
                         }"
                         @if($isCollapsible) data-collapsible data-section-id="{{ \Illuminate\Support\Str::slug($item['title']) }}" @if($isDefaultCollapsed) data-default-collapsed @endif @endif>
                        <h3 class="{{ $titleClasses }}" :class="mode === 'expanded' ? 'flex items-center space-x-1' : 'text-center'">
                            @if($isCollapsible)
                                <button class="focus:outline-none section-toggle hover:opacity-80 transition-opacity duration-200" 
                                        :class="mode === 'expanded' ? 'flex items-center w-full text-left' : 'w-full'"
                                        onclick="toggleSection(this)">
                                    <span x-show="mode === 'expanded'" class="transition-transform duration-300 mr-1 text-xs" data-toggle-icon>
                                        @if($isDefaultCollapsed) ▶ @else ▼ @endif
                                    </span>
                                    <span x-show="mode === 'expanded'" class="flex-1 text-xs">{{ $item['title'] }}</span>
                                    <div x-show="mode !== 'expanded'" class="w-1 h-1 bg-gray-400 rounded-full mx-auto"></div>
                                </button>
                                <!-- Section tooltip for compact modes -->
                                <div x-show="mode !== 'expanded'" class="group relative">
                                    <div class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        {{ $item['title'] }}
                                    </div>
                                </div>
                            @else
                                <span x-show="mode === 'expanded'" class="w-0.5 h-0.5 bg-gray-400 rounded-full"></span>
                                <span x-show="mode === 'expanded'" class="text-xs">{{ $item['title'] }}</span>
                                <div x-show="mode !== 'expanded'" class="w-0.5 h-0.5 bg-gray-400 rounded-full mx-auto group relative">
                                    <div class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        {{ $item['title'] }}
                                    </div>
                                </div>
                            @endif
                        </h3>
                    </div>
                @else
                    @php
                    $isActive = $activeItem === $item['key'];
                    $routeParams = $item['params'] ?? [];
                    $selectedClient = \App\Services\NavigationService::getSelectedClient();
                    
                    // Conditional display logic
                    $shouldDisplay = true;
                    if (isset($item['show_if'])) {
                        $condition = $item['show_if'];
                        $shouldDisplay = false;
                        
                        if ($selectedClient) {
                            switch ($condition) {
                                case 'has_open_tickets':
                                    $shouldDisplay = $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->exists();
                                    break;
                                case 'has_pending_items':
                                    // Check for overdue invoices or pending approvals
                                    $shouldDisplay = $selectedClient->invoices()->where('status', 'overdue')->exists() ||
                                                   $selectedClient->invoices()->where('status', 'draft')->exists();
                                    break;
                                case 'has_assets':
                                    $shouldDisplay = $selectedClient->assets()->count() > 0;
                                    break;
                            }
                        }
                    }
                    
                    // Skip if shouldn't display
                    if (!$shouldDisplay) {
                        continue;
                    }
                    
                    // Handle dynamic client parameter replacement
                    if (isset($routeParams['client']) && $routeParams['client'] === 'current') {
                        if ($selectedClient) {
                            $routeParams['client'] = $selectedClient->id;
                        } else {
                            continue;
                        }
                    }
                    
                    // Handle dynamic client_id parameter replacement
                    if (isset($routeParams['client_id']) && $routeParams['client_id'] === 'current') {
                        if ($selectedClient) {
                            $routeParams['client_id'] = $selectedClient->id;
                        } else {
                            continue;
                        }
                    }
                    
                    // Enhanced badge logic with smart counting
                    $badgeCount = 0;
                    $badgeType = $item['badge_type'] ?? 'info';
                    
                    if ($selectedClient) {
                        switch ($item['key']) {
                            case 'open-tickets':
                                $badgeCount = $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->count();
                                break;
                            case 'pending':
                                $badgeCount = $selectedClient->invoices()->where('status', 'overdue')->count() +
                                             $selectedClient->invoices()->where('status', 'draft')->count();
                                break;
                            case 'contacts':
                                $badgeCount = $selectedClient->contacts()->count();
                                break;
                            case 'locations':
                                $badgeCount = $selectedClient->locations()->count();
                                break;
                            case 'tickets':
                                $badgeCount = $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->count();
                                break;
                            case 'assets':
                                $badgeCount = $selectedClient->assets()->count();
                                break;
                            case 'invoices':
                                $badgeCount = $selectedClient->invoices()->whereIn('status', ['draft', 'sent'])->count();
                                break;
                        }
                    }
                    
                    // Badge styling based on type
                    $badgeClasses = 'ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';
                    switch ($badgeType) {
                        case 'urgent':
                            $badgeClasses .= $isActive ? ' bg-red-100 text-red-800' : ' bg-red-100 text-red-800';
                            break;
                        case 'warning':
                            $badgeClasses .= $isActive ? ' bg-yellow-100 text-yellow-800' : ' bg-yellow-100 text-yellow-800';
                            break;
                        default:
                            $badgeClasses .= $isActive ? ' bg-indigo-100 text-indigo-800' : ' bg-gray-100 text-gray-800';
                    }
                    
                    // Modern link styling with gradients
                    $classes = $isActive
                        ? 'bg-gradient-to-r from-indigo-50 to-indigo-100 border-indigo-500 text-indigo-700 group flex items-center px-4 py-3 text-sm font-semibold rounded-xl border-l-4 shadow-sm transform scale-105 transition-all duration-200'
                        : 'text-gray-600 hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-100 hover:text-gray-900 group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 hover:shadow-sm hover:transform hover:scale-102';
                        
                    // Parent section handling for collapsible sections
                    $parentSection = $item['parent_section'] ?? null;
                    $itemClasses = $parentSection ? "collapsible-item parent-{$parentSection}" : '';
                    @endphp
                    
                    <div class="{{ $itemClasses }} mb-0.5 relative group" @if($parentSection) data-parent-section="{{ $parentSection }}" @endif
                         :class="{
                             'mx-0.5': mode === 'expanded',
                             'mx-0': mode !== 'expanded'
                         }">
                        <a href="{{ route($item['route'], $routeParams) }}" 
                           class="relative flex items-center rounded-lg transition-all duration-200 group {{ $isActive ? 'bg-gradient-to-r from-indigo-50 to-indigo-100 border-indigo-500 text-indigo-700 border-l-2 shadow-sm' : 'text-gray-600 hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-100 hover:text-gray-900 hover:shadow-sm' }}" 
                           :class="{
                               'px-2 py-1.5': mode === 'expanded',
                               'px-1.5 py-1 justify-center': mode === 'compact',
                               'px-1 py-0.5 justify-center': mode === 'mini'
                           }"
                           @if($item['description'] ?? false) title="{{ $item['description'] }}" @endif>
                            
                            <!-- Icon -->
                            <span class="{{ $isActive ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-600' }} flex-shrink-0 h-4 w-4 flex items-center justify-center transition-colors duration-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                            </span>
                            
                            <!-- Text (expanded mode only) -->
                            <span x-show="mode === 'expanded'" 
                                  x-transition:enter="transition ease-out duration-200 delay-75"
                                  x-transition:enter-start="opacity-0 -translate-x-1"
                                  x-transition:enter-end="opacity-100 translate-x-0"
                                  x-transition:leave="transition ease-in duration-150"
                                  x-transition:leave-start="opacity-100 translate-x-0"
                                  x-transition:leave-end="opacity-0 -translate-x-1"
                                  class="flex-1 font-medium ml-2 text-sm">
                                {{ $item['name'] }}
                            </span>
                            
                            <!-- Badge (expanded mode) -->
                            @if($badgeCount > 0)
                                <span x-show="mode === 'expanded'" x-transition
                                      class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $badgeType === 'urgent' ? 'bg-red-100 text-red-800' : ($badgeType === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-indigo-100 text-indigo-800') }}">
                                    @if($badgeType === 'urgent' && $badgeCount > 0)
                                        <span class="flex items-center space-x-0.5">
                                            <span class="w-1 h-1 bg-red-500 rounded-full animate-pulse"></span>
                                            <span class="text-xs">{{ $badgeCount }}</span>
                                        </span>
                                    @elseif($badgeType === 'warning' && $badgeCount > 0)
                                        <span class="flex items-center space-x-0.5">
                                            <span class="w-1 h-1 bg-yellow-500 rounded-full"></span>
                                            <span class="text-xs">{{ $badgeCount }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs">{{ $badgeCount }}</span>
                                    @endif
                                </span>
                                
                                <!-- Badge indicator for compact modes -->
                                <div x-show="mode !== 'expanded'" class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-gradient-to-r {{ $badgeType === 'urgent' ? 'from-red-500 to-red-600' : ($badgeType === 'warning' ? 'from-yellow-500 to-yellow-600' : 'from-indigo-500 to-indigo-600') }} rounded-full text-white text-xs flex items-center justify-center">
                                    <span class="text-xs font-bold" style="font-size: 8px;">{{ $badgeCount > 9 ? '9' : $badgeCount }}</span>
                                </div>
                            @endif
                        </a>
                        
                        <!-- Hover tooltip for compact modes -->
                        <div x-show="mode !== 'expanded'" 
                             class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50 top-1/2 transform -translate-y-1/2">
                            {{ $item['name'] }}
                            @if($badgeCount > 0)
                                <span class="ml-1 px-1 py-0.5 bg-gray-700 rounded text-xs">{{ $badgeCount }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <!-- Ultra-Compact Sidebar Footer -->
        <div class="border-t border-gray-200/60 bg-gradient-to-r from-gray-50 to-gray-50/30 transition-all duration-300"
             :class="{
                 'px-2 py-1.5': mode === 'expanded',
                 'px-1 py-1': mode === 'compact', 
                 'px-0.5 py-0.5': mode === 'mini'
             }">
            <div class="flex items-center text-xs text-gray-500" :class="mode === 'expanded' ? 'justify-between' : 'justify-center'">
                <div class="flex items-center" :class="mode === 'expanded' ? 'space-x-1.5' : 'justify-center'">
                    <div class="w-2 h-2 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-1.5 h-1.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <span x-show="mode === 'expanded'" 
                          x-transition:enter="transition ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0 -translate-x-1"
                          x-transition:enter-end="opacity-100 translate-x-0"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100 translate-x-0"
                          x-transition:leave-end="opacity-0 -translate-x-1"
                          class="font-medium text-xs">{{ ucfirst($activeDomain) }}</span>
                </div>
                
                <!-- Mode Toggle Button -->
                <button @click="cycleMode()" 
                        class="text-gray-400 hover:text-gray-600 transition-colors duration-200 flex-shrink-0 p-0.5" 
                        :class="mode === 'expanded' ? '' : 'mt-1'"
                        :title="'Switch to ' + getNextModeLabel() + ' mode'">
                    <!-- Expanded mode icon -->
                    <svg x-show="mode === 'expanded'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <!-- Compact mode icon -->
                    <svg x-show="mode === 'compact'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <!-- Mini mode icon -->
                    <svg x-show="mode === 'mini'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </button>
                
                <!-- Mode tooltip for compact modes -->
                <div x-show="mode !== 'expanded'" class="group relative">
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                        {{ ucfirst($activeDomain) }} - Click to cycle
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Modern Sidebar Styling -->
<style>
/* Scrollbar Styling */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 9999px;
}

.scrollbar-track-gray-100::-webkit-scrollbar-track {
    background-color: #f3f4f6;
}

/* Prevent flash of unstyled content */
[x-cloak] {
    display: none !important;
}

/* Smooth loading state */
.sidebar-loading {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.sidebar-loaded {
    opacity: 1;
}

/* Enhanced transitions */
.collapsible-item {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
    overflow: hidden;
}

.collapsible-section.collapsed .collapsible-item {
    opacity: 0;
    max-height: 0;
    transform: scaleY(0);
    margin: 0;
    padding: 0;
}

.section-toggle {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.section-toggle:hover {
    transform: translateX(2px);
}

.section-toggle [data-toggle-icon] {
    display: inline-block;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.priority-section {
    position: sticky;
    top: 0;
    background: linear-gradient(to right, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.95));
    backdrop-filter: blur(8px);
    z-index: 10;
    border-bottom: 1px solid rgba(243, 244, 246, 0.6);
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-radius: 0 0 12px 12px;
}

/* Hover effects */
a:hover {
    transform: translateX(2px);
}

/* Scale utilities */
.hover\:scale-102:hover {
    transform: scale(1.02);
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -280px;
        transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 50;
        backdrop-filter: blur(12px);
    }
    
    .sidebar.open {
        left: 0;
    }
    
    .priority-section {
        position: sticky;
        top: 0;
        background: linear-gradient(to right, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
        backdrop-filter: blur(12px);
        z-index: 20;
    }
    
    .collapsible-section:not(.priority-section) {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .collapsible-section.expanded:not(.priority-section) {
        max-height: 1200px;
    }

    /* Mobile-specific animations */
    .sidebar a {
        transition: all 0.2s ease;
    }
    
    .sidebar a:active {
        transform: scale(0.98);
    }
}
</style>

<!-- Sidebar Manager Alpine.js Component -->
<script>
function sidebarManager() {
    return {
        mode: 'expanded', // 'expanded', 'compact', 'mini'
        initialized: false,
        
        init() {
            // Load preferences immediately before Alpine renders
            this.loadPreferences();
            this.setupResponsiveMode();
            
            // Mark as initialized and setup animations
            this.$nextTick(() => {
                this.initialized = true;
                this.setupAnimations();
                
                // Add loaded class for additional styling
                this.$el.classList.add('sidebar-loaded');
                this.$el.classList.remove('sidebar-loading');
            });
            
            // Listen for window resize to adjust mode automatically
            window.addEventListener('resize', () => {
                if (this.initialized) {
                    this.setupResponsiveMode();
                }
            });
        },
        
        cycleMode() {
            const modes = ['expanded', 'compact', 'mini'];
            const currentIndex = modes.indexOf(this.mode);
            const nextIndex = (currentIndex + 1) % modes.length;
            this.mode = modes[nextIndex];
            this.savePreferences();
        },
        
        setMode(newMode) {
            if (['expanded', 'compact', 'mini'].includes(newMode)) {
                this.mode = newMode;
                this.savePreferences();
            }
        },
        
        getNextModeLabel() {
            const labels = {
                expanded: 'compact',
                compact: 'mini',
                mini: 'expanded'
            };
            return labels[this.mode] || 'expanded';
        },
        
        setupResponsiveMode() {
            const width = window.innerWidth;
            const savedMode = localStorage.getItem('sidebarMode');
            
            // Use saved preference if available, otherwise auto-adjust
            if (savedMode && ['expanded', 'compact', 'mini'].includes(savedMode)) {
                this.mode = savedMode;
            } else {
                // Auto-adjust mode based on screen size
                if (width < 768) {
                    this.mode = 'mini'; // Mobile
                } else if (width < 1024) {
                    this.mode = 'compact'; // Tablet
                } else if (width < 1280) {
                    this.mode = 'compact'; // Small desktop
                } else {
                    this.mode = 'expanded'; // Large desktop
                }
                // Save the auto-selected mode
                this.savePreferences();
            }
        },
        
        loadPreferences() {
            try {
                const savedMode = localStorage.getItem('sidebarMode');
                if (savedMode && ['expanded', 'compact', 'mini'].includes(savedMode)) {
                    this.mode = savedMode;
                    return;
                }
            } catch (e) {
                console.warn('Could not load sidebar preferences:', e);
            }
            
            // Fallback to responsive mode if no saved preference
            this.setDefaultMode();
        },
        
        setDefaultMode() {
            const width = window.innerWidth;
            if (width < 768) {
                this.mode = 'mini';
            } else if (width < 1280) {
                this.mode = 'compact';
            } else {
                this.mode = 'expanded';
            }
        },
        
        savePreferences() {
            try {
                localStorage.setItem('sidebarMode', this.mode);
            } catch (e) {
                console.warn('Could not save sidebar preferences:', e);
            }
            
            // Emit event for other components
            if (this.initialized) {
                window.dispatchEvent(new CustomEvent('sidebar-mode-changed', {
                    detail: { mode: this.mode }
                }));
            }
        },
        
        setupAnimations() {
            // Add stagger animation to sidebar items with fade-in effect
            this.$nextTick(() => {
                const items = this.$el.querySelectorAll('a');
                items.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-10px)';
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.animationDelay = `${index * 0.05}s`;
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, index * 20);
                });
                
                // Remove x-cloak after animations start
                setTimeout(() => {
                    this.$el.removeAttribute('x-cloak');
                }, 50);
            });
        },
        
        // Utility methods for mode checking
        isExpanded() {
            return this.mode === 'expanded';
        },
        
        isCompact() {
            return this.mode === 'compact';
        },
        
        isMini() {
            return this.mode === 'mini';
        }
    };
}
</script>

<!-- Progressive Disclosure JavaScript -->
<script>
function toggleSection(button) {
    const sectionDiv = button.closest('[data-collapsible]');
    const toggleIcon = button.querySelector('[data-toggle-icon]');
    const sectionId = sectionDiv.dataset.sectionId;
    
    // Toggle collapsed state
    sectionDiv.classList.toggle('collapsed');
    
    // Update icon
    if (sectionDiv.classList.contains('collapsed')) {
        toggleIcon.textContent = '▶';
        // Hide items
        const items = document.querySelectorAll(`[data-parent-section="${sectionId.replace('-', '')}"]`);
        items.forEach(item => {
            item.style.maxHeight = '0';
            item.style.opacity = '0';
            item.style.overflow = 'hidden';
        });
    } else {
        toggleIcon.textContent = '▼';
        // Show items
        const items = document.querySelectorAll(`[data-parent-section="${sectionId.replace('-', '')}"]`);
        items.forEach(item => {
            item.style.maxHeight = 'none';
            item.style.opacity = '1';
            item.style.overflow = 'visible';
        });
    }
    
    // Save user preference
    saveCollapsibleState(sectionId, sectionDiv.classList.contains('collapsed'));
}

function saveCollapsibleState(sectionId, isCollapsed) {
    if (typeof(Storage) !== "undefined") {
        localStorage.setItem(`sidebar_${sectionId}_collapsed`, isCollapsed ? '1' : '0');
    }
}

function loadCollapsibleStates() {
    if (typeof(Storage) !== "undefined") {
        document.querySelectorAll('[data-collapsible]').forEach(section => {
            const sectionId = section.dataset.sectionId;
            const savedState = localStorage.getItem(`sidebar_${sectionId}_collapsed`);
            const defaultCollapsed = section.hasAttribute('data-default-collapsed');
            
            const shouldBeCollapsed = savedState !== null ?
                (savedState === '1') :
                defaultCollapsed;
                
            if (shouldBeCollapsed) {
                section.classList.add('collapsed');
                const button = section.querySelector('.section-toggle');
                if (button) {
                    const toggleIcon = button.querySelector('[data-toggle-icon]');
                    if (toggleIcon) {
                        toggleIcon.textContent = '▶';
                    }
                }
                
                // Hide items immediately
                const items = document.querySelectorAll(`[data-parent-section="${sectionId.replace('-', '')}"]`);
                items.forEach(item => {
                    item.style.maxHeight = '0';
                    item.style.opacity = '0';
                    item.style.overflow = 'hidden';
                });
            }
        });
    }
}

// Mobile sidebar toggle
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCollapsibleStates();
    
    // Add mobile hamburger if doesn't exist
    if (window.innerWidth <= 768 && !document.querySelector('.mobile-sidebar-toggle')) {
        const toggle = document.createElement('button');
        toggle.className = 'mobile-sidebar-toggle fixed top-4 left-4 z-50 bg-white p-2 rounded shadow-md md:hidden';
        toggle.innerHTML = '☰';
        toggle.onclick = toggleMobileSidebar;
        document.body.appendChild(toggle);
    }
});

// Handle window resize with modern improvements
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        if (window.innerWidth > 768) {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('open');
            }
        }
        
        // Update compact mode based on screen size
        const compactBreakpoint = 1280; // xl breakpoint
        if (window.innerWidth < compactBreakpoint && localStorage.getItem('autoCompact') !== 'false') {
            document.querySelectorAll('[x-data*="modernSidebar"]').forEach(el => {
                el._x_dataStack[0].compact = true;
            });
        }
    }, 100);
});
</script>
@endif