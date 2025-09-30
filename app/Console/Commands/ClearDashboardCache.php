<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DashboardLazyLoadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:clear-cache 
                            {--widget= : Clear cache for specific widget type}
                            {--company= : Clear cache for specific company}
                            {--client= : Clear cache for specific client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear dashboard widget cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $widget = $this->option('widget');
        $companyId = $this->option('company');
        $clientId = $this->option('client');

        if ($widget) {
            // Clear specific widget cache
            DashboardLazyLoadService::clearWidgetCache($widget, $companyId, $clientId);
            $this->info("Cache cleared for widget: {$widget}");
        } else {
            // Clear all dashboard cache
            $patterns = [
                'dashboard_widget_*',
                'kpi_grid_*',
                'revenue_chart_*',
                'ticket_chart_*',
                'dashboard_analytics_*',
            ];

            $totalCleared = 0;
            foreach ($patterns as $pattern) {
                $keys = Cache::getRedis()->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                    $totalCleared++;
                }
            }

            $this->info("Dashboard cache cleared! ({$totalCleared} keys removed)");
        }

        return Command::SUCCESS;
    }
}
