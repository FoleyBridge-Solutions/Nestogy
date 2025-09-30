<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardLazyLoadService
{
    /**
     * Check if lazy loading is enabled
     */
    public static function isEnabled(): bool
    {
        return config('dashboard.lazy_loading.enabled', true);
    }

    /**
     * Check if a widget should be lazy loaded
     */
    public static function shouldLazyLoad(string $widgetType): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        // Check if widget is in immediate load list
        $immediate = config('dashboard.lazy_loading.immediate', []);
        if (in_array($widgetType, $immediate)) {
            return false;
        }

        return true;
    }

    /**
     * Get loading strategy for a widget
     */
    public static function getLoadingStrategy(string $widgetType): string
    {
        $viewport = config('dashboard.lazy_loading.viewport', []);
        $deferred = config('dashboard.lazy_loading.deferred', []);

        if (in_array($widgetType, $viewport)) {
            return 'viewport';
        }

        if (in_array($widgetType, $deferred)) {
            return 'on-load';
        }

        return 'viewport'; // Default strategy
    }

    /**
     * Get cache TTL for a widget
     */
    public static function getCacheTTL(string $widgetType): int
    {
        $widgetTTL = config("dashboard.cache.widget_ttl.{$widgetType}");

        if ($widgetTTL !== null) {
            return $widgetTTL;
        }

        return config('dashboard.cache.ttl', 300);
    }

    /**
     * Clear widget cache
     */
    public static function clearWidgetCache(string $widgetType, ?int $companyId = null, ?int $clientId = null): void
    {
        $pattern = "dashboard_widget_{$widgetType}";

        if ($companyId) {
            $pattern .= "_{$companyId}";
        }

        if ($clientId) {
            $pattern .= "_client_{$clientId}";
        }

        $pattern .= '*';

        // Clear all matching cache keys
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::channel('performance')->info('Widget cache cleared', [
            'widget' => $widgetType,
            'company_id' => $companyId,
            'client_id' => $clientId,
        ]);
    }

    /**
     * Track widget performance
     */
    public static function trackPerformance(string $widgetType, float $loadTime, array $metadata = []): void
    {
        if (! config('dashboard.performance.track_load_times', true)) {
            return;
        }

        $slowThreshold = config('dashboard.performance.slow_threshold', 1000);
        $channel = config('dashboard.performance.log_channel', 'performance');

        $logData = array_merge([
            'widget' => $widgetType,
            'load_time_ms' => round($loadTime * 1000, 2),
            'is_slow' => ($loadTime * 1000) > $slowThreshold,
            'timestamp' => now()->toIso8601String(),
        ], $metadata);

        Log::channel($channel)->info('Widget performance', $logData);

        // Store in cache for analytics
        $analyticsKey = 'dashboard_analytics_'.date('Y-m-d');
        $analytics = Cache::get($analyticsKey, []);

        if (! isset($analytics[$widgetType])) {
            $analytics[$widgetType] = [
                'count' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'max_time' => 0,
                'min_time' => PHP_INT_MAX,
            ];
        }

        $analytics[$widgetType]['count']++;
        $analytics[$widgetType]['total_time'] += $loadTime * 1000;
        $analytics[$widgetType]['avg_time'] = $analytics[$widgetType]['total_time'] / $analytics[$widgetType]['count'];
        $analytics[$widgetType]['max_time'] = max($analytics[$widgetType]['max_time'], $loadTime * 1000);
        $analytics[$widgetType]['min_time'] = min($analytics[$widgetType]['min_time'], $loadTime * 1000);

        Cache::put($analyticsKey, $analytics, 86400); // Store for 24 hours
    }

    /**
     * Get widget loading priority
     */
    public static function getPriority(string $widgetType): int
    {
        return config("dashboard.priority.{$widgetType}", 99);
    }

    /**
     * Get widgets sorted by priority
     */
    public static function sortByPriority(array $widgets): array
    {
        usort($widgets, function ($a, $b) {
            $priorityA = self::getPriority($a['type'] ?? '');
            $priorityB = self::getPriority($b['type'] ?? '');

            return $priorityA <=> $priorityB;
        });

        return $widgets;
    }
}
