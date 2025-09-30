<?php

namespace App\Domains\Asset\Services;

use App\Domains\Integration\Services\AssetSyncService;
use App\Models\Asset;
use Illuminate\Support\Facades\Log;

/**
 * Asset Lifecycle Management Service
 *
 * Provides predictive analytics, lifecycle tracking, and replacement
 * planning for assets based on RMM data and performance trends.
 */
class AssetLifecycleService
{
    protected AssetSyncService $syncService;

    public function __construct(AssetSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Analyze asset lifecycle and provide recommendations.
     */
    public function analyzeAssetLifecycle(Asset $asset): array
    {
        $analysis = [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'analysis_date' => now()->toISOString(),
        ];

        // Get comprehensive device status
        $deviceStatus = $this->syncService->getDeviceStatus($asset);

        if ($deviceStatus['success']) {
            $analysis['current_status'] = $this->analyzeCurrentStatus($asset, $deviceStatus['data']);
            $analysis['performance_trends'] = $this->analyzePerformanceTrends($asset, $deviceStatus['data']);
            $analysis['health_score'] = $this->calculateHealthScore($asset, $deviceStatus['data']);
            $analysis['lifecycle_stage'] = $this->determineLifecycleStage($asset, $analysis);
            $analysis['replacement_prediction'] = $this->predictReplacementDate($asset, $analysis);
            $analysis['cost_analysis'] = $this->analyzeCostEffectiveness($asset, $analysis);
            $analysis['recommendations'] = $this->generateRecommendations($asset, $analysis);
        } else {
            $analysis['error'] = 'Unable to retrieve device status for analysis';
            $analysis['recommendations'] = ['Restore RMM connectivity to enable lifecycle analysis'];
        }

        return $analysis;
    }

    /**
     * Get lifecycle analytics for multiple assets.
     */
    public function getBulkLifecycleAnalytics(array $assetIds): array
    {
        $analytics = [];
        $summary = [
            'total_assets' => count($assetIds),
            'analyzed' => 0,
            'errors' => 0,
            'lifecycle_distribution' => [],
            'replacement_timeline' => [],
        ];

        foreach ($assetIds as $assetId) {
            try {
                $asset = Asset::findOrFail($assetId);
                $analysis = $this->analyzeAssetLifecycle($asset);

                if (! isset($analysis['error'])) {
                    $analytics[] = $analysis;
                    $summary['analyzed']++;

                    // Update summary statistics
                    $stage = $analysis['lifecycle_stage']['stage'];
                    $summary['lifecycle_distribution'][$stage] = ($summary['lifecycle_distribution'][$stage] ?? 0) + 1;

                    if (isset($analysis['replacement_prediction']['months_until_replacement'])) {
                        $months = $analysis['replacement_prediction']['months_until_replacement'];
                        $timeframe = $this->categorizeReplacementTimeframe($months);
                        $summary['replacement_timeline'][$timeframe] = ($summary['replacement_timeline'][$timeframe] ?? 0) + 1;
                    }
                } else {
                    $summary['errors']++;
                    Log::warning('Asset lifecycle analysis failed', [
                        'asset_id' => $assetId,
                        'error' => $analysis['error'],
                    ]);
                }

            } catch (\Exception $e) {
                $summary['errors']++;
                Log::error('Asset lifecycle analysis error', [
                    'asset_id' => $assetId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'analytics' => $analytics,
            'summary' => $summary,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Track asset performance trends over time.
     */
    public function trackPerformanceTrends(Asset $asset, int $days = 30): array
    {
        // This would typically query historical performance data
        // For now, simulate trend analysis

        $deviceStatus = $this->syncService->getDeviceStatus($asset);

        if (! $deviceStatus['success']) {
            return [
                'success' => false,
                'error' => 'Unable to retrieve device status',
            ];
        }

        $currentPerformance = $deviceStatus['data']['performance'] ?? [];

        // Simulate historical trend data
        $trends = [
            'cpu_usage' => $this->generateTrendData($currentPerformance['cpu']['usage_percent'] ?? 50, $days),
            'memory_usage' => $this->generateTrendData($currentPerformance['memory']['usage_percent'] ?? 60, $days),
            'disk_usage' => $this->generateDiskUsageTrend($asset, $days),
            'uptime_stability' => $this->generateUptimeTrend($asset, $days),
            'error_frequency' => $this->generateErrorFrequencyTrend($asset, $days),
        ];

        return [
            'success' => true,
            'asset_id' => $asset->id,
            'period_days' => $days,
            'trends' => $trends,
            'trend_analysis' => $this->analyzeTrends($trends),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate capacity planning recommendations.
     */
    public function generateCapacityPlan(Asset $asset): array
    {
        $deviceStatus = $this->syncService->getDeviceStatus($asset);

        if (! $deviceStatus['success']) {
            return [
                'success' => false,
                'error' => 'Unable to retrieve device status for capacity planning',
            ];
        }

        $performance = $deviceStatus['data']['performance'] ?? [];
        $hardware = $deviceStatus['data']['hardware'] ?? [];

        $plan = [
            'asset_id' => $asset->id,
            'current_capacity' => $this->analyzeCurrentCapacity($performance, $hardware),
            'utilization_trends' => $this->analyzeUtilizationTrends($asset),
            'bottlenecks' => $this->identifyBottlenecks($performance, $hardware),
            'recommendations' => [],
            'timeline' => [],
        ];

        // Generate specific recommendations
        $plan['recommendations'] = $this->generateCapacityRecommendations($plan);
        $plan['timeline'] = $this->generateCapacityTimeline($plan);

        return $plan;
    }

    /**
     * Predict asset failures based on performance data.
     */
    public function predictFailures(Asset $asset): array
    {
        $deviceStatus = $this->syncService->getDeviceStatus($asset);

        if (! $deviceStatus['success']) {
            return [
                'success' => false,
                'error' => 'Unable to retrieve device status for failure prediction',
            ];
        }

        $performance = $deviceStatus['data']['performance'] ?? [];
        $hardware = $deviceStatus['data']['hardware'] ?? [];

        $predictions = [
            'asset_id' => $asset->id,
            'risk_factors' => $this->identifyRiskFactors($asset, $performance, $hardware),
            'failure_probability' => $this->calculateFailureProbability($asset, $performance, $hardware),
            'predicted_failures' => $this->predictSpecificFailures($asset, $performance, $hardware),
            'preventive_actions' => $this->suggestPreventiveActions($asset),
            'monitoring_recommendations' => $this->generateMonitoringRecommendations($asset),
        ];

        return $predictions;
    }

    /**
     * Generate warranty and support analytics.
     */
    public function analyzeWarrantyStatus(Asset $asset): array
    {
        $warranty = [
            'asset_id' => $asset->id,
            'warranty_status' => $this->getWarrantyStatus($asset),
            'support_status' => $this->getSupportStatus($asset),
            'cost_analysis' => $this->analyzeWarrantyCosts($asset),
            'renewal_recommendations' => $this->generateWarrantyRecommendations($asset),
        ];

        return $warranty;
    }

    // Protected helper methods

    protected function analyzeCurrentStatus(Asset $asset, array $deviceData): array
    {
        $agent = $deviceData['agent'] ?? [];
        $performance = $deviceData['performance'] ?? [];

        return [
            'online' => $agent['online'] ?? false,
            'last_seen' => $agent['last_seen'] ?? null,
            'uptime_days' => ($performance['uptime']['uptime_seconds'] ?? 0) / 86400,
            'cpu_usage' => $performance['cpu']['usage_percent'] ?? null,
            'memory_usage' => $performance['memory']['usage_percent'] ?? null,
            'disk_free_gb' => $this->calculateTotalFreeDisk($performance['disk'] ?? []),
            'pending_reboot' => $performance['system']['pending_reboot'] ?? false,
        ];
    }

    protected function analyzePerformanceTrends(Asset $asset, array $deviceData): array
    {
        // Simulate performance trend analysis
        $performance = $deviceData['performance'] ?? [];

        return [
            'cpu_trend' => $this->calculateTrend($performance['cpu']['usage_percent'] ?? 50),
            'memory_trend' => $this->calculateTrend($performance['memory']['usage_percent'] ?? 60),
            'disk_trend' => $this->calculateDiskTrend($performance['disk'] ?? []),
            'stability_trend' => $this->calculateStabilityTrend($asset),
        ];
    }

    protected function calculateHealthScore(Asset $asset, array $deviceData): array
    {
        $scores = [];
        $weights = [];

        // Performance score (0-100)
        $performance = $deviceData['performance'] ?? [];
        $cpuUsage = $performance['cpu']['usage_percent'] ?? 50;
        $memoryUsage = $performance['memory']['usage_percent'] ?? 60;

        $scores['performance'] = max(0, 100 - ($cpuUsage * 0.5 + $memoryUsage * 0.5));
        $weights['performance'] = 0.3;

        // Uptime score
        $uptimeSeconds = $performance['uptime']['uptime_seconds'] ?? 86400;
        $scores['uptime'] = min(100, ($uptimeSeconds / 86400) * 10); // 10 points per day
        $weights['uptime'] = 0.2;

        // Disk health score
        $disks = $performance['disk'] ?? [];
        $avgDiskUsage = $this->calculateAverageDiskUsage($disks);
        $scores['disk_health'] = max(0, 100 - $avgDiskUsage);
        $weights['disk_health'] = 0.2;

        // System health score
        $pendingReboot = $performance['system']['pending_reboot'] ?? false;
        $scores['system_health'] = $pendingReboot ? 70 : 100;
        $weights['system_health'] = 0.2;

        // Age factor
        $ageInYears = $asset->age_in_years ?? 0;
        $scores['age_factor'] = max(0, 100 - ($ageInYears * 15)); // 15 points per year
        $weights['age_factor'] = 0.1;

        // Calculate weighted average
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($scores as $component => $score) {
            $weight = $weights[$component];
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        $overallScore = $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;

        return [
            'overall_score' => $overallScore,
            'grade' => $this->scoreToGrade($overallScore),
            'component_scores' => $scores,
            'recommendations' => $this->getHealthRecommendations($scores),
        ];
    }

    protected function determineLifecycleStage(Asset $asset, array $analysis): array
    {
        $ageInYears = $asset->age_in_years ?? 0;
        $healthScore = $analysis['health_score']['overall_score'] ?? 100;

        if ($ageInYears < 1) {
            $stage = 'new';
        } elseif ($ageInYears < 3 && $healthScore > 80) {
            $stage = 'prime';
        } elseif ($ageInYears < 5 && $healthScore > 60) {
            $stage = 'mature';
        } elseif ($healthScore > 40) {
            $stage = 'aging';
        } else {
            $stage = 'end_of_life';
        }

        return [
            'stage' => $stage,
            'age_years' => $ageInYears,
            'health_score' => $healthScore,
            'stage_description' => $this->getStageDescription($stage),
        ];
    }

    protected function predictReplacementDate(Asset $asset, array $analysis): array
    {
        $stage = $analysis['lifecycle_stage']['stage'];
        $healthScore = $analysis['health_score']['overall_score'] ?? 100;
        $ageInYears = $asset->age_in_years ?? 0;

        // Prediction algorithm based on multiple factors
        $monthsUntilReplacement = match ($stage) {
            'new' => 48 - ($ageInYears * 12),
            'prime' => 36 - ($ageInYears * 12) + (($healthScore - 80) * 0.5),
            'mature' => 24 - ($ageInYears * 12) + (($healthScore - 60) * 0.3),
            'aging' => 12 + (($healthScore - 40) * 0.2),
            'end_of_life' => max(1, ($healthScore - 20) * 0.1),
            default => 24,
        };

        $monthsUntilReplacement = max(1, round($monthsUntilReplacement));
        $replacementDate = now()->addMonths($monthsUntilReplacement);

        return [
            'months_until_replacement' => $monthsUntilReplacement,
            'predicted_replacement_date' => $replacementDate->toDateString(),
            'confidence_level' => $this->calculatePredictionConfidence($analysis),
            'factors' => [
                'age' => $ageInYears,
                'health_score' => $healthScore,
                'lifecycle_stage' => $stage,
            ],
        ];
    }

    protected function analyzeCostEffectiveness(Asset $asset, array $analysis): array
    {
        $monthsUntilReplacement = $analysis['replacement_prediction']['months_until_replacement'] ?? 12;
        $healthScore = $analysis['health_score']['overall_score'] ?? 100;

        // Estimate costs (would typically come from asset purchase data)
        $estimatedCurrentValue = $this->estimateCurrentValue($asset);
        $estimatedReplacementCost = $this->estimateReplacementCost($asset);
        $monthlySupportCost = $this->estimateMonthlySupportCost($asset, $healthScore);

        $totalCostToReplacement = $monthlySupportCost * $monthsUntilReplacement;
        $costEfficiencyRatio = $estimatedCurrentValue / max(1, $totalCostToReplacement);

        return [
            'current_value' => $estimatedCurrentValue,
            'replacement_cost' => $estimatedReplacementCost,
            'monthly_support_cost' => $monthlySupportCost,
            'total_cost_to_replacement' => $totalCostToReplacement,
            'cost_efficiency_ratio' => round($costEfficiencyRatio, 2),
            'recommendation' => $costEfficiencyRatio > 0.5 ? 'continue_using' : 'consider_replacement',
        ];
    }

    protected function generateRecommendations(Asset $asset, array $analysis): array
    {
        $recommendations = [];
        $stage = $analysis['lifecycle_stage']['stage'];
        $healthScore = $analysis['health_score']['overall_score'] ?? 100;
        $monthsToReplacement = $analysis['replacement_prediction']['months_until_replacement'] ?? 12;

        // Stage-based recommendations
        switch ($stage) {
            case 'new':
                $recommendations[] = [
                    'type' => 'optimization',
                    'priority' => 'low',
                    'action' => 'Configure optimal performance baselines',
                    'timeline' => 'next_30_days',
                ];
                break;

            case 'prime':
                $recommendations[] = [
                    'type' => 'maintenance',
                    'priority' => 'medium',
                    'action' => 'Schedule regular maintenance workflows',
                    'timeline' => 'ongoing',
                ];
                break;

            case 'mature':
                $recommendations[] = [
                    'type' => 'monitoring',
                    'priority' => 'medium',
                    'action' => 'Increase monitoring frequency for early issue detection',
                    'timeline' => 'immediate',
                ];
                break;

            case 'aging':
                $recommendations[] = [
                    'type' => 'replacement_planning',
                    'priority' => 'high',
                    'action' => 'Begin replacement planning and budgeting',
                    'timeline' => 'next_90_days',
                ];
                break;

            case 'end_of_life':
                $recommendations[] = [
                    'type' => 'replacement',
                    'priority' => 'critical',
                    'action' => 'Schedule immediate replacement',
                    'timeline' => 'next_30_days',
                ];
                break;
        }

        // Health-based recommendations
        if ($healthScore < 60) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'action' => 'Investigate performance issues and optimize system',
                'timeline' => 'next_7_days',
            ];
        }

        // Timeline-based recommendations
        if ($monthsToReplacement <= 6) {
            $recommendations[] = [
                'type' => 'procurement',
                'priority' => 'high',
                'action' => 'Initiate procurement process for replacement device',
                'timeline' => 'immediate',
            ];
        }

        return $recommendations;
    }

    // Additional helper methods (simplified for brevity)

    protected function generateTrendData(float $currentValue, int $days): array
    {
        // Simulate trend data with some variance
        $trend = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $variance = rand(-10, 10);
            $trend[$date] = max(0, min(100, $currentValue + $variance));
        }

        return $trend;
    }

    protected function calculateTotalFreeDisk(array $disks): float
    {
        return array_sum(array_column($disks, 'free_gb'));
    }

    protected function calculateTrend(float $value): string
    {
        // Simplified trend calculation
        return $value > 80 ? 'increasing' : ($value < 40 ? 'decreasing' : 'stable');
    }

    protected function scoreToGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    protected function getStageDescription(string $stage): string
    {
        return match ($stage) {
            'new' => 'Recently deployed, optimal performance expected',
            'prime' => 'Peak performance period, regular maintenance recommended',
            'mature' => 'Stable operation, increased monitoring advised',
            'aging' => 'Performance declining, replacement planning needed',
            'end_of_life' => 'Critical replacement required',
            default => 'Unknown lifecycle stage',
        };
    }

    protected function calculatePredictionConfidence(array $analysis): float
    {
        // Simplified confidence calculation based on data availability
        $factors = [
            'has_health_score' => isset($analysis['health_score']['overall_score']),
            'has_performance_data' => isset($analysis['performance_trends']),
            'has_age_data' => isset($analysis['lifecycle_stage']['age_years']),
        ];

        $availableFactors = array_sum($factors);

        return round(($availableFactors / count($factors)) * 100, 1);
    }

    protected function estimateCurrentValue(Asset $asset): float
    {
        // Simplified depreciation calculation
        $ageInYears = $asset->age_in_years ?? 0;
        $estimatedOriginalCost = 1000; // Would come from asset data
        $depreciationRate = 0.2; // 20% per year

        return max(100, $estimatedOriginalCost * pow(1 - $depreciationRate, $ageInYears));
    }

    protected function estimateReplacementCost(Asset $asset): float
    {
        // Simplified replacement cost estimation
        return match ($asset->type) {
            'Server' => 3000,
            'Laptop' => 1200,
            'Desktop' => 800,
            default => 1000,
        };
    }

    protected function estimateMonthlySupportCost(Asset $asset, float $healthScore): float
    {
        // Lower health score = higher support costs
        $baseCost = 50;
        $healthMultiplier = (100 - $healthScore) / 100;

        return $baseCost * (1 + $healthMultiplier);
    }

    protected function categorizeReplacementTimeframe(int $months): string
    {
        return match (true) {
            $months <= 3 => 'immediate',
            $months <= 6 => 'short_term',
            $months <= 12 => 'medium_term',
            default => 'long_term',
        };
    }

    // Placeholder methods for complex analytics (would be fully implemented)
    protected function generateDiskUsageTrend(Asset $asset, int $days): array
    {
        return [];
    }

    protected function generateUptimeTrend(Asset $asset, int $days): array
    {
        return [];
    }

    protected function generateErrorFrequencyTrend(Asset $asset, int $days): array
    {
        return [];
    }

    protected function analyzeTrends(array $trends): array
    {
        return ['overall_trend' => 'stable'];
    }

    protected function analyzeCurrentCapacity(array $performance, array $hardware): array
    {
        return [];
    }

    protected function analyzeUtilizationTrends(Asset $asset): array
    {
        return [];
    }

    protected function identifyBottlenecks(array $performance, array $hardware): array
    {
        return [];
    }

    protected function generateCapacityRecommendations(array $plan): array
    {
        return [];
    }

    protected function generateCapacityTimeline(array $plan): array
    {
        return [];
    }

    protected function identifyRiskFactors(Asset $asset, array $performance, array $hardware): array
    {
        return [];
    }

    protected function calculateFailureProbability(Asset $asset, array $performance, array $hardware): float
    {
        return 0.1;
    }

    protected function predictSpecificFailures(Asset $asset, array $performance, array $hardware): array
    {
        return [];
    }

    protected function suggestPreventiveActions(Asset $asset): array
    {
        return [];
    }

    protected function generateMonitoringRecommendations(Asset $asset): array
    {
        return [];
    }

    protected function getWarrantyStatus(Asset $asset): array
    {
        return ['status' => 'active', 'expires' => $asset->warranty_expire?->toDateString()];
    }

    protected function getSupportStatus(Asset $asset): array
    {
        return ['status' => 'active'];
    }

    protected function analyzeWarrantyCosts(Asset $asset): array
    {
        return ['annual_cost' => 200];
    }

    protected function generateWarrantyRecommendations(Asset $asset): array
    {
        return [];
    }

    protected function calculateDiskTrend(array $disks): string
    {
        return 'stable';
    }

    protected function calculateStabilityTrend(Asset $asset): string
    {
        return 'stable';
    }

    protected function calculateAverageDiskUsage(array $disks): float
    {
        return 50.0;
    }

    protected function getHealthRecommendations(array $scores): array
    {
        return [];
    }
}
