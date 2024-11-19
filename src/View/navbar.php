<style>
.tt-query, /* UPDATE: newer versions use tt-input instead of tt-query */
.tt-hint {
    width: 396px;
    height: 30px;
    padding: 8px 12px;
    font-size: 24px;
    line-height: 30px;
    border: 2px solid #ccc;
    border-radius: 8px;
    outline: none;
}

.tt-query { /* UPDATE: newer versions use tt-input instead of tt-query */
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
}

.tt-hint {
    color: #999;
}

.tt-menu { /* UPDATE: newer versions use tt-menu instead of tt-dropdown-menu */
    width: 95%;
    margin-top: 12px;
    padding: 8px 0;
    background-color: #fff;
    border: 1px solid #ccc;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    box-shadow: 0 5px 10px rgba(0,0,0,.2);
    overflow-y: auto;
    max-height: 75vh;
}

.tt-suggestion {
    padding: 3px 20px;
    font-size: 18px;
    line-height: 24px;
}

.tt-suggestion.tt-is-under-cursor { /* UPDATE: newer versions use .tt-suggestion.tt-cursor */
    color: #fff;
    background-color: #0097cf;

}

.tt-suggestion p {
    margin: 0;
}
</style>

<?php


if ($client_page) {
    $client_id = $client_header['client_id'];
    $client_name = $client_header['client_name'];
    $client_rate = $client_header['client_rate'] ?? 0;
    $client_currency_code = $client_header['client_currency_code'] ?? 'USD';
    $client_amount_paid = $client_header['client_payments'] ?? 0;
    $client_balance = $client_header['client_balance'] ?? 0;
    $client_recurring_monthly = $client_header['client_recurring_monthly'] ?? 0;
    $client_net_terms = $client_header['client_net_terms'] ?? 0;

    $client_open_tickets = $client_header['client_open_tickets'] ?? 0;
    $client_closed_tickets = $client_header['client_closed_tickets'] ?? 0;

    $location_address = $client_header['client_primary_location']['location_address'] ?? '';
    $location_city = $client_header['client_primary_location']['location_city'] ?? '';
    $location_state = $client_header['client_primary_location']['location_state'] ?? '';
    $location_zip = $client_header['client_primary_location']['location_zip'] ?? '';
    $location_country = $client_header['client_primary_location']['location_country'] ?? '';


    $contact_name = $client_header['client_primary_contact']['contact_name'] ?? '';
    $contact_email = $client_header['client_primary_contact']['contact_email'] ?? '';
    $contact_phone = $client_header['client_primary_contact']['contact_phone'] ?? '';
    $contact_mobile = $client_header['client_primary_contact']['contact_mobile'] ?? '';



    $clientMenuItems = [
        ['title' => 'Client List',
            'icon' => 'bx bx-list-ul',
            'link' => '/public/?page=clients',
            'roles' => ['admin', 'accounting', 'sales', 'tech']
        ],
        ['title' => 'Client Overview',
            'icon' => 'bx bx-info-circle',
            'link' => '/public/?page=client&action=show&client_id=' . $client_id,
            'roles' => ['admin', 'accounting', 'sales', 'tech']
        ],
        [ 'title' => 'Support',
            'icon' => 'bx bx-support',
            'children' => [
                ['title' => 'Tickets', 'link' => '/public/?page=tickets&client_id=' . $client_id, 'icon' => 'bx bx-first-aid', 'roles' => ['admin', 'tech']],
                ['title' => 'Contacts', 'link' => '/public/?page=contact&client_id=' . $client_id, 'icon' => 'bx bx-user', 'roles' => ['admin', 'tech']],
                ['title' => 'Locations', 'link' => '/public/?page=location&client_id=' . $client_id, 'icon' => 'bx bx-map', 'roles' => ['admin', 'tech']],
                ['title' => 'Trips', 'link' => '/public/?page=trips&client_id=' . $client_id, 'icon' => 'bx bx-car', 'roles' => ['admin', 'tech']],
                ['title' => 'Projects', 'link' => '/public/?page=projects&client_id=' . $client_id, 'icon' => 'bx bx-task', 'roles' => ['admin', 'tech']],
            ],
            'roles' => ['admin', 'tech']
        ],
        ['title' => 'Documentation',
            'icon' => 'bx bx-book',
            'children' => [
                ['title' => 'Assets', 'link' => '/public/?page=documentation&documentation_type=asset&client_id=' . $client_id, 'icon' => 'bx bx-box', 'roles' => ['admin', 'tech']],
                ['title' => 'Licenses', 'link' => '/public/?page=documentation&documentation_type=license&client_id=' . $client_id, 'icon' => 'bx bx-key', 'roles' => ['admin', 'tech']],
                ['title' => 'Logins', 'link' => '/public/?page=documentation&documentation_type=login&client_id=' . $client_id, 'icon' => 'bx bx-log-in', 'roles' => ['admin', 'tech']],    
                ['title' => 'Networks', 'link' => '/public/?page=documentation&documentation_type=network&client_id=' . $client_id, 'icon' => 'bx bx-network-chart', 'roles' => ['admin', 'tech']],
                ['title' => 'Services', 'link' => '/public/?page=documentation&documentation_type=service&client_id=' . $client_id, 'icon' => 'bx bx-server', 'roles' => ['admin', 'tech']],
                ['title' => 'Vendors', 'link' => '/public/?page=documentation&documentation_type=vendor&client_id=' . $client_id, 'icon' => 'bx bx-user-voice', 'roles' => ['admin', 'tech']],
                ['title' => 'Files', 'link' => '/public/?page=documentation&documentation_type=file&client_id=' . $client_id, 'icon' => 'bx bx-paperclip', 'roles' => ['admin', 'tech']],
                ['title' => 'Documents', 'link' => '/public/?page=documentation&documentation_type=document&client_id=' . $client_id, 'icon' => 'bx bx-file', 'roles' => ['admin', 'tech']],
            ],
            'roles' => ['admin', 'tech']
        ],
        ['title' => 'Finance',
            'icon' => 'bx bx-dollar',
            'children' => [
                ['title' => 'Invoices', 'link' => '/public/?page=invoices&client_id=' . $client_id, 'icon' => 'bx bx-receipt', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Subscriptions', 'link' => '/public/?page=subscriptions&client_id=' . $client_id, 'icon' => 'bx bx-receipt', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Quotes', 'link' => '/public/?page=quotes&client_id=' . $client_id, 'icon' => 'bx bx-message-square-detail', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Payments', 'link' => '/public/?page=payments&client_id=' . $client_id, 'icon' => 'bx bx-credit-card', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Statements', 'link' => '/public/?page=statement&client_id=' . $client_id, 'icon' => 'bx bx-file', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Credits', 'link' => '/public/?page=credits&client_id=' . $client_id, 'icon' => 'bx bx-money', 'roles' => ['admin', 'accounting', 'sales']]
            ],
            'roles' => ['admin', 'accounting', 'sales']
        ],
        ['title' => 'Other',
            'icon' => 'bx bx-plus',
            'children' => [
                ['title' => 'Contracts', 'link' => '/public/?page=contracts&client_id=' . $client_id, 'icon' => 'bx bx-file', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Bulk Email', 'link' => '/public/?page=bulk_email&client_id=' . $client_id, 'icon' => 'bx bx-mail-send', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Shared Links', 'link' => '/public/?page=shared_links&client_id=' . $client_id, 'icon' => 'bx bx-link', 'roles' => ['admin', 'accounting', 'sales']],
                ['title' => 'Audit Logs', 'link' => '/public/?page=audit_logs&client_id=' . $client_id, 'icon' => 'bx bx-history', 'roles' => ['admin']]
            ],
            'roles' => ['admin']
        ]
    ];
} else {
    $menuItems = [
        ['title' => 'Clients',
            'icon' => 'bx bx-briefcase',
            'link' => '/public/?page=clients',
            'roles' => ['admin', 'accounting', 'sales', 'tech']
        ],
        ['title' => 'Support',
            'icon' => 'bx bx-support',
            'children' => [
                ['title' => 'Tickets', 'link' => '/public/?page=tickets', 'icon' => 'bx bx-first-aid', 'roles' => ['admin', 'tech', 'account_management']],
                ['title' => 'Trips', 'link' => '/public/?page=trips', 'icon' => 'bx bx-car', 'roles' => ['admin', 'tech', 'account_management']],
                ['title' => 'Projects', 'link' => '/public/?page=projects', 'icon' => 'bx bx-task', 'roles' => ['admin', 'tech', 'account_management']],
                ['title' => 'Calendar', 'link' => '/public/?page=calendar', 'icon' => 'bx bx-calendar', 'roles' => ['admin', 'tech', 'account_management']],
                ['title' => 'Inventory', 'link' => '/public/?page=inventory', 'icon' => 'bx bx-store', 'roles' => ['admin', 'tech']]
            ],
            'roles' => ['admin', 'tech', 'account_management']
        ],
        ['title' => 'Financial',
            'icon' => 'bx bx-dollar',
            'children' => [
                ['title' => 'Income', 
                    'icon' => 'bx bx-money', 
                    'children' => [
                        ['title' => 'Quotes', 'link' => '/public/?page=quotes', 'icon' => 'bx bx-message-square-detail', 'roles' => ['admin', 'sales']  ],
                        ['title' => 'Invoices', 'link' => '/public/?page=invoices', 'icon' => 'bx bx-receipt', 'roles' => ['admin', 'sales']],
                        ['title' => 'Payments',
                            'icon' => 'bx bx-credit-card',
                            'children' => [
                                ['title' => 'View Payments', 'link' => '/public/?page=payments', 'icon' => 'bx bx-credit-card', 'roles' => ['admin', 'accounting']],
                                ['title' => 'Make Payment', 'link' => '/public/?page=make_payment', 'icon' => 'bx bx-credit-card', 'roles' => ['admin', 'accounting']],
                                ['title' => 'Unreconciled Income', 'link' => '/public/?page=unreconciled&type=income', 'icon' => 'bx bx-receipt', 'roles' => ['admin', 'accounting']]
                            ],
                            'roles' => ['admin', 'accounting']
                        ],
                        ['title' => 'Subscriptions', 'link' => '/public/?page=subscriptions', 'icon' => 'bx bx-credit-card', 'roles' => ['admin', 'sales']],
                        ['title' => 'Products', 'link' => '/public/?page=products', 'icon' => 'bx bx-box', 'roles' => ['admin', 'sales']],
                    ],
                    'roles' => ['admin', 'accounting']
                ],
                ['title' => 'Expenses',
                    'icon' => 'bx bx-money-withdraw', 
                    'children' => [
                        ['title' => 'Reconciled Expenses', 'link' => '/public/?page=expenses', 'icon' => 'bx bx-money-withdraw', 'roles' => ['admin', 'accounting']],
                        ['title' => 'Unreconciled Expenses', 'link' => '/public/?page=unreconciled&type=expense', 'icon' => 'bx bx-receipt', 'roles' => ['admin', 'accounting']],
                    ],
                    'roles' => ['admin', 'accounting']
                ],
                ['title' => 'Accounting',
                    'icon' => 'bx bx-calculator',
                    'children' => [
                        ['title' => 'Transfers', 'link' => '/public/?page=transfers', 'icon' => 'bx bx-transfer', 'roles' => ['admin', 'accounting']],
                        ['title' => 'Accounts', 'link' => '/public/?page=accounts', 'icon' => 'bx bx-wallet', 'roles' => ['admin', 'accounting']],
                        ['title' => 'Credits', 'link' => '/public/?page=credits', 'icon' => 'bx bx-money', 'roles' => ['admin', 'accounting']],
                        ['title' => 'Contracts', 'link' => '/public/?page=contracts', 'icon' => 'bx bx-file', 'roles' => ['admin', 'sales']]

                    ],
                    'roles' => ['admin', 'accounting']
                ]
            ],
            'roles' => ['admin', 'accounting']
        ],
        ['title' => 'Account Management',
            'icon' => 'bx bx-folder',
            'children' => [
                ['title' => 'Client Satisfaction',
                    'icon' => 'bx bx-star',
                    'children' => [
                        ['title' => 'Unrated Tickets', 'link' => '/public/?page=unrated_tickets', 'icon' => 'bx bx-star', 'roles' => ['account_management', 'admin']],
                        ['title' => 'Aging Tickets', 'link' => '/public/?page=aging_tickets', 'icon' => 'bx bx-time', 'roles' => ['account_management', 'admin']],
                        ['title' => 'Closed Tickets', 'link' => '/public/?page=closed_tickets', 'icon' => 'bx bx-lock-alt', 'roles' => ['account_management', 'admin']],
                        ['title' => 'Clients without Login', 'link' => '/public/?page=clients_without_login', 'icon' => 'bx bx-shield-plus', 'roles' => ['account_management', 'admin']]
                    ],
                    'roles' => ['account_management', 'admin']
                ],
                ['title' => 'Collections',
                    'icon' => 'bx bx-collection',
                    'children' => [
                        ['title' => 'Aging Invoices', 'link' => '/public/?page=aging_invoices', 'icon' => 'bx bxs-time', 'roles' => ['account_management', 'admin']],
                        ['title' => 'Collections', 'link' => '/public/?page=report&report=collections', 'icon' => 'bx bx-box', 'roles' => ['account_management', 'admin']]
                    ],
                    'roles' => ['account_management', 'admin']
                ],
                ['title' => 'Sales',
                    'icon' => 'bx bx-cart',
                    'children' => [
                        ['title' => 'Clients Without Subscription', 'link' => '/public/?page=clients_without_subscription', 'icon' => 'bx bx-box', 'roles' => ['account_management', 'admin']],
                        ['title' => 'Sales Pipeline', 'link' => '/public/?page=sales_pipeline', 'icon' => 'bx bx-box', 'roles' => ['account_management', 'admin']]
                    ],
                    'roles' => ['account_management', 'admin']
                ]
            ],
            'roles' => ['account_management', 'admin']
        ],
        ['title' => 'Human Resources',
            'icon' => 'bx bx-user-voice',
            'children' => [
                ['title' => 'Payroll', 'link' => '/public/?page=hr&hr_page=payroll', 'icon' => 'bx bx-money', 'roles' => ['admin']]
            ],
            'roles' => ['admin']
        ],
        ['title' => 'Reports',
            'icon' => 'bx bx-bar-chart',
            'children' => [
                [
                    'title' => 'Financial',
                    'icon' => 'bx bx-dollar',
                    'children' => [
                        ['title' => 'Income', 'link' => '/public/?page=report&report=income_summary', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Income By Client', 'link' => '/public/?page=report&report=income_by_client', 'icon' => 'bx bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Recurring Income by Client', 'link' => '/public/?page=report&report=recurring_by_client', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Expenses', 'link' => '/public/?page=report&report=expense_summary', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Expenses By Vendor', 'link' => '/public/?page=report&report=expenses_by_vendor', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Budgets', 'link' => '/public/?page=report&report=budget', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Profit & Loss', 'link' => '/public/?page=report&report=profit_loss', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Balance Sheet', 'link' => '/public/?page=report&report=balance_sheet', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Cash Flow', 'link' => '/public/?page=report&report=cash_flow', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Tax Summary', 'link' => '/public/?page=report&report=tax_summary', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                        ['title' => 'Collections', 'link' => '/public/?page=report&report=collections', 'icon' => 'bx bx-box', 'roles' => ['admin']]
                    ]
                ],
                ['title' => 'Technical', 'icon' => 'bx bx-cog', 'children' => [
                    ['title' => 'Unbilled Tickets', 'link' => '/public/?page=report&report=tickets_unbilled', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                    ['title' => 'Tickets', 'link' => '/public/?page=report&report=tickets', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                    ['title' => 'Tickets by Client', 'link' => '/public/?page=report&report=tickets_by_client', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                    ['title' => 'Password Rotation', 'link' => '/public/?page=report&report=password_rotation', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                    ['title' => 'All Assets', 'link' => '/public/?page=report&report=all_assets', 'icon' => 'bx bx-box', 'roles' => ['admin']],
                ]]
            ],
            'roles' => ['admin']
        ],
        ['title' => 'Administration',
            'icon' => 'bx bx-wrench',
            'children' => [
                ['title' => 'Users', 'link' => '/public/?page=admin&admin_page=users', 'icon' => 'bx bx-user', 'roles' => ['admin']],
                ['title' => 'API Keys', 'link' => '/public/?page=admin&admin_page=api_keys', 'icon' => 'bx bx-key', 'roles' => ['admin']],
                ['title' => 'Tags and Categories', 'icon' => 'bx bx-tag', 'children' => [
                    ['title' => 'Tags', 'link' => '/public/?page=admin&admin_page=tags', 'icon' => 'bx bx-purchase-tag', 'roles' => ['admin']],
                    ['title' => 'Categories', 'link' => '/public/?page=admin&admin_page=categories', 'icon' => 'bx bx-category', 'roles' => ['admin']]
                ]],
                ['title' => 'Financial', 'icon' => 'bx bx-dollar', 'children' => [
                    ['title' => 'Taxes', 'link' => '/public/?page=admin&admin_page=taxes', 'icon' => 'bx bx-bank', 'roles' => ['admin']],
                    ['title' => 'Account Types', 'link' => '/public/?page=admin&admin_page=account_types', 'icon' => 'bx bx-university', 'roles' => ['admin']]
                ]],
                ['title' => 'Templates', 'icon' => 'bx bx-file', 'children' => [
                    ['title' => 'Vendor Templates', 'link' => '/public/?page=admin&admin_page=vendor_templates', 'icon' => 'bx bx-file', 'roles' => ['admin']],
                    ['title' => 'License Templates', 'link' => '/public/?page=admin&admin_page=license_templates', 'icon' => 'bx bx-file', 'roles' => ['admin']],
                    ['title' => 'Document Templates', 'link' => '/public/?page=admin&admin_page=document_templates', 'icon' => 'bx bx-file', 'roles' => ['admin']],
                ]],
                ['title' => 'Maintenance', 'icon' => 'bx bx-cog', 'children' => [
                    ['title' => 'Mail Queue', 'link' => '/public/?page=admin&admin_page=mail_queue', 'icon' => 'bx bx-envelope', 'roles' => ['admin']],
                    ['title' => 'Audit Logs', 'link' => '/public/?page=admin&admin_page=audit_logs', 'icon' => 'bx bx-history', 'roles' => ['admin']],
                    ['title' => 'Backup', 'link' => '/public/?page=admin&admin_page=backup', 'icon' => 'bx bx-cloud-download', 'roles' => ['admin']],
                    ['title' => 'Debug', 'link' => '/public/?page=admin&admin_page=debug', 'icon' => 'bx bx-bug', 'roles' => ['admin']]
                ]]
            ],
            'roles' => ['admin']
        ],
        ['title' => 'Settings',
            'icon' => 'bx bx-cog',
            'children' => [
                ['title' => 'Modules', 'icon' => 'bx bx-checkbox', 'children' => [
                    ['title' => 'Enabled Modules', 'link' => '/public/?page=settings&settings_page=modules', 'icon' => 'bx bx-checkbox-square', 'roles' => ['admin']],
                    ['title' => 'Invoice Module', 'link' => '/public/?page=settings&settings_page=invoice', 'icon' => 'bx bx-barcode', 'roles' => ['admin']],
                    ['title' => 'Ticket Module', 'link' => '/public/?page=settings&settings_page=ticket', 'icon' => 'bx bx-first-aid', 'roles' => ['admin']],
                    ['title' => 'Task Module', 'link' => '/public/?page=settings&settings_page=task', 'icon' => 'bx bx-task', 'roles' => ['admin']],
                    ['title' => 'Calendar Module', 'link' => '/public/?page=settings&settings_page=calendar', 'icon' => 'bx bx-calendar', 'roles' => ['admin']],
                    ['title' => 'Quote Module', 'link' => '/public/?page=settings&settings_page=quote', 'icon' => 'bx bx-message-square-detail', 'roles' => ['admin']],
                    ['title' => 'Expense Module', 'link' => '/public/?page=settings&settings_page=expense', 'icon' => 'bx bx-money', 'roles' => ['admin']],
                    ['title' => 'Transfer Module', 'link' => '/public/?page=settings&settings_page=transfer', 'icon' => 'bx bx-transfer', 'roles' => ['admin']],
                    ['title' => 'Online Payments Module', 'link' => '/public/?page=settings&settings_page=online_payments', 'icon' => 'bx bx-credit-card', 'roles' => ['admin']],
                    ['title' => 'Integrations', 'link' => '/public/?page=settings&settings_page=integrations', 'icon' => 'bx bx-plug', 'roles' => ['admin']],
                ]],
                ['title' => 'General', 'icon' => 'bx bx-cog', 'children' => [
                    ['title' => 'Company', 'link' => '/public/?page=settings&settings_page=company', 'icon' => 'bx bx-building', 'roles' => ['admin']],
                    ['title' => 'Localization', 'link' => '/public/?page=settings&settings_page=localization', 'icon' => 'bx bx-globe', 'roles' => ['admin']],
                    ['title' => 'Security', 'link' => '/public/?page=settings&settings_page=security', 'icon' => 'bx bx-lock', 'roles' => ['admin']],
                    ['title' => 'Email', 'link' => '/public/?page=settings&settings_page=email', 'icon' => 'bx bx-envelope', 'roles' => ['admin']],
                    ['title' => 'Notifications', 'link' => '/public/?page=settings&settings_page=notifications', 'icon' => 'bx bx-bell', 'roles' => ['admin']],
                    ['title' => 'Custom Fields', 'link' => '/public/?page=settings&settings_page=custom_fields', 'icon' => 'bx bx-list-ul', 'roles' => ['admin']],
                    ['title' => 'Defaults', 'link' => '/public/?page=settings&settings_page=defaults', 'icon' => 'bx bx-cog', 'roles' => ['admin']],
                    ['title' => 'Integrations', 'link' => '/public/?page=settings&settings_page=integrations', 'icon' => 'bx bx-plug', 'roles' => ['admin']],
                    ['title' => 'Webhooks', 'link' => '/public/?page=settings&settings_page=webhooks', 'icon' => 'bx bx-link', 'roles' => ['admin']],
                    ['title' => 'AI', 'link' => '/public/?page=settings&settings_page=ai', 'icon' => 'bx bx-brain', 'roles' => ['admin']],
                ]]
            ],
            'roles' => ['admin']
        ],
        ['title' => 'Help',
            'icon' => 'bx bx-help-circle',
            'children' => [
                ['title' => 'Help Course', 'link' => '?page=learn', 'icon' => 'bx bx-book', 'roles' => ['tech', 'admin', 'sales', 'accounting']],
                ['title' => 'Knowledge Base', 'link' => '?page=help&help_page=knowledge_base', 'icon' => 'bx bx-book', 'roles' => ['tech', 'admin', 'sales', 'accounting']],
                ['title' => 'Standard Operating Procedures', 'link' => '?page=sop', 'icon' => 'bx bx-book', 'roles' => ['tech', 'admin', 'sales', 'accounting']]
            ],
            'roles' => ['tech', 'admin', 'sales', 'accounting']
        ]
    ];
}

// Render Nav menu
function renderMenu($menuItems, $userRole = ['tech'], $isSubmenu = false)
{
    $ulClass = $isSubmenu ? 'menu-sub' : 'menu-inner';
    $html = "<ul class=\"$ulClass\">";

    foreach ($menuItems as $item) {
        $hasAccess = false;
        if (isset($item['roles'])) {
            foreach ($item['roles'] as $role) {
                if (in_array($role, $userRole)) {
                    $hasAccess = true;
                    break;
                }
            }
        } else {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            continue;
        }

        $hasChildren = isset($item['children']);
        $link = $hasChildren ? 'javascript:void(0)' : $item['link'];

        $html .= '<li class="menu-item">';
        $html .= "<a href=\"$link\" class=\"menu-link" . ($hasChildren ? ' menu-toggle' : '') . "\">";
        $html .= '<i class="menu-icon tf-icons ' . $item['icon'] . '"></i>';
        $html .= '<div data-i18n="' . $item['title'] . '">' . $item['title'] . '</div>';
        $html .= '</a>';

        if ($hasChildren) {
            $html .= renderMenu($item['children'], $userRole, true);
        }
        $html .= '</li>';
    }

    $html .= '</ul>';

    echo $html;
    return $html;
}

// Render user shortcuts
function renderUserShortcuts($shortcutsData, $shortcutsMap)
{
    $html = '<div class="row row-bordered overflow-visible g-0">';
    $colCount = 0;

    foreach ($shortcutsData as $row) {
        $key = $row['shortcut_key'];
        if (array_key_exists($key, $shortcutsMap)) {
            $shortcut = $shortcutsMap[$key];
            $html .= '<div class="dropdown-shortcuts-item col">';
            $html .= '<span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">';
            $html .= '<i class="' . $shortcut['icon'] . ' fs-4"></i>';
            $html .= '</span>';
            $html .= '<a href="' . $shortcut['link'] . '" class="stretched-link">' . $shortcut['name'] . '</a>';
            $html .= '<small class="text-muted mb-0">' . $shortcut['description'] . '</small>';
            $html .= '</div>';

            $colCount++;
            if ($colCount % 2 == 0) {
                $html .= '</div><div class="row row-bordered overflow-visible g-0">';
            }
        }
    }
    $html .= '</div>';

    echo $html;
}

require_once "/var/www/portal.twe.tech/includes/shortcuts.php";

//TODO: Implement notifications
$num_notifications = 0;

if ($client_page) {
    $nav_title = 'TWE: ' . initials($client_name);
} else {
    $nav_title = 'TWE Technologies';
}
$nav_title_link = '/public/';

?>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme d-print-none">
            <div class="app-brand demo d-print-none">
                <a href="<?= $nav_title_link ?>" class="app-brand-link gap-2">
                    <span class="app-brand-text demo menu-text fw-bold"><?= $nav_title ?></span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
                </a>
            </div>

            <div class="menu-inner-shadow d-print-none"></div>

            <div class="menu-inner py-1 d-print-none">
                <?php if ($client_page) {
                    renderMenu($clientMenuItems, [$_SESSION['user_role']]);
                } else {
                    renderMenu($menuItems, [$_SESSION['user_role']]);
                } ?>
            </div>

        </aside>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Navbar -->
            <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                id="layout-navbar">

                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

                        <!-- Search -->
                        <div class="navbar-nav align-items-center ">
                            <div class="nav-item navbar-search-wrapper mb-0">
                                <a
                                    class="nav-item nav-link search-toggler px-0"
                                    href="javascript:void(0);">
                                    <i class="bx bx-search bx-md"></i>
                                    <span
                                        class="d-none d-md-inline-block text-muted fw-normal ms-4">Search (Ctrl+/)</span>
                                </a>
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- Quick links  -->
                            <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="bx bx-grid-alt bx-sm"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end py-0">
                                    <div class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center py-3">
                                            <h5 class="text-body mb-0 me-auto">Shortcuts</h5>
                                        </div>
                                    </div>
                                    <div class="dropdown-shortcuts-list scrollable-container">
                                        <?php
                                            !isset($shortcutsData) ? $shortcutsData = [] : $shortcutsData;
                                            renderUserShortcuts($shortcutsData, $shortcutsMap);
                                            ?>
                                    </div>
                                </div>
                            </li>
                            <!-- Quick links -->

                            <!-- Style Switcher -->
                            <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="bx bx-sm"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                                            <span class="align-middle"><i class="bx bx-sun me-2"></i>Light</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                                            <span class="align-middle"><i class="bx bx-moon me-2"></i>Dark</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                                            <span class="align-middle"><i class="bx bx-desktop me-2"></i>System</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- / Style Switcher-->

                            <!-- Notification -->
                            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="bx bx-bell bx-sm"></i>
                                    <?= $num_notifications > 0 ? '<span class="badge bg-danger rounded-pill badge-notifications">' . $num_notifications . '</span>' : $num_notifications ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end py-0">
                                    <li class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center py-3">
                                            <h5 class="text-body mb-0 me-auto">Notification</h5>
                                            <a href="javascript:void(0)" class="dropdown-notifications-all text-body"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read"><i
                                                    class="bx fs-4 bx-envelope-open"></i></a>
                                        </div>
                                    </li>
                                    <li class="dropdown-notifications-list scrollable-container">
                                        <ul class="list-group list-group-flush">
                                            <?php
                                                //TODO: Implement notifications
                                                ?>
                                        </ul>
                                    </li>
                                    <li class="dropdown-menu-footer border-top p-3">
                                        <a class="btn btn-primary text-uppercase w-100" href="/old_pages/notifications.php">view all
                                            notifications</a>
                                    </li>
                                </ul>
                            </li>
                            <!-- Open Tickets -->
                            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
                                <a class="nav-link loadModalContentBtn" href="#" data-toggle="modal" data-target="#dynamicModal"
                                    id="openTicketsModal" data-modal-file="top_nav_tickets_modal.php">
                                    <i class="bx bx-first-aid bx-sm"></i>
                                    <span class="badge rounded-pill badge-notifications" id="runningTicketsCount">0</span>
                                </a>
                            </li>
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="<?= "/uploads/users/$user_id/$user_avatar"; ?>" alt="<?= nullable_htmlentities($user_name); ?>"
                                            class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="pages-account-settings-account.html">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="<?= "/uploads/users/$user_id/$user_avatar"; ?>" alt
                                                            class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span
                                                        class="fw-medium d-block"><?= stripslashes(nullable_htmlentities($user_name)); ?></span>
                                                    <small
                                                        class="text-muted"><?= nullable_htmlentities(ucfirst($user_role)); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/old_pages/user/user_details.php">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="employee_timeclock_modal.php?user_id=<?= $user_id ?>">
                                            <i class="bx bx-time"></i>
                                            <span class="align-middle">Time Clock</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/old_pages/user/user_preferences.php">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/post.php?logout" target="_blank">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>

                    <!-- Search Small Screens -->
                    <div class="navbar-search-wrapper search-input-wrapper container-xxl d-none">
                        <input type="text" class="form-control search-input border-0" placeholder="Search..."
                            aria-label="Search..." />
                        <i class="bx bx-x bx-sm search-toggler cursor-pointer"></i>
                    </div>
                </div>
            </nav> <!-- / Navbar -->


            <!-- Content wrapper -->
            <div class="content-wrapper">

                <!-- Content -->
                <div class="container-xxl flex-grow-1 container-p-y">
                <?php
                //Alert Feedback
                if (!empty($_SESSION['alert_message'])) {
                    if (!isset($_SESSION['alert_type'])) {
                        $_SESSION['alert_type'] = "info";
                    }
                    ?>
                    <div class="alert alert-<?= $_SESSION['alert_type']; ?>" id="alert">
                        <?= $_SESSION['alert_message']; ?>
                        <button class='close' data-bs-dismiss='alert'>&times;</button>
                    </div>
                    <?php
                    unset($_SESSION['alert_type']);
                    unset($_SESSION['alert_message']);
                }
                ?>