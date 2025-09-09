@props(['activeDomain' => null, 'activeItem' => null, 'mobile' => false])

@php
$mobile = $mobile ?? false;
$selectedClient = \App\Services\NavigationService::getSelectedClient();

// Advanced sidebar configuration with grouped sections
$sidebarConfig = [
    'settings' => [
        'title' => 'Settings',
        'icon' => 'cog-6-tooth',
        'groups' => [
            [
                'heading' => 'Account',
                'expandable' => false,
                'items' => [
                    ['name' => 'Profile Information', 'route' => 'users.profile', 'icon' => 'user', 'key' => 'profile'],
                    ['name' => 'Security Settings', 'route' => 'settings.security', 'icon' => 'lock-closed', 'key' => 'security'],
                    ['name' => 'Preferences', 'route' => 'users.profile', 'icon' => 'adjustments-horizontal', 'key' => 'preferences'],
                ]
            ],
            [
                'heading' => 'Company',
                'expandable' => true,
                'default_expanded' => false,
                'items' => [
                    ['name' => 'General Settings', 'route' => 'settings.general', 'icon' => 'building-office', 'key' => 'general'],
                    ['name' => 'Billing & Financial', 'route' => 'settings.billing-financial', 'icon' => 'currency-dollar', 'key' => 'billing'],
                    ['name' => 'User Management', 'route' => 'settings.user-management', 'icon' => 'users', 'key' => 'users'],
                    ['name' => 'Roles & Permissions', 'route' => 'settings.roles.index', 'icon' => 'shield-check', 'key' => 'roles'],
                ]
            ],
            [
                'heading' => 'System',
                'expandable' => true,
                'default_expanded' => false,
                'items' => [
                    ['name' => 'Email Configuration', 'route' => 'settings.email', 'icon' => 'envelope', 'key' => 'email'],
                    ['name' => 'Integrations', 'route' => 'settings.integrations', 'icon' => 'puzzle-piece', 'key' => 'integrations'],
                    ['name' => 'API & Webhooks', 'route' => 'settings.api-webhooks', 'icon' => 'code-bracket', 'key' => 'api'],
                    ['name' => 'Backup & Recovery', 'route' => 'settings.backup-recovery', 'icon' => 'archive-box', 'key' => 'backup'],
                ]
            ],
            [
                'heading' => 'Customization',
                'expandable' => true,
                'default_expanded' => false,
                'items' => [
                    ['name' => 'Ticketing Settings', 'route' => 'settings.ticketing-service-desk', 'icon' => 'ticket', 'key' => 'ticketing'],
                    ['name' => 'Asset Management', 'route' => 'settings.asset-inventory', 'icon' => 'computer-desktop', 'key' => 'assets'],
                    ['name' => 'Project Settings', 'route' => 'settings.project-management', 'icon' => 'folder', 'key' => 'projects'],
                    ['name' => 'Client Portal', 'route' => 'settings.client-portal', 'icon' => 'globe-alt', 'key' => 'portal'],
                ]
            ]
        ]
    ],
    'clients' => [
        'title' => 'Client Management',
        'icon' => 'user-group',
        'groups' => [
            // OVERVIEW SECTION - Direct access to client dashboard
            [
                'heading' => 'Overview',
                'expandable' => false,
                'items' => [
                    ['name' => 'Client Details', 'route' => 'clients.show', 'icon' => 'chart-pie', 'key' => 'details', 'params' => ['client' => 'current']],
                ]
            ],

            // CONTACTS & LOCATIONS - Core client information
            [
                'heading' => null, // No heading, shown directly
                'expandable' => false,
                'items' => [
                    ['name' => 'Contacts', 'route' => 'clients.contacts.index', 'icon' => 'users', 'key' => 'contacts', 'params' => ['client' => 'current'], 'badge' => 'getContactCount'],
                    ['name' => 'Locations', 'route' => 'clients.locations.index', 'icon' => 'map-pin', 'key' => 'locations', 'params' => ['client' => 'current'], 'badge' => 'getLocationCount'],
                ]
            ],

            // SUPPORT SECTION - Active support and project management
            [
                'heading' => 'SUPPORT',
                'expandable' => true,
                'default_expanded' => true,
                'items' => [
                    ['name' => 'Tickets', 'route' => 'tickets.index', 'icon' => 'ticket', 'key' => 'tickets', 'badge' => 'getOpenTicketCount'],
                    ['name' => 'Recurring Tickets', 'route' => 'tickets.recurring.index', 'icon' => 'arrow-path', 'key' => 'recurring-tickets', 'badge' => 'getRecurringTicketCount'],
                    ['name' => 'Projects', 'route' => 'projects.index', 'icon' => 'folder', 'key' => 'projects', 'badge' => 'getProjectCount'],
                    // ['name' => 'Vendors', 'route' => 'clients.vendors.index', 'icon' => 'building-storefront', 'key' => 'vendors', 'params' => ['client' => 'current'], 'badge' => 'getVendorCount'], // Route not yet implemented
                    ['name' => 'Calendar', 'route' => 'clients.calendar-events.index', 'icon' => 'calendar-days', 'key' => 'calendar', 'params' => ['client' => 'current'], 'badge' => 'getCalendarEventCount'],
                ]
            ],

            // DOCUMENTATION SECTION - IT infrastructure and documentation
            [
                'heading' => 'DOCUMENTATION',
                'expandable' => true,
                'default_expanded' => true,
                'items' => [
                    ['name' => 'Assets', 'route' => 'clients.assets.index', 'icon' => 'computer-desktop', 'key' => 'assets', 'params' => ['client' => 'current'], 'badge' => 'getAssetCount'],
                    ['name' => 'Licenses', 'route' => 'clients.licenses.index', 'icon' => 'key', 'key' => 'licenses', 'params' => ['client' => 'current'], 'badge' => 'getLicenseCount'],
                    ['name' => 'Credentials', 'route' => 'clients.credentials.index', 'icon' => 'lock-closed', 'key' => 'credentials', 'params' => ['client' => 'current'], 'badge' => 'getCredentialCount'],
                    ['name' => 'Networks', 'route' => 'clients.networks.index', 'icon' => 'wifi', 'key' => 'networks', 'params' => ['client' => 'current'], 'badge' => 'getNetworkCount'],
                    ['name' => 'Racks', 'route' => 'clients.racks.index', 'icon' => 'server-stack', 'key' => 'racks', 'params' => ['client' => 'current'], 'badge' => 'getRackCount'],
                    ['name' => 'Certificates', 'route' => 'clients.certificates.index', 'icon' => 'shield-check', 'key' => 'certificates', 'params' => ['client' => 'current'], 'badge' => 'getCertificateCount'],
                    ['name' => 'Domains', 'route' => 'clients.domains.index', 'icon' => 'globe-alt', 'key' => 'domains', 'params' => ['client' => 'current'], 'badge' => 'getDomainCount'],
                    ['name' => 'Services', 'route' => 'clients.services.index', 'icon' => 'cog', 'key' => 'services', 'params' => ['client' => 'current'], 'badge' => 'getServiceCount'],
                    ['name' => 'Documents', 'route' => 'clients.documents.index', 'icon' => 'document', 'key' => 'documents', 'params' => ['client' => 'current'], 'badge' => 'getDocumentCount'],
                    ['name' => 'Files', 'route' => 'clients.files.index', 'icon' => 'folder-open', 'key' => 'files', 'params' => ['client' => 'current'], 'badge' => 'getFileCount'],
                ]
            ],

            // BILLING SECTION - Financial management
            [
                'heading' => 'BILLING & FINANCE',
                'expandable' => true,
                'default_expanded' => true,
                'items' => [
                    ['name' => 'Contracts', 'route' => 'financial.contracts.index', 'icon' => 'document-duplicate', 'key' => 'contracts', 'badge' => 'getContractCount'],
                    ['name' => 'Quotes', 'route' => 'financial.quotes.index', 'icon' => 'document-currency-dollar', 'key' => 'quotes', 'badge' => 'getQuoteCount'],
                    ['name' => 'Invoices', 'route' => 'financial.invoices.index', 'icon' => 'document-text', 'key' => 'invoices', 'badge' => 'getInvoiceCount'],
                    ['name' => 'Payments', 'route' => 'financial.payments.index', 'icon' => 'credit-card', 'key' => 'payments', 'badge' => 'getPaymentCount'],
                ]
            ]
        ]
    ],
    'tickets' => [
        'title' => 'Ticket Management',
        'icon' => 'ticket',
        'groups' => [
            [
                'heading' => 'Overview',
                'items' => [
                    ['name' => 'All Tickets', 'route' => 'tickets.index', 'icon' => 'home', 'key' => 'overview'],
                    ['name' => 'Create Ticket', 'route' => 'tickets.create', 'icon' => 'plus', 'key' => 'create']
                ]
            ],
            [
                'heading' => 'My Work',
                'expandable' => true,
                'items' => [
                    ['name' => 'My Tickets', 'route' => 'tickets.index', 'icon' => 'user', 'key' => 'my-tickets', 'params' => ['filter' => 'my']],
                    ['name' => 'Assigned to Me', 'route' => 'tickets.index', 'icon' => 'user', 'key' => 'assigned', 'params' => ['assigned' => 'me']]
                ]
            ],
            [
                'heading' => 'Status',
                'expandable' => true,
                'items' => [
                    ['name' => 'Open Tickets', 'route' => 'tickets.index', 'icon' => 'exclamation-circle', 'key' => 'open', 'params' => ['status' => 'open']],
                    ['name' => 'In Progress', 'route' => 'tickets.index', 'icon' => 'clock', 'key' => 'in-progress', 'params' => ['status' => 'in-progress']],
                    ['name' => 'Resolved', 'route' => 'tickets.index', 'icon' => 'check-circle', 'key' => 'resolved', 'params' => ['status' => 'resolved']]
                ]
            ]
        ]
    ],
    'assets' => [
        'title' => 'Asset Management',
        'icon' => 'computer-desktop',
        'groups' => [
            [
                'heading' => 'Overview',
                'items' => [
                    ['name' => 'All Assets', 'route' => 'assets.index', 'icon' => 'home', 'key' => 'overview'],
                    ['name' => 'Add Asset', 'route' => 'assets.create', 'icon' => 'plus', 'key' => 'create']
                ]
            ],
            [
                'heading' => 'Categories',
                'expandable' => true,
                'items' => [
                    ['name' => 'All Assets', 'route' => 'assets.index', 'icon' => 'computer-desktop', 'key' => 'index'],
                    ['name' => 'Hardware', 'route' => 'assets.index', 'icon' => 'computer-desktop', 'key' => 'hardware', 'params' => ['category' => 'hardware']],
                    ['name' => 'Software', 'route' => 'assets.index', 'icon' => 'code-bracket', 'key' => 'software', 'params' => ['category' => 'software']],
                    ['name' => 'Licenses', 'route' => 'assets.index', 'icon' => 'key', 'key' => 'licenses', 'params' => ['category' => 'license']]
                ]
            ]
        ]
    ],
    'financial' => [
        'title' => 'Financial Management',
        'icon' => 'currency-dollar',
        'groups' => [
            [
                'heading' => 'Overview',
                'items' => [
                    ['name' => 'Financial Overview', 'route' => 'financial.dashboard', 'icon' => 'chart-bar', 'key' => 'overview']
                ]
            ],
            [
                'heading' => 'Contracts & Billing',
                'expandable' => true,
                'items' => [
                    ['name' => 'Contracts', 'route' => 'financial.contracts.index', 'icon' => 'document-check', 'key' => 'contracts'],
                    ['name' => 'Invoices', 'route' => 'financial.invoices.index', 'icon' => 'document-text', 'key' => 'invoices'],
                    ['name' => 'Payments', 'route' => 'financial.payments.index', 'icon' => 'credit-card', 'key' => 'payments']
                ]
            ],
            [
                'heading' => 'Reports',
                'expandable' => true,
                'items' => [
                    ['name' => 'Revenue Reports', 'route' => 'financial.reports.revenue', 'icon' => 'chart-line', 'key' => 'revenue'],
                    ['name' => 'Expense Reports', 'route' => 'financial.reports.expenses', 'icon' => 'chart-pie', 'key' => 'expenses']
                ]
            ]
        ]
    ]
];

$currentSidebar = $sidebarConfig[$activeDomain] ?? null;

// Helper to resolve params
$resolveParams = function($params, $selectedClient) {
    $resolved = [];
    foreach ($params as $key => $value) {
        if ($value === 'current' && $selectedClient) {
            $resolved[$key] = $selectedClient->id;
        } else {
            $resolved[$key] = $value;
        }
    }
    return $resolved;
};

// Helper to get badge counts from database
$getBadgeCount = function($badgeType, $selectedClient) {
    if (!$selectedClient) {
        return null;
    }

    try {
        switch ($badgeType) {
            // CONTACTS & LOCATIONS
            case 'getContactCount':
                // Use ClientContact model if exists, fallback to Contact
                if (class_exists('\App\Domains\Client\Models\ClientContact')) {
                    return \App\Domains\Client\Models\ClientContact::where('client_id', $selectedClient->id)->count();
                }
                return $selectedClient->contacts()->count();

            case 'getLocationCount':
                return $selectedClient->locations()->count();

            // SUPPORT SECTION
            case 'getOpenTicketCount':
                // Use domain model if exists
                if (class_exists('\App\Domains\Ticket\Models\Ticket')) {
                    return \App\Domains\Ticket\Models\Ticket::where('client_id', $selectedClient->id)
                        ->whereIn('status', ['open', 'in-progress'])
                        ->count();
                }
                return $selectedClient->tickets()
                    ->whereIn('status', ['open', 'in-progress'])
                    ->count();

            case 'getRecurringTicketCount':
                if (class_exists('\App\Domains\Ticket\Models\RecurringTicket')) {
                    return \App\Domains\Ticket\Models\RecurringTicket::where('client_id', $selectedClient->id)
                        ->where('is_active', true)
                        ->count();
                }
                return 0;

            case 'getProjectCount':
                return $selectedClient->projects()
                    ->whereIn('status', ['active', 'in-progress', 'planning'])
                    ->count();

            case 'getVendorCount':
                if (class_exists('\App\Domains\Client\Models\ClientVendor')) {
                    return \App\Domains\Client\Models\ClientVendor::where('client_id', $selectedClient->id)
                        ->where('is_active', true)
                        ->count();
                }
                return \App\Models\Vendor::where('company_id', $selectedClient->company_id)
                    ->whereHas('clients', function($q) use ($selectedClient) {
                        $q->where('client_id', $selectedClient->id);
                    })
                    ->count();

            case 'getCalendarEventCount':
                if (class_exists('\App\Domains\Client\Models\ClientCalendarEvent')) {
                    return \App\Domains\Client\Models\ClientCalendarEvent::where('client_id', $selectedClient->id)
                        ->where('start_date', '>=', now())
                        ->where('start_date', '<=', now()->addDays(30))
                        ->count();
                }
                return 0;

            // DOCUMENTATION SECTION
            case 'getAssetCount':
                return $selectedClient->assets()->count();

            case 'getLicenseCount':
                if (class_exists('\App\Domains\Client\Models\ClientLicense')) {
                    return \App\Domains\Client\Models\ClientLicense::where('client_id', $selectedClient->id)
                        ->where('is_active', true)
                        ->count();
                }
                return 0;

            case 'getCredentialCount':
                if (class_exists('\App\Domains\Client\Models\ClientCredential')) {
                    return \App\Domains\Client\Models\ClientCredential::where('client_id', $selectedClient->id)
                        ->count();
                }
                return 0;

            case 'getNetworkCount':
                if (class_exists('\App\Domains\Client\Models\ClientNetwork')) {
                    return \App\Domains\Client\Models\ClientNetwork::where('client_id', $selectedClient->id)
                        ->count();
                }
                return \App\Models\Network::where('client_id', $selectedClient->id)->count();

            case 'getRackCount':
                if (class_exists('\App\Domains\Client\Models\ClientRack')) {
                    return \App\Domains\Client\Models\ClientRack::where('client_id', $selectedClient->id)
                        ->count();
                }
                return 0;

            case 'getCertificateCount':
                if (class_exists('\App\Domains\Client\Models\ClientCertificate')) {
                    return \App\Domains\Client\Models\ClientCertificate::where('client_id', $selectedClient->id)
                        ->where('expiry_date', '>', now())
                        ->count();
                }
                return 0;

            case 'getDomainCount':
                if (class_exists('\App\Domains\Client\Models\ClientDomain')) {
                    return \App\Domains\Client\Models\ClientDomain::where('client_id', $selectedClient->id)
                        ->where('is_active', true)
                        ->count();
                }
                return 0;

            case 'getServiceCount':
                if (class_exists('\App\Domains\Client\Models\ClientService')) {
                    return \App\Domains\Client\Models\ClientService::where('client_id', $selectedClient->id)
                        ->where('is_active', true)
                        ->count();
                }
                return \App\Models\Service::where('client_id', $selectedClient->id)->count();

            case 'getDocumentCount':
                if (class_exists('\App\Domains\Client\Models\ClientDocument')) {
                    return \App\Domains\Client\Models\ClientDocument::where('client_id', $selectedClient->id)
                        ->count();
                }
                return \App\Models\Document::where('client_id', $selectedClient->id)->count();

            case 'getFileCount':
                if (class_exists('\App\Domains\Client\Models\ClientFile')) {
                    return \App\Domains\Client\Models\ClientFile::where('client_id', $selectedClient->id)
                        ->count();
                }
                return \App\Models\File::where('client_id', $selectedClient->id)->count();

            // BILLING SECTION
            case 'getInvoiceCount':
                return $selectedClient->invoices()
                    ->whereIn('status', ['draft', 'sent', 'overdue'])
                    ->count();

            case 'getRecurringInvoiceCount':
                if (class_exists('\App\Domains\Client\Models\ClientRecurringInvoice')) {
                    return \App\Domains\Client\Models\ClientRecurringInvoice::where('client_id', $selectedClient->id)
                        ->where('status', 'active')
                        ->count();
                }
                return $selectedClient->recurringInvoices()
                    ->where('status', true)
                    ->count();

            case 'getContractCount':
                if (class_exists('\App\Domains\Contract\Models\Contract')) {
                    return \App\Domains\Contract\Models\Contract::where('client_id', $selectedClient->id)
                        ->where('status', 'active')
                        ->count();
                }
                return $selectedClient->contracts()
                    ->where('status', 'active')
                    ->count();

            case 'getQuoteCount':
                if (class_exists('\App\Domains\Financial\Models\Quote')) {
                    return \App\Domains\Financial\Models\Quote::where('client_id', $selectedClient->id)
                        ->whereIn('status', ['draft', 'sent', 'pending'])
                        ->count();
                }
                if (class_exists('\App\Models\Quote')) {
                    return \App\Models\Quote::where('client_id', $selectedClient->id)
                        ->whereIn('status', ['draft', 'sent', 'pending'])
                        ->count();
                }
                return 0;

            case 'getPaymentCount':
                // Show recent payments (last 30 days)
                return $selectedClient->payments()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
            
            case 'getExpenseCount':
                // Show expenses for this client
                if (class_exists('\App\Models\Expense')) {
                    return \App\Models\Expense::where('client_id', $selectedClient->id)
                        ->where('company_id', $selectedClient->company_id)
                        ->count();
                }
                return 0;

            case 'getTripCount':
                if (class_exists('\App\Domains\Client\Models\ClientTrip')) {
                    return \App\Domains\Client\Models\ClientTrip::where('client_id', $selectedClient->id)
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->count();
                }
                return 0;

            default:
                return null;
        }
    } catch (\Exception $e) {
        // Log error for debugging if needed
        \Log::debug('Badge count error for ' . $badgeType . ': ' . $e->getMessage());
        return null;
    }
};
@endphp

@if($currentSidebar)
    <div class="w-full flex flex-col h-full px-4 bg-white dark:bg-zinc-900">
        
        <!-- Sidebar Header -->
        <div class="flex-shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-zinc-800 dark:to-zinc-900 -mx-4">
            <div class="flex items-center px-4 py-4 space-x-3">
                <flux:icon name="{{ $currentSidebar['icon'] }}" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="text-zinc-900 dark:text-zinc-100 truncate">
                        {{ $currentSidebar['title'] }}
                    </flux:heading>
                    @if($selectedClient)
                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 truncate">
                            {{ $selectedClient->display_name }}
                        </flux:text>
                    @endif
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto {{ $mobile ? 'pb-4' : '' }}">
            <flux:navlist class="space-y-0">
                @foreach($currentSidebar['groups'] as $group)
                    @if($group['expandable'] ?? false)
                        <flux:navlist.group 
                            heading="{{ $group['heading'] ?? '' }}" 
                            expandable 
                            :expanded="$group['default_expanded'] ?? false">
                            @foreach($group['items'] as $item)
                                @php
                                    // Skip items where route doesn't exist
                                    if (!Route::has($item['route'])) {
                                        continue;
                                    }
                                    $isActive = $activeItem === $item['key'];
                                    $routeParams = $resolveParams($item['params'] ?? [], $selectedClient);
                                    $badgeCount = isset($item['badge']) ? $getBadgeCount($item['badge'], $selectedClient) : null;
                                @endphp
                                
                                @if($badgeCount)
                                    <flux:navlist.item 
                                        href="{{ route($item['route'], $routeParams) }}" 
                                        :current="$isActive"
                                        icon="{{ $item['icon'] }}"
                                        badge="{{ $badgeCount }}"
                                        class="{{ $mobile ? 'py-6 text-base min-h-[44px]' : '' }}"
                                    >
                                        {{ $item['name'] }}
                                    </flux:navlist.item>
                                @else
                                    <flux:navlist.item 
                                        href="{{ route($item['route'], $routeParams) }}" 
                                        :current="$isActive"
                                        icon="{{ $item['icon'] }}"
                                        class="{{ $mobile ? 'py-6 text-base min-h-[44px]' : '' }}"
                                    >
                                        {{ $item['name'] }}
                                    </flux:navlist.item>
                                @endif
                            @endforeach
                        </flux:navlist.group>
                    @else
                        <flux:navlist.group heading="{{ $group['heading'] ?? '' }}" class="mt-6 first:mt-0">
                            @foreach($group['items'] as $item)
                                @php
                                    // Skip items where route doesn't exist
                                    if (!Route::has($item['route'])) {
                                        continue;
                                    }
                                    $isActive = $activeItem === $item['key'];
                                    $routeParams = $resolveParams($item['params'] ?? [], $selectedClient);
                                    $badgeCount = isset($item['badge']) ? $getBadgeCount($item['badge'], $selectedClient) : null;
                                @endphp
                                
                                @if($badgeCount)
                                    <flux:navlist.item 
                                        href="{{ route($item['route'], $routeParams) }}" 
                                        :current="$isActive"
                                        icon="{{ $item['icon'] }}"
                                        badge="{{ $badgeCount }}"
                                        class="{{ $mobile ? 'py-6 text-base min-h-[44px]' : '' }}"
                                    >
                                        {{ $item['name'] }}
                                    </flux:navlist.item>
                                @else
                                    <flux:navlist.item 
                                        href="{{ route($item['route'], $routeParams) }}" 
                                        :current="$isActive"
                                        icon="{{ $item['icon'] }}"
                                        class="{{ $mobile ? 'py-6 text-base min-h-[44px]' : '' }}"
                                    >
                                        {{ $item['name'] }}
                                    </flux:navlist.item>
                                @endif
                            @endforeach
                        </flux:navlist.group>
                    @endif
                @endforeach
            </flux:navlist>
        </div>

        <!-- Footer with Status -->
        <div class="flex-shrink-0 border-t border-zinc-200 dark:border-zinc-700 -mx-4">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-gradient-to-r from-green-400 to-blue-500 rounded-full animate-pulse"></div>
                    <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 font-medium">
                        {{ ucfirst($activeDomain) }} Active
                    </flux:text>
                </div>
                @if($selectedClient)
                    <flux:icon name="building-office" class="w-4 h-4 text-zinc-400" />
                @endif
            </div>
        </div>
    </div>
@endif
