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
            'patterns' => ['clients'],
        ],
        'tickets' => [
            'routes' => [
                'tickets.*',
            ],
            'patterns' => ['tickets'],
        ],
        'assets' => [
            'routes' => [
                'assets.*',
            ],
            'patterns' => ['assets'],
        ],
        'financial' => [
            'routes' => [
                'financial.*',
                'billing.*',
                'collections.*',
                'products.*',
                'services.*',
            ],
            'patterns' => ['financial', 'billing', 'collections', 'products', 'services'],
        ],
        'projects' => [
            'routes' => [
                'projects.*',
            ],
            'patterns' => ['projects'],
        ],
        'reports' => [
            'routes' => [
                'reports.*',
            ],
            'patterns' => ['reports'],
        ],
        'knowledge' => [
            'routes' => [
                'knowledge.*',
            ],
            'patterns' => ['knowledge'],
        ],
        'integrations' => [
            'routes' => [
                'integrations.*',
                'webhooks.*',
                'api.*',
            ],
            'patterns' => ['integrations', 'webhooks', 'api'],
        ],
        'settings' => [
            'routes' => [
                'settings.*',
                'users.*',
                'admin.*',
            ],
            'patterns' => ['settings', 'users', 'admin'],
        ],
        'physical-mail' => [
            'routes' => [
                'mail.*',
                'physical-mail.*',
            ],
            'patterns' => ['mail', 'physical-mail'],
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
            'clients.tags' => 'client-dashboard',
            'clients.update-notes' => 'client-dashboard',
            'clients.archive' => 'client-dashboard',
            'clients.restore' => 'client-dashboard',

            // Contacts
            'clients.contacts.index' => 'contacts',
            'clients.contacts.create' => 'contacts',
            'clients.contacts.show' => 'contacts',
            'clients.contacts.edit' => 'contacts',
            'clients.contacts.export' => 'contacts',

            // Locations
            'clients.locations.index' => 'locations',
            'clients.locations.create' => 'locations',
            'clients.locations.show' => 'locations',
            'clients.locations.edit' => 'locations',
            'clients.locations.export' => 'locations',

            // Documents & Files
            'clients.documents.index' => 'documents',
            'clients.documents.create' => 'documents',
            'clients.documents.show' => 'documents',
            'clients.documents.edit' => 'documents',
            'clients.documents.destroy' => 'documents',
            'clients.files.index' => 'files',
            'clients.files.create' => 'files',
            'clients.files.show' => 'files',
            'clients.files.edit' => 'files',
            'clients.files.destroy' => 'files',

            // IT Documentation
            'clients.it-documentation.index' => 'it-documentation',
            'clients.it-documentation.create' => 'it-documentation',
            'clients.it-documentation.show' => 'it-documentation',
            'clients.it-documentation.edit' => 'it-documentation',
            'clients.it-documentation.export' => 'it-documentation',
            'clients.it-documentation.download' => 'it-documentation',
            'clients.it-documentation.duplicate' => 'it-documentation',
            'clients.it-documentation.create-version' => 'it-documentation',
            'clients.it-documentation.complete-review' => 'it-documentation',
            'clients.it-documentation.overdue-reviews' => 'it-documentation',
            'clients.it-documentation.bulk-update-access' => 'it-documentation',

            // Assets
            'clients.assets.index' => 'assets',
            'clients.assets.create' => 'assets',
            'clients.assets.show' => 'assets',
            'clients.assets.edit' => 'assets',
            'clients.assets.destroy' => 'assets',

            // Infrastructure Management
            'clients.licenses.index' => 'licenses',
            'clients.licenses.create' => 'licenses',
            'clients.licenses.show' => 'licenses',
            'clients.licenses.edit' => 'licenses',
            'clients.licenses.destroy' => 'licenses',
            'clients.credentials.index' => 'credentials',
            'clients.credentials.create' => 'credentials',
            'clients.credentials.show' => 'credentials',
            'clients.credentials.edit' => 'credentials',
            'clients.credentials.destroy' => 'credentials',
            'clients.networks.index' => 'networks',
            'clients.networks.create' => 'networks',
            'clients.networks.show' => 'networks',
            'clients.networks.edit' => 'networks',
            'clients.networks.destroy' => 'networks',
            'clients.certificates.index' => 'certificates',
            'clients.certificates.create' => 'certificates',
            'clients.certificates.show' => 'certificates',
            'clients.certificates.edit' => 'certificates',
            'clients.certificates.destroy' => 'certificates',
            'clients.domains.index' => 'domains',
            'clients.domains.create' => 'domains',
            'clients.domains.show' => 'domains',
            'clients.domains.edit' => 'domains',
            'clients.domains.destroy' => 'domains',
            'clients.racks.index' => 'racks',
            'clients.racks.create' => 'racks',
            'clients.racks.show' => 'racks',
            'clients.racks.edit' => 'racks',
            'clients.racks.destroy' => 'racks',

            // Service Management
            'clients.services.index' => 'services',
            'clients.services.create' => 'services',
            'clients.services.show' => 'services',
            'clients.services.edit' => 'services',
            'clients.services.destroy' => 'services',
            'clients.vendors.index' => 'vendors',
            'clients.vendors.create' => 'vendors',
            'clients.vendors.show' => 'vendors',
            'clients.vendors.edit' => 'vendors',
            'clients.vendors.destroy' => 'vendors',

            // Calendar & Scheduling
            'clients.calendar-events.index' => 'calendar-events',
            'clients.calendar-events.create' => 'calendar-events',
            'clients.calendar-events.show' => 'calendar-events',
            'clients.calendar-events.edit' => 'calendar-events',
            'clients.calendar-events.destroy' => 'calendar-events',
            'clients.trips.index' => 'trips',
            'clients.trips.create' => 'trips',
            'clients.trips.show' => 'trips',
            'clients.trips.edit' => 'trips',
            'clients.trips.destroy' => 'trips',

            // Financial
            'clients.recurring-invoices.index' => 'recurring-invoices',
            'clients.recurring-invoices.create' => 'recurring-invoices',
            'clients.recurring-invoices.show' => 'recurring-invoices',
            'clients.recurring-invoices.edit' => 'recurring-invoices',
            'clients.recurring-invoices.destroy' => 'recurring-invoices',
            'clients.quotes.index' => 'quotes',
            'clients.quotes.create' => 'quotes',
            'clients.quotes.show' => 'quotes',
            'clients.quotes.edit' => 'quotes',
            'clients.quotes.destroy' => 'quotes',
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
            'tickets.workflows.duplicate' => 'workflows',
            'tickets.workflows.export' => 'workflows',
            'tickets.workflows.import' => 'workflows',
            'tickets.workflows.toggleActive' => 'workflows',
            'tickets.workflows.executeTransition' => 'workflows',
            'tickets.workflows.getAvailableTransitions' => 'workflows',
            'tickets.workflows.testConditions' => 'workflows',
            'tickets.workflows.previewActions' => 'workflows',

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
            // Invoices
            'financial.invoices.index' => 'invoices',
            'financial.invoices.create' => 'create-invoice',
            'financial.invoices.show' => 'invoices',
            'financial.invoices.edit' => 'invoices',
            'financial.invoices.export.csv' => 'export-invoices',
            'financial.invoices.send' => 'invoices',
            'financial.invoices.pdf' => 'invoices',
            'financial.invoices.duplicate' => 'invoices',
            'financial.invoices.items.store' => 'invoices',
            'financial.invoices.items.update' => 'invoices',
            'financial.invoices.items.destroy' => 'invoices',
            'financial.invoices.payments.store' => 'invoices',
            'financial.invoices.update-status' => 'invoices',
            'financial.invoices.timeline' => 'invoices',

            // Payments
            'financial.payments.index' => 'payments',
            'financial.payments.create' => 'create-payment',
            'financial.payments.show' => 'payments',
            'financial.payments.edit' => 'payments',
            'financial.payments.destroy' => 'payments',

            // Expenses
            'financial.expenses.index' => 'expenses',
            'financial.expenses.create' => 'create-expense',
            'financial.expenses.show' => 'expenses',
            'financial.expenses.edit' => 'expenses',
            'financial.expenses.destroy' => 'expenses',

            // Quotes
            'financial.quotes.index' => 'quotes',
            'financial.quotes.create' => 'quotes',
            'financial.quotes.show' => 'quotes',
            'financial.quotes.edit' => 'quotes',
            'financial.quotes.destroy' => 'quotes',
            'financial.quotes.approve' => 'quotes',
            'financial.quotes.reject' => 'quotes',
            'financial.quotes.send' => 'quotes',
            'financial.quotes.pdf' => 'quotes',
            'financial.quotes.duplicate' => 'quotes',
            'financial.quotes.convert-to-invoice' => 'quotes',
            'financial.quotes.convert-to-contract' => 'quotes',
            'financial.quotes.approval-history' => 'quotes',
            'financial.quotes.versions' => 'quotes',
            'financial.quotes.versions.restore' => 'quotes',

            // Contracts
            'financial.contracts.index' => 'contracts',
            'financial.contracts.create' => 'contracts',

            // Products & Services
            'products.index' => 'products',
            'products.create' => 'create-product',
            'products.show' => 'products',
            'products.edit' => 'products',
            'products.destroy' => 'products',
            'products.import' => 'products',
            'products.export' => 'products',
            'services.index' => 'services',
            'services.create' => 'create-service',
            'services.show' => 'services',
            'services.edit' => 'services',
            'services.destroy' => 'services',
            'financial.contracts.show' => 'contracts',
            'financial.contracts.edit' => 'contracts',
            'financial.contracts.destroy' => 'contracts',
            'financial.contracts.approve' => 'contracts',
            'financial.contracts.reject' => 'contracts',
            'financial.contracts.send-for-signature' => 'contracts',
            'financial.contracts.activate' => 'contracts',
            'financial.contracts.terminate' => 'contracts',
            'financial.contracts.renew' => 'contracts',
            'financial.contracts.pdf' => 'contracts',
            'financial.contracts.duplicate' => 'contracts',
            'financial.contracts.approval-history' => 'contracts',
            'financial.contracts.audit-trail' => 'contracts',
            'financial.contracts.milestones.store' => 'contracts',
            'financial.contracts.milestones.update' => 'contracts',
            'financial.contracts.milestones.destroy' => 'contracts',
            'financial.contracts.milestones.complete' => 'contracts',
            'financial.contracts.convert-to-invoice' => 'contracts',
            'financial.contracts.compliance-status' => 'contracts',

            // Analytics
            'financial.analytics.index' => 'analytics',
            'financial.analytics.revenue' => 'analytics-revenue',
            'financial.analytics.performance' => 'analytics-performance',
            'financial.analytics.clients' => 'analytics-clients',
            'financial.analytics.forecast' => 'analytics-forecast',
            'financial.analytics.risk' => 'analytics-risk',
            'financial.analytics.lifecycle' => 'analytics-lifecycle',
            'financial.analytics.export' => 'analytics-export',

            // Collections (if implemented)
            'collections.dashboard' => 'collections',

            // Billing Portal
            'billing.index' => 'billing',
            'billing.subscription' => 'billing-subscription',
            'billing.payment-methods' => 'billing-payment-methods',
            'billing.change-plan' => 'billing-change-plan',
            'billing.update-plan' => 'billing-update-plan',
            'billing.invoices' => 'billing-invoices',
            'billing.invoices.download' => 'billing-invoices',
            'billing.usage' => 'billing-usage',
            'billing.cancel-subscription' => 'billing-subscription',
            'billing.reactivate-subscription' => 'billing-subscription',
            'billing.portal' => 'billing-portal',
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
            'reports.category' => 'category',
            'reports.builder' => 'builder',
            'reports.generate' => 'generate',
            'reports.save' => 'save',
            'reports.schedule' => 'schedule',
            'reports.scheduled' => 'scheduled',
        ],
        'knowledge' => [
            // Main knowledge base routes
            'knowledge.index' => 'index',
            'knowledge.articles.index' => 'articles',
            'knowledge.articles.create' => 'create-article',
            'knowledge.articles.show' => 'articles',
            'knowledge.articles.edit' => 'articles',
            'knowledge.articles.destroy' => 'articles',
            'knowledge.categories.index' => 'categories',
            'knowledge.categories.create' => 'categories',
            'knowledge.categories.show' => 'categories',
            'knowledge.categories.edit' => 'categories',
            'knowledge.categories.destroy' => 'categories',
            // Search and analytics
            'knowledge.search' => 'search',
            'knowledge.popular' => 'popular',
            'knowledge.recent' => 'recent',
            'knowledge.analytics' => 'analytics',
        ],
        'integrations' => [
            // Main integration routes
            'integrations.index' => 'index',
            'integrations.rmm.index' => 'rmm',
            'integrations.rmm.create' => 'rmm',
            'integrations.rmm.show' => 'rmm',
            'integrations.rmm.edit' => 'rmm',
            'integrations.rmm.destroy' => 'rmm',
            'integrations.rmm.sync' => 'rmm',
            'integrations.webhooks.index' => 'webhooks',
            'integrations.webhooks.create' => 'webhooks',
            'integrations.webhooks.show' => 'webhooks',
            'integrations.webhooks.edit' => 'webhooks',
            'integrations.webhooks.destroy' => 'webhooks',
            'integrations.webhooks.test' => 'webhooks',
            // API management
            'integrations.api.tokens' => 'api-tokens',
            'integrations.api.logs' => 'api-logs',
            'integrations.api.documentation' => 'api-docs',
        ],
        'settings' => [
            // Main settings
            'settings.index' => 'index',
            'settings.general' => 'general',
            'settings.security' => 'security',
            'settings.email' => 'email',
            'settings.integrations' => 'integrations',

            // User management
            'users.index' => 'users',
            'users.create' => 'users',
            'users.show' => 'users',
            'users.edit' => 'users',
            'users.destroy' => 'users',
            'users.profile' => 'profile',
            'users.profile.update' => 'profile',

            // Admin (super-admin only)
            'admin.subscriptions.index' => 'subscriptions',
            'admin.subscriptions.analytics' => 'subscriptions-analytics',
            'admin.subscriptions.export' => 'subscriptions-export',
            'admin.subscriptions.show' => 'subscriptions',
            'admin.subscriptions.create-tenant' => 'subscriptions',
            'admin.subscriptions.change-plan' => 'subscriptions',
            'admin.subscriptions.cancel' => 'subscriptions',
            'admin.subscriptions.reactivate' => 'subscriptions',
            'admin.subscriptions.suspend-tenant' => 'subscriptions',
            'admin.subscriptions.reactivate-tenant' => 'subscriptions',
        ],
        'portal' => [
            // Client portal routes
            'client.login' => 'login',
            'client.dashboard' => 'dashboard',
            'client.contracts' => 'contracts',
            'client.contracts.show' => 'contracts',
            'client.contracts.sign' => 'contracts',
            'client.contracts.download' => 'contracts',
            'client.milestones.show' => 'milestones',
            'client.milestones.progress' => 'milestones',
            'client.invoices.index' => 'invoices',
            'client.invoices.show' => 'invoices',
            'client.invoices.download' => 'invoices',
            'client.profile' => 'profile',
            'client.profile.update' => 'profile',
        ],
    ];

    /**
     * Get the current active domain based on the route
     * This now also serves as the sidebar context for the new extensible sidebar
     */
    public static function getActiveDomain(): ?string
    {
        return static::getSidebarContext();
    }
    
    /**
     * Get the current sidebar context based on the route
     * This replaces getActiveDomain for the new sidebar system
     */
    public static function getSidebarContext(): ?string
    {
        $currentRouteName = Route::currentRouteName();

        if (! $currentRouteName) {
            return null;
        }

        // Special case: Hide sidebar on clients.index when no client is selected
        if ($currentRouteName === 'clients.index' && !static::getSelectedClient()) {
            return null;
        }
        
        // Hide sidebar on client create/edit routes
        if (in_array($currentRouteName, ['clients.create', 'clients.store'])) {
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

        if (! $currentRouteName || ! $activeDomain) {
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
        if (! empty($params)) {
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

        if (! $activeDomain || ! $currentRouteName) {
            return [];
        }

        $breadcrumbs = [];
        $routeSegments = explode('.', $currentRouteName);
        
        // Check if there's a selected client in the session
        $selectedClient = static::getSelectedClient();
        
        // Special handling for clients domain pages
        if ($activeDomain === 'clients') {
            // If we have a selected client on clients.index, just show the client name
            if ($currentRouteName === 'clients.index' && $selectedClient) {
                $breadcrumbs[] = [
                    'name' => $selectedClient->name,
                    'active' => true,
                ];
                return $breadcrumbs;
            }
            
            // For client creation/import pages (no specific client), show the action
            if (in_array($currentRouteName, ['clients.create', 'clients.import.form'])) {
                $pageTitle = static::getPageTitleFromRoute($currentRouteName, $activeDomain);
                if ($pageTitle) {
                    $breadcrumbs[] = [
                        'name' => $pageTitle,
                        'active' => true,
                    ];
                }
                return $breadcrumbs;
            }
            
            // For clients.index without a selected client, don't show any breadcrumb
            if ($currentRouteName === 'clients.index' && !$selectedClient) {
                return [];
            }
        }
        
        // For other domain index pages with a selected client
        // Show: Client Name > Domain (e.g., "Acme Corp > Tickets")
        $isDomainIndex = $currentRouteName === static::getDomainIndexRoute($activeDomain);
        if ($isDomainIndex && $selectedClient && $activeDomain !== 'clients') {
            $breadcrumbs[] = [
                'name' => $selectedClient->name,
                'route' => 'clients.show',
                'params' => ['client' => $selectedClient->id],
            ];
            $breadcrumbs[] = [
                'name' => static::getDomainDisplayName($activeDomain),
                'active' => true,
            ];
            return $breadcrumbs;
        }
        
        // Only add client as root if we're not already on the client's page
        // This prevents duplication like "Client Name > Client Name"
        $isClientPage = $activeDomain === 'clients' && in_array($currentRouteName, ['clients.show', 'clients.edit']);
        
        if ($selectedClient && !$isClientPage) {
            // Add client as the root breadcrumb
            $breadcrumbs[] = [
                'name' => $selectedClient->name,
                'route' => 'clients.show',
                'params' => ['client' => $selectedClient->id],
            ];
        }
        
        // Add the domain breadcrumb (but never show "Clients" domain)
        $isDomainIndex = $currentRouteName === static::getDomainIndexRoute($activeDomain);
        $domainName = static::getDomainDisplayName($activeDomain);
        
        // Don't add domain breadcrumb if:
        // 1. It's the Clients domain (we never show this)
        // 2. We're on the domain index page AND have a client selected
        // 3. We're on the domain index page with no other breadcrumbs
        $skipDomainBreadcrumb = ($activeDomain === 'clients') || 
                                 ($isDomainIndex && ($selectedClient || empty($breadcrumbs)));
        
        if (!$skipDomainBreadcrumb) {
            $breadcrumbs[] = [
                'name' => $domainName,
                'route' => static::getDomainIndexRoute($activeDomain),
            ];
        }

        // Handle sub-sections within domains (e.g., financial.contracts, clients.contacts)
        if (count($routeSegments) >= 3) {
            $subsection = $routeSegments[1]; // e.g., 'contracts', 'invoices', 'contacts'
            $subsectionName = static::getSubsectionDisplayName($subsection);
            
            // Add subsection for financial domain
            if ($activeDomain === 'financial') {
                // Only add subsection breadcrumb if we're not on the subsection index page
                if ($routeSegments[2] !== 'index' || count($routeSegments) > 3) {
                    $breadcrumbs[] = [
                        'name' => $subsectionName,
                        'route' => $routeSegments[0] . '.' . $subsection . '.index',
                    ];
                }
            }
            
            // For client sub-sections (contacts, locations, etc.), add them directly
            // since we're not showing "Clients" as a breadcrumb
            if ($activeDomain === 'clients' && in_array($subsection, ['contacts', 'locations', 'documents', 'notes'])) {
                $breadcrumbs[] = [
                    'name' => $subsectionName,
                    'route' => $routeSegments[0] . '.' . $subsection . '.index',
                ];
            }
        }

        // Add specific page breadcrumb based on route
        $pageTitle = static::getPageTitleFromRoute($currentRouteName, $activeDomain);
        if ($pageTitle && !empty($pageTitle)) {
            // Check if this is a different title than what we already have
            $lastBreadcrumb = end($breadcrumbs);
            if ($lastBreadcrumb === false || $pageTitle !== $lastBreadcrumb['name']) {
                $breadcrumbs[] = [
                    'name' => $pageTitle,
                    'active' => true,
                ];
            }
        }

        // Mark the last breadcrumb as active if no specific page title was added
        if (!empty($breadcrumbs)) {
            $lastKey = array_key_last($breadcrumbs);
            if (!isset($breadcrumbs[$lastKey]['active'])) {
                $breadcrumbs[$lastKey]['active'] = true;
            }
        }

        return $breadcrumbs;
    }
    
    /**
     * Get display name for a domain
     */
    protected static function getDomainDisplayName(string $domain): string
    {
        $names = [
            'clients' => 'Clients',
            'tickets' => 'Tickets',
            'assets' => 'Assets',
            'financial' => 'Financial',
            'projects' => 'Projects',
            'reports' => 'Reports',
            'knowledge' => 'Knowledge Base',
            'integrations' => 'Integrations',
            'settings' => 'Settings',
        ];
        
        return $names[$domain] ?? ucfirst($domain);
    }
    
    /**
     * Get display name for a subsection
     */
    protected static function getSubsectionDisplayName(string $subsection): string
    {
        $names = [
            // Financial subsections
            'contracts' => 'Contracts',
            'invoices' => 'Invoices',
            'payments' => 'Payments',
            'credit-notes' => 'Credit Notes',
            'quotes' => 'Quotes',
            // Client subsections
            'contacts' => 'Contacts',
            'locations' => 'Locations',
            'documents' => 'Documents',
            'notes' => 'Notes',
            // Asset subsections
            'maintenance' => 'Maintenance',
            'warranties' => 'Warranties',
        ];
        
        return $names[$subsection] ?? ucfirst(str_replace('-', ' ', $subsection));
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
            'knowledge' => 'knowledge.index',
            'integrations' => 'integrations.index',
            'settings' => 'settings.index',
        ];

        return $routes[$domain] ?? 'dashboard';
    }

    /**
     * Get page title from route
     */
    protected static function getPageTitleFromRoute(string $routeName, string $domain): string
    {
        // Extract route segments
        $segments = explode('.', $routeName);
        
        // Special handling for specific routes
        $specificRoutes = [
            // Financial routes
            'financial.contracts.index' => 'Contracts',
            'financial.contracts.create' => 'New Contract',
            'financial.contracts.edit' => 'Edit Contract',
            'financial.contracts.show' => 'Contract Details',
            'financial.invoices.index' => 'Invoices',
            'financial.invoices.create' => 'New Invoice',
            'financial.invoices.edit' => 'Edit Invoice',
            'financial.invoices.show' => 'Invoice Details',
            'financial.payments.index' => 'Payments',
            'financial.credit-notes.index' => 'Credit Notes',
            'financial.quotes.index' => 'Quotes',
            // Client routes
            'clients.create' => 'New Client',
            'clients.import.form' => 'Import Clients',
            'clients.edit' => 'Edit Client',
            'clients.show' => 'Client Details',
            'clients.contacts.index' => 'Contacts',
            'clients.contacts.create' => 'New Contact',
            'clients.contacts.edit' => 'Edit Contact',
            'clients.locations.index' => 'Locations',
            'clients.locations.create' => 'New Location',
            'clients.locations.edit' => 'Edit Location',
            // Ticket routes
            'tickets.index' => 'Tickets',
            'tickets.create' => 'New Ticket',
            'tickets.show' => 'Ticket Details',
            'tickets.edit' => 'Edit Ticket',
            // Asset routes
            'assets.index' => 'Assets',
            'assets.create' => 'New Asset',
            'assets.show' => 'Asset Details',
            'assets.edit' => 'Edit Asset',
            // Project routes
            'projects.index' => 'Projects',
            'projects.create' => 'New Project',
            'projects.show' => 'Project Details',
            'projects.edit' => 'Edit Project',
        ];
        
        // Check if we have a specific route title
        if (isset($specificRoutes[$routeName])) {
            return $specificRoutes[$routeName];
        }
        
        // Generic action titles
        $actionTitles = [
            'create' => 'Create',
            'edit' => 'Edit',
            'show' => 'Details',
            'import' => 'Import',
            'export' => 'Export',
            'index' => null, // Index pages don't need additional breadcrumb
        ];

        // Get the action (last segment)
        $action = end($segments);
        if (isset($actionTitles[$action])) {
            if ($actionTitles[$action] === null) {
                // For index pages, check if there's a sub-section
                if (count($segments) > 2) {
                    // e.g., financial.contracts.index -> Contracts
                    return ucfirst($segments[count($segments) - 2]);
                }
                return ''; // No additional breadcrumb for domain index
            }
            
            // Get the entity name (second to last segment)
            if (count($segments) > 2) {
                $entity = $segments[count($segments) - 2];
                return ucfirst(Str::singular($entity)) . ' ' . $actionTitles[$action];
            }
            
            return $actionTitles[$action];
        }

        // Default to capitalizing the last meaningful segment
        if (count($segments) > 1) {
            return ucfirst(str_replace('-', ' ', end($segments)));
        }

        return '';
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
     * Register a sidebar section dynamically
     * This allows modules to add their own sidebar sections
     * 
     * @param string $context The sidebar context (e.g., 'main', 'settings')
     * @param string $key Unique key for the section
     * @param array $section Section configuration
     */
    public static function registerSidebarSection(string $context, string $key, array $section): void
    {
        if (app()->bound(\App\Services\SidebarConfigProvider::class)) {
            app(\App\Services\SidebarConfigProvider::class)->registerSection($context, $key, $section);
        }
    }
    
    /**
     * Register multiple sidebar sections at once
     * 
     * @param string $context The sidebar context
     * @param array $sections Array of sections with keys
     */
    public static function registerSidebarSections(string $context, array $sections): void
    {
        foreach ($sections as $key => $section) {
            static::registerSidebarSection($context, $key, $section);
        }
    }

    /**
     * Get badge counts for navigation items (with permission filtering)
     */
    public static function getBadgeCounts(string $domain): array
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        if (! $companyId) {
            return [];
        }

        // Check if user has permission to view this domain
        if (! static::canAccessDomain($user, $domain)) {
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
        $permission = $domain.'.view';

        return $user->hasPermission($permission);
    }

    /**
     * Get filtered navigation items based on user permissions
     */
    public static function getFilteredNavigationItems(string $domain): array
    {
        $user = auth()->user();

        if (! static::canAccessDomain($user, $domain)) {
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

            case 'knowledge':
                return static::getFilteredKnowledgeNavigation($user);

            case 'integrations':
                return static::getFilteredIntegrationsNavigation($user);

            case 'settings':
                return static::getFilteredSettingsNavigation($user);

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

        if ($user->hasPermission('financial.quotes.view')) {
            $items['quotes'] = 'Quotes';
        }

        if ($user->hasPermission('contracts.view')) {
            $items['contracts'] = 'Contracts';
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

        if ($user->hasPermission('financial.quotes.manage')) {
            $items['create-quote'] = 'Create Quote';
        }

        if ($user->hasPermission('contracts.create')) {
            $items['create-contract'] = 'Create Contract';
        }

        if ($user->hasPermission('financial.payments.manage')) {
            $items['create-payment'] = 'Record Payment';
        }

        if ($user->hasPermission('financial.expenses.manage')) {
            $items['create-expense'] = 'Add Expense';
        }

        if ($user->hasPermission('contracts.analytics')) {
            $items['contract-analytics'] = 'Contract Analytics';
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

        if ($user->hasPermission('tickets.templates.view')) {
            $items['templates'] = 'Templates';
        }

        if ($user->hasPermission('tickets.time-tracking.view')) {
            $items['time-tracking'] = 'Time Tracking';
        }

        if ($user->hasPermission('tickets.calendar.view')) {
            $items['calendar'] = 'Calendar';
        }

        if ($user->hasPermission('tickets.recurring.view')) {
            $items['recurring'] = 'Recurring Tickets';
        }

        if ($user->hasPermission('tickets.priority-queue.view')) {
            $items['priority-queue'] = 'Priority Queue';
        }

        if ($user->hasPermission('tickets.workflows.view')) {
            $items['workflows'] = 'Workflows';
        }

        if ($user->hasPermission('tickets.assignments.view')) {
            $items['assignments'] = 'Assignments';
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

        if ($user->hasPermission('reports.schedule')) {
            $items['scheduled'] = 'Scheduled Reports';
        }

        if ($user->hasPermission('reports.builder')) {
            $items['builder'] = 'Report Builder';
        }

        return $items;
    }

    /**
     * Get filtered knowledge base navigation items
     */
    protected static function getFilteredKnowledgeNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('knowledge.view')) {
            $items['index'] = 'Browse Knowledge';
        }

        if ($user->hasPermission('knowledge.articles.view')) {
            $items['articles'] = 'All Articles';
        }

        if ($user->hasPermission('knowledge.articles.create')) {
            $items['create-article'] = 'Create Article';
        }

        if ($user->hasPermission('knowledge.categories.view')) {
            $items['categories'] = 'Categories';
        }

        if ($user->hasPermission('knowledge.search')) {
            $items['search'] = 'Search';
        }

        $items['popular'] = 'Popular Articles';
        $items['recent'] = 'Recent Updates';

        if ($user->hasPermission('knowledge.analytics')) {
            $items['analytics'] = 'Analytics';
        }

        return $items;
    }

    /**
     * Get filtered integrations navigation items
     */
    protected static function getFilteredIntegrationsNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('integrations.view')) {
            $items['index'] = 'Integration Hub';
        }

        if ($user->hasPermission('integrations.rmm.view')) {
            $items['rmm'] = 'RMM Integration';
        }

        if ($user->hasPermission('integrations.webhooks.view')) {
            $items['webhooks'] = 'Webhooks';
        }

        if ($user->hasPermission('integrations.api.view')) {
            $items['api-tokens'] = 'API Tokens';
            $items['api-logs'] = 'API Logs';
            $items['api-docs'] = 'API Documentation';
        }

        return $items;
    }

    /**
     * Get filtered settings navigation items
     */
    protected static function getFilteredSettingsNavigation($user): array
    {
        $items = [];

        if ($user->hasPermission('settings.view')) {
            $items['index'] = 'Settings Overview';
        }

        if ($user->hasPermission('settings.general')) {
            $items['general'] = 'General Settings';
        }

        if ($user->hasPermission('settings.security')) {
            $items['security'] = 'Security';
        }

        if ($user->hasPermission('settings.email')) {
            $items['email'] = 'Email Configuration';
        }

        if ($user->hasPermission('settings.integrations')) {
            $items['integrations'] = 'Integration Settings';
        }

        if ($user->hasPermission('users.view')) {
            $items['users'] = 'User Management';
        }

        if ($user->hasPermission('users.profile')) {
            $items['profile'] = 'My Profile';
        }

        // Super admin only
        if ($user->company_id === 1 && $user->hasPermission('admin.subscriptions.view')) {
            $items['subscriptions'] = 'Subscription Management';
            $items['subscriptions-analytics'] = 'Subscription Analytics';
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
            'quotes' => 'financial.quotes.view',
            'contracts' => 'contracts.view',
            'payments' => 'financial.payments.view',
            'expenses' => 'financial.expenses.view',
            'create-invoice' => 'financial.invoices.manage',
            'create-quote' => 'financial.quotes.manage',
            'create-contract' => 'contracts.create',
            'create-payment' => 'financial.payments.manage',
            'create-expense' => 'financial.expenses.manage',
            'contract-analytics' => 'contracts.analytics',
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
            'templates' => 'tickets.templates.view',
            'time-tracking' => 'tickets.time-tracking.view',
            'calendar' => 'tickets.calendar.view',
            'recurring' => 'tickets.recurring.view',
            'priority-queue' => 'tickets.priority-queue.view',
            'workflows' => 'tickets.workflows.view',
            'assignments' => 'tickets.assignments.view',
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
                'contacts' => \App\Models\Contact::where('company_id', $companyId)->count(),
                'locations' => \App\Models\Location::where('company_id', $companyId)->count(),
                'documents' => 0, // Model not yet created
                'files' => 0, // Model not yet created
                'licenses' => 0, // Model not yet created
                'credentials' => 0, // Model not yet created
                'networks' => 0, // Model not yet created
                'services' => 0, // Model not yet created
                'vendors' => 0, // Model not yet created
                'racks' => 0, // Model not yet created
                'certificates' => 0, // Model not yet created
                'domains' => 0, // Model not yet created
                'recurring-invoices' => \App\Models\Recurring::where('company_id', $companyId)->count(),
                'quotes' => \App\Models\Quote::where('company_id', $companyId)->count(),
                'trips' => 0, // Model not yet created
                'calendar-events' => 0, // Model not yet created
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
                'watching' => (clone $baseQuery)->whereHas('watchers', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->count(),
                'scheduled' => (clone $baseQuery)->whereNotNull('scheduled_at')->where('scheduled_at', '>', now())->count(),
            ];

            // Add advanced functionality counts if models exist
            try {
                $counts['templates'] = \App\Domains\Ticket\Models\TicketTemplate::where('company_id', $companyId)
                    ->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['templates'] = 0;
            }

            try {
                $counts['time-tracking'] = \App\Domains\Ticket\Models\TicketTimeEntry::where('company_id', $companyId)
                    ->whereNull('ended_at')->count();
            } catch (\Exception $e) {
                $counts['time-tracking'] = 0;
            }

            try {
                $counts['calendar'] = \App\Domains\Ticket\Models\TicketCalendarEvent::where('company_id', $companyId)
                    ->where('start_date', '>=', now()->startOfDay())
                    ->where('start_date', '<=', now()->addDays(7)->endOfDay())
                    ->count();
            } catch (\Exception $e) {
                $counts['calendar'] = 0;
            }

            try {
                $counts['recurring'] = \App\Domains\Ticket\Models\RecurringTicket::where('company_id', $companyId)
                    ->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['recurring'] = 0;
            }

            try {
                $counts['workflows'] = \App\Domains\Ticket\Models\TicketWorkflow::where('company_id', $companyId)
                    ->where('is_active', true)->count();
            } catch (\Exception $e) {
                $counts['workflows'] = 0;
            }

            try {
                $counts['priority-queue'] = \App\Domains\Ticket\Models\TicketPriorityQueue::where('company_id', $companyId)
                    ->where('is_active', true)->count();
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
            // Asset maintenance and warranty counts - models not yet created
            $counts['maintenance'] = 0;
            $counts['maintenance-overdue'] = 0;
            $counts['warranties'] = 0;
            $counts['warranties-expiring'] = 0;
            $counts['depreciations'] = 0;

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
                'quotes' => \App\Models\Quote::where('company_id', $companyId)->count(),
                'contracts' => \App\Domains\Contract\Models\Contract::where('company_id', $companyId)->count(),
                'active-contracts' => \App\Domains\Contract\Models\Contract::where('company_id', $companyId)->where('status', 'active')->count(),
                'expiring-contracts' => \App\Domains\Contract\Models\Contract::where('company_id', $companyId)->expiringSoon(30)->count(),
                'payments' => \App\Models\Payment::where('company_id', $companyId)->count(),
                'expenses' => \App\Models\Expense::where('company_id', $companyId)->count(),
                'pending-payments' => \App\Models\Payment::where('company_id', $companyId)->where('status', 'pending')->count(),
                'pending-expenses' => \App\Models\Expense::where('company_id', $companyId)->where('status', 'pending_approval')->count(),
                'approved-expenses' => \App\Models\Expense::where('company_id', $companyId)->where('status', 'approved')->count(),
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
                    $counts['my_projects'] = (clone $baseQuery)->where(function ($q) use ($userId) {
                        $q->where('manager_id', $userId)
                            ->orWhereHas('members', function ($memberQuery) use ($userId) {
                                $memberQuery->where('user_id', $userId)->where('is_active', true);
                            });
                    })->count();
                }

                // Task counts
                try {
                    $taskClass = '\Foleybridge\Nestogy\Domains\Project\Models\Task';
                    if (class_exists($taskClass)) {
                        $taskQuery = $taskClass::whereHas('project', function ($q) use ($companyId) {
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
                        $milestoneQuery = $milestoneClass::whereHas('project', function ($q) use ($companyId) {
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
        if (! $clientId) {
            return null;
        }

        $user = auth()->user();
        if (! $user) {
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
        if (! $user) {
            return false;
        }

        // Verify the client belongs to the user's company
        try {
            $client = \App\Models\Client::where('id', $clientId)
                ->where('company_id', $user->company_id)
                ->first();

            if ($client) {
                session(['selected_client_id' => $clientId]);

                // Mark client as accessed for recent tracking
                $client->markAsAccessed();

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

        if (! $selectedClient) {
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

            if ($user->hasPermission('contracts.view')) {
                $items['contracts'] = 'Contracts';
            }

            if ($user->hasPermission('financial.invoices.view')) {
                $items['invoices'] = 'Invoices';
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
        if (! $clientId) {
            return static::getClientBadgeCounts($companyId);
        }

        try {
            // Get counts scoped to specific client
            $counts = [
                'contacts' => \App\Models\Contact::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'locations' => \App\Models\Location::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'documents' => 0, // ClientDocument model not yet created
                'files' => 0, // ClientFile model not yet created
                'licenses' => 0, // ClientLicense model not yet created
                'credentials' => 0, // ClientCredential model not yet created
                'networks' => 0, // ClientNetwork model not yet created
                'services' => 0, // ClientService model not yet created
                'vendors' => 0, // ClientVendor model not yet created
                'racks' => 0, // ClientRack model not yet created
                'certificates' => 0, // ClientCertificate model not yet created
                'domains' => 0, // ClientDomain model not yet created
                'recurring-invoices' => \App\Models\Recurring::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'quotes' => \App\Models\Quote::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'contracts' => \App\Domains\Contract\Models\Contract::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'invoices' => \App\Models\Invoice::where('company_id', $companyId)
                    ->where('client_id', $clientId)->count(),
                'trips' => 0, // ClientTrip model not yet created
                'calendar-events' => 0, // ClientCalendarEvent model not yet created
            ];

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
        if (! $client) {
            return null;
        }

        $user = auth()->user();
        if (! $user) {
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
                'recent_activity' => [],
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
        if (! $user) {
            return [];
        }

        $urgentItems = [
            'total' => 0,
            'financial' => 0,
            'notifications' => 0,
            'client' => [],
            'items' => [],
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
                if (! isset($urgentItems['client'][$clientId])) {
                    $urgentItems['client'][$clientId] = [
                        'total' => 0,
                        'critical' => 0,
                        'urgent' => 0,
                        'items' => [],
                    ];
                }

                $urgentItems['client'][$clientId]['critical']++;
                $urgentItems['client'][$clientId]['total']++;
                $urgentItems['client'][$clientId]['items'][] = [
                    'id' => $ticket->id,
                    'title' => $ticket->subject ?? 'Untitled Ticket',
                    'type' => 'ticket',
                    'priority' => 'critical',
                    'url' => route('tickets.show', $ticket),
                ];
            }

            foreach ($overdueInvoices as $invoice) {
                $clientId = $invoice->client_id;
                if (! isset($urgentItems['client'][$clientId])) {
                    $urgentItems['client'][$clientId] = [
                        'total' => 0,
                        'critical' => 0,
                        'urgent' => 0,
                        'items' => [],
                    ];
                }

                $urgentItems['client'][$clientId]['urgent']++;
                $urgentItems['client'][$clientId]['total']++;
                $urgentItems['client'][$clientId]['items'][] = [
                    'id' => $invoice->id,
                    'title' => 'Overdue Invoice #'.$invoice->number,
                    'type' => 'invoice',
                    'priority' => 'urgent',
                    'url' => route('financial.invoices.show', $invoice),
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
        if (! $user) {
            return [];
        }

        $todaysWork = [
            'total' => 0,
            'upcoming' => 0,
            'client' => [],
            'scheduled' => [],
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
                if (! isset($todaysWork['client'][$clientId])) {
                    $todaysWork['client'][$clientId] = 0;
                }
                $todaysWork['client'][$clientId]++;
            }

            foreach ($upcomingTickets as $ticket) {
                $clientId = $ticket->client_id ?? 'unassigned';
                if (! isset($todaysWork['scheduled'][$clientId])) {
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
            'active_nav_item' => static::getActiveNavigationItem(),
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
        $activeDomain = static::getActiveDomain();
        $breadcrumbs = [];

        // Start with client if one is selected
        if ($selectedClient) {
            $breadcrumbs[] = [
                'name' => $selectedClient->name,
                'route' => 'clients.show',
                'params' => ['client' => $selectedClient->id],
                'active' => false,
            ];
        }

        // Add domain if we're in a domain context
        if ($activeDomain) {
            $breadcrumbs[] = [
                'name' => static::getDomainDisplayName($activeDomain),
                'route' => static::getDomainIndexRoute($activeDomain),
                'active' => false,
            ];
        } else if (!$selectedClient) {
            // Only use Dashboard as root when not in a domain and no client selected
            $breadcrumbs[] = [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'active' => false,
            ];
        }

        // Add workflow context
        if ($workflow && $workflow !== 'default') {
            $workflowNames = [
                'urgent' => 'Urgent Items',
                'today' => "Today's Work",
                'scheduled' => 'Scheduled Work',
                'financial' => 'Financial Tasks',
                'reports' => 'Reports',
            ];

            $breadcrumbs[] = [
                'name' => $workflowNames[$workflow] ?? ucfirst($workflow),
                'route' => 'dashboard',
                'params' => ['view' => $workflow],
                'active' => false,
            ];
        }

        // Mark last item as active
        if (! empty($breadcrumbs)) {
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
                        'color' => 'red',
                    ];
                }

                if (static::userCanPerform($user, 'tickets.view')) {
                    $actions[] = [
                        'label' => 'Review SLA Breaches',
                        'route' => 'tickets.index',
                        'params' => ['filter' => 'sla_breach'],
                        'icon' => 'clock',
                        'color' => 'orange',
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
                        'color' => 'blue',
                    ];
                }

                if (static::userCanPerform($user, 'tickets.view')) {
                    $actions[] = [
                        'label' => 'View Calendar',
                        'route' => 'tickets.calendar.index',
                        'params' => [],
                        'icon' => 'calendar',
                        'color' => 'green',
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
                        'color' => 'green',
                    ];
                }

                if (static::userCanPerform($user, 'financial.payments.create')) {
                    $actions[] = [
                        'label' => 'Record Payment',
                        'route' => 'financial.payments.create',
                        'params' => [],
                        'icon' => 'credit-card',
                        'color' => 'blue',
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
        if (! $user) {
            return 'user';
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'admin';
        }
        if (method_exists($user, 'isTech') && $user->isTech()) {
            return 'tech';
        }
        if (method_exists($user, 'isAccountant') && $user->isAccountant()) {
            return 'accountant';
        }

        return 'user';
    }

    /**
     * Check if user can perform an action (helper method)
     */
    protected static function userCanPerform($user, $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        // Fallback for basic role checking
        $role = static::getUserPrimaryRole($user);

        $allowedActions = [
            'admin' => ['*'], // Admin can do everything
            'tech' => ['tickets.*', 'assets.*', 'clients.view'],
            'accountant' => ['financial.*', 'reports.*', 'clients.view'],
            'user' => ['tickets.view', 'clients.view'],
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
            'badges' => [],
        ];

        $user = auth()->user();
        if (! $user) {
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
                'financial' => $highlights['financial_count'],
            ];

            // Add alerts for critical items
            if ($highlights['urgent_count'] > 0) {
                $highlights['alerts'][] = [
                    'type' => 'urgent',
                    'count' => $highlights['urgent_count'],
                    'message' => $highlights['urgent_count'].' urgent item(s) require attention',
                ];
            }

        } catch (\Exception $e) {
            // Return default counts if queries fail
        }

        return $highlights;
    }

    /**
     * Get user's favorite clients
     */
    public static function getFavoriteClients(?int $limit = 5): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect([]);
        }

        try {
            $favoriteService = new \App\Services\ClientFavoriteService;

            return $favoriteService->getFavoriteClients($user, $limit);
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get user's recent clients (excluding favorites)
     */
    public static function getRecentClients(?int $limit = 3): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect([]);
        }

        try {
            $favoriteService = new \App\Services\ClientFavoriteService;

            return $favoriteService->getRecentClients($user, $limit);
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get smart client suggestions (favorites + recent up to 8 total)
     */
    public static function getSmartClientSuggestions(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'favorites' => collect([]),
                'recent' => collect([]),
                'total' => 0,
            ];
        }

        try {
            $favoriteService = new \App\Services\ClientFavoriteService;

            return $favoriteService->getSmartClientSuggestions($user);
        } catch (\Exception $e) {
            return [
                'favorites' => collect([]),
                'recent' => collect([]),
                'total' => 0,
            ];
        }
    }

    /**
     * Toggle favorite status for a client
     */
    public static function toggleClientFavorite(int $clientId): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        try {
            $client = \App\Models\Client::where('id', $clientId)
                ->where('company_id', $user->company_id)
                ->first();

            if (! $client) {
                return false;
            }

            $favoriteService = new \App\Services\ClientFavoriteService;

            return $favoriteService->toggle($user, $client);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a client is favorited by the current user
     */
    public static function isClientFavorite(int $clientId): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        try {
            $client = \App\Models\Client::where('id', $clientId)
                ->where('company_id', $user->company_id)
                ->first();

            if (! $client) {
                return false;
            }

            $favoriteService = new \App\Services\ClientFavoriteService;

            return $favoriteService->isFavorite($user, $client);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get recent client IDs from session
     */
    public static function getRecentClientIds(): array
    {
        return session('recent_client_ids', []);
    }

    /**
     * Add a client ID to recent list in session
     */
    public static function addToRecentClients(int $clientId): void
    {
        $recentIds = session('recent_client_ids', []);

        // Remove if already exists to avoid duplicates
        $recentIds = array_diff($recentIds, [$clientId]);

        // Add to the beginning
        array_unshift($recentIds, $clientId);

        // Keep only the last 10
        $recentIds = array_slice($recentIds, 0, 10);

        session(['recent_client_ids' => $recentIds]);
    }
}
