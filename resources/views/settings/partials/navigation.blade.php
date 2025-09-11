@php
    $isMobile = $mobile ?? false;
    
    // Navigation configuration
    $navigationSections = [
        'core' => [
            'title' => 'Core Settings',
            'routes' => ['settings.general', 'settings.security', 'settings.email', 'settings.user-management', 'settings.permissions.*', 'settings.roles.*', 'subsidiaries.index'],
            'items' => [
                ['route' => 'settings.general', 'title' => 'General', 'active_routes' => ['settings.general', 'settings.index']],
                ['route' => 'settings.security', 'title' => 'Security & Access', 'active_routes' => ['settings.security']],
                ['route' => 'settings.email', 'title' => 'Email & Communication', 'active_routes' => ['settings.email']],
                ['route' => 'settings.user-management', 'title' => 'User Management', 'active_routes' => ['settings.user-management']],
                ['route' => 'settings.permissions.index', 'title' => 'Permissions Management', 'active_routes' => ['settings.permissions.*']],
                ['route' => 'settings.roles.index', 'title' => 'Roles & Abilities', 'active_routes' => ['settings.roles.*']],
                ['route' => 'subsidiaries.index', 'title' => 'Subsidiary Management', 'active_routes' => ['subsidiaries.index', 'subsidiaries.create', 'subsidiaries.edit', 'subsidiaries.show']]
            ]
        ],
        'financial' => [
            'title' => 'Financial Management',
            'routes' => ['settings.billing-financial', 'settings.accounting', 'settings.payment-gateways'],
            'items' => [
                ['route' => 'settings.billing-financial', 'title' => 'Billing & Financial', 'active_routes' => ['settings.billing-financial']],
                ['route' => 'settings.accounting', 'title' => 'Accounting Integration', 'active_routes' => ['settings.accounting']],
                ['route' => 'settings.payment-gateways', 'title' => 'Payment Gateways', 'active_routes' => ['settings.payment-gateways']]
            ]
        ],
        'service' => [
            'title' => 'Service Delivery',
            'routes' => ['settings.ticketing-service-desk', 'settings.project-management', 'settings.asset-inventory', 'settings.client-portal', 'settings.contract-clauses', 'settings.contract-templates.*', 'settings.template-clauses'],
            'items' => [
                ['route' => 'settings.ticketing-service-desk', 'title' => 'Ticketing & Service Desk', 'active_routes' => ['settings.ticketing-service-desk']],
                ['route' => 'settings.project-management', 'title' => 'Project Management', 'active_routes' => ['settings.project-management']],
                ['route' => 'settings.asset-inventory', 'title' => 'Asset & Inventory', 'active_routes' => ['settings.asset-inventory']],
                ['route' => 'settings.client-portal', 'title' => 'Client Portal', 'active_routes' => ['settings.client-portal']],
                ['route' => 'settings.contract-clauses', 'title' => 'Contract Clauses', 'active_routes' => ['settings.contract-clauses']],
                ['route' => 'settings.contract-templates.index', 'title' => 'Contract Templates', 'active_routes' => ['settings.contract-templates.*', 'settings.template-clauses']]
            ]
        ],
        'tech' => [
            'title' => 'Technology Integration',
            'routes' => ['settings.rmm-monitoring', 'settings.integrations', 'settings.automation-workflows', 'settings.api-webhooks'],
            'items' => [
                ['route' => 'settings.rmm-monitoring', 'title' => 'RMM & Monitoring', 'active_routes' => ['settings.rmm-monitoring']],
                ['route' => 'settings.integrations', 'title' => 'Third-Party Integrations', 'active_routes' => ['settings.integrations']],
                ['route' => 'settings.automation-workflows', 'title' => 'Automation & Workflows', 'active_routes' => ['settings.automation-workflows']],
                ['route' => 'settings.api-webhooks', 'title' => 'API & Webhooks', 'active_routes' => ['settings.api-webhooks']]
            ]
        ],
        'compliance' => [
            'title' => 'Compliance & Security',
            'routes' => ['settings.compliance-audit', 'settings.backup-recovery', 'settings.data-management'],
            'items' => [
                ['route' => 'settings.compliance-audit', 'title' => 'Compliance & Audit', 'active_routes' => ['settings.compliance-audit']],
                ['route' => 'settings.backup-recovery', 'title' => 'Backup & Recovery', 'active_routes' => ['settings.backup-recovery']],
                ['route' => 'settings.data-management', 'title' => 'Data Management', 'active_routes' => ['settings.data-management']]
            ]
        ],
        'system' => [
            'title' => 'System & Performance',
            'routes' => ['settings.performance-optimization', 'settings.reporting-analytics', 'settings.notifications-alerts', 'settings.mobile-remote'],
            'items' => [
                ['route' => 'settings.performance-optimization', 'title' => 'Performance & Optimization', 'active_routes' => ['settings.performance-optimization']],
                ['route' => 'settings.reporting-analytics', 'title' => 'Reporting & Analytics', 'active_routes' => ['settings.reporting-analytics']],
                ['route' => 'settings.notifications-alerts', 'title' => 'Notifications & Alerts', 'active_routes' => ['settings.notifications-alerts']],
                ['route' => 'settings.mobile-remote', 'title' => 'Mobile & Remote Access', 'active_routes' => ['settings.mobile-remote']]
            ]
        ],
        'knowledge' => [
            'title' => 'Knowledge & Training',
            'routes' => ['settings.training-documentation', 'settings.knowledge-base'],
            'items' => [
                ['route' => 'settings.training-documentation', 'title' => 'Training & Documentation', 'active_routes' => ['settings.training-documentation']],
                ['route' => 'settings.knowledge-base', 'title' => 'Knowledge Base', 'active_routes' => ['settings.knowledge-base']]
            ]
        ]
    ];
    
    $quickActions = [
        [
            'route' => 'settings.templates',
            'title' => 'Settings Templates',
            'active_routes' => ['settings.templates'],
            'type' => 'link'
        ],
        [
            'route' => 'settings.export',
            'title' => 'Export Settings',
            'active_routes' => ['settings.export'],
            'type' => 'link'
        ],
        [
            'title' => 'Import Settings',
            'type' => 'button',
            'action' => "Livewire.dispatch('open-import-modal')"
        ]
    ];
    
    $activeSection = 'core';
    foreach ($navigationSections as $sectionKey => $section) {
        if (request()->routeIs(...$section['routes'])) {
            $activeSection = $sectionKey;
            break;
        }
    }
@endphp

<flux:navlist class="{{ $isMobile ? '' : 'bg-white dark:bg-gray-800 rounded-lg shadow-sm' }}">
    <div class="p-2">
        @foreach($navigationSections as $sectionKey => $section)
            <flux:navlist.group
                heading="{{ $section['title'] }}"
                expandable
                :expanded="$activeSection === '{{ $sectionKey }}'">
                @foreach($section['items'] as $item)
                    <flux:navlist.item
                        href="{{ route($item['route']) }}"
                        :current="request()->routeIs(...$item['active_routes'])">
                        {{ $item['title'] }}
                    </flux:navlist.item>
                @endforeach
            </flux:navlist.group>
        @endforeach

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
             <flux:navlist.group heading="Quick Actions">
                @foreach($quickActions as $action)
                    @if($action['type'] === 'link')
                        <flux:navlist.item
                            href="{{ route($action['route']) }}"
                            :current="request()->routeIs(...$action['active_routes'])">
                            {{ $action['title'] }}
                        </flux:navlist.item>
                    @else
                        <flux:navlist.item href="#" onclick="{{ $action['action'] }}; return false;">
                            {{ $action['title'] }}
                        </flux:navlist.item>
                    @endif
                @endforeach
            </flux:navlist.group>
        </div>
    </div>
</flux:navlist>
