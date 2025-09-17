<?php

// Domain Route Configuration
// This file defines how each domain's routes should be registered
// 
// IMPORTANT: Set 'apply_grouping' => false when the domain's routes.php file
// already defines its own middleware, prefix, or name to avoid double application

return [
    'Asset' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes handle their own grouping
        'priority' => 90,
        'description' => 'Asset and inventory management routes',
        'tags' => ['inventory', 'equipment', 'assets'],
        'features' => [
            'export' => true,
            'import' => true,
            'bulk_operations' => true,
            'qr_codes' => true,
        ],
    ],

    'Client' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes handle their own grouping
        'priority' => 60,
        'description' => 'Customer relationship management routes',
        'tags' => ['crm', 'customers', 'contacts'],
        'features' => [
            'session_based_context' => true,
            'bulk_operations' => true,
            'export' => true,
            'import' => true,
        ],
    ],

    'Email' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes define their own prefix and name
        'priority' => 95,
        'description' => 'Email management and inbox routes',
        'tags' => ['communication', 'inbox', 'oauth'],
        'features' => [
            'oauth_integration' => true,
            'multiple_providers' => true,
            'sync_capabilities' => true,
        ],
    ],

    'Financial' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes define their own middleware and prefix
        'priority' => 50,
        'description' => 'Financial management, billing, and accounting routes',
        'tags' => ['business', 'billing', 'finance', 'accounting'],
        'features' => [
            'invoicing' => true,
            'contracts' => true,
            'payments' => true,
            'reports' => true,
            'recurring_billing' => true,
        ],
    ],

    'Integration' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes define their own middleware and prefix
        'priority' => 80,
        'description' => 'RMM and third-party integration API routes',
        'tags' => ['api', 'external', 'rmm', 'sync'],
        'features' => [
            'rmm_providers' => ['connectwise', 'datto', 'ninja'],
            'client_mapping' => true,
            'background_sync' => true,
        ],
    ],

    'Knowledge' => [
        'enabled' => true,
        'middleware' => ['web', 'auth', 'verified'],
        'prefix' => 'knowledge',
        'name' => 'knowledge.',
        'apply_grouping' => true,  // Config applies grouping (no routes file yet)
        'priority' => 85,
        'description' => 'Knowledge base and documentation routes',
        'tags' => ['documentation', 'knowledge', 'articles'],
        'features' => [
            'search' => true,
            'categories' => true,
            'versioning' => true,
        ],
    ],

    'Lead' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes define their own middleware
        'priority' => 65,
        'description' => 'Lead management and conversion routes',
        'tags' => ['sales', 'leads', 'conversion'],
        'features' => [
            'import' => true,
            'export' => true,
            'bulk_operations' => true,
            'lead_scoring' => true,
            'conversion_tracking' => true,
        ],
    ],

    'Marketing' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes have mixed public/private routes
        'priority' => 75,
        'description' => 'Marketing campaigns and email tracking routes',
        'tags' => ['marketing', 'campaigns', 'email'],
        'features' => [
            'email_campaigns' => true,
            'sequences' => true,
            'tracking' => true,
            'analytics' => true,
            'unsubscribe' => true,
        ],
    ],

    'Project' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes handle their own grouping
        'priority' => 70,
        'description' => 'Project and task management routes',
        'tags' => ['management', 'projects', 'tasks'],
        'features' => [
            'nested_tasks' => true,
            'time_tracking' => true,
            'milestones' => true,
        ],
    ],

    'Ticket' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes define their own prefix and name
        'priority' => 70,
        'description' => 'Help desk and support ticket management routes',
        'tags' => ['support', 'helpdesk', 'tickets'],
        'features' => [
            'time_tracking' => true,
            'workflows' => true,
            'templates' => true,
            'priority_queue' => true,
            'recurring_tickets' => true,
            'smart_timer' => true,
        ],
    ],

    'Contract' => [
        'enabled' => false,  // Disabled - routes are in Financial domain
        'apply_grouping' => false,
        'priority' => 55,
        'description' => 'Contract management routes',
        'tags' => ['contracts', 'agreements', 'sla'],
        'features' => [
            'templates' => true,
            'e_signatures' => true,
            'milestones' => true,
        ],
    ],

    'Report' => [
        'enabled' => false,  // Not implemented yet
        'middleware' => ['web', 'auth', 'verified'],
        'prefix' => 'reports',
        'name' => 'reports.',
        'apply_grouping' => true,
        'priority' => 100,
        'description' => 'Analytics and reporting routes',
        'tags' => ['analytics', 'reports', 'dashboards'],
        'features' => [
            'custom_reports' => true,
            'scheduled_reports' => true,
            'export' => true,
        ],
    ],

    'Security' => [
        'enabled' => false,  // Routes are in web.php for now
        'apply_grouping' => false,
        'priority' => 10,
        'description' => 'Security and authentication routes',
        'tags' => ['auth', 'security', '2fa'],
        'features' => [
            'two_factor' => true,
            'device_trust' => true,
            'ip_blocking' => true,
        ],
    ],

    'Product' => [
        'enabled' => false,  // Not implemented yet
        'middleware' => ['web', 'auth', 'verified'],
        'prefix' => 'products',
        'name' => 'products.',
        'apply_grouping' => true,
        'priority' => 85,
        'description' => 'Product and service catalog routes',
        'tags' => ['catalog', 'products', 'services'],
        'features' => [
            'categories' => true,
            'pricing_tiers' => true,
            'bundles' => true,
        ],
    ],

    'PhysicalMail' => [
        'enabled' => true,
        'apply_grouping' => false,  // Routes file defines its own groups
        'priority' => 75,
        'description' => 'Physical mail service via PostGrid',
        'tags' => ['mail', 'postgrid', 'letters', 'postcards', 'checks'],
        'features' => [
            'letters' => true,
            'postcards' => true,
            'checks' => true,
            'bulk_mail' => true,
            'tracking' => true,
            'webhooks' => true,
        ],
    ],
];