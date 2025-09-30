<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait LazyLoadable
{
    /**
     * Render placeholder while component is loading
     */
    public function placeholder(array $params = [])
    {
        $placeholderView = $this->getPlaceholderView();

        // Pass any component parameters to the placeholder
        $data = array_merge($params, [
            'widgetType' => $this->getWidgetType(),
            'widgetTitle' => $this->getWidgetTitle(),
        ]);

        return view($placeholderView, $data);
    }

    /**
     * Get the placeholder view path
     */
    protected function getPlaceholderView(): string
    {
        $widgetType = $this->getWidgetType();
        $specificPlaceholder = "livewire.dashboard.placeholders.skeleton-{$widgetType}";

        // Check if specific placeholder exists, otherwise use default
        if (view()->exists($specificPlaceholder)) {
            return $specificPlaceholder;
        }

        return 'livewire.dashboard.placeholders.skeleton-default';
    }

    /**
     * Get widget type based on class name
     */
    protected function getWidgetType(): string
    {
        $className = class_basename(static::class);

        // Map class names to widget types
        $typeMap = [
            'KpiGrid' => 'kpi',
            'RevenueChart' => 'chart',
            'TicketChart' => 'chart',
            'ClientHealth' => 'metrics',
            'TeamPerformance' => 'metrics',
            'AlertPanel' => 'feed',
            'ActivityFeed' => 'feed',
            'QuickActions' => 'actions',
            'TicketQueue' => 'table',
            'SlaMonitor' => 'table',
            'ResourceAllocation' => 'metrics',
            'ResponseTimes' => 'chart',
            'FinancialKpis' => 'kpi',
            'InvoiceStatus' => 'table',
            'PaymentTracking' => 'table',
            'CollectionMetrics' => 'metrics',
            'OverdueInvoices' => 'table',
            'MyTickets' => 'table',
            'KnowledgeBase' => 'list',
            'CustomerSatisfaction' => 'metrics',
            'RecentSolutions' => 'list',
        ];

        return $typeMap[$className] ?? 'default';
    }

    /**
     * Get widget title for placeholder
     */
    protected function getWidgetTitle(): string
    {
        $className = class_basename(static::class);

        // Map class names to titles
        $titleMap = [
            'KpiGrid' => 'Key Performance Indicators',
            'RevenueChart' => 'Revenue Analysis',
            'TicketChart' => 'Ticket Trends',
            'ClientHealth' => 'Client Health Scores',
            'TeamPerformance' => 'Team Performance',
            'AlertPanel' => 'System Alerts',
            'ActivityFeed' => 'Recent Activity',
            'QuickActions' => 'Quick Actions',
            'TicketQueue' => 'Ticket Queue',
            'SlaMonitor' => 'SLA Monitoring',
            'ResourceAllocation' => 'Resource Allocation',
            'ResponseTimes' => 'Response Time Analysis',
            'FinancialKpis' => 'Financial KPIs',
            'InvoiceStatus' => 'Invoice Status',
            'PaymentTracking' => 'Payment Tracking',
            'CollectionMetrics' => 'Collection Metrics',
            'OverdueInvoices' => 'Overdue Invoices',
            'MyTickets' => 'My Tickets',
            'KnowledgeBase' => 'Knowledge Base',
            'CustomerSatisfaction' => 'Customer Satisfaction',
            'RecentSolutions' => 'Recent Solutions',
        ];

        return $titleMap[$className] ?? Str::headline($className);
    }

    /**
     * Track widget load performance
     */
    protected function trackLoadTime(string $method = 'mount'): void
    {
        if (config('app.debug')) {
            $start = microtime(true);
            register_shutdown_function(function () use ($start, $method) {
                $loadTime = microtime(true) - $start;

                logger()->channel('performance')->info('Widget loaded', [
                    'widget' => class_basename(static::class),
                    'method' => $method,
                    'time' => round($loadTime * 1000, 2).'ms',
                    'memory' => round(memory_get_peak_usage() / 1024 / 1024, 2).'MB',
                ]);
            });
        }
    }
}
