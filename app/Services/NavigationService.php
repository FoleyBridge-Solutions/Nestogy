<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationService
{
    /**
     * Domain route mappings
     */
    protected static $domainMappings = [
        'clients' => [
            'routes' => [
                'clients.*',
            ],
            'patterns' => ['clients']
        ],
        'tickets' => [
            'routes' => [
                'tickets.*',
            ],
            'patterns' => ['tickets']
        ],
        'assets' => [
            'routes' => [
                'assets.*',
            ],
            'patterns' => ['assets']
        ],
        'financial' => [
            'routes' => [
                'financial.*',
            ],
            'patterns' => ['financial']
        ],
        'projects' => [
            'routes' => [
                'projects.*',
            ],
            'patterns' => ['projects']
        ],
        'reports' => [
            'routes' => [
                'reports.*',
            ],
            'patterns' => ['reports']
        ],
    ];

    /**
     * Navigation item mappings for each domain
     */
    protected static $navigationMappings = [
        'clients' => [
            // Client Selection & Management
            'clients.index' => 'index',
            'clients.create' => 'create',
            'clients.leads' => 'leads',
            'clients.import.form' => 'import',
            'clients.import' => 'import',
            'clients.export' => 'export',
            'clients.export.csv' => 'export',
            'clients.template.download' => 'template',
            'clients.switch' => 'switch',
            
            // Client-Specific Routes (client context required)
            'clients.show' => 'client-dashboard',
            'clients.edit' => 'client-dashboard',
            'clients.contacts.index' => 'contacts',
            'clients.contacts.create' => 'contacts',
            'clients.contacts.show' => 'contacts',
            'clients.contacts.edit' => 'contacts',
            'clients.contacts.export' => 'contacts',
            'clients.locations.index' => 'locations',
            'clients.locations.create' => 'locations',
            'clients.locations.show' => 'locations',
            'clients.locations.edit' => 'locations',
            'clients.locations.export' => 'locations',
            'clients.documents.index' => 'documents',
            'clients.documents.create' => 'documents',
            'clients.documents.show' => 'documents',
            'clients.documents.edit' => 'documents',
            'clients.documents.export' => 'documents',
            'clients.files.index' => 'files',
            'clients.files.create' => 'files',
            'clients.files.show' => 'files',
            'clients.files.export' => 'files',
            'clients.licenses.index' => 'licenses',
            'clients.licenses.create' => 'licenses',
            'clients.licenses.show' => 'licenses',
            'clients.licenses.edit' => 'licenses',
            'clients.licenses.export' => 'licenses',
            'clients.credentials.index' => 'credentials',
            'clients.credentials.create' => 'credentials',
            'clients.credentials.show' => 'credentials',
            'clients.credentials.edit' => 'credentials',
            'clients.credentials.export' => 'credentials',
            'clients.networks.index' => 'networks',
            'clients.networks.create' => 'networks',
            'clients.networks.show' => 'networks',
            'clients.networks.edit' => 'networks',
            'clients.networks.export' => 'networks',
            'clients.services.index' => 'services',
            'clients.services.create' => 'services',
            'clients.services.show' => 'services',
            'clients.services.edit' => 'services',
            'clients.services.export' => 'services',
            'clients.vendors.index' => 'vendors',
            'clients.vendors.create' => 'vendors',
            'clients.vendors.show' => 'vendors',
            'clients.vendors.edit' => 'vendors',
            'clients.vendors.export' => 'vendors',
            'clients.racks.index' => 'racks',
            'clients.racks.create' => 'racks',
            'clients.racks.show' => 'racks',
            'clients.racks.edit' => 'racks',
            'clients.racks.export' => 'racks',
            'clients.certificates.index' => 'certificates',
            'clients.certificates.create' => 'certificates',
            'clients.certificates.show' => 'certificates',
            'clients.certificates.edit' => 'certificates',
            'clients.certificates.export' => 'certificates',
            'clients.domains.index' => 'domains',
            'clients.domains.create' => 'domains',
            'clients.domains.show' => 'domains',
            'clients.domains.edit' => 'domains',
            'clients.domains.export' => 'domains',
            'clients.calendar-events.index' => 'calendar-events',
            'clients.calendar-events.create' => 'calendar-events',
            'clients.calendar-events.show' => 'calendar-events',
            'clients.calendar-events.edit' => 'calendar-events',
            'clients.calendar-events.export' => 'calendar-events',
            'clients.recurring-invoices.index' => 'recurring-invoices',
            'clients.recurring-invoices.create' => 'recurring-invoices',
            'clients.recurring-invoices.show' => 'recurring-invoices',
            'clients.recurring-invoices.edit' => 'recurring-invoices',
            'clients.recurring-invoices.export' => 'recurring-invoices',
            'clients.quotes.index' => 'quotes',
            'clients.quotes.create' => 'quotes',
            'clients.quotes.show' => 'quotes',
            'clients.quotes.edit' => 'quotes',
            'clients.quotes.export' => 'quotes',
            'clients.trips.index' => 'trips',
            'clients.trips.create' => 'trips',
            'clients.trips.show' => 'trips',
            'clients.trips.edit' => 'trips',
            'clients.trips.export' => 'trips',
        ],
        'tickets' => [
            'tickets.index' => 'index',
            'tickets.create' => 'create',
            'tickets.show' => 'index',
            'tickets.edit' => 'index',
            'tickets.export.csv' => 'export',
            
            // Templates
            'tickets.templates.index' => 'templates',
            'tickets.templates.create' => 'templates',
            'tickets.templates.show' => 'templates',
            'tickets.templates.edit' => 'templates',
            'tickets.templates.export' => 'templates',
            
            // Time Tracking
            'tickets.time-tracking.index' => 'time-tracking',
            'tickets.time-tracking.create' => 'time-tracking',
            'tickets.time-tracking.show' => 'time-tracking',
            'tickets.time-tracking.edit' => 'time-tracking',
            'tickets.time-tracking.export' => 'time-tracking',
            
            // Calendar
            'tickets.calendar.index' => 'calendar',
            'tickets.calendar.create' => 'calendar',
            'tickets.calendar.show' => 'calendar',
            'tickets.calendar.edit' => 'calendar',
            
            // Recurring Tickets
            'tickets.recurring.index' => 'recurring',
            'tickets.recurring.create' => 'recurring',
            'tickets.recurring.show' => 'recurring',
            'tickets.recurring.edit' => 'recurring',
            'tickets.recurring.export' => 'recurring',
            
            // Priority Queue
            'tickets.priority-queue.index' => 'priority-queue',
            'tickets.priority-queue.create' => 'priority-queue',
            'tickets.priority-queue.show' => 'priority-queue',
            'tickets.priority-queue.edit' => 'priority-queue',
            'tickets.priority-queue.export' => 'priority-queue',
            
            // Workflows
            'tickets.workflows.index' => 'workflows',
            'tickets.workflows.create' => 'workflows',
            'tickets.workflows.show' => 'workflows',
            'tickets.workflows.edit' => 'workflows',
            'tickets.workflows.export' => 'workflows',
            
            // Assignments
            'tickets.assignments.index' => 'assignments',
            'tickets.assignments.export' => 'assignments',
        ],
        'assets' => [
            'assets.index' => 'index',
            'assets.create' => 'create',
            'assets.show' => 'index',
            'assets.edit' => 'index',
            'assets.import.form' => 'import',
            'assets.import' => 'import',
            'assets.export' => 'export',
            'assets.template.download' => 'template',
            
            // Asset Maintenance
            'assets.maintenance.index' => 'maintenance',
            'assets.maintenance.create' => 'maintenance',
            'assets.maintenance.show' => 'maintenance',
            'assets.maintenance.edit' => 'maintenance',
            'assets.maintenance.export' => 'maintenance',
            'assets.maintenance.complete' => 'maintenance',
            'assets.maintenance.schedule-next' => 'maintenance',
            
            // Asset Warranties
            'assets.warranties.index' => 'warranties',
            'assets.warranties.create' => 'warranties',
            'assets.warranties.show' => 'warranties',
            'assets.warranties.edit' => 'warranties',
            'assets.warranties.export' => 'warranties',
            'assets.warranties.expiry-report' => 'warranties',
            'assets.warranties.renew' => 'warranties',
            'assets.warranties.mark-expired' => 'warranties',
            
            // Asset Depreciations
            'assets.depreciations.index' => 'depreciations',
            'assets.depreciations.create' => 'depreciations',
            'assets.depreciations.show' => 'depreciations',
            'assets.depreciations.edit' => 'depreciations',
            'assets.depreciations.export' => 'depreciations',
            'assets.depreciations.report' => 'depreciations',
            'assets.depreciations.recalculate' => 'depreciations',
        ],
        'financial' => [
            'financial.invoices.index' => 'invoices',
            'financial.invoices.create' => 'create-invoice',
            'financial.invoices.show' => 'invoices',
            'financial.invoices.edit' => 'invoices',
            'financial.invoices.export.csv' => 'export-invoices',
            'financial.payments.index' => 'payments',
            'financial.payments.create' => 'create-payment',
            'financial.payments.show' => 'payments',
            'financial.payments.edit' => 'payments',
            'financial.expenses.index' => 'expenses',
            'financial.expenses.create' => 'create-expense',
            'financial.expenses.show' => 'expenses',
            'financial.expenses.edit' => 'expenses',
        ],
        'projects' => [
            // Main project routes
            'projects.index' => 'index',
            'projects.create' => 'create',
            'projects.show' => 'index',
            'projects.edit' => 'index',
            'projects.export' => 'export',
            
            // Project views
            'projects.timeline' => 'timeline',
            'projects.kanban' => 'kanban',
            'projects.reports' => 'reports',
            
            // Task management routes
            'projects.tasks.index' => 'tasks',
            'projects.tasks.create' => 'tasks',
            'projects.tasks.show' => 'tasks',
            'projects.tasks.edit' => 'tasks',
            'projects.tasks.kanban' => 'tasks-kanban',
            'projects.tasks.calendar' => 'tasks-calendar',
            'projects.tasks.gantt' => 'tasks-gantt',
            
            // Team management routes
            'projects.members.index' => 'team',
            'projects.members.create' => 'team',
            'projects.members.show' => 'team',
            'projects.members.edit' => 'team',
            
            // Milestones routes
            'projects.milestones.index' => 'milestones',
            'projects.milestones.create' => 'milestones',
            'projects.milestones.show' => 'milestones',
            'projects.milestones.edit' => 'milestones',
            
            // Templates routes
            'projects.templates.index' => 'templates',
            'projects.templates.create' => 'templates',
            'projects.templates.show' => 'templates',
            'projects.templates.edit' => 'templates',
            
            // Time tracking routes
            'projects.time.index' => 'time-tracking',
            'projects.time.create' => 'time-tracking',
            'projects.time.show' => 'time-tracking',
            
            // File management routes
            'projects.files.index' => 'files',
            'projects.files.upload' => 'files',
            'projects.files.show' => 'files',
        ],
        'reports' => [
            'reports.index' => 'index',
            'reports.financial' => 'financial',
            'reports.tickets' => 'tickets',
            'reports.assets' => 'assets',
            'reports.clients' => 'clients',
            'reports.projects' => 'projects',
            'reports.users' => 'users',
        ],
    ];

    /**
     * Get the current active domain based on the route
     */
    public static function getActiveDomain(): ?string
    {
        $currentRouteName = Route::currentRouteName();
        
        if (!$currentRouteName) {
            return null;
        }

        foreach (static::$domainMappings as $domain => $config) {
            // Check route patterns
            foreach ($config['routes'] as $routePattern) {
                if (Str::is($routePattern, $currentRouteName)) {
                    return $domain;
                }
            }
            
            // Check URL patterns
            foreach ($config['patterns'] as $pattern) {
                if (Str::contains($currentRouteName, $pattern)) {
                    return $domain;
                }
            }
        }

        return null;
    }

    /**
     * Get the current active navigation item based on the route
     */
    public static function getActiveNavigationItem(): ?string
    {
        $currentRouteName = Route::currentRouteName();
        $activeDomain = static::getActiveDomain();

        if (!$currentRouteName || !$activeDomain) {
            return null;
        }

        $navigationMapping = static::$navigationMappings[$activeDomain] ?? [];

        // Direct route match
        if (isset($navigationMapping[$currentRouteName])) {
            return $navigationMapping[$currentRouteName];
        }

        // Handle filtered routes (with query parameters)
        $request = request();
        
        if ($activeDomain === 'tickets' && $currentRouteName === 'tickets.index') {
            if ($request->get('filter') === 'my') {
                return 'my-tickets';
            }
            if ($request->get('status') === 'open') {
                return 'open';
            }
            if ($request->get('filter') === 'scheduled') {
                return 'scheduled';
            }
        }

        if ($activeDomain === 'projects' && $currentRouteName === 'projects.index') {
            if ($request->get('status') === 'active') {
                return 'active';
            }
            if ($request->get('status') === 'completed') {
                return 'completed';
            }
            if ($request->get('status') === 'planning') {
                return 'planning';
            }
            if ($request->get('status') === 'on_hold') {
                return 'on-hold';
            }
            if ($request->get('status') === 'overdue') {
                return 'overdue';
            }
            if ($request->get('view') === 'timeline') {
                return 'timeline';
            }
            if ($request->get('view') === 'kanban') {
                return 'kanban';
            }
            if ($request->get('assigned_to') === 'me') {
                return 'my-projects';
            }
        }

        if ($activeDomain === 'projects' && Str::contains($currentRouteName, 'tasks')) {
            if ($request->get('view') === 'kanban') {
                return 'tasks-kanban';
            }
            if ($request->get('view') === 'calendar') {
                return 'tasks-calendar';
            }
            if ($request->get('view') === 'gantt') {
                return 'tasks-gantt';
            }
            if ($request->get('assigned_to') === 'me') {
                return 'my-tasks';
            }
        }

        if ($activeDomain === 'assets' && $currentRouteName === 'assets.index') {
            if ($request->get('view') === 'qr') {
                return 'qr-codes';
            }
            if ($request->get('view') === 'labels') {
                return 'labels';
            }
        }

        if ($activeDomain === 'reports' && $currentRouteName === 'reports.financial') {
            if ($request->get('type') === 'invoices') {
                return 'invoices';
            }
            if ($request->get('type') === 'payments') {
                return 'payments';
            }
        }

        return null;
    }

    /**
     * Check if a route is active (used for navigation highlighting)
     */
    public static function isRouteActive(string $routeName, array $params = []): bool
    {
        $currentRouteName = Route::currentRouteName();
        
        if ($currentRouteName !== $routeName) {
            return false;
        }

        // Check if parameters match
        if (!empty($params)) {
            $request = request();
            foreach ($params as $key => $value) {
                if ($request->get($key) !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get breadcrumb data for the current page
     */
    public static function getBreadcrumbs(): array
    {
        $currentRouteName = Route::currentRouteName();
        $activeDomain = static::getActiveDomain();
        
        if (!$activeDomain || !$currentRouteName) {
            return [];
        }

        $breadcrumbs = [
            [
                'name' => 'Dashboard',
                'route' => 'dashboard'
            ],
            [
                'name' => ucfirst($activeDomain),
                'route' => static::getDomainIndexRoute($activeDomain)
            ]
        ];

        // Add specific page breadcrumb based on route
        $pageTitle = static::getPageTitleFromRoute($currentRouteName, $activeDomain);
        if ($pageTitle && $pageTitle !== ucfirst($activeDomain)) {
            $breadcrumbs[] = [
                'name' => $pageTitle,
                'active' => true
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Get the index route for a domain
     */
    protected static function getDomainIndexRoute(string $domain): string
    {
        $routes = [
            'clients' => 'clients.index',
            'tickets' => 'tickets.index',
            'assets' => 'assets.index',
            'financial' => 'financial.invoices.index',
            'projects' => 'projects.index',
            'reports' => 'reports.index',
        ];

        return $routes[$domain] ?? 'dashboard';
    }

    /**
     * Get page title from route
     */
    protected static function getPageTitleFromRoute(string $routeName, string $domain): string
    {
        $titles = [
            'create' => 'Create',
            'edit' => 'Edit',
            'show' => 'View',
            'import' => 'Import',
            'export' => 'Export',
        ];

        foreach ($titles as $action => $title) {
            if (Str::contains($routeName, $action)) {
                return $title;
            }
        }

        return ucfirst($domain);
    }

    /**
     * Get quick stats for dashboard widgets (domain-specific)
     */
    public static function getDomainStats(string $domain): array
    {
        // This method can be extended to return domain-specific statistics
        // For now, returning empty array - can be implemented as needed
        return [];
    }

    /**
     * Get badge counts for navigation items (with permission filtering)
     */
    public static function getBadgeCounts(string $domain): array
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        if (!$companyId) {
            return [];
        }

        // Check if user has permission to view this domain
        if (!static::canAccessDomain($user, $domain)) {
            return [];
        }

        switch ($domain) {
            case 'clients':
                return static::getClientBadgeCounts($companyId);
            
            case 'tickets':
                return static::getTicketBadgeCounts($companyId, $user->id);
            
            case 'assets':
                return static::getAssetBadgeCounts($companyId);
                
            case 'financial':
                return static::getFinancialBadgeCounts($companyId);
                
            case 'projects':
                return static::getProjectBadgeCounts($companyId);
                
            default:
                return [];
        }
    }

    /**
     * Check if user can access a specific domain
     */
    public static function canAccessDomain($user, string $domain): bool
    {
        $permission = $domain . '.view';
        return $user->hasPermission($permission);
    }

    /**
     * Get filtered navigation items based on user permissions
     */
    public static function getFilteredNavigationItems(string $domain): array
    {
        $user = auth()->user();
        
        if (!static::canAccessDomain($user, $domain)) {
            return [];
        }

        switch ($domain) {
            case 'clients':
                return static::getFilteredClientNavigation($user);
            
            case 'tickets':
                return static::getFilteredTicketNavigation($user);
            
            case 'assets':
                return static::getFilteredAssetNavigation($user);
                
            case 'financial':
                return static::getFilteredFinancialNavigation($user);
                
            case 'projects':
                return static::getFilteredProjectNavigation($user);
                
            case 'reports':
                return static::getFilteredReportsNavigation($user);
                
            default:
                return [];
        }
    }

    /**
     * Get filtered client navigation items (client-aware)
     */
    protected static function getFilteredClientNavigation($user): array
    {
        return static::getClientNavigationItems($user);
    }

    /**
     * Get filtered asset navigation items
     */
    protected static function getFilteredAssetNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('assets.view')) {
            $items['index'] = 'All Assets';
        }

        if ($user->hasPermission('assets.create')) {
            $items['create'] = 'Add New Asset';
        }

        if ($user->hasPermission('assets.export')) {
            $items['export'] = 'Export Assets';
        }

        if ($user->hasPermission('assets.maintenance.view')) {
            $items['maintenance'] = 'Maintenance';
        }

        if ($user->hasPermission('assets.warranties.view')) {
            $items['warranties'] = 'Warranties';
        }

        if ($user->hasPermission('assets.depreciations.view')) {
            $items['depreciations'] = 'Depreciation';
        }

        return $items;
    }

    /**
     * Get filtered financial navigation items
     */
    protected static function getFilteredFinancialNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('financial.invoices.view')) {
            $items['invoices'] = 'Invoices';
        }

        if ($user->hasPermission('financial.payments.view')) {
            $items['payments'] = 'Payments';
        }

        if ($user->hasPermission('financial.expenses.view')) {
            $items['expenses'] = 'Expenses';
        }

        if ($user->hasPermission('financial.invoices.manage')) {
            $items['create-invoice'] = 'Create Invoice';
        }

        if ($user->hasPermission('financial.payments.manage')) {
            $items['create-payment'] = 'Record Payment';
        }

        if ($user->hasPermission('financial.expenses.manage')) {
            $items['create-expense'] = 'Add Expense';
        }

        return $items;
    }

    /**
     * Get filtered project navigation items
     */
    protected static function getFilteredProjectNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('projects.view')) {
            $items['index'] = 'All Projects';
        }

        if ($user->hasPermission('projects.create')) {
            $items['create'] = 'Create Project';
        }

        if ($user->hasPermission('projects.tasks.view')) {
            $items['tasks'] = 'Tasks';
        }

        if ($user->hasPermission('projects.members.view')) {
            $items['team'] = 'Team Management';
        }

        if ($user->hasPermission('projects.templates.view')) {
            $items['templates'] = 'Templates';
        }

        return $items;
    }

    /**
     * Get filtered ticket navigation items
     */
    protected static function getFilteredTicketNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('tickets.view')) {
            $items['index'] = 'All Tickets';
        }

        if ($user->hasPermission('tickets.create')) {
            $items['create'] = 'Create Ticket';
        }

        if ($user->hasPermission('tickets.export')) {
            $items['export'] = 'Export Tickets';
        }

        return $items;
    }

    /**
     * Get filtered reports navigation items
     */
    protected static function getFilteredReportsNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('reports.view')) {
            $items['index'] = 'Reports Dashboard';
        }

        if ($user->hasPermission('reports.financial')) {
            $items['financial'] = 'Financial Reports';
        }

        if ($user->hasPermission('reports.tickets')) {
            $items['tickets'] = 'Ticket Reports';
        }

        if ($user->hasPermission('reports.assets')) {
            $items['assets'] = 'Asset Reports';
        }

        if ($user->hasPermission('reports.clients')) {
            $items['clients'] = 'Client Reports';
        }

        if ($user->hasPermission('reports.projects')) {
            $items['projects'] = 'Project Reports';
        }

        if ($user->hasPermission('reports.users')) {
            $items['users'] = 'User Reports';
        }

        return $items;
    }

    /**
     * Check if user can access specific navigation item
     */
    public static function canAccessNavigationItem($user, string $domain, string $item): bool
    {
        switch ($domain) {
            case 'clients':
                return static::canAccessClientNavItem($user, $item);
            case 'assets':
                return static::canAccessAssetNavItem($user, $item);
            case 'financial':
                return static::canAccessFinancialNavItem($user, $item);
            case 'projects':
                return static::canAccessProjectNavItem($user, $item);
            case 'tickets':
                return static::canAccessTicketNavItem($user, $item);
            case 'reports':
                return static::canAccessReportNavItem($user, $item);
            default:
                return false;
        }
    }

    /**
     * Check client navigation item access
     */
    protected static function canAccessClientNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'index' => 'clients.view',
            'create' => 'clients.create',
            'export' => 'clients.export',
            'import' => 'clients.import',
            'contacts' => 'clients.contacts.view',
            'locations' => 'clients.locations.view',
            'documents' => 'clients.documents.view',
            'files' => 'clients.files.view',
            'licenses' => 'clients.licenses.view',
            'credentials' => 'clients.credentials.view',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Check asset navigation item access
     */
    protected static function canAccessAssetNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'index' => 'assets.view',
            'create' => 'assets.create',
            'export' => 'assets.export',
            'maintenance' => 'assets.maintenance.view',
            'warranties' => 'assets.warranties.view',
            'depreciations' => 'assets.depreciations.view',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Check financial navigation item access
     */
    protected static function canAccessFinancialNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'invoices' => 'financial.invoices.view',
            'payments' => 'financial.payments.view',
            'expenses' => 'financial.expenses.view',
            'create-invoice' => 'financial.invoices.manage',
            'create-payment' => 'financial.payments.manage',
            'create-expense' => 'financial.expenses.manage',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Check project navigation item access
     */
    protected static function canAccessProjectNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'index' => 'projects.view',
            'create' => 'projects.create',
            'tasks' => 'projects.tasks.view',
            'team' => 'projects.members.view',
            'templates' => 'projects.templates.view',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Check ticket navigation item access
     */
    protected static function canAccessTicketNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'index' => 'tickets.view',
            'create' => 'tickets.create',
            'export' => 'tickets.export',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Check report navigation item access
     */
    protected static function canAccessReportNavItem($user, string $item): bool
    {
        $itemPermissions = [
            'index' => 'reports.view',
            'financial' => 'reports.financial',
            'tickets' => 'reports.tickets',
            'assets' => 'reports.assets',
            'clients' => 'reports.clients',
            'projects' => 'reports.projects',
            'users' => 'reports.users',
        ];

        return isset($itemPermissions[$item]) && $user->hasPermission($itemPermissions[$item]);
    }

    /**
     * Get badge counts for client navigation items
     */
    protected static function getClientBadgeCounts(int $companyId): array
    {
        try {
            return [
                'contacts' => \App\Domains\Client\Models\ClientContact::where('company_id', $companyId)->count(),
                'locations' => \App\Models\ClientAddress::where('company_id', $companyId)->count(),
                'documents' => \App\Domains\Client\Models\ClientDocument::where('company_id', $companyId)->count(),
                'files' => \App\Domains\Client\Models\ClientFile::where('company_id', $companyId)->count(),
                'licenses' => \App\Domains\Client\Models\ClientLicense::where('company_id', $companyId)->count(),
                'credentials' => \App\Domains\Client\Models\ClientCredential::where('company_id', $companyId)->count(),
                'networks' => \App\Domains\Client\Models\ClientNetwork::where('company_id', $companyId)->count(),
                'services' => \App\Domains\Client\Models\ClientService::where('company_id', $companyId)->count(),
                'vendors' => \App\Domains\Client\Models\ClientVendor::where('company_id', $companyId)->count(),
                'racks' => \App\Models\ClientRack::where('company_id', $companyId)->count(),
                'certificates' => \App\Models\ClientCertificate::where('company_id', $companyId)->count(),
                'domains' => \App\Models\ClientDomain::where('company_id', $companyId)->count(),
                'recurring-invoices' => \App\Models\ClientRecurringInvoice::where('company_id', $companyId)->count(),
                'quotes' => \App\Models\ClientQuote::where('company_id', $companyId)->count(),
                'trips' => \App\Models\ClientTrip::where('company_id', $companyId)->count(),
                'calendar-events' => \App\Models\ClientCalendarEvent::where('company_id', $companyId)->count(),
            ];
        } catch (\Exception $e) {
            // Return empty counts if any model doesn't exist yet
            return [];
        }
    }

    /**
     * Get badge counts for ticket navigation items
     */
    protected static function getTicketBadgeCounts(int $companyId, int $userId): array
    {
        try {
            $baseQuery = \App\Models\Ticket::where('company_id', $companyId);
            
            $counts = [
                'open' => (clone $baseQuery)->where('status', 'open')->count(),
                'in-progress' => (clone $baseQuery)->where('status', 'in-progress')->count(),
                'waiting' => (clone $baseQuery)->where('status', 'waiting')->count(),
                'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
                'my-tickets' => (clone $baseQuery)->where('assigned_to', $userId)->count(),
                'assigned' => (clone $baseQuery)->where('assigned_to', $userId)->whereIn('status', ['open', 'in-progress'])->count(),
                'watching' => (clone $baseQuery)->whereHas('watchers', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->count(),
                'scheduled' => (clone $baseQuery)->whereNotNull('scheduled_at')->where('scheduled_at', '>', now())->count(),
            ];
            
            // Add advanced functionality counts if models exist
            try {
                $counts['templates'] = \App\Domains\Ticket\Models\TicketTemplate::where('company_id', $companyId)->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['templates'] = 0;
            }
            
            try {
                $counts['recurring'] = \App\Domains\Ticket\Models\RecurringTicket::where('company_id', $companyId)->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['recurring'] = 0;
            }
            
            try {
                $counts['workflows'] = \App\Domains\Ticket\Models\TicketWorkflow::where('company_id', $companyId)->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['workflows'] = 0;
            }
            
            try {
                $counts['priority-queue'] = \App\Domains\Ticket\Models\PriorityQueue::where('company_id', $companyId)->whereIn('status', ['pending', 'escalated'])->count();
            } catch (\Exception $e) {
                $counts['priority-queue'] = 0;
            }
            
            return $counts;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get badge counts for asset navigation items
     */
    protected static function getAssetBadgeCounts(int $companyId): array
    {
        try {
            $baseQuery = \App\Models\Asset::where('company_id', $companyId);
            
            $counts = [
                'hardware' => (clone $baseQuery)->where('category', 'hardware')->count(),
                'software' => (clone $baseQuery)->where('category', 'software')->count(),
                'licenses' => (clone $baseQuery)->where('category', 'licenses')->count(),
                'mobile' => (clone $baseQuery)->where('category', 'mobile')->count(),
            ];
            
            // Add new asset domain functionality counts
            try {
                $counts['maintenance'] = \App\Domains\Asset\Models\AssetMaintenance::whereHas('asset', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->where('status', 'scheduled')->count();
                
                $counts['maintenance-overdue'] = \App\Domains\Asset\Models\AssetMaintenance::whereHas('asset', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->where('scheduled_date', '<', now())->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->count();
                
                $counts['warranties'] = \App\Domains\Asset\Models\AssetWarranty::whereHas('asset', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->where('status', 'active')->count();
                
                $counts['warranties-expiring'] = \App\Domains\Asset\Models\AssetWarranty::whereHas('asset', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->whereBetween('warranty_end_date', [now(), now()->addDays(30)])->where('status', 'active')->count();
                
                $counts['depreciations'] = \App\Domains\Asset\Models\AssetDepreciation::whereHas('asset', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->count();
            } catch (\Exception $e) {
                // If new models don't exist yet, set counts to 0
                $counts['maintenance'] = 0;
                $counts['maintenance-overdue'] = 0;
                $counts['warranties'] = 0;
                $counts['warranties-expiring'] = 0;
                $counts['depreciations'] = 0;
            }
            
            return $counts;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get badge counts for financial navigation items
     */
    protected static function getFinancialBadgeCounts(int $companyId): array
    {
        try {
            return [
                'invoices' => \App\Models\Invoice::where('company_id', $companyId)->count(),
                'payments' => \App\Domains\Financial\Models\Payment::where('company_id', $companyId)->count(),
                'expenses' => \App\Domains\Financial\Models\Expense::where('company_id', $companyId)->count(),
                'pending-payments' => \App\Domains\Financial\Models\Payment::where('company_id', $companyId)->where('status', 'pending')->count(),
                'pending-expenses' => \App\Domains\Financial\Models\Expense::where('company_id', $companyId)->where('status', 'pending_approval')->count(),
                'approved-expenses' => \App\Domains\Financial\Models\Expense::where('company_id', $companyId)->where('status', 'approved')->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get badge counts for project navigation items
     */
    protected static function getProjectBadgeCounts(int $companyId): array
    {
        try {
            // Try enhanced project model first, fallback to basic if needed
            $projectClass = class_exists('\Foleybridge\Nestogy\Domains\Project\Models\Project')
                ? '\Foleybridge\Nestogy\Domains\Project\Models\Project'
                : '\App\Models\Project';
                
            $baseQuery = $projectClass::where('company_id', $companyId);
            
            $counts = [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', 'active')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'planning' => (clone $baseQuery)->where('status', 'planning')->count(),
                'on_hold' => (clone $baseQuery)->where('status', 'on_hold')->count(),
                'overdue' => 0,
                'due_soon' => 0,
                'my_projects' => 0,
                'critical_priority' => 0,
            ];

            // Enhanced counts if using new project model
            if ($projectClass === '\Foleybridge\Nestogy\Domains\Project\Models\Project') {
                $counts['overdue'] = (clone $baseQuery)->overdue()->count();
                $counts['due_soon'] = (clone $baseQuery)->dueSoon()->count();
                $counts['critical_priority'] = (clone $baseQuery)->byPriority('critical')->active()->count();
                
                // My projects (where user is manager or team member)
                $userId = auth()->id();
                if ($userId) {
                    $counts['my_projects'] = (clone $baseQuery)->where(function($q) use ($userId) {
                        $q->where('manager_id', $userId)
                          ->orWhereHas('members', function($memberQuery) use ($userId) {
                              $memberQuery->where('user_id', $userId)->where('is_active', true);
                          });
                    })->count();
                }

                // Task counts
                try {
                    $taskClass = '\Foleybridge\Nestogy\Domains\Project\Models\Task';
                    if (class_exists($taskClass)) {
                        $taskQuery = $taskClass::whereHas('project', function($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                        
                        $counts['all_tasks'] = (clone $taskQuery)->count();
                        $counts['my_tasks'] = $userId ? (clone $taskQuery)->assignedTo($userId)->count() : 0;
                        $counts['overdue_tasks'] = (clone $taskQuery)->overdue()->count();
                        $counts['tasks_due_soon'] = (clone $taskQuery)->dueSoon()->count();
                        $counts['blocked_tasks'] = (clone $taskQuery)->byStatus('blocked')->count();
                        $counts['unassigned_tasks'] = (clone $taskQuery)->whereNull('assigned_to')->count();
                    }
                } catch (\Exception $e) {
                    // Task counts not available
                }

                // Milestone counts
                try {
                    $milestoneClass = '\Foleybridge\Nestogy\Domains\Project\Models\ProjectMilestone';
                    if (class_exists($milestoneClass)) {
                        $milestoneQuery = $milestoneClass::whereHas('project', function($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                        
                        $counts['total_milestones'] = (clone $milestoneQuery)->count();
                        $counts['completed_milestones'] = (clone $milestoneQuery)->completed()->count();
                        $counts['overdue_milestones'] = (clone $milestoneQuery)->overdue()->count();
                        $counts['critical_milestones'] = (clone $milestoneQuery)->critical()->count();
                    }
                } catch (\Exception $e) {
                    // Milestone counts not available
                }

                // Template counts
                try {
                    $templateClass = '\Foleybridge\Nestogy\Domains\Project\Models\ProjectTemplate';
                    if (class_exists($templateClass)) {
                        $counts['templates'] = $templateClass::where('company_id', $companyId)
                            ->active()->count();
                        $counts['public_templates'] = $templateClass::where('is_public', true)
                            ->active()->count();
                    }
                } catch (\Exception $e) {
                    // Template counts not available
                }
            
            }

            return $counts;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the currently selected client from session
     */
    public static function getSelectedClient()
    {
        $clientId = session('selected_client_id');
        if (!$clientId) {
            return null;
        }

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        try {
            $client = \App\Models\Client::where('id', $clientId)
                ->where('company_id', $user->company_id)
                ->first();
            
            return $client;
        } catch (\Exception $e) {
            // Clear invalid session data
            session()->forget('selected_client_id');
            return null;
        }
    }

    /**
     * Set the selected client in session
     */
    public static function setSelectedClient($clientId)
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Verify the client belongs to the user's company
        try {
            $client = \App\Models\Client::where('id', $clientId)
                ->where('company_id', $user->company_id)
                ->first();
                
            if ($client) {
                session(['selected_client_id' => $clientId]);
                return true;
            }
        } catch (\Exception $e) {
            // Model doesn't exist yet or other error
        }

        return false;
    }

    /**
     * Clear the selected client from session
     */
    public static function clearSelectedClient()
    {
        session()->forget('selected_client_id');
    }

    /**
     * Check if a client is currently selected
     */
    public static function hasSelectedClient(): bool
    {
        return session()->has('selected_client_id') && static::getSelectedClient() !== null;
    }

    /**
     * Get client-aware navigation items for the client domain
     */
    public static function getClientNavigationItems($user): array
    {
        $items = [];
        $selectedClient = static::getSelectedClient();

        if (!$selectedClient) {
            // No client selected - show client selection items
            if ($user->hasPermission('clients.view')) {
                $items['index'] = 'Select Client';
                $items['leads'] = 'Client Leads';
            }
            
            if ($user->hasPermission('clients.create')) {
                $items['create'] = 'Add New Client';
            }
            
            if ($user->hasPermission('clients.export')) {
                $items['export'] = 'Export Clients';
            }
            
            if ($user->hasPermission('clients.import')) {
                $items['import'] = 'Import Clients';
            }
        } else {
            // Client selected - show client-specific items
            $items['client-dashboard'] = 'Client Dashboard';
            $items['switch'] = 'Switch Client';
            
            // Add client-specific functionality
            if ($user->hasPermission('clients.contacts.view')) {
                $items['contacts'] = 'Contacts';
            }
            
            if ($user->hasPermission('clients.locations.view')) {
                $items['locations'] = 'Locations';
            }
            
            if ($user->hasPermission('clients.documents.view')) {
                $items['documents'] = 'Documents';
            }
            
            if ($user->hasPermission('clients.files.view')) {
                $items['files'] = 'Files';
            }
            
            if ($user->hasPermission('clients.licenses.view')) {
                $items['licenses'] = 'Licenses';
            }
            
            if ($user->hasPermission('clients.credentials.view')) {
                $items['credentials'] = 'Credentials';
            }
            
            if ($user->hasPermission('clients.networks.view')) {
                $items['networks'] = 'Networks';
            }
            
            if ($user->hasPermission('clients.services.view')) {
                $items['services'] = 'Services';
            }
            
            if ($user->hasPermission('clients.vendors.view')) {
                $items['vendors'] = 'Vendors';
            }
            
            if ($user->hasPermission('clients.racks.view')) {
                $items['racks'] = 'Racks';
            }
            
            if ($user->hasPermission('clients.certificates.view')) {
                $items['certificates'] = 'Certificates';
            }
            
            if ($user->hasPermission('clients.domains.view')) {
                $items['domains'] = 'Domains';
            }
            
            if ($user->hasPermission('clients.calendar-events.view')) {
                $items['calendar-events'] = 'Calendar Events';
            }
            
            if ($user->hasPermission('clients.recurring-invoices.view')) {
                $items['recurring-invoices'] = 'Recurring Invoices';
            }
            
            if ($user->hasPermission('clients.quotes.view')) {
                $items['quotes'] = 'Quotes';
            }
            
            if ($user->hasPermission('clients.trips.view')) {
                $items['trips'] = 'Trips';
            }
        }

        return $items;
    }

    /**
     * Get client-specific badge counts
     */
    public static function getClientSpecificBadgeCounts(int $companyId, ?int $clientId = null): array
    {
        if (!$clientId) {
            return static::getClientBadgeCounts($companyId);
        }

        try {
            // Get counts scoped to specific client
            $counts = [
                'contacts' => \App\Domains\Client\Models\ClientContact::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'locations' => \App\Models\ClientAddress::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'documents' => \App\Domains\Client\Models\ClientDocument::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'files' => \App\Domains\Client\Models\ClientFile::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'licenses' => \App\Domains\Client\Models\ClientLicense::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'credentials' => \App\Domains\Client\Models\ClientCredential::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
            ];

            // Add more specific counts as needed
            try {
                $counts['networks'] = \App\Domains\Client\Models\ClientNetwork::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['services'] = \App\Domains\Client\Models\ClientService::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['vendors'] = \App\Domains\Client\Models\ClientVendor::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['racks'] = \App\Models\ClientRack::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['certificates'] = \App\Models\ClientCertificate::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['domains'] = \App\Models\ClientDomain::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['recurring-invoices'] = \App\Models\ClientRecurringInvoice::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['quotes'] = \App\Models\ClientQuote::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['trips'] = \App\Models\ClientTrip::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
                $counts['calendar-events'] = \App\Models\ClientCalendarEvent::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count();
            } catch (\Exception $e) {
                // Models may not exist yet - ignore
            }

            return $counts;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get workflow context for a specific client
     */
    public static function getClientWorkflowContext($client): ?array
        {
            if (!$client) {
                return null;
            }
    
            $user = auth()->user();
            if (!$user) {
                return null;
            }
    
            try {
                $context = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'current_workflow' => 'general',
                    'status' => 'idle',
                    'priority_items' => [],
                    'scheduled_items' => [],
                    'recent_activity' => []
                ];
    
                // Determine current workflow based on activities
                $now = now();
                $todayStart = $now->startOfDay();
                $todayEnd = $now->endOfDay();
    
                // Check for critical issues
                $criticalTickets = static::getCriticalTicketsForClient($client->id);
                $overdueInvoices = static::getOverdueInvoicesForClient($client->id);
                
                if ($criticalTickets->count() > 0 || $overdueInvoices->count() > 0) {
                    $context['current_workflow'] = 'critical_response';
                    $context['status'] = 'critical';
                } elseif (static::hasScheduledWorkToday($client->id)) {
                    $context['current_workflow'] = 'scheduled_work';
                    $context['status'] = 'active';
                } elseif (static::hasUpcomingScheduledWork($client->id)) {
                    $context['current_workflow'] = 'preparation';
                    $context['status'] = 'scheduled';
                }
    
                return $context;
            } catch (\Exception $e) {
                return null;
            }
        }
    
        /**
         * Get urgent items across all contexts
         */
        public static function getUrgentItems(): array
        {
            $user = auth()->user();
            if (!$user) {
                return [];
            }
    
            $urgentItems = [
                'total' => 0,
                'financial' => 0,
                'notifications' => 0,
                'client' => [],
                'items' => []
            ];
    
            try {
                $companyId = $user->company_id;
                
                // Get critical tickets
                $criticalTickets = static::getCriticalTickets($companyId);
                $urgentItems['total'] += $criticalTickets->count();
                
                // Get overdue invoices
                $overdueInvoices = static::getOverdueInvoices($companyId);
                $urgentItems['financial'] = $overdueInvoices->count();
                $urgentItems['total'] += $overdueInvoices->count();
    
                // Get SLA breaches
                $slaBreaches = static::getSLABreaches($companyId);
                $urgentItems['total'] += $slaBreaches->count();
    
                // Organize by client
                foreach ($criticalTickets as $ticket) {
                    $clientId = $ticket->client_id;
                    if (!isset($urgentItems['client'][$clientId])) {
                        $urgentItems['client'][$clientId] = [
                            'total' => 0,
                            'critical' => 0,
                            'urgent' => 0,
                            'items' => []
                        ];
                    }
                    
                    $urgentItems['client'][$clientId]['critical']++;
                    $urgentItems['client'][$clientId]['total']++;
                    $urgentItems['client'][$clientId]['items'][] = [
                        'id' => $ticket->id,
                        'title' => $ticket->subject ?? 'Untitled Ticket',
                        'type' => 'ticket',
                        'priority' => 'critical',
                        'url' => route('tickets.show', $ticket)
                    ];
                }
    
                foreach ($overdueInvoices as $invoice) {
                    $clientId = $invoice->client_id;
                    if (!isset($urgentItems['client'][$clientId])) {
                        $urgentItems['client'][$clientId] = [
                            'total' => 0,
                            'critical' => 0,
                            'urgent' => 0,
                            'items' => []
                        ];
                    }
                    
                    $urgentItems['client'][$clientId]['urgent']++;
                    $urgentItems['client'][$clientId]['total']++;
                    $urgentItems['client'][$clientId]['items'][] = [
                        'id' => $invoice->id,
                        'title' => 'Overdue Invoice #' . $invoice->number,
                        'type' => 'invoice',
                        'priority' => 'urgent',
                        'url' => route('financial.invoices.show', $invoice)
                    ];
                }
    
                $urgentItems['notifications'] = min($urgentItems['total'], 99);
    
                return $urgentItems;
            } catch (\Exception $e) {
                return $urgentItems;
            }
        }
    
        /**
         * Get today's work items
         */
        public static function getTodaysWork(): array
        {
            $user = auth()->user();
            if (!$user) {
                return [];
            }
    
            $todaysWork = [
                'total' => 0,
                'upcoming' => 0,
                'client' => [],
                'scheduled' => []
            ];
    
            try {
                $companyId = $user->company_id;
                $today = now()->startOfDay();
                $tomorrow = now()->addDay()->startOfDay();
                $nextWeek = now()->addWeek()->startOfDay();
    
                // Get today's scheduled tickets
                $todayTickets = static::getScheduledTicketsForPeriod($companyId, $today, $tomorrow);
                $todaysWork['total'] += $todayTickets->count();
    
                // Get upcoming scheduled items (next 7 days)
                $upcomingTickets = static::getScheduledTicketsForPeriod($companyId, $tomorrow, $nextWeek);
                $todaysWork['upcoming'] = $upcomingTickets->count();
    
                // Organize by client
                foreach ($todayTickets as $ticket) {
                    $clientId = $ticket->client_id ?? 'unassigned';
                    if (!isset($todaysWork['client'][$clientId])) {
                        $todaysWork['client'][$clientId] = 0;
                    }
                    $todaysWork['client'][$clientId]++;
                }
    
                foreach ($upcomingTickets as $ticket) {
                    $clientId = $ticket->client_id ?? 'unassigned';
                    if (!isset($todaysWork['scheduled'][$clientId])) {
                        $todaysWork['scheduled'][$clientId] = 0;
                    }
                    $todaysWork['scheduled'][$clientId]++;
                }
    
                return $todaysWork;
            } catch (\Exception $e) {
                return $todaysWork;
            }
        }
    
        /**
         * Helper methods for workflow context
         */
        protected static function getCriticalTicketsForClient($clientId)
        {
            try {
                return \App\Models\Ticket::where('client_id', $clientId)
                    ->where('priority', 'critical')
                    ->whereIn('status', ['open', 'in-progress'])
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
    
        protected static function getOverdueInvoicesForClient($clientId)
        {
            try {
                return \App\Models\Invoice::where('client_id', $clientId)
                    ->where('status', 'overdue')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
    
        protected static function hasScheduledWorkToday($clientId): bool
        {
            try {
                $today = now()->startOfDay();
                $tomorrow = now()->addDay()->startOfDay();
                
                return \App\Models\Ticket::where('client_id', $clientId)
                    ->whereBetween('scheduled_at', [$today, $tomorrow])
                    ->exists();
            } catch (\Exception $e) {
                return false;
            }
        }
    
        protected static function hasUpcomingScheduledWork($clientId): bool
        {
            try {
                $tomorrow = now()->addDay()->startOfDay();
                $nextWeek = now()->addWeek()->startOfDay();
                
                return \App\Models\Ticket::where('client_id', $clientId)
                    ->whereBetween('scheduled_at', [$tomorrow, $nextWeek])
                    ->exists();
            } catch (\Exception $e) {
                return false;
            }
        }
    
        protected static function getCriticalTickets($companyId)
        {
            try {
                return \App\Models\Ticket::where('company_id', $companyId)
                    ->where('priority', 'critical')
                    ->whereIn('status', ['open', 'in-progress'])
                    ->with('client')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
    
        protected static function getOverdueInvoices($companyId)
        {
            try {
                return \App\Models\Invoice::where('company_id', $companyId)
                    ->where('status', 'overdue')
                    ->with('client')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
    
        protected static function getSLABreaches($companyId)
        {
            try {
                // Get tickets that have been open for more than 24 hours (simplified SLA check)
                return \App\Models\Ticket::where('company_id', $companyId)
                    ->where('created_at', '<', now()->subHours(24))
                    ->whereIn('status', ['open', 'in-progress'])
                    ->with('client')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
    
        protected static function getScheduledTicketsForPeriod($companyId, $start, $end)
        {
            try {
                return \App\Models\Ticket::where('company_id', $companyId)
                    ->whereBetween('scheduled_at', [$start, $end])
                    ->whereIn('status', ['open', 'in-progress', 'scheduled'])
                    ->with('client')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        }
        
        /**
         * Set workflow context in session
         */
        public static function setWorkflowContext($workflow)
        {
            session(['current_workflow' => $workflow]);
        }
        
        /**
         * Get current workflow context from session
         */
        public static function getWorkflowContext()
        {
            return session('current_workflow', 'default');
        }
        
        /**
         * Clear workflow context from session
         */
        public static function clearWorkflowContext()
        {
            session()->forget('current_workflow');
        }
        
        /**
         * Check if a specific workflow is active
         */
        public static function isWorkflowActive($workflow): bool
        {
            return static::getWorkflowContext() === $workflow;
        }
        
        /**
         * Get workflow-specific navigation state
         */
        public static function getWorkflowNavigationState(): array
        {
            $workflow = static::getWorkflowContext();
            $selectedClient = static::getSelectedClient();
            
            return [
                'workflow' => $workflow,
                'client_id' => $selectedClient?->id,
                'client_name' => $selectedClient?->name,
                'active_domain' => static::getActiveDomain(),
                'active_nav_item' => static::getActiveNavigationItem()
            ];
        }
        
        /**
         * Get workflow-specific routing parameters
         */
        public static function getWorkflowRouteParams($workflow): array
        {
            $params = [];
            $selectedClient = static::getSelectedClient();
            
            if ($selectedClient) {
                $params['client_id'] = $selectedClient->id;
            }
            
            switch ($workflow) {
                case 'urgent':
                    $params['priority'] = 'Critical,High';
                    $params['status'] = 'Open,In Progress';
                    break;
                    
                case 'today':
                    $params['date'] = now()->toDateString();
                    break;
                    
                case 'scheduled':
                    $params['scheduled'] = '1';
                    $params['date_from'] = now()->toDateString();
                    $params['date_to'] = now()->addWeek()->toDateString();
                    break;
                    
                case 'financial':
                    $params['status'] = 'Draft,Sent,Overdue';
                    break;
            }
            
            return $params;
        }
        
        /**
         * Generate workflow-aware breadcrumbs
         */
        public static function getWorkflowBreadcrumbs(): array
        {
            $workflow = static::getWorkflowContext();
            $selectedClient = static::getSelectedClient();
            $breadcrumbs = [];
            
            // Base breadcrumb
            $breadcrumbs[] = [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'active' => false
            ];
            
            // Add workflow context
            if ($workflow && $workflow !== 'default') {
                $workflowNames = [
                    'urgent' => 'Urgent Items',
                    'today' => "Today's Work",
                    'scheduled' => 'Scheduled Work',
                    'financial' => 'Financial Tasks',
                    'reports' => 'Reports'
                ];
                
                $breadcrumbs[] = [
                    'name' => $workflowNames[$workflow] ?? ucfirst($workflow),
                    'route' => 'dashboard',
                    'params' => ['view' => $workflow],
                    'active' => false
                ];
            }
            
            // Add client context
            if ($selectedClient) {
                $breadcrumbs[] = [
                    'name' => $selectedClient->name,
                    'route' => 'clients.show',
                    'params' => ['client' => $selectedClient->id],
                    'active' => false
                ];
            }
            
            // Mark last item as active
            if (!empty($breadcrumbs)) {
                $breadcrumbs[count($breadcrumbs) - 1]['active'] = true;
            }
            
            return $breadcrumbs;
        }
        
        /**
         * Get workflow-specific quick actions
         */
        public static function getWorkflowQuickActions($workflow, $userRole = null): array
        {
            $user = auth()->user();
            $role = $userRole ?? ($user ? static::getUserPrimaryRole($user) : 'user');
            $selectedClient = static::getSelectedClient();
            
            $actions = [];
            $clientParam = $selectedClient ? ['client_id' => $selectedClient->id] : [];
            
            switch ($workflow) {
                case 'urgent':
                    if (static::userCanPerform($user, 'tickets.create')) {
                        $actions[] = [
                            'label' => 'Create Critical Ticket',
                            'route' => 'tickets.create',
                            'params' => array_merge($clientParam, ['priority' => 'Critical']),
                            'icon' => 'exclamation-triangle',
                            'color' => 'red'
                        ];
                    }
                    
                    if (static::userCanPerform($user, 'tickets.view')) {
                        $actions[] = [
                            'label' => 'Review SLA Breaches',
                            'route' => 'tickets.index',
                            'params' => ['filter' => 'sla_breach'],
                            'icon' => 'clock',
                            'color' => 'orange'
                        ];
                    }
                    break;
                    
                case 'today':
                    if (static::userCanPerform($user, 'tickets.create')) {
                        $actions[] = [
                            'label' => 'Create Ticket',
                            'route' => 'tickets.create',
                            'params' => $clientParam,
                            'icon' => 'plus',
                            'color' => 'blue'
                        ];
                    }
                    
                    if (static::userCanPerform($user, 'tickets.view')) {
                        $actions[] = [
                            'label' => 'View Calendar',
                            'route' => 'tickets.calendar.index',
                            'params' => [],
                            'icon' => 'calendar',
                            'color' => 'green'
                        ];
                    }
                    break;
                    
                case 'financial':
                    if (static::userCanPerform($user, 'financial.invoices.create')) {
                        $actions[] = [
                            'label' => 'Create Invoice',
                            'route' => 'financial.invoices.create',
                            'params' => $clientParam,
                            'icon' => 'file-invoice',
                            'color' => 'green'
                        ];
                    }
                    
                    if (static::userCanPerform($user, 'financial.payments.create')) {
                        $actions[] = [
                            'label' => 'Record Payment',
                            'route' => 'financial.payments.create',
                            'params' => [],
                            'icon' => 'credit-card',
                            'color' => 'blue'
                        ];
                    }
                    break;
            }
            
            return $actions;
        }
        
        /**
         * Get user's primary role (helper method)
         */
        protected static function getUserPrimaryRole($user): string
        {
            if (!$user) return 'user';
            
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) return 'admin';
            if (method_exists($user, 'isTech') && $user->isTech()) return 'tech';
            if (method_exists($user, 'isAccountant') && $user->isAccountant()) return 'accountant';
            
            return 'user';
        }
        
        /**
         * Check if user can perform an action (helper method)
         */
        protected static function userCanPerform($user, $permission): bool
        {
            if (!$user) return false;
            
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($permission);
            }
            
            // Fallback for basic role checking
            $role = static::getUserPrimaryRole($user);
            
            $allowedActions = [
                'admin' => ['*'], // Admin can do everything
                'tech' => ['tickets.*', 'assets.*', 'clients.view'],
                'accountant' => ['financial.*', 'reports.*', 'clients.view'],
                'user' => ['tickets.view', 'clients.view']
            ];
            
            $userActions = $allowedActions[$role] ?? [];
            
            foreach ($userActions as $allowedAction) {
                if ($allowedAction === '*' || $allowedAction === $permission) {
                    return true;
                }
                
                // Check wildcard patterns
                if (str_ends_with($allowedAction, '*')) {
                    $prefix = rtrim($allowedAction, '*');
                    if (str_starts_with($permission, $prefix)) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        /**
         * Get workflow-specific navigation highlights
         */
        public static function getWorkflowNavigationHighlights($workflow): array
        {
            $highlights = [
                'urgent_count' => 0,
                'today_count' => 0,
                'scheduled_count' => 0,
                'financial_count' => 0,
                'alerts' => [],
                'badges' => []
            ];
            
            $user = auth()->user();
            if (!$user) {
                return $highlights;
            }
            
            try {
                $companyId = $user->company_id;
                $selectedClient = static::getSelectedClient();
                $baseQuery = ['company_id' => $companyId];
                
                if ($selectedClient) {
                    $baseQuery['client_id'] = $selectedClient->id;
                }
                
                // Get urgent items count
                $criticalTickets = \App\Models\Ticket::where($baseQuery)
                    ->whereIn('priority', ['Critical', 'High'])
                    ->whereIn('status', ['Open', 'In Progress'])
                    ->count();
                
                $overdueInvoices = \App\Models\Invoice::where($baseQuery)
                    ->where('status', 'Sent')
                    ->where('due_date', '<', now())
                    ->count();
                
                $highlights['urgent_count'] = $criticalTickets + $overdueInvoices;
                
                // Get today's work count
                $todayStart = now()->startOfDay();
                $todayEnd = now()->endOfDay();
                
                $todaysTickets = \App\Models\Ticket::where($baseQuery)
                    ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
                    ->count();
                    
                $highlights['today_count'] = $todaysTickets;
                
                // Get scheduled work count
                $scheduledTickets = \App\Models\Ticket::where($baseQuery)
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>', now())
                    ->count();
                    
                $highlights['scheduled_count'] = $scheduledTickets;
                
                // Get financial items count
                $pendingInvoices = \App\Models\Invoice::where($baseQuery)
                    ->where('status', 'Draft')
                    ->count();
                    
                $highlights['financial_count'] = $pendingInvoices;
                
                // Set workflow-specific badges
                $highlights['badges'] = [
                    'urgent' => $highlights['urgent_count'],
                    'today' => $highlights['today_count'],
                    'scheduled' => $highlights['scheduled_count'],
                    'financial' => $highlights['financial_count']
                ];
                
                // Add alerts for critical items
                if ($highlights['urgent_count'] > 0) {
                    $highlights['alerts'][] = [
                        'type' => 'urgent',
                        'count' => $highlights['urgent_count'],
                        'message' => $highlights['urgent_count'] . ' urgent item(s) require attention'
                    ];
                }
                
            } catch (\Exception $e) {
                // Return default counts if queries fail
            }
            
            return $highlights;
        }
    }