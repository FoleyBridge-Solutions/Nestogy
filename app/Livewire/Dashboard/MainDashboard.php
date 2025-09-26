<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use App\Services\DashboardDataService;
use App\Services\DashboardLazyLoadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MainDashboard extends Component
{
    public string $view = 'executive'; // executive, operations, financial, support
    public array $widgets = [];
    public array $allWidgetConfigs = [];
    public array $metrics = [];
    public bool $darkMode = false;
    public int $refreshInterval = 30; // seconds
    public string $kpiPeriod = 'month';
    
    protected function getListeners()
    {
        $listeners = [
            'refreshDashboard' => 'loadDashboardData',
            'widgetUpdated' => 'handleWidgetUpdate',
            'changeView' => 'switchView',
        ];
        
        // Add dynamic Echo listener if user is authenticated
        if (Auth::check()) {
            $companyId = Auth::user()->company_id;
            $listeners["echo:dashboard.{$companyId},DashboardDataUpdated"] = 'handleRealtimeUpdate';
        }
        
        return $listeners;
    }

    public function mount()
    {
        $this->initializeDashboard();
        $this->loadDashboardData();
    }

    public function initializeDashboard()
    {
        $user = Auth::user();
        
        // Set view based on user role
        if ($user->isAdmin()) {
            $this->view = session('dashboard_view', 'executive');
        } elseif ($user->isTech()) {
            $this->view = 'operations';
        } elseif ($user->isAccountant()) {
            $this->view = 'financial';
        } else {
            $this->view = 'support';
        }
        
        // Load user preferences
        $this->darkMode = optional($user->userSetting)->theme === 'dark';
        
        // Configure widgets based on view
        $this->configureWidgets();
    }

    public function configureWidgets()
    {
        // Store all widget configurations with lazy loading info
        $widgetConfigs = [
            'executive' => [
                ['type' => 'alert-panel', 'size' => 'full'],
                ['type' => 'kpi-grid', 'size' => 'full'],
                ['type' => 'revenue-chart', 'size' => 'half'],
                ['type' => 'ticket-chart', 'size' => 'half'],
                ['type' => 'team-performance', 'size' => 'half'],
                ['type' => 'quick-actions', 'size' => 'half'],
                ['type' => 'client-health', 'size' => 'half'],
                ['type' => 'activity-feed', 'size' => 'half'],
            ],
            'operations' => [
                ['type' => 'sla-monitor', 'size' => 'full'],
                ['type' => 'ticket-queue', 'size' => 'full'],
                ['type' => 'resource-allocation', 'size' => 'half'],
                ['type' => 'ticket-chart', 'size' => 'half'],
                ['type' => 'response-times', 'size' => 'half'],
                ['type' => 'activity-feed', 'size' => 'half'],
            ],
            'financial' => [
                ['type' => 'financial-kpis', 'size' => 'full'],
                ['type' => 'revenue-chart', 'size' => 'half'],
                ['type' => 'invoice-status', 'size' => 'half'],
                ['type' => 'payment-tracking', 'size' => 'half'],
                ['type' => 'collection-metrics', 'size' => 'half'],
                ['type' => 'overdue-invoices', 'size' => 'full'],
            ],
            'support' => [
                ['type' => 'my-tickets', 'size' => 'full'],
                ['type' => 'ticket-chart', 'size' => 'half'],
                ['type' => 'knowledge-base', 'size' => 'half'],
                ['type' => 'customer-satisfaction', 'size' => 'half'],
                ['type' => 'recent-solutions', 'size' => 'half'],
            ],
        ];
        
        // Add lazy loading configuration to each widget
        foreach ($widgetConfigs as $view => &$widgets) {
            // Don't sort - preserve our manual order
            // $widgets = DashboardLazyLoadService::sortByPriority($widgets);
            foreach ($widgets as &$widget) {
                $widget['lazy'] = DashboardLazyLoadService::shouldLazyLoad($widget['type']);
                $widget['strategy'] = DashboardLazyLoadService::getLoadingStrategy($widget['type']);
            }
        }
        
        $this->allWidgetConfigs = $widgetConfigs;
        
        // Set the current view's widgets
        $this->widgets = $this->allWidgetConfigs[$this->view] ?? $this->allWidgetConfigs['executive'];
    }

    public function loadDashboardData()
    {
        $cacheKey = "dashboard_data_{$this->view}_" . Auth::id();
        
        $this->metrics = Cache::remember($cacheKey, 60, function () {
            // Load basic metrics for now - widgets will handle their own data loading
            $companyId = Auth::user()->company_id;
            
            try {
                $service = new DashboardDataService($companyId);

                $data = match($this->view) {
                    'executive' => $service->getExecutiveDashboardData(now()->startOfMonth(), now()->endOfMonth()),
                    'operations' => method_exists($service, 'getOperationsDashboardData')
                        ? $service->getOperationsDashboardData()
                        : ['view' => 'operations'],
                    'financial' => method_exists($service, 'getFinancialDashboardData')
                        ? $service->getFinancialDashboardData(now()->startOfMonth(), now()->endOfMonth())
                        : ['view' => 'financial'],
                    'support' => method_exists($service, 'getSupportDashboardData')
                        ? $service->getSupportDashboardData()
                        : ['view' => 'support'],
                    default => []
                };
            } catch (\Exception $e) {
                // Fallback data if service fails
                $data = [
                    'view' => $this->view,
                    'company_id' => $companyId,
                    'loaded_at' => now(),
                    'error' => 'Service temporarily unavailable'
                ];
            }
            
            return $data;
        });
        
        $this->dispatch('dashboard-data-loaded', $this->metrics);
    }

    public function setKpiPeriod(string $period): void
    {
        if (!in_array($period, ['month', 'quarter', 'year', 'all'], true)) {
            return;
        }

        if ($this->kpiPeriod === $period) {
            return;
        }

        $this->kpiPeriod = $period;
        $this->dispatch('set-kpi-period', period: $period);
    }

    public function updatedView($value)
    {
        \Log::info('Dashboard view updated', ['old' => $this->view, 'new' => $value]);
        
        if (in_array($value, ['executive', 'operations', 'financial', 'support'])) {
            // Save the new view to session
            session(['dashboard_view' => $value]);
            
            // Clear widgets first to force re-render
            $this->widgets = [];
            
            // Reconfigure widgets for new view
            $this->configureWidgets();
            
            // Clear cache to force fresh data load
            $cacheKey = "dashboard_data_{$this->view}_" . Auth::id();
            Cache::forget($cacheKey);
            
            // Reload dashboard data
            $this->loadDashboardData();
            
            \Log::info('Dashboard view change complete', [
                'view' => $this->view,
                'widgets' => array_column($this->widgets, 'type')
            ]);
        }
    }

    public function switchView($view)
    {
        if (in_array($view, ['executive', 'operations', 'financial', 'support'])) {
            $this->view = $view;
            session(['dashboard_view' => $view]);
            
            // Clear widgets first to force re-render
            $this->widgets = [];
            
            // Reconfigure widgets for new view
            $this->configureWidgets();
            
            // Clear cache to force fresh data load
            $cacheKey = "dashboard_data_{$this->view}_" . Auth::id();
            Cache::forget($cacheKey);
            
            // Reload dashboard data
            $this->loadDashboardData();
        }
    }

    public function exportDashboard($format = 'pdf')
    {
        // Export functionality
        $this->dispatch('export-dashboard', [
            'view' => $this->view,
            'format' => $format,
            'data' => $this->metrics
        ]);
    }

    #[On('refresh-widget')]
    public function refreshWidget($widgetType)
    {
        // Refresh specific widget data
        $this->dispatch("refresh-{$widgetType}");
    }

    public function handleWidgetUpdate($widgetType, $data)
    {
        // Handle widget-specific updates
        $this->metrics[$widgetType] = $data;
    }

    protected function filterByClient($data, $clientId)
    {
        // Filter dashboard data by selected client
        // Implementation depends on data structure
        return $data;
    }

    public function toggleFullscreen()
    {
        $this->dispatch('toggle-fullscreen');
    }

    public function openCommandPalette()
    {
        $this->dispatch('open-command-palette');
    }

    #[On('dashboard-realtime-update')]
    public function handleRealtimeUpdate($data)
    {
        // Handle real-time dashboard updates from broadcasting
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'new-ticket':
                    $this->refreshWidget('ticket-chart');
                    $this->refreshWidget('kpi-grid');
                    $this->refreshWidget('activity-feed');
                    break;
                    
                case 'payment-received':
                    $this->refreshWidget('revenue-chart');
                    $this->refreshWidget('kpi-grid');
                    $this->refreshWidget('activity-feed');
                    break;
                    
                case 'invoice-created':
                    $this->refreshWidget('kpi-grid');
                    $this->refreshWidget('activity-feed');
                    break;
                    
                case 'client-updated':
                    $this->refreshWidget('client-health');
                    $this->refreshWidget('activity-feed');
                    break;
                    
                default:
                    // Refresh all widgets for unknown updates
                    $this->loadDashboardData();
                    break;
            }
        }
    }

    public function enableAutoRefresh()
    {
        $this->dispatch('enable-auto-refresh', ['interval' => $this->refreshInterval]);
    }

    public function disableAutoRefresh()
    {
        $this->dispatch('disable-auto-refresh');
    }

    public function render()
    {
        // Make sure widget configs are available
        if (empty($this->allWidgetConfigs)) {
            $this->configureWidgets();
        }
        
        return view('livewire.dashboard.main-dashboard', [
            'metrics' => $this->metrics,
            'widgets' => $this->widgets,
            'allWidgetConfigs' => $this->allWidgetConfigs,
            'view' => $this->view,
            'refreshInterval' => $this->refreshInterval,
        ])->extends('layouts.app')->section('content');
    }
}
