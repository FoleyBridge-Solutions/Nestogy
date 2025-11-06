<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Templates
    |--------------------------------------------------------------------------
    |
    | Default roles created for each new company (tenant).
    | When a company is created, these roles are automatically provisioned
    | with the specified permissions.
    |
    | Each company gets their own isolated copy of these roles, scoped by
    | company_id in Bouncer.
    |
    */

    'admin' => [
        'title' => 'Administrator',
        'description' => 'Full system access for company administrators. Can manage all aspects of the company account including users, settings, and billing.',
        'permissions' => [
            // Full access to core features
            'clients.*',
            'assets.*',
            'tickets.*',
            'contracts.*',
            'projects.*',
            'financial.*',
            'users.*',
            'settings.*',
            'reports.*',
            'knowledge.*',
            
            // Specific permissions
            'leads.*',
            'marketing.*',
            'view-campaigns',
            'create-campaigns',
            'edit-campaigns',
            'delete-campaigns',
            'manage-campaigns',
            'control-campaigns',
            'enroll-campaigns',
            'view-campaign-analytics',
            'test-campaigns',
        ],
    ],

    'tech' => [
        'title' => 'Technician',
        'description' => 'Technical support role with access to tickets, assets, and client information.',
        'permissions' => [
            // Client access (view only)
            'clients.view',
            'clients.contacts.view',
            'clients.locations.view',
            
            // Full asset management
            'assets.*',
            
            // Full ticket management
            'tickets.*',
            
            // Project management (limited)
            'projects.view',
            'projects.manage',
            
            // Knowledge base
            'knowledge.view',
            'knowledge.create',
            'knowledge.edit',
            
            // Reporting (limited)
            'reports.view',
            'reports.tickets',
            'reports.assets',
        ],
    ],

    'accountant' => [
        'title' => 'Accountant',
        'description' => 'Financial management role with access to invoices, payments, and financial reports.',
        'permissions' => [
            // Client access (view only)
            'clients.view',
            
            // Full financial access
            'financial.*',
            
            // Contract access (view + financial)
            'contracts.view',
            'contracts.financials',
            
            // Financial reporting
            'reports.view',
            'reports.financial',
            'reports.export',
        ],
    ],

    'sales' => [
        'title' => 'Sales Representative',
        'description' => 'Sales role with access to leads, quotes, and client management.',
        'permissions' => [
            // Client management
            'clients.view',
            'clients.create',
            'clients.edit',
            
            // Full lead management
            'leads.*',
            
            // Contract access
            'contracts.view',
            'contracts.create',
            
            // Project visibility
            'projects.view',
            
            // Quotes and financial (limited)
            'financial.quotes.view',
            'financial.quotes.create',
            'financial.quotes.edit',
            'financial.quotes.send',
            
            // Reporting
            'reports.view',
            'reports.clients',
        ],
    ],

    'marketing' => [
        'title' => 'Marketing Specialist',
        'description' => 'Marketing role with access to campaigns, leads, and marketing analytics.',
        'permissions' => [
            // Client and lead access (limited)
            'clients.view',
            'leads.view',
            'leads.create',
            'leads.edit',
            
            // Full marketing access
            'marketing.*',
            'view-campaigns',
            'create-campaigns',
            'edit-campaigns',
            'control-campaigns',
            'enroll-campaigns',
            'view-campaign-analytics',
            'test-campaigns',
            
            // Knowledge base
            'knowledge.view',
            'knowledge.create',
            'knowledge.edit',
            
            // Reporting
            'reports.view',
        ],
    ],

    'client' => [
        'title' => 'Client User',
        'description' => 'Limited access role for client portal users. Can view tickets and submit requests.',
        'permissions' => [
            // Ticket access (limited)
            'tickets.view',
            'tickets.create',
            
            // Asset visibility (own assets only)
            'assets.view',
            
            // Knowledge base access
            'knowledge.view',
        ],
    ],
];
