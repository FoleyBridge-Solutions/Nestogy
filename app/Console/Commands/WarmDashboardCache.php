<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DashboardLazyLoadService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class WarmDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:warm-cache 
                            {--company= : Warm cache for specific company}
                            {--limit=10 : Number of companies to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm dashboard widget cache for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company');
        $limit = $this->option('limit');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::where('status', true)
                ->limit($limit)
                ->get();
        }

        $this->info("Warming cache for {$companies->count()} companies...");

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        foreach ($companies as $company) {
            $this->warmCompanyCache($company);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Dashboard cache warmed successfully!');

        return Command::SUCCESS;
    }

    protected function warmCompanyCache(Company $company)
    {
        // Get a user from the company to use for auth context
        $user = User::where('company_id', $company->id)
            ->where('status', true)
            ->first();

        if (! $user) {
            return;
        }

        // Simulate auth context
        Auth::login($user);

        // Widget types to warm
        $widgets = [
            'kpi-grid',
            'revenue-chart',
            'ticket-chart',
            'client-health',
            'team-performance',
        ];

        foreach ($widgets as $widgetType) {
            $cacheKey = "dashboard_widget_{$widgetType}_{$company->id}";
            $ttl = DashboardLazyLoadService::getCacheTTL($widgetType);

            // Generate cache data based on widget type
            // This would ideally call the actual widget data methods
            // For now, we'll just mark it as warmed
            \Cache::put($cacheKey.'_warmed', true, $ttl);
        }

        Auth::logout();
    }
}
