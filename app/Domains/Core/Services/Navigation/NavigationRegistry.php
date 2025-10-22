<?php

namespace App\Domains\Core\Services\Navigation;

class NavigationRegistry
{
    protected static array $registry = [];

    public static function register(string $domain, string $key, array $config): void
    {
        if (!isset(static::$registry[$domain])) {
            static::$registry[$domain] = [];
        }
        
        static::$registry[$domain][$key] = array_merge([
            'label' => null,
            'icon' => null,
            'route' => null,
            'permission' => null,
            'section' => 'general',
            'order' => 999,
        ], $config);
    }

    public static function get(string $domain, ?string $key = null): array
    {
        if ($key === null) {
            return static::$registry[$domain] ?? [];
        }
        
        return static::$registry[$domain][$key] ?? [];
    }

    public static function getBySection(string $domain, string $section): array
    {
        $items = static::get($domain);
        
        return array_filter($items, fn($item) => ($item['section'] ?? 'general') === $section);
    }

    public static function all(): array
    {
        return static::$registry;
    }

    public static function boot(): void
    {
        static::registerFinancialDomain();
        static::registerTicketsDomain();
        static::registerClientsDomain();
        static::registerAssetsDomain();
        static::registerProjectsDomain();
        static::registerMarketingDomain();
    }

    protected static function registerFinancialDomain(): void
    {
        static::register('financial', 'invoices', [
            'label' => 'Invoices',
            'icon' => 'document-text',
            'route' => 'financial.invoices.index',
            'permission' => 'financial.invoices.view',
            'section' => 'billing',
            'order' => 10,
        ]);

        static::register('financial', 'quotes', [
            'label' => 'Quotes',
            'icon' => 'document-duplicate',
            'route' => 'financial.quotes.index',
            'permission' => 'financial.quotes.view',
            'section' => 'billing',
            'order' => 20,
        ]);

        static::register('financial', 'contracts', [
            'label' => 'Contracts',
            'icon' => 'document-check',
            'route' => 'financial.contracts.index',
            'permission' => 'contracts.view',
            'section' => 'billing',
            'order' => 30,
        ]);

        static::register('financial', 'payments', [
            'label' => 'Payments',
            'icon' => 'credit-card',
            'route' => 'financial.payments.index',
            'permission' => 'financial.payments.view',
            'section' => 'billing',
            'order' => 40,
        ]);

        static::register('financial', 'credits', [
            'label' => 'Client Credits',
            'icon' => 'ticket',
            'route' => 'financial.credits.index',
            'permission' => 'financial.credits.view',
            'section' => 'billing',
            'order' => 45,
        ]);

        static::register('financial', 'expenses', [
            'label' => 'Expenses',
            'icon' => 'receipt-percent',
            'route' => 'financial.expenses.index',
            'permission' => 'financial.expenses.view',
            'section' => 'billing',
            'order' => 50,
        ]);

        static::register('financial', 'products', [
            'label' => 'Products',
            'icon' => 'cube',
            'route' => 'products.index',
            'permission' => 'manage-products',
            'section' => 'products',
            'order' => 10,
        ]);

        static::register('financial', 'services', [
            'label' => 'Services',
            'icon' => 'wrench-screwdriver',
            'route' => 'services.index',
            'permission' => 'manage-products',
            'section' => 'products',
            'order' => 20,
        ]);

        static::register('financial', 'bundles', [
            'label' => 'Bundles',
            'icon' => 'rectangle-stack',
            'route' => 'bundles.index',
            'permission' => 'manage-bundles',
            'section' => 'products',
            'order' => 30,
        ]);
    }

    protected static function registerTicketsDomain(): void
    {
        static::register('tickets', 'overview', [
            'label' => 'All Tickets',
            'icon' => 'ticket',
            'route' => 'tickets.index',
            'permission' => 'tickets.view',
            'section' => 'primary',
            'order' => 10,
        ]);

        static::register('tickets', 'create', [
            'label' => 'Create Ticket',
            'icon' => 'plus',
            'route' => 'tickets.create',
            'permission' => 'tickets.create',
            'section' => 'primary',
            'order' => 20,
        ]);

        static::register('tickets', 'my-tickets', [
            'label' => 'My Tickets',
            'icon' => 'user',
            'route' => 'tickets.index',
            'params' => ['filter' => 'my'],
            'permission' => 'tickets.view',
            'section' => 'my-work',
            'order' => 10,
        ]);

        static::register('tickets', 'active-timers', [
            'label' => 'Active Timers',
            'icon' => 'clock',
            'route' => 'tickets.active-timers',
            'permission' => 'tickets.view',
            'section' => 'my-work',
            'order' => 20,
        ]);

        static::register('tickets', 'sla-violations', [
            'label' => 'SLA Violations',
            'icon' => 'exclamation-triangle',
            'route' => 'tickets.sla-violations',
            'permission' => 'tickets.view',
            'section' => 'critical',
            'order' => 10,
        ]);

        static::register('tickets', 'unassigned', [
            'label' => 'Unassigned Tickets',
            'icon' => 'user-minus',
            'route' => 'tickets.unassigned',
            'permission' => 'tickets.view',
            'section' => 'critical',
            'order' => 20,
        ]);
    }

    protected static function registerClientsDomain(): void
    {
        static::register('clients', 'details', [
            'label' => 'Client Details',
            'icon' => 'building-office',
            'route' => 'clients.show',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'client-info',
            'order' => 10,
        ]);

        static::register('clients', 'contacts', [
            'label' => 'Contacts',
            'icon' => 'users',
            'route' => 'clients.contacts.index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.contacts.view',
            'section' => 'client-info',
            'order' => 20,
        ]);

        static::register('clients', 'locations', [
            'label' => 'Locations',
            'icon' => 'map-pin',
            'route' => 'clients.locations.index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'client-info',
            'order' => 30,
        ]);

        static::register('clients', 'tickets-overview', [
            'label' => 'All Tickets',
            'icon' => 'ticket',
            'route' => 'tickets.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'tickets.view',
            'section' => 'tickets',
            'order' => 10,
        ]);

        static::register('clients', 'tickets-create', [
            'label' => 'Create Ticket',
            'icon' => 'plus',
            'route' => 'tickets.create',
            'params' => [],
            'requires_client' => true,
            'permission' => 'tickets.create',
            'section' => 'tickets',
            'order' => 20,
        ]);

        static::register('clients', 'tickets-my', [
            'label' => 'My Tickets',
            'icon' => 'user',
            'route' => 'tickets.index',
            'params' => ['filter' => 'my'],
            'requires_client' => true,
            'permission' => 'tickets.view',
            'section' => 'tickets',
            'order' => 30,
        ]);

        static::register('clients', 'tickets-sla', [
            'label' => 'SLA Violations',
            'icon' => 'exclamation-triangle',
            'route' => 'tickets.sla-violations',
            'params' => [],
            'requires_client' => true,
            'permission' => 'tickets.view',
            'section' => 'tickets',
            'order' => 40,
        ]);

        static::register('clients', 'assets-overview', [
            'label' => 'All Assets',
            'icon' => 'server',
            'route' => 'assets.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'assets.view',
            'section' => 'assets',
            'order' => 10,
        ]);

        static::register('clients', 'assets-create', [
            'label' => 'Add New Asset',
            'icon' => 'plus',
            'route' => 'assets.create',
            'params' => [],
            'requires_client' => true,
            'permission' => 'assets.create',
            'section' => 'assets',
            'order' => 20,
        ]);



        static::register('clients', 'projects-overview', [
            'label' => 'All Projects',
            'icon' => 'briefcase',
            'route' => 'projects.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'projects.view',
            'section' => 'projects',
            'order' => 10,
        ]);

        static::register('clients', 'projects-create', [
            'label' => 'Create Project',
            'icon' => 'plus',
            'route' => 'projects.create',
            'params' => [],
            'requires_client' => true,
            'permission' => 'projects.create',
            'section' => 'projects',
            'order' => 20,
        ]);

        static::register('clients', 'projects-active', [
            'label' => 'Active Projects',
            'icon' => 'play',
            'route' => 'projects.index',
            'params' => ['status' => 'active'],
            'requires_client' => true,
            'permission' => 'projects.view',
            'section' => 'projects',
            'order' => 30,
        ]);



        static::register('clients', 'it-docs', [
            'label' => 'IT Documentation',
            'icon' => 'document-text',
            'route' => 'clients.it-documentation.client-index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'infrastructure',
            'order' => 10,
        ]);

        static::register('clients', 'domains', [
            'label' => 'Domains',
            'icon' => 'globe-alt',
            'route' => 'clients.domains.index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'infrastructure',
            'order' => 30,
        ]);

        static::register('clients', 'credentials', [
            'label' => 'Credentials',
            'icon' => 'key',
            'route' => 'clients.credentials.index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'infrastructure',
            'order' => 40,
        ]);

        static::register('clients', 'licenses', [
            'label' => 'Licenses',
            'icon' => 'identification',
            'route' => 'clients.licenses.index',
            'params' => ['client' => '{client_id}'],
            'requires_client' => true,
            'permission' => 'clients.view',
            'section' => 'infrastructure',
            'order' => 50,
        ]);

        static::register('clients', 'invoices', [
            'label' => 'Invoices',
            'icon' => 'document-text',
            'route' => 'financial.invoices.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'financial.invoices.view',
            'section' => 'billing',
            'order' => 10,
        ]);

        static::register('clients', 'quotes', [
            'label' => 'Quotes',
            'icon' => 'document-duplicate',
            'route' => 'financial.quotes.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'financial.quotes.view',
            'section' => 'billing',
            'order' => 20,
        ]);

        static::register('clients', 'contracts', [
            'label' => 'Contracts',
            'icon' => 'document-check',
            'route' => 'financial.contracts.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'contracts.view',
            'section' => 'billing',
            'order' => 30,
        ]);

        static::register('clients', 'payments', [
            'label' => 'Payments',
            'icon' => 'credit-card',
            'route' => 'financial.payments.index',
            'params' => [],
            'requires_client' => true,
            'permission' => 'financial.payments.view',
            'section' => 'billing',
            'order' => 40,
        ]);
    }

    protected static function registerAssetsDomain(): void
    {
        static::register('assets', 'overview', [
            'label' => 'All Assets',
            'icon' => 'server',
            'route' => 'assets.index',
            'permission' => 'assets.view',
            'section' => 'primary',
            'order' => 10,
        ]);

        static::register('assets', 'create', [
            'label' => 'Add New Asset',
            'icon' => 'plus',
            'route' => 'assets.create',
            'permission' => 'assets.create',
            'section' => 'primary',
            'order' => 20,
        ]);
    }

    protected static function registerProjectsDomain(): void
    {
        static::register('projects', 'overview', [
            'label' => 'All Projects',
            'icon' => 'briefcase',
            'route' => 'projects.index',
            'permission' => 'projects.view',
            'section' => 'primary',
            'order' => 10,
        ]);

        static::register('projects', 'create', [
            'label' => 'Create Project',
            'icon' => 'plus',
            'route' => 'projects.create',
            'permission' => 'projects.create',
            'section' => 'primary',
            'order' => 20,
        ]);

        static::register('projects', 'active', [
            'label' => 'Active Projects',
            'icon' => 'play',
            'route' => 'projects.index',
            'params' => ['status' => 'active'],
            'permission' => 'projects.view',
            'section' => 'filters',
            'order' => 10,
        ]);

        static::register('projects', 'completed', [
            'label' => 'Completed Projects',
            'icon' => 'check-circle',
            'route' => 'projects.index',
            'params' => ['status' => 'completed'],
            'permission' => 'projects.view',
            'section' => 'filters',
            'order' => 20,
        ]);
    }

    protected static function registerMarketingDomain(): void
    {
        // CAMPAIGNS Section
        static::register('marketing', 'all-campaigns', [
            'label' => 'All Campaigns',
            'icon' => 'megaphone',
            'route' => 'marketing.campaigns.index',
            'permission' => 'view-campaigns',
            'section' => 'campaigns',
            'order' => 10,
            'description' => 'Email marketing campaigns and automation',
            'commands' => [
                'show' => ['campaigns', 'marketing', 'email campaigns'],
                'goto' => ['campaigns', 'marketing'],
            ],
        ]);

        static::register('marketing', 'create-campaign', [
            'label' => 'Create Campaign',
            'icon' => 'plus',
            'route' => 'marketing.campaigns.create',
            'permission' => 'create-campaigns',
            'section' => 'campaigns',
            'order' => 20,
            'description' => 'Create new marketing campaign',
            'commands' => [
                'create' => ['campaign', 'marketing campaign', 'email campaign'],
            ],
        ]);

        static::register('marketing', 'active-campaigns', [
            'label' => 'Active Campaigns',
            'icon' => 'play',
            'route' => 'marketing.campaigns.index',
            'params' => ['status' => 'active'],
            'permission' => 'view-campaigns',
            'section' => 'campaigns',
            'order' => 30,
        ]);

        static::register('marketing', 'draft-campaigns', [
            'label' => 'Draft Campaigns',
            'icon' => 'document-text',
            'route' => 'marketing.campaigns.index',
            'params' => ['status' => 'draft'],
            'permission' => 'view-campaigns',
            'section' => 'campaigns',
            'order' => 40,
        ]);

        // LEADS & CONTACTS Section
        static::register('marketing', 'all-leads', [
            'label' => 'All Leads',
            'icon' => 'user-group',
            'route' => 'leads.index',
            'permission' => 'view-leads',
            'section' => 'leads',
            'order' => 10,
            'description' => 'Manage and track leads',
        ]);

        static::register('marketing', 'qualified-leads', [
            'label' => 'Qualified Leads',
            'icon' => 'star',
            'route' => 'leads.index',
            'params' => ['status' => 'qualified'],
            'permission' => 'view-leads',
            'section' => 'leads',
            'order' => 20,
        ]);

        static::register('marketing', 'high-score-leads', [
            'label' => 'High-Score Leads',
            'icon' => 'fire',
            'route' => 'leads.index',
            'params' => ['score' => 'high'],
            'permission' => 'view-leads',
            'section' => 'leads',
            'order' => 25,
        ]);

        static::register('marketing', 'lead-dashboard', [
            'label' => 'Lead Dashboard',
            'icon' => 'chart-bar',
            'route' => 'leads.dashboard',
            'permission' => 'view-leads',
            'section' => 'leads',
            'order' => 30,
        ]);

        static::register('marketing', 'import-leads', [
            'label' => 'Import Leads',
            'icon' => 'arrow-down-tray',
            'route' => 'leads.import.form',
            'permission' => 'manage-leads',
            'section' => 'leads',
            'order' => 40,
        ]);

        // AUTOMATION & SEQUENCES Section
        static::register('marketing', 'active-enrollments', [
            'label' => 'Active Enrollments',
            'icon' => 'bolt',
            'route' => 'marketing.enrollments.index',
            'permission' => 'view-campaigns',
            'section' => 'automation',
            'order' => 10,
        ]);

        static::register('marketing', 'email-templates', [
            'label' => 'Email Templates',
            'icon' => 'document-duplicate',
            'route' => 'marketing.templates.index',
            'permission' => 'manage-templates',
            'section' => 'automation',
            'order' => 20,
        ]);

        // ANALYTICS & INSIGHTS Section
        static::register('marketing', 'campaign-performance', [
            'label' => 'Campaign Performance',
            'icon' => 'chart-bar',
            'route' => 'marketing.analytics.campaigns',
            'permission' => 'view-analytics',
            'section' => 'analytics',
            'order' => 10,
        ]);

        static::register('marketing', 'email-tracking', [
            'label' => 'Email Performance',
            'icon' => 'envelope-open',
            'route' => 'marketing.analytics.email-tracking',
            'permission' => 'view-analytics',
            'section' => 'analytics',
            'order' => 20,
        ]);

        static::register('marketing', 'attribution', [
            'label' => 'Attribution Report',
            'icon' => 'link',
            'route' => 'marketing.analytics.attribution',
            'permission' => 'view-analytics',
            'section' => 'analytics',
            'order' => 30,
        ]);

        static::register('marketing', 'revenue-attribution', [
            'label' => 'Revenue Attribution',
            'icon' => 'currency-dollar',
            'route' => 'marketing.analytics.revenue',
            'permission' => 'view-analytics',
            'section' => 'analytics',
            'order' => 40,
        ]);
    }
}
