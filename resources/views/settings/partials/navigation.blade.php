@php
    $isMobile = $mobile ?? false;
    
    // Navigation configuration
    $navigationSections = [
        'core' => [
            'title' => 'Core Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
            'routes' => ['settings.general', 'settings.security', 'settings.email', 'settings.user-management', 'subsidiaries.index'],
            'items' => [
                ['route' => 'settings.general', 'title' => 'General', 'active_routes' => ['settings.general', 'settings.index']],
                ['route' => 'settings.security', 'title' => 'Security & Access', 'active_routes' => ['settings.security']],
                ['route' => 'settings.email', 'title' => 'Email & Communication', 'active_routes' => ['settings.email']],
                ['route' => 'settings.user-management', 'title' => 'User Management', 'active_routes' => ['settings.user-management']],
                ['route' => 'subsidiaries.index', 'title' => 'Subsidiary Management', 'active_routes' => ['subsidiaries.index', 'subsidiaries.create', 'subsidiaries.edit', 'subsidiaries.show']]
            ]
        ],
        'financial' => [
            'title' => 'Financial Management',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            'routes' => ['settings.billing-financial', 'settings.accounting', 'settings.payment-gateways'],
            'items' => [
                ['route' => 'settings.billing-financial', 'title' => 'Billing & Financial', 'active_routes' => ['settings.billing-financial']],
                ['route' => 'settings.accounting', 'title' => 'Accounting Integration', 'active_routes' => ['settings.accounting']],
                ['route' => 'settings.payment-gateways', 'title' => 'Payment Gateways', 'active_routes' => ['settings.payment-gateways']]
            ]
        ],
        'service' => [
            'title' => 'Service Delivery',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>',
            'routes' => ['settings.ticketing-service-desk', 'settings.project-management', 'settings.asset-inventory', 'settings.client-portal', 'settings.contract-clauses', 'settings.contract-templates', 'settings.template-clauses'],
            'items' => [
                ['route' => 'settings.ticketing-service-desk', 'title' => 'Ticketing & Service Desk', 'active_routes' => ['settings.ticketing-service-desk']],
                ['route' => 'settings.project-management', 'title' => 'Project Management', 'active_routes' => ['settings.project-management']],
                ['route' => 'settings.asset-inventory', 'title' => 'Asset & Inventory', 'active_routes' => ['settings.asset-inventory']],
                ['route' => 'settings.client-portal', 'title' => 'Client Portal', 'active_routes' => ['settings.client-portal']],
                ['route' => 'settings.contract-clauses', 'title' => 'Contract Clauses', 'active_routes' => ['settings.contract-clauses']],
                ['route' => 'settings.contract-templates', 'title' => 'Template Clauses', 'active_routes' => ['settings.contract-templates', 'settings.template-clauses']]
            ]
        ],
        'tech' => [
            'title' => 'Technology Integration',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
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
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
            'routes' => ['settings.compliance-audit', 'settings.backup-recovery', 'settings.data-management'],
            'items' => [
                ['route' => 'settings.compliance-audit', 'title' => 'Compliance & Audit', 'active_routes' => ['settings.compliance-audit']],
                ['route' => 'settings.backup-recovery', 'title' => 'Backup & Recovery', 'active_routes' => ['settings.backup-recovery']],
                ['route' => 'settings.data-management', 'title' => 'Data Management', 'active_routes' => ['settings.data-management']]
            ]
        ],
        'system' => [
            'title' => 'System & Performance',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
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
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>',
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
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>',
            'active_routes' => ['settings.templates'],
            'type' => 'link'
        ],
        [
            'route' => 'settings.export',
            'title' => 'Export Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>',
            'active_routes' => ['settings.export'],
            'type' => 'link'
        ],
        [
            'title' => 'Import Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>',
            'type' => 'button',
            'action' => "@click=\"\$dispatch('open-import-modal')\""
        ]
    ];
    
    // Active section detection
    $activeSection = 'core';
    foreach ($navigationSections as $sectionKey => $section) {
        if (request()->routeIs(...$section['routes'])) {
            $activeSection = $sectionKey;
            break;
        }
    }
    
    // Responsive classes
    $containerClasses = $isMobile ? '' : 'bg-white rounded-lg shadow-sm';
    $headerClasses = $isMobile ? 'px-4 py-3' : 'p-4 border-b border-gray-200';
    $navClasses = $isMobile ? 'px-2' : 'p-2';
    $buttonBaseClasses = $isMobile ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm';
    $iconBaseClasses = $isMobile ? 'w-5 h-5' : 'w-4 h-4';
    $iconSizeClasses = $isMobile ? 'w-5 h-5 mr-3' : 'w-4 h-4 mr-2';
    $containerInnerClasses = $isMobile ? 'mt-2 space-y-1 pl-8' : 'mt-1 space-y-1 pl-6';
    $quickActionsHeaderClasses = $isMobile ? 'px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3' : 'px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2';
    $quickActionsContainerClasses = $isMobile ? 'mt-6 pt-4 border-t border-gray-200' : 'mt-4 pt-4 border-t border-gray-200';
@endphp

<div class="{{ $containerClasses }}" x-data="{ 
    activeSection: '{{ $activeSection }}',
    isMobile: {{ $isMobile ? 'true' : 'false' }},
    handleItemClick(url, section = null) {
        if (this.isMobile) {
            // Close mobile menu when navigating
            this.$dispatch('close-mobile-menu');
        }
        
        // Check if lazy loading is enabled
        if (section && window.settingsLazyEnabled) {
            // Dispatch navigation event for lazy loading
            this.$dispatch('settingsNavigate', { section: section });
            return false;
        }
        
        // Fall back to regular navigation
        window.location.href = url;
    }
}">
    @if(!$isMobile)
    <div class="{{ $headerClasses }}">
        <h3 class="text-lg font-semibold text-gray-900">Settings</h3>
        <p class="text-sm text-gray-600 mt-1">Configure your system preferences</p>
    </div>
    @endif
    
    <nav class="{{ $navClasses }}">
        <!-- Navigation Sections -->
        @foreach($navigationSections as $sectionKey => $section)
        <div class="mb-2">
            <button @click="activeSection = activeSection === '{{ $sectionKey }}' ? null : '{{ $sectionKey }}'" 
                    class="w-full flex items-center justify-between {{ $buttonBaseClasses }} font-medium text-gray-700 hover:bg-gray-50 rounded-md transition-colors touch-manipulation">
                <span class="flex items-center">
                    <svg class="{{ $iconSizeClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $section['icon'] !!}
                    </svg>
                    {{ $section['title'] }}
                </span>
                <svg class="{{ $iconBaseClasses }} transition-transform" :class="{'rotate-180': activeSection === '{{ $sectionKey }}'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="activeSection === '{{ $sectionKey }}'" x-transition class="{{ $containerInnerClasses }}">
                @foreach($section['items'] as $item)
                <a href="{{ route($item['route']) }}" 
                   @click.prevent="handleItemClick('{{ route($item['route']) }}', '{{ str_replace('settings.', '', $item['route']) }}')"
                   class="block {{ $buttonBaseClasses }} {{ request()->routeIs(...$item['active_routes']) ? 'bg-blue-50 text-blue-700 font-medium border-l-2 border-blue-700' : 'text-gray-600 hover:bg-gray-50' }} rounded-md touch-manipulation">
                    {{ $item['title'] }}
                </a>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Quick Actions -->
        <div class="{{ $quickActionsContainerClasses }}">
            <h4 class="{{ $quickActionsHeaderClasses }}">Quick Actions</h4>
            @foreach($quickActions as $action)
                @if($action['type'] === 'link')
                <a href="{{ route($action['route']) }}" 
                   @click.prevent="handleItemClick('{{ route($action['route']) }}', '{{ str_replace('settings.', '', $action['route']) }}')"
                   class="block {{ $buttonBaseClasses }} {{ request()->routeIs(...$action['active_routes']) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }} rounded-md touch-manipulation">
                    <span class="flex items-center">
                        <svg class="{{ $iconSizeClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $action['icon'] !!}
                        </svg>
                        {{ $action['title'] }}
                    </span>
                </a>
                @else
                <button {!! $action['action'] !!}
                        class="w-full text-left {{ $buttonBaseClasses }} text-gray-600 hover:bg-gray-50 rounded-md touch-manipulation">
                    <span class="flex items-center">
                        <svg class="{{ $iconSizeClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $action['icon'] !!}
                        </svg>
                        {{ $action['title'] }}
                    </span>
                </button>
                @endif
            @endforeach
        </div>
    </nav>
</div>