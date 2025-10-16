<?php

namespace App\Domains\Core\Services\Navigation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SettingsSidebarContext
{
    protected static array $registeredSections = [];

    protected static array $configCache = [];

    private const PHYSICAL_MAIL_LABEL = 'Physical Mail';

    /**
     * Get the full settings sidebar configuration
     */
    public static function getConfiguration(): array
    {
        $user = Auth::user();
        $cacheKey = $user ? 'settings_' . $user->id : 'settings_guest';
        
        // Check cache first
        if (isset(static::$configCache[$cacheKey])) {
            return static::$configCache[$cacheKey];
        }

        $config = [
            'title' => 'Settings',
            'icon' => 'cog-6-tooth',
            'sections' => static::buildSections(),
        ];

        // Apply permission filtering
        $config = static::filterByPermissions($config);

        // Cache the configuration
        static::$configCache[$cacheKey] = $config;

        return $config;
    }

    /**
     * Build all settings sections
     */
    protected static function buildSections(): array
    {
        return array_filter([
            static::buildPrimarySection(),
            static::buildCompanySection(),
            static::buildSecuritySection(),
            static::buildCommunicationSection(),
            static::buildFinancialSection(),
            static::buildOperationsSection(),
            static::buildIntegrationsSection(),
            static::buildSystemSection(),
            ...static::$registeredSections,
        ]);
    }

    /**
     * Build primary section (overview)
     */
    protected static function buildPrimarySection(): array
    {
        return [
            'type' => 'primary',
            'items' => [
                [
                    'name' => 'Settings Overview',
                    'route' => 'settings.index',
                    'icon' => 'home',
                    'key' => 'overview',
                    'description' => 'All settings at a glance',
                ],
            ],
        ];
    }

    /**
     * Build Company section
     */
    protected static function buildCompanySection(): array
    {
        return [
            'type' => 'section',
            'title' => 'COMPANY',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'General',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'company', 'category' => 'general'],
                    'icon' => 'adjustments-horizontal',
                    'key' => 'general',
                    'description' => 'Company information and basic settings',
                ],
                [
                    'name' => 'Branding',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'company', 'category' => 'branding'],
                    'icon' => 'paint-brush',
                    'key' => 'branding',
                    'description' => 'Logo, colors, and brand guidelines',
                ],
                [
                    'name' => 'Users',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'company', 'category' => 'users'],
                    'icon' => 'users',
                    'key' => 'users',
                    'description' => 'Manage team members and accounts',
                ],
                [
                    'name' => 'Subsidiaries',
                    'route' => 'subsidiaries.index',
                    'icon' => 'building-office-2',
                    'key' => 'subsidiaries',
                    'description' => 'Manage company branches',
                ],
            ],
        ];
    }

    /**
     * Build Security section
     */
    protected static function buildSecuritySection(): array
    {
        return [
            'type' => 'section',
            'title' => 'SECURITY',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Access Control',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'security', 'category' => 'access'],
                    'icon' => 'shield-check',
                    'key' => 'access',
                    'description' => 'Control who can access what',
                ],
                [
                    'name' => 'Authentication',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'security', 'category' => 'auth'],
                    'icon' => 'finger-print',
                    'key' => 'auth',
                    'description' => 'Login security and 2FA settings',
                ],
                [
                    'name' => 'Compliance',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'security', 'category' => 'compliance'],
                    'icon' => 'clipboard-document-check',
                    'key' => 'compliance',
                    'description' => 'GDPR, HIPAA, and compliance settings',
                ],
                [
                    'name' => 'Permissions',
                    'url' => '/settings/permissions/manage?tab=matrix',
                    'icon' => 'key',
                    'key' => 'permissions',
                    'description' => 'Manage granular permissions',
                ],
                [
                    'name' => 'Roles',
                    'url' => '/settings/permissions/manage?tab=roles',
                    'icon' => 'identification',
                    'key' => 'roles',
                    'description' => 'Create and manage user roles',
                ],
            ],
        ];
    }

    /**
     * Build Communication section
     */
    protected static function buildCommunicationSection(): array
    {
        return [
            'type' => 'section',
            'title' => 'COMMUNICATION',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Email',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'communication', 'category' => 'email'],
                    'icon' => 'envelope',
                    'key' => 'email',
                    'description' => 'Email configuration and SMTP settings',
                ],
                [
                    'name' => 'Notification Preferences',
                    'route' => 'settings.notifications',
                    'icon' => 'bell',
                    'key' => 'notifications',
                    'description' => 'Configure email and in-app notification preferences',
                ],
                [
                    'name' => self::PHYSICAL_MAIL_LABEL,
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'communication', 'category' => 'physical-mail'],
                    'icon' => 'paper-airplane',
                    'key' => 'physical-mail',
                    'description' => 'Physical mail integration settings',
                ],
                [
                    'name' => 'Templates',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'communication', 'category' => 'templates'],
                    'icon' => 'document-duplicate',
                    'key' => 'templates',
                    'description' => 'Email and notification templates',
                ],
            ],
        ];
    }

    /**
     * Build Financial section
     */
    protected static function buildFinancialSection(): array
    {
        return [
            'type' => 'section',
            'title' => 'FINANCIAL',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Billing',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'financial', 'category' => 'billing'],
                    'icon' => 'credit-card',
                    'key' => 'billing',
                    'description' => 'Billing cycles and invoice settings',
                ],
                [
                    'name' => 'Accounting',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'financial', 'category' => 'accounting'],
                    'icon' => 'book-open',
                    'key' => 'accounting',
                    'description' => 'General ledger and accounting rules',
                ],
                [
                    'name' => 'Payments',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'financial', 'category' => 'payments'],
                    'icon' => 'banknotes',
                    'key' => 'payments',
                    'description' => 'Payment gateways and methods',
                ],
                [
                    'name' => 'Taxes',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'financial', 'category' => 'taxes'],
                    'icon' => 'calculator',
                    'key' => 'taxes',
                    'description' => 'Tax rates and compliance',
                ],
            ],
        ];
    }

    /**
     * Build Operations section
     */
    protected static function buildOperationsSection(): array
    {
        return [
            'type' => 'section',
            'title' => 'OPERATIONS',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Ticketing',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'ticketing'],
                    'icon' => 'ticket',
                    'key' => 'ticketing',
                    'description' => 'Ticket system configuration',
                ],
                [
                    'name' => 'Projects',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'projects'],
                    'icon' => 'folder',
                    'key' => 'projects',
                    'description' => 'Project management settings',
                ],
                [
                    'name' => 'Assets',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'assets'],
                    'icon' => 'computer-desktop',
                    'key' => 'assets',
                    'description' => 'Asset inventory configuration',
                ],
                [
                    'name' => 'Contracts',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'contracts'],
                    'icon' => 'document-text',
                    'key' => 'contracts',
                    'description' => 'Contract templates and management',
                ],
                [
                    'name' => 'Contract Clauses',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'clauses'],
                    'icon' => 'document-check',
                    'key' => 'clauses',
                    'description' => 'Pre-built contract clauses',
                ],
                [
                    'name' => 'Portal',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'portal'],
                    'icon' => 'user-group',
                    'key' => 'portal',
                    'description' => 'Client portal settings',
                ],
                [
                    'name' => 'Reports',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'reports'],
                    'icon' => 'chart-bar',
                    'key' => 'reports',
                    'description' => 'Report generation and export',
                ],
                [
                    'name' => 'Knowledge Base',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'knowledge'],
                    'icon' => 'book-open',
                    'key' => 'knowledge',
                    'description' => 'Knowledge base and documentation',
                ],
                [
                    'name' => 'Training',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'operations', 'category' => 'training'],
                    'icon' => 'academic-cap',
                    'key' => 'training',
                    'description' => 'Training programs and resources',
                ],
                [
                    'name' => 'Categories',
                    'route' => 'settings.categories.index',
                    'icon' => 'folder-open',
                    'key' => 'categories',
                    'description' => 'Manage system categories',
                ],
            ],
        ];
    }

    /**
     * Build Integrations section
     */
    protected static function buildIntegrationsSection(): array
    {
        return [
            'type' => 'section',
            'title' => 'INTEGRATIONS',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Overview',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'integrations', 'category' => 'overview'],
                    'icon' => 'puzzle-piece',
                    'key' => 'integrations-overview',
                    'description' => 'Available integrations',
                ],
                [
                    'name' => 'RMM',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'integrations', 'category' => 'rmm'],
                    'icon' => 'computer-desktop',
                    'key' => 'rmm',
                    'description' => 'Remote monitoring and management',
                ],
                [
                    'name' => 'API',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'integrations', 'category' => 'api'],
                    'icon' => 'link',
                    'key' => 'api',
                    'description' => 'API keys and configuration',
                ],
                [
                    'name' => 'Webhooks',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'integrations', 'category' => 'webhooks'],
                    'icon' => 'arrow-path',
                    'key' => 'webhooks',
                    'description' => 'Webhook endpoints and events',
                ],
            ],
        ];
    }

    /**
     * Build System section
     */
    protected static function buildSystemSection(): array
    {
        return [
            'type' => 'section',
            'title' => 'SYSTEM',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Performance',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'system', 'category' => 'performance'],
                    'icon' => 'rocket-launch',
                    'key' => 'performance',
                    'description' => 'Caching and performance tuning',
                ],
                [
                    'name' => 'Database',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'system', 'category' => 'database'],
                    'icon' => 'circle-stack',
                    'key' => 'database',
                    'description' => 'Database optimization and maintenance',
                ],
                [
                    'name' => 'Backup',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'system', 'category' => 'backup'],
                    'icon' => 'archive-box',
                    'key' => 'backup',
                    'description' => 'Backup schedules and recovery',
                ],
                [
                    'name' => 'Automation',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'system', 'category' => 'automation'],
                    'icon' => 'bolt',
                    'key' => 'automation',
                    'description' => 'Workflow automation and rules',
                ],
                [
                    'name' => 'Mobile Access',
                    'route' => 'settings.category.show',
                    'params' => ['domain' => 'system', 'category' => 'mobile'],
                    'icon' => 'device-phone-mobile',
                    'key' => 'mobile',
                    'description' => 'Mobile app configuration',
                ],
                [
                    'name' => 'Mail Queue',
                    'route' => 'mail-queue.index',
                    'icon' => 'envelope',
                    'key' => 'mail-queue',
                    'description' => 'Monitor and manage queued emails',
                ],
            ],
        ];
    }

    /**
     * Get active category/item based on current route
     */
    public static function getActiveCategory(): ?string
    {
        $route = Route::currentRouteName();

        if (!$route) {
            return null;
        }

        $sections = static::buildSections();

        foreach ($sections as $section) {
            if (!isset($section['items'])) {
                continue;
            }

            foreach ($section['items'] as $item) {
                $itemRoute = $item['route'] ?? null;

                if (!$itemRoute) {
                    continue;
                }

                // Match exact route or route with wildcards
                if (Str::is($itemRoute, $route) || Str::is($itemRoute . '.*', $route)) {
                    return $item['key'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Get all sections
     */
    public static function getAllSections(): array
    {
        return static::buildSections();
    }

    /**
     * Get a specific section by key
     */
    public static function getSection(string $sectionKey): ?array
    {
        $sections = static::buildSections();

        foreach ($sections as $section) {
            if (($section['key'] ?? null) === $sectionKey) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Get items within a section
     */
    public static function getSectionItems(string $sectionKey): ?array
    {
        $section = static::getSection($sectionKey);

        return $section['items'] ?? null;
    }

    /**
     * Get a specific item by key
     */
    public static function getItem(string $itemKey): ?array
    {
        $sections = static::buildSections();

        foreach ($sections as $section) {
            if (!isset($section['items'])) {
                continue;
            }

            foreach ($section['items'] as $item) {
                if (($item['key'] ?? null) === $itemKey) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Check if a specific item is active
     */
    public static function isItemActive(string $itemKey): bool
    {
        return static::getActiveCategory() === $itemKey;
    }

    /**
     * Register a new section dynamically
     */
    public static function registerSection(string $key, array $section): void
    {
        $section['key'] = $key;
        static::$registeredSections[$key] = $section;
        // Clear cache when registering new sections
        static::$configCache = [];
    }

    /**
     * Register an item within a section
     */
    public static function registerItem(string $sectionKey, string $itemKey, array $item): void
    {
        if (!isset(static::$registeredSections[$sectionKey])) {
            static::$registeredSections[$sectionKey] = [
                'type' => 'section',
                'key' => $sectionKey,
                'title' => ucfirst($sectionKey),
                'expandable' => true,
                'default_expanded' => false,
                'items' => [],
            ];
        }

        $item['key'] = $itemKey;
        static::$registeredSections[$sectionKey]['items'][$itemKey] = $item;
        // Clear cache
        static::$configCache = [];
    }

    /**
     * Remove a section
     */
    public static function removeSection(string $key): void
    {
        unset(static::$registeredSections[$key]);
        // Clear cache
        static::$configCache = [];
    }

    /**
     * Remove an item from a section
     */
    public static function removeItem(string $sectionKey, string $itemKey): void
    {
        if (isset(static::$registeredSections[$sectionKey]['items'][$itemKey])) {
            unset(static::$registeredSections[$sectionKey]['items'][$itemKey]);
            // Clear cache
            static::$configCache = [];
        }
    }

    /**
     * Store last visited item in session
     */
    public static function setLastVisitedItem(string $itemKey): void
    {
        if (static::getItem($itemKey) !== null) {
            Session::put('settings_last_visited_item', $itemKey);
        }
    }

    /**
     * Get last visited item from session
     */
    public static function getLastVisitedItem(): ?string
    {
        return Session::get('settings_last_visited_item');
    }

    /**
     * Clear last visited item from session
     */
    public static function clearLastVisitedItem(): void
    {
        Session::forget('settings_last_visited_item');
    }

    /**
     * Filter configuration based on user permissions
     */
    protected static function filterByPermissions(array $config): array
    {
        if (empty($config['sections'])) {
            return $config;
        }

        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $filteredSections = [];

        foreach ($config['sections'] as $section) {
            $filteredSection = static::filterSection($section, $user);

            if ($filteredSection !== null) {
                $filteredSections[] = $filteredSection;
            }
        }

        $config['sections'] = $filteredSections;

        return $config;
    }

    /**
     * Filter a single section based on user permissions
     */
    protected static function filterSection(array $section, $user): ?array
    {
        // Check section-level permissions
        if (isset($section['permission']) && !static::userHasPermission($user, $section['permission'])) {
            return null;
        }

        // If no items, return section as-is
        if (!isset($section['items'])) {
            return $section;
        }

        // Filter items within section
        $filteredItems = static::filterSectionItems($section['items'], $user);

        // Only return section if it has items
        if (empty($filteredItems)) {
            return null;
        }

        $section['items'] = $filteredItems;

        return $section;
    }

    /**
     * Filter section items based on user permissions
     */
    protected static function filterSectionItems(array $items, $user): array
    {
        $filteredItems = [];

        foreach ($items as $item) {
            if (!isset($item['permission']) || static::userHasPermission($user, $item['permission'])) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * Check if user has permission
     */
    protected static function userHasPermission($user, string $permission): bool
    {
        // Super admin always has access
        if ($user->company_id === 1 || $user->id === 1) {
            return true;
        }

        // For settings permissions, allow access if user can access settings in general
        if (str_starts_with($permission, 'settings.')) {
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission('settings.view') || $user->hasPermission($permission);
            }

            return $user->can('settings.view') || $user->can($permission);
        }

        // Use the appropriate permission checking method
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        return $user->can($permission);
    }
}
