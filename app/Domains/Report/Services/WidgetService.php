<?php

namespace App\Domains\Report\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Widget Service
 * 
 * Reusable widget components for dashboards
 */
class WidgetService
{
    /**
     * Generate KPI widget data
     */
    public function getKPIWidget(array $config): array
    {
        $cacheKey = 'kpi_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $currentValue = $this->executeQuery($config['query'], $config['params'] ?? []);
            $previousValue = null;
            $change = null;
            $changePercent = null;

            // Calculate comparison if previous period query is provided
            if (isset($config['previous_query'])) {
                $previousValue = $this->executeQuery(
                    $config['previous_query'], 
                    $config['previous_params'] ?? []
                );
                
                if ($previousValue && $previousValue > 0) {
                    $change = $currentValue - $previousValue;
                    $changePercent = ($change / $previousValue) * 100;
                }
            }

            return [
                'type' => 'kpi',
                'title' => $config['title'],
                'value' => $currentValue,
                'previous_value' => $previousValue,
                'change' => $change,
                'change_percent' => $changePercent ? round($changePercent, 2) : null,
                'format' => $config['format'] ?? 'number', // number, currency, percentage
                'trend' => $this->determineTrend($changePercent, $config['good_direction'] ?? 'up'),
                'icon' => $config['icon'] ?? null,
                'color' => $config['color'] ?? 'primary',
                'target' => $config['target'] ?? null,
                'target_progress' => $config['target'] ? ($currentValue / $config['target']) * 100 : null,
            ];
        });
    }

    /**
     * Generate chart widget data
     */
    public function getChartWidget(array $config): array
    {
        $cacheKey = 'chart_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $data = $this->executeQuery($config['query'], $config['params'] ?? []);
            
            // Process data based on chart type
            $chartData = $this->processChartData($data, $config);

            return [
                'type' => 'chart',
                'chart_type' => $config['chart_type'], // line, bar, pie, doughnut, area
                'title' => $config['title'],
                'data' => $chartData,
                'options' => $config['options'] ?? [],
                'height' => $config['height'] ?? 300,
                'colors' => $config['colors'] ?? null,
            ];
        });
    }

    /**
     * Generate table widget data
     */
    public function getTableWidget(array $config): array
    {
        $cacheKey = 'table_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $query = $config['query'];
            $params = $config['params'] ?? [];
            
            // Add pagination if specified
            if (isset($config['paginate'])) {
                $query .= " LIMIT " . ($config['paginate']['offset'] ?? 0) . ", " . $config['paginate']['limit'];
            }

            $data = DB::select($query, $params);

            return [
                'type' => 'table',
                'title' => $config['title'],
                'columns' => $config['columns'],
                'data' => $data,
                'sortable' => $config['sortable'] ?? true,
                'searchable' => $config['searchable'] ?? false,
                'pagination' => $config['pagination'] ?? null,
                'actions' => $config['actions'] ?? [],
            ];
        });
    }

    /**
     * Generate gauge widget data
     */
    public function getGaugeWidget(array $config): array
    {
        $cacheKey = 'gauge_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $value = $this->executeQuery($config['query'], $config['params'] ?? []);
            $max = $config['max'] ?? 100;
            $percentage = ($value / $max) * 100;

            return [
                'type' => 'gauge',
                'title' => $config['title'],
                'value' => $value,
                'percentage' => round($percentage, 2),
                'max' => $max,
                'ranges' => $config['ranges'] ?? [
                    ['min' => 0, 'max' => 30, 'color' => '#dc3545', 'label' => 'Poor'],
                    ['min' => 30, 'max' => 70, 'color' => '#ffc107', 'label' => 'Fair'],
                    ['min' => 70, 'max' => 100, 'color' => '#28a745', 'label' => 'Good'],
                ],
                'format' => $config['format'] ?? 'number',
            ];
        });
    }

    /**
     * Generate stat list widget data
     */
    public function getStatListWidget(array $config): array
    {
        $cacheKey = 'stat_list_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $stats = [];
            
            foreach ($config['stats'] as $statConfig) {
                $value = $this->executeQuery($statConfig['query'], $statConfig['params'] ?? []);
                
                $stats[] = [
                    'label' => $statConfig['label'],
                    'value' => $value,
                    'format' => $statConfig['format'] ?? 'number',
                    'icon' => $statConfig['icon'] ?? null,
                    'color' => $statConfig['color'] ?? 'secondary',
                    'change' => $statConfig['change'] ?? null,
                ];
            }

            return [
                'type' => 'stat_list',
                'title' => $config['title'],
                'stats' => $stats,
                'layout' => $config['layout'] ?? 'vertical', // vertical, horizontal, grid
            ];
        });
    }

    /**
     * Generate progress widget data
     */
    public function getProgressWidget(array $config): array
    {
        $cacheKey = 'progress_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 300, function () use ($config) {
            $items = [];
            
            foreach ($config['items'] as $itemConfig) {
                $current = $this->executeQuery($itemConfig['current_query'], $itemConfig['params'] ?? []);
                $target = $itemConfig['target'] ?? $this->executeQuery($itemConfig['target_query'], $itemConfig['params'] ?? []);
                
                $percentage = $target > 0 ? ($current / $target) * 100 : 0;
                
                $items[] = [
                    'label' => $itemConfig['label'],
                    'current' => $current,
                    'target' => $target,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage, $itemConfig['thresholds'] ?? []),
                ];
            }

            return [
                'type' => 'progress',
                'title' => $config['title'],
                'items' => $items,
                'show_values' => $config['show_values'] ?? true,
                'show_percentages' => $config['show_percentages'] ?? true,
            ];
        });
    }

    /**
     * Generate alert widget data
     */
    public function getAlertWidget(array $config): array
    {
        $cacheKey = 'alert_widget:' . md5(serialize($config));
        
        return Cache::remember($cacheKey, $config['cache_minutes'] ?? 60, function () use ($config) {
            $alerts = [];
            
            foreach ($config['alerts'] as $alertConfig) {
                $value = $this->executeQuery($alertConfig['query'], $alertConfig['params'] ?? []);
                
                if ($this->shouldShowAlert($value, $alertConfig['condition'])) {
                    $alerts[] = [
                        'type' => $alertConfig['type'] ?? 'warning', // success, info, warning, danger
                        'title' => $alertConfig['title'],
                        'message' => $this->formatAlertMessage($alertConfig['message'], $value),
                        'action_url' => $alertConfig['action_url'] ?? null,
                        'action_text' => $alertConfig['action_text'] ?? 'View Details',
                        'priority' => $alertConfig['priority'] ?? 'medium',
                        'dismissible' => $alertConfig['dismissible'] ?? true,
                    ];
                }
            }

            return [
                'type' => 'alerts',
                'title' => $config['title'] ?? 'Alerts',
                'alerts' => $alerts,
                'max_display' => $config['max_display'] ?? 5,
            ];
        });
    }

    /**
     * Execute a SQL query and return the first result value
     */
    protected function executeQuery(string $query, array $params = [])
    {
        $result = DB::select($query, $params);
        
        if (empty($result)) {
            return 0;
        }

        $first = (array) $result[0];
        return reset($first); // Get first column value
    }

    /**
     * Process chart data based on chart type
     */
    protected function processChartData($data, array $config): array
    {
        if (empty($data)) {
            return ['labels' => [], 'datasets' => []];
        }

        $chartType = $config['chart_type'];
        $data = collect($data)->map(function ($item) {
            return (array) $item;
        });

        switch ($chartType) {
            case 'pie':
            case 'doughnut':
                return $this->processPieChartData($data, $config);
            
            case 'line':
            case 'area':
                return $this->processLineChartData($data, $config);
            
            case 'bar':
            case 'column':
                return $this->processBarChartData($data, $config);
            
            default:
                return $this->processGenericChartData($data, $config);
        }
    }

    /**
     * Process pie/doughnut chart data
     */
    protected function processPieChartData(Collection $data, array $config): array
    {
        $labels = $data->pluck(array_keys($data->first())[0])->toArray();
        $values = $data->pluck(array_keys($data->first())[1])->toArray();

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $values,
                'backgroundColor' => $config['colors'] ?? $this->generateColors(count($labels)),
            ]]
        ];
    }

    /**
     * Process line/area chart data
     */
    protected function processLineChartData(Collection $data, array $config): array
    {
        $keys = array_keys($data->first());
        $labels = $data->pluck($keys[0])->toArray();
        
        $datasets = [];
        for ($i = 1; $i < count($keys); $i++) {
            $datasets[] = [
                'label' => $config['series_labels'][$i-1] ?? $keys[$i],
                'data' => $data->pluck($keys[$i])->toArray(),
                'borderColor' => $config['colors'][$i-1] ?? $this->generateColors(1)[0],
                'backgroundColor' => $config['chart_type'] === 'area' 
                    ? $this->hexToRgba($config['colors'][$i-1] ?? $this->generateColors(1)[0], 0.2)
                    : 'transparent',
                'fill' => $config['chart_type'] === 'area',
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * Process bar chart data
     */
    protected function processBarChartData(Collection $data, array $config): array
    {
        return $this->processLineChartData($data, $config); // Same structure
    }

    /**
     * Process generic chart data
     */
    protected function processGenericChartData(Collection $data, array $config): array
    {
        return [
            'labels' => $data->pluck(array_keys($data->first())[0])->toArray(),
            'datasets' => [[
                'data' => $data->pluck(array_keys($data->first())[1])->toArray(),
            ]]
        ];
    }

    /**
     * Determine trend direction
     */
    protected function determineTrend(?float $changePercent, string $goodDirection = 'up'): ?string
    {
        if ($changePercent === null) {
            return null;
        }

        $isPositive = $changePercent > 0;
        
        if ($goodDirection === 'up') {
            return $isPositive ? 'positive' : 'negative';
        } else {
            return $isPositive ? 'negative' : 'positive';
        }
    }

    /**
     * Get progress color based on percentage and thresholds
     */
    protected function getProgressColor(float $percentage, array $thresholds = []): string
    {
        if (empty($thresholds)) {
            $thresholds = [
                ['min' => 0, 'max' => 50, 'color' => 'danger'],
                ['min' => 50, 'max' => 80, 'color' => 'warning'],
                ['min' => 80, 'max' => 100, 'color' => 'success'],
            ];
        }

        foreach ($thresholds as $threshold) {
            if ($percentage >= $threshold['min'] && $percentage < $threshold['max']) {
                return $threshold['color'];
            }
        }

        return 'primary';
    }

    /**
     * Check if alert should be shown based on condition
     */
    protected function shouldShowAlert($value, array $condition): bool
    {
        $operator = $condition['operator'] ?? '>';
        $threshold = $condition['threshold'] ?? 0;

        switch ($operator) {
            case '>':
                return $value > $threshold;
            case '>=':
                return $value >= $threshold;
            case '<':
                return $value < $threshold;
            case '<=':
                return $value <= $threshold;
            case '=':
            case '==':
                return $value == $threshold;
            case '!=':
                return $value != $threshold;
            default:
                return false;
        }
    }

    /**
     * Format alert message with value placeholder
     */
    protected function formatAlertMessage(string $message, $value): string
    {
        return str_replace('{value}', $value, $message);
    }

    /**
     * Generate color palette
     */
    protected function generateColors(int $count): array
    {
        $baseColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6B7280'
        ];

        if ($count <= count($baseColors)) {
            return array_slice($baseColors, 0, $count);
        }

        // Generate additional colors if needed
        $colors = $baseColors;
        for ($i = count($baseColors); $i < $count; $i++) {
            $colors[] = $this->generateRandomColor();
        }

        return $colors;
    }

    /**
     * Generate a random color
     */
    protected function generateRandomColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    /**
     * Convert hex color to rgba
     */
    protected function hexToRgba(string $hex, float $alpha = 1): string
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }
}