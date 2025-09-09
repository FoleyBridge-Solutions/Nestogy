<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\TaxEngine\TaxEngineRouter;
use App\Models\ServiceTaxRate;
use App\Models\TaxProfile;

/**
 * Tax System Health Check Command
 *
 * Performs comprehensive health checks on the tax calculation system
 * and reports on performance, errors, and potential issues.
 */
class TaxSystemHealthCheck extends Command
{
    private const DEFAULT_BATCH_SIZE = 100;

    // Class constants to reduce duplication
    private const MSG_HEALTH_CHECK_START = '🏥 Starting Tax System Health Check...';
    private const MSG_DB_OK = '✅ Database connectivity: OK';
    private const MSG_DB_FAIL = '❌ Database connectivity: FAILED';
    
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tax:health-check
                            {--company= : Specific company ID to check}
                            {--detailed : Show detailed information}
                            {--fix : Attempt to fix issues automatically}';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive health check on the tax calculation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Starting Tax System Health Check...');
        $this->newLine();

        $companyId = $this->option('company');
        $detailed = $this->option('detailed');
        $autoFix = $this->option('fix');

        $issues = [];
        $warnings = [];

        // Check database connectivity and basic setup
        $issues = array_merge($issues, $this->checkDatabaseSetup($companyId));

        // Check tax profiles configuration
        $issues = array_merge($issues, $this->checkTaxProfiles($companyId));

        // Check tax rates configuration
        $issues = array_merge($issues, $this->checkTaxRates($companyId));

        // Check cache performance
        $warnings = array_merge($warnings, $this->checkCachePerformance());

        // Check calculation performance
        $warnings = array_merge($warnings, $this->checkCalculationPerformance());

        // Check for error patterns
        $issues = array_merge($issues, $this->checkErrorPatterns());

        // Test actual tax calculations
        $issues = array_merge($issues, $this->testTaxCalculations($companyId));

        // Attempt fixes if requested
        if ($autoFix && !empty($issues)) {
            $this->attemptFixes($issues);
        }

        // Display results
        $this->displayResults($issues, $warnings, $detailed);

        // Return appropriate exit code
        return empty($issues) ? 0 : 1;
    }

    /**
     * Check database setup and connectivity
     */
    protected function checkDatabaseSetup(?int $companyId): array
    {
        $issues = [];

        try {
            // Test database connectivity
            DB::connection()->getPdo();
            $this->info('✅ Database connectivity: OK');

            // Check required tables
            $requiredTables = [
                'tax_profiles',
                'service_tax_rates',
                'tax_jurisdictions',
                'tax_calculations',
                'tax_categories'
            ];

            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $issues[] = [
                        'type' => 'database',
                        'severity' => 'critical',
                        'message' => "Required table '{$table}' does not exist",
                        'fixable' => false,
                    ];
                }
            }

            if (empty($issues)) {
                $this->info('✅ Required tables: OK');
            }

        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'database',
                'severity' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'fixable' => false,
            ];
        }

        return $issues;
    }

    /**
     * Check tax profiles configuration
     */
    protected function checkTaxProfiles(?int $companyId): array
    {
        $issues = [];

        $query = TaxProfile::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $profileCount = $query->count();
        $activeProfileCount = $query->where('is_active', true)->count();

        if ($profileCount === 0) {
            $issues[] = [
                'type' => 'tax_profiles',
                'severity' => 'warning',
                'message' => 'No tax profiles configured',
                'fixable' => true,
                'fix_method' => 'create_default_profiles',
            ];
        } elseif ($activeProfileCount === 0) {
            $issues[] = [
                'type' => 'tax_profiles',
                'severity' => 'critical',
                'message' => 'No active tax profiles found',
                'fixable' => true,
                'fix_method' => 'activate_default_profile',
            ];
        } else {
            $this->info("✅ Tax profiles: {$activeProfileCount} active profiles");
        }

        // Check for profiles with missing required fields
        $profilesWithIssues = $query->where('is_active', true)
            ->get()
            ->filter(function ($profile) {
                return empty($profile->required_fields) && $profile->profile_type !== 'general';
            });

        if ($profilesWithIssues->count() > 0) {
            $issues[] = [
                'type' => 'tax_profiles',
                'severity' => 'warning',
                'message' => "Found {$profilesWithIssues->count()} active profiles with missing field definitions",
                'fixable' => false,
            ];
        }

        return $issues;
    }

    /**
     * Check tax rates configuration
     */
    protected function checkTaxRates(?int $companyId): array
    {
        $issues = [];

        $query = ServiceTaxRate::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $rateCount = $query->count();
        $activeRateCount = $query->active()->count();

        if ($rateCount === 0) {
            $issues[] = [
                'type' => 'tax_rates',
                'severity' => 'warning',
                'message' => 'No tax rates configured',
                'fixable' => true,
                'fix_method' => 'initialize_default_rates',
            ];
        } elseif ($activeRateCount === 0) {
            $issues[] = [
                'type' => 'tax_rates',
                'severity' => 'critical',
                'message' => 'No active tax rates found',
                'fixable' => false,
            ];
        } else {
            $this->info("✅ Tax rates: {$activeRateCount} active rates");
        }

        // Check for rates with invalid configurations
        $invalidRates = $query->active()
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('rate_type', 'percentage')
                         ->where('percentage_rate', '<=', 0);
                })->orWhere(function ($subQ) {
                    $subQ->where('rate_type', 'fixed')
                         ->where('fixed_amount', '<=', 0);
                });
            })->count();

        if ($invalidRates > 0) {
            $issues[] = [
                'type' => 'tax_rates',
                'severity' => 'warning',
                'message' => "Found {$invalidRates} tax rates with invalid rate values",
                'fixable' => false,
            ];
        }

        return $issues;
    }

    /**
     * Check cache performance
     */
    protected function checkCachePerformance(): array
    {
        $warnings = [];

        try {
            // Test cache functionality
            $testKey = 'tax_health_check_' . time();
            Cache::put($testKey, 'test_value', 60);
            $cachedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($cachedValue !== 'test_value') {
                $warnings[] = [
                    'type' => 'cache',
                    'severity' => 'warning',
                    'message' => 'Cache not functioning properly',
                ];
            } else {
                $this->info('✅ Cache functionality: OK');
            }

            // Check cache statistics if available
            $cacheStats = Cache::get('tax_metrics:*', []);
            if (empty($cacheStats)) {
                $this->info('ℹ️  No cache performance metrics available yet');
            }

        } catch (\Exception $e) {
            $warnings[] = [
                'type' => 'cache',
                'severity' => 'warning',
                'message' => 'Cache error: ' . $e->getMessage(),
            ];
        }

        return $warnings;
    }

    /**
     * Check calculation performance
     */
    protected function checkCalculationPerformance(): array
    {
        $warnings = [];

        // Check recent calculation performance from database
        $recentCalculations = DB::table('tax_calculations')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('
                AVG(calculation_time_ms) as avg_time,
                MAX(calculation_time_ms) as max_time,
                COUNT(*) as total_calculations,
                SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as error_count
            ')
            ->first();

        if ($recentCalculations && $recentCalculations->total_calculations > 0) {
            $avgTime = $recentCalculations->avg_time;
            $maxTime = $recentCalculations->max_time;
            $errorRate = ($recentCalculations->error_count / $recentCalculations->total_calculations) * 100;

            if ($avgTime > 1000) { // 1 second average
                $warnings[] = [
                    'type' => 'performance',
                    'severity' => 'warning',
                    'message' => "Average calculation time is high: {$avgTime}ms",
                ];
            }

            if ($maxTime > 10000) { // 10 seconds max
                $warnings[] = [
                    'type' => 'performance',
                    'severity' => 'warning',
                    'message' => "Maximum calculation time is very high: {$maxTime}ms",
                ];
            }

            if ($errorRate > 5) { // 5% error rate
                $warnings[] = [
                    'type' => 'performance',
                    'severity' => 'warning',
                    'message' => "High error rate: {$errorRate}%",
                ];
            }

            if (empty($warnings)) {
                $this->info("✅ Performance: {$recentCalculations->total_calculations} calculations, avg {$avgTime}ms");
            }
        } else {
            $this->info('ℹ️  No recent calculation data available for performance analysis');
        }

        return $warnings;
    }

    /**
     * Check for error patterns
     */
    protected function checkErrorPatterns(): array
    {
        $issues = [];

        // Check for critical error patterns in cache
        $alertKeys = Cache::store()->getKeys('tax_alert:*') ?? [];
        $recentAlerts = collect($alertKeys)
            ->map(fn($key) => Cache::get($key))
            ->filter()
            ->filter(function ($alert) {
                return isset($alert['timestamp']) &&
                       \Carbon\Carbon::parse($alert['timestamp'])->isAfter(now()->subHours(24));
            });

        if ($recentAlerts->count() > 0) {
            $criticalAlerts = $recentAlerts->where('type', 'critical_error_pattern')->count();
            $performanceAlerts = $recentAlerts->where('type', 'critical_performance')->count();

            if ($criticalAlerts > 0) {
                $issues[] = [
                    'type' => 'errors',
                    'severity' => 'critical',
                    'message' => "Found {$criticalAlerts} critical error alerts in the last 24 hours",
                    'fixable' => false,
                ];
            }

            if ($performanceAlerts > 0) {
                $issues[] = [
                    'type' => 'errors',
                    'severity' => 'warning',
                    'message' => "Found {$performanceAlerts} performance alerts in the last 24 hours",
                    'fixable' => false,
                ];
            }
        }

        return $issues;
    }

    /**
     * Test actual tax calculations
     */
    protected function testTaxCalculations(?int $companyId): array
    {
        $issues = [];

        try {
            $testCompanyId = $companyId ?? 1;
            $taxEngine = new TaxEngineRouter($testCompanyId);

            // Test basic calculation
            $testParams = [
                'base_price' => 100.00,
                'quantity' => 1,
                'category_type' => 'general',
                'customer_address' => [
                    'state' => 'CA',
                    'city' => 'San Francisco',
                    'zip' => '94102',
                    'country' => 'US',
                ],
            ];

            $startTime = microtime(true);
            $result = $taxEngine->calculateTaxes($testParams);
            $endTime = microtime(true);

            $calculationTime = ($endTime - $startTime) * 1000;

            if (!isset($result['final_amount'])) {
                $issues[] = [
                    'type' => 'calculation',
                    'severity' => 'critical',
                    'message' => 'Tax calculation test failed - no result returned',
                    'fixable' => false,
                ];
            } elseif ($calculationTime > 5000) {
                $issues[] = [
                    'type' => 'calculation',
                    'severity' => 'warning',
                    'message' => "Tax calculation test was slow: {$calculationTime}ms",
                    'fixable' => false,
                ];
            } else {
                $this->info("✅ Tax calculation test: OK ({$calculationTime}ms)");
            }

        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'calculation',
                'severity' => 'critical',
                'message' => 'Tax calculation test failed: ' . $e->getMessage(),
                'fixable' => false,
            ];
        }

        return $issues;
    }

    /**
     * Attempt to fix issues automatically
     */
    protected function attemptFixes(array $issues): void
    {
        $this->info('🔧 Attempting to fix issues automatically...');

        foreach ($issues as $issue) {
            if (!($issue['fixable'] ?? false)) {
                continue;
            }

            switch ($issue['fix_method'] ?? null) {
                case 'create_default_profiles':
                    $this->createDefaultProfiles();
                    break;

                case 'activate_default_profile':
                    $this->activateDefaultProfile();
                    break;

                case 'initialize_default_rates':
                    $this->initializeDefaultRates();
                    break;
                default:
        // No action needed
        break;
}
        }
    }

    /**
     * Create default tax profiles
     */
    protected function createDefaultProfiles(): void
    {
        $this->info('Creating default tax profiles...');

        $companies = DB::table('users')
            ->select('company_id')
            ->distinct()
            ->whereNotNull('company_id')
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            TaxProfile::createDefaultProfiles($companyId);
        }

        $this->info('✅ Default tax profiles created');
    }

    /**
     * Activate default profile
     */
    protected function activateDefaultProfile(): void
    {
        $this->info('Activating default tax profiles...');

        TaxProfile::where('profile_type', TaxProfile::TYPE_GENERAL)
            ->update(['is_active' => true]);

        $this->info('✅ Default tax profiles activated');
    }

    /**
     * Initialize default rates (placeholder)
     */
    protected function initializeDefaultRates(): void
    {
        $this->info('Default rate initialization would be implemented based on business requirements');
    }

    /**
     * Display health check results
     */
    protected function displayResults(array $issues, array $warnings, bool $detailed): void
    {
        $this->newLine();

        if (empty($issues) && empty($warnings)) {
            $this->info('🎉 Tax system health check completed successfully - no issues found!');
            return;
        }

        if (!empty($issues)) {
            $this->error('❌ Issues found:');
            foreach ($issues as $issue) {
                $severity = $issue['severity'] === 'critical' ? '🔴' : '🟡';
                $fixable = ($issue['fixable'] ?? false) ? ' (fixable)' : '';
                $this->line("  {$severity} {$issue['message']}{$fixable}");

                if ($detailed && isset($issue['details'])) {
                    $this->line("     Details: {$issue['details']}");
                }
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line("  🟡 {$warning['message']}");
            }
            $this->newLine();
        }

        $totalIssues = count($issues);
        $totalWarnings = count($warnings);
        $this->info("Summary: {$totalIssues} issues, {$totalWarnings} warnings");

        if ($totalIssues > 0) {
            $this->line('Run with --fix to attempt automatic fixes where possible');
        }
    }
}
