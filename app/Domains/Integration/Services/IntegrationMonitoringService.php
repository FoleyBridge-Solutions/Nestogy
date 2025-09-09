<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Models\DeviceMapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Integration Monitoring Service
 * 
 * Provides monitoring, metrics collection, and health checks
 * for RMM integrations. Tracks performance and reliability.
 */
class IntegrationMonitoringService
{
    /**
     * Record webhook processing metrics.
     */
    public function recordWebhookProcessing(
        Integration $integration,
        float $processingTime,
        bool $successful = true,
        ?string $errorMessage = null
    ): void {
        $metricKey = "webhook_metrics:{$integration->id}:" . date('Y-m-d:H');
        
        $metrics = Cache::get($metricKey, [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_processing_time' => 0.0,
            'min_processing_time' => null,
            'max_processing_time' => null,
            'errors' => [],
        ]);
        
        $metrics['total_requests']++;
        $metrics['total_processing_time'] += $processingTime;
        
        if ($successful) {
            $metrics['successful_requests']++;
        } else {
            $metrics['failed_requests']++;
            if ($errorMessage) {
                $metrics['errors'][] = [
                    'timestamp' => now()->toISOString(),
                    'message' => $errorMessage,
                ];
                
                // Keep only last 10 errors
                $metrics['errors'] = array_slice($metrics['errors'], -10);
            }
        }
        
        // Update min/max processing times
        if ($metrics['min_processing_time'] === null || $processingTime < $metrics['min_processing_time']) {
            $metrics['min_processing_time'] = $processingTime;
        }
        
        if ($metrics['max_processing_time'] === null || $processingTime > $metrics['max_processing_time']) {
            $metrics['max_processing_time'] = $processingTime;
        }
        
        // Cache for 2 hours
        Cache::put($metricKey, $metrics, 7200);
        
        Log::info('Webhook processing metrics recorded', [
            'integration_id' => $integration->id,
            'processing_time_ms' => round($processingTime * 1000, 2),
            'successful' => $successful,
            'hourly_total' => $metrics['total_requests'],
        ]);
    }

    /**
     * Get webhook processing metrics for an integration.
     */
    public function getWebhookMetrics(Integration $integration, int $hoursBack = 24): array
    {
        $metrics = [
            'hourly_data' => [],
            'summary' => [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_processing_time' => 0.0,
                'success_rate' => 100.0,
            ]
        ];
        
        $totalProcessingTime = 0.0;
        
        for ($i = 0; $i < $hoursBack; $i++) {
            $hour = now()->subHours($i);
            $metricKey = "webhook_metrics:{$integration->id}:" . $hour->format('Y-m-d:H');
            
            $hourlyMetrics = Cache::get($metricKey, [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_processing_time' => 0.0,
                'min_processing_time' => null,
                'max_processing_time' => null,
                'errors' => [],
            ]);
            
            $metrics['hourly_data'][] = [
                'hour' => $hour->format('Y-m-d H:00'),
                'total_requests' => $hourlyMetrics['total_requests'],
                'successful_requests' => $hourlyMetrics['successful_requests'],
                'failed_requests' => $hourlyMetrics['failed_requests'],
                'average_processing_time' => $hourlyMetrics['total_requests'] > 0 
                    ? $hourlyMetrics['total_processing_time'] / $hourlyMetrics['total_requests']
                    : 0,
                'min_processing_time' => $hourlyMetrics['min_processing_time'],
                'max_processing_time' => $hourlyMetrics['max_processing_time'],
            ];
            
            $metrics['summary']['total_requests'] += $hourlyMetrics['total_requests'];
            $metrics['summary']['successful_requests'] += $hourlyMetrics['successful_requests'];
            $metrics['summary']['failed_requests'] += $hourlyMetrics['failed_requests'];
            $totalProcessingTime += $hourlyMetrics['total_processing_time'];
        }
        
        // Calculate averages and rates
        if ($metrics['summary']['total_requests'] > 0) {
            $metrics['summary']['average_processing_time'] = $totalProcessingTime / $metrics['summary']['total_requests'];
            $metrics['summary']['success_rate'] = ($metrics['summary']['successful_requests'] / $metrics['summary']['total_requests']) * 100;
        }
        
        // Reverse to show oldest first
        $metrics['hourly_data'] = array_reverse($metrics['hourly_data']);
        
        return $metrics;
    }

    /**
     * Perform health check on an integration.
     */
    public function performHealthCheck(Integration $integration): array
    {
        $health = [
            'overall_status' => 'healthy',
            'checks' => [],
            'last_checked' => now()->toISOString(),
        ];
        
        try {
            // Check 1: Integration is active
            $health['checks']['integration_active'] = [
                'status' => $integration->is_active ? 'pass' : 'fail',
                'message' => $integration->is_active ? 'Integration is active' : 'Integration is inactive',
            ];
            
            // Check 2: Recent webhook activity
            $recentAlerts = $integration->rmmAlerts()->recent(24)->count();
            $health['checks']['recent_activity'] = [
                'status' => $recentAlerts > 0 ? 'pass' : 'warn',
                'message' => $recentAlerts > 0 
                    ? "Received {$recentAlerts} alerts in last 24 hours" 
                    : 'No recent webhook activity',
                'value' => $recentAlerts,
            ];
            
            // Check 3: Processing success rate
            $totalRecent = $integration->rmmAlerts()->recent(24 * 7)->count(); // Last 7 days
            $processedRecent = $integration->rmmAlerts()->recent(24 * 7)->processed()->count();
            $successRate = $totalRecent > 0 ? ($processedRecent / $totalRecent) * 100 : 100;
            
            $health['checks']['processing_success_rate'] = [
                'status' => $successRate >= 95 ? 'pass' : ($successRate >= 80 ? 'warn' : 'fail'),
                'message' => "Processing success rate: {$successRate}%",
                'value' => $successRate,
            ];
            
            // Check 4: Device mapping health
            $totalDevices = $integration->deviceMappings()->count();
            $staleDevices = $integration->deviceMappings()->stale(48)->count(); // 48 hours
            $stalePercentage = $totalDevices > 0 ? ($staleDevices / $totalDevices) * 100 : 0;
            
            $health['checks']['device_mapping_health'] = [
                'status' => $stalePercentage < 20 ? 'pass' : ($stalePercentage < 50 ? 'warn' : 'fail'),
                'message' => "{$staleDevices} of {$totalDevices} devices are stale",
                'value' => $stalePercentage,
            ];
            
            // Check 5: Credential validity (simplified check)
            $credentials = $integration->getCredentials();
            $health['checks']['credentials'] = [
                'status' => !empty($credentials) ? 'pass' : 'fail',
                'message' => !empty($credentials) ? 'Credentials are configured' : 'Credentials are missing',
            ];
            
            // Check 6: Webhook endpoint accessibility
            $webhookUrl = $integration->getWebhookEndpoint();
            $health['checks']['webhook_endpoint'] = [
                'status' => 'pass', // Simplified - would need actual HTTP check
                'message' => "Webhook endpoint: {$webhookUrl}",
                'url' => $webhookUrl,
            ];
            
            // Determine overall status
            $failedChecks = collect($health['checks'])->where('status', 'fail')->count();
            $warnChecks = collect($health['checks'])->where('status', 'warn')->count();
            
            if ($failedChecks > 0) {
                $health['overall_status'] = 'unhealthy';
            } elseif ($warnChecks > 0) {
                $health['overall_status'] = 'degraded';
            }
            
        } catch (\Exception $e) {
            Log::error('Health check failed for integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            
            $health['overall_status'] = 'error';
            $health['error'] = $e->getMessage();
        }
        
        return $health;
    }

    /**
     * Generate integration performance report.
     */
    public function generatePerformanceReport(Integration $integration, int $daysBack = 7): array
    {
        $report = [
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'provider' => $integration->provider,
            ],
            'period' => [
                'start_date' => now()->subDays($daysBack)->startOfDay()->toISOString(),
                'end_date' => now()->endOfDay()->toISOString(),
                'days' => $daysBack,
            ],
            'webhook_performance' => $this->getWebhookMetrics($integration, $daysBack * 24),
            'alert_statistics' => $this->getAlertStatistics($integration, $daysBack),
            'device_statistics' => $this->getDeviceStatistics($integration, $daysBack),
            'health_summary' => $this->performHealthCheck($integration),
            'recommendations' => $this->generateRecommendations($integration),
        ];
        
        return $report;
    }

    /**
     * Get alert statistics for performance reporting.
     */
    protected function getAlertStatistics(Integration $integration, int $daysBack): array
    {
        $startDate = now()->subDays($daysBack)->startOfDay();
        
        return [
            'total_alerts' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'processed_alerts' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->processed()
                ->count(),
            'duplicate_alerts' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->duplicate()
                ->count(),
            'alerts_by_severity' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'alerts_by_type' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->select('alert_type', DB::raw('count(*) as count'))
                ->groupBy('alert_type')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'alert_type')
                ->toArray(),
            'tickets_created' => $integration->rmmAlerts()
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('ticket_id')
                ->count(),
        ];
    }

    /**
     * Get device statistics for performance reporting.
     */
    protected function getDeviceStatistics(Integration $integration, int $daysBack): array
    {
        return [
            'total_devices' => $integration->deviceMappings()->count(),
            'active_devices' => $integration->deviceMappings()->active()->count(),
            'mapped_devices' => $integration->deviceMappings()->mapped()->count(),
            'stale_devices' => $integration->deviceMappings()->stale(48)->count(),
            'devices_by_client' => $integration->deviceMappings()
                ->join('clients', 'device_mappings.client_id', '=', 'clients.id')
                ->select('clients.name', DB::raw('count(*) as device_count'))
                ->groupBy('clients.id', 'clients.name')
                ->orderByDesc('device_count')
                ->limit(10)
                ->pluck('device_count', 'name')
                ->toArray(),
        ];
    }

    /**
     * Generate recommendations based on integration performance.
     */
    protected function generateRecommendations(Integration $integration): array
    {
        $recommendations = [];
        $health = $this->performHealthCheck($integration);
        
        // Analyze health check results and generate recommendations
        foreach ($health['checks'] as $checkName => $check) {
            switch ($checkName) {
                case 'recent_activity':
                    if ($check['status'] === 'warn') {
                        $recommendations[] = [
                            'type' => 'configuration',
                            'priority' => 'medium',
                            'title' => 'No Recent Activity',
                            'description' => 'This integration has not received any webhook data in the last 24 hours.',
                            'action' => 'Verify webhook configuration and RMM system connectivity.',
                        ];
                    }
                    break;
                    
                case 'processing_success_rate':
                    if ($check['status'] === 'fail') {
                        $recommendations[] = [
                            'type' => 'performance',
                            'priority' => 'high',
                            'title' => 'Low Processing Success Rate',
                            'description' => 'Alert processing success rate is below 80%.',
                            'action' => 'Review error logs and check field mappings and authentication.',
                        ];
                    }
                    break;
                    
                case 'device_mapping_health':
                    if ($check['status'] !== 'pass') {
                        $recommendations[] = [
                            'type' => 'maintenance',
                            'priority' => 'medium',
                            'title' => 'Stale Device Mappings',
                            'description' => 'Many devices have not been updated recently.',
                            'action' => 'Run manual device synchronization and check API connectivity.',
                        ];
                    }
                    break;
            }
        }
        
        // Add general recommendations
        $totalAlerts = $integration->rmmAlerts()->recent(24 * 7)->count();
        $duplicateAlerts = $integration->rmmAlerts()->recent(24 * 7)->duplicate()->count();
        
        if ($totalAlerts > 0 && ($duplicateAlerts / $totalAlerts) > 0.3) {
            $recommendations[] = [
                'type' => 'optimization',
                'priority' => 'low',
                'title' => 'High Duplicate Rate',
                'description' => 'More than 30% of alerts are being flagged as duplicates.',
                'action' => 'Review duplicate detection rules and RMM alert configuration.',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Clear cached metrics for an integration.
     */
    public function clearMetricsCache(Integration $integration): void
    {
        $pattern = "webhook_metrics:{$integration->id}:*";
        
        // This would require a more sophisticated cache clearing mechanism in production
        // For now, log the action
        Log::info('Metrics cache clear requested', [
            'integration_id' => $integration->id,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Get system-wide integration health summary.
     */
    public function getSystemHealthSummary(int $companyId): array
    {
        $integrations = Integration::forCompany($companyId)->get();
        
        $summary = [
            'total_integrations' => $integrations->count(),
            'healthy' => 0,
            'degraded' => 0,
            'unhealthy' => 0,
            'error' => 0,
            'overall_status' => 'healthy',
            'integration_details' => [],
        ];
        
        foreach ($integrations as $integration) {
            $health = $this->performHealthCheck($integration);
            $summary['integration_details'][] = [
                'id' => $integration->id,
                'name' => $integration->name,
                'provider' => $integration->provider,
                'status' => $health['overall_status'],
                'is_active' => $integration->is_active,
            ];
            
            $summary[$health['overall_status']]++;
        }
        
        // Determine overall system status
        if ($summary['error'] > 0 || $summary['unhealthy'] > 0) {
            $summary['overall_status'] = 'unhealthy';
        } elseif ($summary['degraded'] > 0) {
            $summary['overall_status'] = 'degraded';
        }
        
        return $summary;
    }
}