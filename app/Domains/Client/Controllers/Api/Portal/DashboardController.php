<?php

namespace App\Domains\Client\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Portal Dashboard Controller
 *
 * Handles all dashboard-related functionality including:
 * - Main dashboard data retrieval
 * - Account summaries and overviews
 * - Recent activity feeds
 * - Quick statistics and metrics
 * - Widget data for customizable dashboards
 */
class DashboardController extends PortalApiController
{
    /**
     * Get comprehensive dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('dashboard', 30, 60);
            $this->logActivity('dashboard_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Dashboard data retrieved successfully', $dashboardData);

        } catch (Exception $e) {
            return $this->handleException($e, 'dashboard retrieval');
        }
    }

    /**
     * Get account summary only
     */
    public function accountSummary(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('account_summary', 60, 60);
            $this->logActivity('account_summary_view');

            // Get just the account summary portion of dashboard data
            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Account summary retrieved successfully', [
                'account_summary' => $dashboardData['account_summary'] ?? [],
                'billing_overview' => $dashboardData['billing_overview'] ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'account summary retrieval');
        }
    }

    /**
     * Get recent activity feed
     */
    public function recentActivity(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('recent_activity', 120, 60);
            $this->logActivity('recent_activity_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Recent activity retrieved successfully', [
                'recent_activity' => $dashboardData['recent_activity'] ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'recent activity retrieval');
        }
    }

    /**
     * Get upcoming items and tasks
     */
    public function upcomingItems(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('upcoming_items', 120, 60);
            $this->logActivity('upcoming_items_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Upcoming items retrieved successfully', [
                'upcoming_items' => $dashboardData['upcoming_items'] ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'upcoming items retrieval');
        }
    }

    /**
     * Get portal usage metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('view_analytics');

            $this->applyRateLimit('portal_metrics', 60, 60);
            $this->logActivity('portal_metrics_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Portal metrics retrieved successfully', [
                'portal_metrics' => $dashboardData['portal_metrics'] ?? [],
                'usage_summary' => $dashboardData['usage_summary'] ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'portal metrics retrieval');
        }
    }

    /**
     * Get service status overview
     */
    public function serviceStatus(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('service_status', 120, 60);
            $this->logActivity('service_status_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Service status retrieved successfully', [
                'service_status' => $dashboardData['service_status'] ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'service status retrieval');
        }
    }

    /**
     * Get widget data for customizable dashboard
     */
    public function widget(Request $request, string $widgetType): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('widget_data', 180, 60);
            $this->logActivity('widget_view', ['widget_type' => $widgetType]);

            $dashboardData = $this->portalService->getDashboardData($client);

            // Map widget types to dashboard data keys
            $widgetDataMap = [
                'account_summary' => 'account_summary',
                'billing_overview' => 'billing_overview',
                'service_status' => 'service_status',
                'recent_activity' => 'recent_activity',
                'upcoming_items' => 'upcoming_items',
                'notifications' => 'notifications',
                'support_summary' => 'support_summary',
                'payment_methods' => 'payment_methods',
                'document_summary' => 'document_summary',
                'portal_metrics' => 'portal_metrics',
            ];

            if (! array_key_exists($widgetType, $widgetDataMap)) {
                return $this->errorResponse('Invalid widget type', 400);
            }

            $dataKey = $widgetDataMap[$widgetType];
            $widgetData = $dashboardData[$dataKey] ?? [];

            return $this->successResponse('Widget data retrieved successfully', [
                'widget_type' => $widgetType,
                'data' => $widgetData,
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'widget data retrieval');
        }
    }

    /**
     * Refresh dashboard cache
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('dashboard_refresh', 5, 300); // Limit to 5 refreshes per 5 minutes
            $this->logActivity('dashboard_refresh');

            // Clear dashboard cache
            \Illuminate\Support\Facades\Cache::forget("portal_dashboard_{$client->id}");

            // Get fresh dashboard data
            $dashboardData = $this->portalService->getDashboardData($client);

            return $this->successResponse('Dashboard refreshed successfully', $dashboardData);

        } catch (Exception $e) {
            return $this->handleException($e, 'dashboard refresh');
        }
    }

    /**
     * Get quick stats for mobile/compact view
     */
    public function quickStats(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('quick_stats', 120, 60);
            $this->logActivity('quick_stats_view');

            $dashboardData = $this->portalService->getDashboardData($client);

            // Extract key statistics for quick view
            $quickStats = [
                'account_balance' => $dashboardData['account_summary']['balance'] ?? 0,
                'overdue_amount' => $dashboardData['billing_overview']['overdue_amount'] ?? 0,
                'active_services' => $dashboardData['service_status']['active_services'] ?? 0,
                'open_tickets' => $dashboardData['support_summary']['open_tickets'] ?? 0,
                'unread_notifications' => $dashboardData['notifications'] ?
                    count(array_filter($dashboardData['notifications'], fn ($n) => ! $n['is_read'])) : 0,
                'next_bill_date' => $dashboardData['account_summary']['next_bill_date'] ?? null,
                'last_payment_amount' => $dashboardData['billing_overview']['last_payment_amount'] ?? 0,
                'auto_pay_enabled' => $dashboardData['account_summary']['auto_pay_enabled'] ?? false,
            ];

            return $this->successResponse('Quick stats retrieved successfully', [
                'quick_stats' => $quickStats,
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'quick stats retrieval');
        }
    }
}
