<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Universal Semantic Colors (Flux UI)
    |--------------------------------------------------------------------------
    |
    | Define what each color universally means across the application.
    | These are Flux UI color names that work with the Flux badge component.
    |
    */
    'semantic' => [
        'success' => 'green',      // Active, completed, paid, resolved
        'error' => 'red',          // Critical errors, overdue, failed
        'info' => 'blue',          // In progress, processing, informational
        'warning' => 'amber',      // Pending, on hold, needs attention
        'special' => 'purple',     // Special states, nurturing
        'neutral' => 'zinc',       // Inactive, closed, draft
        'urgent' => 'orange',      // High priority, needs urgent action
        'caution' => 'yellow',     // Medium priority, open items
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Colors by Domain
    |--------------------------------------------------------------------------
    |
    | Domain-specific status color mappings. Keys should be snake_case.
    | Values are Flux UI color names: red, orange, amber, yellow, green, 
    | blue, purple, zinc, gray
    |
    */
    'statuses' => [
        'ticket' => [
            'new' => 'blue',
            'open' => 'yellow',
            'in_progress' => 'blue',
            'pending' => 'amber',
            'waiting' => 'amber',
            'awaiting_customer' => 'yellow',
            'on_hold' => 'amber',
            'resolved' => 'green',
            'closed' => 'zinc',
            'cancelled' => 'zinc',
        ],

        'invoice' => [
            'draft' => 'zinc',
            'sent' => 'blue',
            'viewed' => 'blue',
            'pending' => 'amber',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'gray',
        ],

        'contract' => [
            'draft' => 'zinc',
            'pending' => 'amber',
            'pending_review' => 'amber',
            'under_negotiation' => 'amber',
            'pending_signature' => 'blue',
            'pending_approval' => 'blue',
            'signed' => 'green',
            'active' => 'green',
            'suspended' => 'red',
            'terminated' => 'red',
            'expired' => 'amber',
            'cancelled' => 'zinc',
        ],

        'asset' => [
            'ready_to_deploy' => 'blue',
            'deployed' => 'green',
            'active' => 'green',
            'inactive' => 'zinc',
            'archived' => 'zinc',
            'maintenance' => 'amber',
            'broken_pending_repair' => 'amber',
            'broken_not_repairable' => 'red',
            'out_for_repair' => 'orange',
            'repair' => 'orange',
            'lost_stolen' => 'red',
            'retired' => 'red',
            'disposed' => 'red',
            'unknown' => 'zinc',
            'in_stock' => 'zinc',
        ],

        'asset_support' => [
            'supported' => 'green',
            'unsupported' => 'red',
            'pending_assignment' => 'amber',
            'excluded' => 'zinc',
        ],

        'service' => [
            'active' => 'green',
            'pending' => 'amber',
            'suspended' => 'red',
            'cancelled' => 'zinc',
            'completed' => 'blue',
        ],

        'project' => [
            'pending' => 'zinc',
            'planning' => 'zinc',
            'active' => 'green',
            'in_progress' => 'green',
            'on_hold' => 'amber',
            'completed' => 'blue',
            'cancelled' => 'red',
            'archived' => 'zinc',
        ],

        'subscription' => [
            'active' => 'green',
            'trialing' => 'blue',
            'past_due' => 'orange',
            'canceled' => 'gray',
            'cancelled' => 'gray',
            'suspended' => 'red',
            'expired' => 'gray',
        ],

        'bank_connection' => [
            'active' => 'green',
            'inactive' => 'gray',
            'error' => 'red',
            'reauth_required' => 'yellow',
        ],

        'credit_note' => [
            'draft' => 'gray',
            'pending_approval' => 'yellow',
            'approved' => 'blue',
            'applied' => 'green',
            'partially_applied' => 'orange',
            'voided' => 'red',
            'expired' => 'red',
        ],

        'warranty' => [
            'active' => 'green',
            'expired' => 'red',
            'cancelled' => 'zinc',
            'pending' => 'blue',
            'suspended' => 'amber',
        ],

        'maintenance' => [
            'scheduled' => 'blue',
            'in_progress' => 'amber',
            'completed' => 'green',
            'cancelled' => 'zinc',
            'overdue' => 'red',
        ],

        'domain' => [
            'active' => 'green',
            'pending' => 'blue',
            'suspended' => 'red',
            'transferred' => 'gray',
            'cancelled' => 'gray',
        ],

        'certificate' => [
            'active' => 'green',
            'pending' => 'blue',
            'revoked' => 'red',
            'inactive' => 'gray',
        ],

        'calendar_event' => [
            'scheduled' => 'blue',
            'confirmed' => 'green',
            'tentative' => 'yellow',
            'cancelled' => 'red',
            'completed' => 'gray',
        ],

        'mail_queue' => [
            'pending' => 'gray',
            'processing' => 'blue',
            'sent' => 'green',
            'failed' => 'red',
            'bounced' => 'orange',
            'complained' => 'purple',
            'cancelled' => 'gray',
        ],

        'lead' => [
            'new' => 'blue',
            'contacted' => 'orange',
            'qualified' => 'green',
            'unqualified' => 'gray',
            'nurturing' => 'purple',
            'converted' => 'green',
            'lost' => 'red',
        ],

        'quote' => [
            'draft' => 'zinc',
            'sent' => 'blue',
            'viewed' => 'purple',
            'accepted' => 'green',
            'declined' => 'red',
            'rejected' => 'red',
            'expired' => 'amber',
        ],

        'payment' => [
            'pending' => 'amber',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'zinc',
        ],

        'time_entry' => [
            'draft' => 'gray',
            'submitted' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'invoiced' => 'purple',
            'paid' => 'purple',
            'completed' => 'green',
            'in_progress' => 'blue',
        ],

        'recurring_ticket' => [
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
        ],

        'workflow' => [
            'active' => 'green',
            'draft' => 'yellow',
            'inactive' => 'gray',
        ],

        'mail' => [
            'pending' => 'yellow',
            'processing' => 'blue',
            'in_transit' => 'blue',
            'delivered' => 'green',
            'returned' => 'orange',
            'cancelled' => 'zinc',
            'failed' => 'red',
        ],

        'credit' => [
            'active' => 'green',
            'depleted' => 'zinc',
            'expired' => 'amber',
            'voided' => 'red',
        ],

        'campaign' => [
            'draft' => 'zinc',
            'scheduled' => 'blue',
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'gray',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Colors
    |--------------------------------------------------------------------------
    |
    | Standard priority color mappings used across all domains.
    |
    */
    'priorities' => [
        'critical' => 'red',
        'urgent' => 'red',
        'high' => 'orange',
        'medium' => 'yellow',
        'normal' => 'zinc',
        'low' => 'gray',
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditional State Colors
    |--------------------------------------------------------------------------
    |
    | Colors for states determined by conditions (expiring, overdue, etc.)
    |
    */
    'conditional' => [
        'expired' => 'red',
        'expiring_soon' => 'yellow',
        'overdue' => 'red',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hex Color Mappings (for charts and visualizations)
    |--------------------------------------------------------------------------
    |
    | Maps Flux UI color names to hex codes for use in charts, PDFs, etc.
    |
    */
    'hex_map' => [
        'red' => '#ef4444',
        'orange' => '#f97316',
        'amber' => '#f59e0b',
        'yellow' => '#eab308',
        'green' => '#10b981',
        'emerald' => '#10b981',
        'blue' => '#3b82f6',
        'indigo' => '#6366f1',
        'purple' => '#a855f7',
        'zinc' => '#71717a',
        'gray' => '#6b7280',
        'slate' => '#64748b',
        'rose' => '#f43f5e',
        'teal' => '#14b8a6',
        'lime' => '#84cc16',
    ],
];
