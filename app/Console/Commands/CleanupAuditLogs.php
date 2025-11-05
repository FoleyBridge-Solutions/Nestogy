<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\AuditLog;
use App\Helpers\ConfigHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--company= : Cleanup for specific company ID} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit logs based on retention policy settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting audit log cleanup...');

        $dryRun = $this->option('dry-run');
        $specificCompanyId = $this->option('company');

        if ($specificCompanyId) {
            $companies = Company::where('id', $specificCompanyId)->get();
            if ($companies->isEmpty()) {
                $this->error("Company with ID {$specificCompanyId} not found.");
                return 1;
            }
        } else {
            $companies = Company::all();
        }

        $totalDeleted = 0;

        foreach ($companies as $company) {
            $deleted = $this->cleanupCompanyLogs($company, $dryRun);
            $totalDeleted += $deleted;
        }

        if ($dryRun) {
            $this->info("Dry run completed. Would delete {$totalDeleted} audit logs.");
        } else {
            $this->info("Cleanup completed. Deleted {$totalDeleted} audit logs.");
        }

        return 0;
    }

    /**
     * Cleanup audit logs for a specific company
     */
    protected function cleanupCompanyLogs(Company $company, bool $dryRun): int
    {
        // Get retention days from settings
        $retentionDays = ConfigHelper::securitySetting(
            $company->id,
            'audit',
            'audit_retention_days',
            365
        );

        $cutoffDate = now()->subDays($retentionDays);

        $this->line("Processing Company: {$company->name} (ID: {$company->id})");
        $this->line("  Retention policy: {$retentionDays} days");
        $this->line("  Cutoff date: {$cutoffDate->toDateTimeString()}");

        // Count logs to be deleted
        $query = AuditLog::where('company_id', $company->id)
            ->where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->line("  No logs to cleanup.");
            return 0;
        }

        $this->line("  Found {$count} logs to cleanup.");

        if (!$dryRun) {
            // Delete in chunks to avoid memory issues
            $deleted = 0;
            do {
                $chunkDeleted = DB::table('audit_logs')
                    ->where('company_id', $company->id)
                    ->where('created_at', '<', $cutoffDate)
                    ->limit(1000)
                    ->delete();

                $deleted += $chunkDeleted;

                if ($chunkDeleted > 0) {
                    $this->line("  Deleted {$deleted}/{$count} logs...");
                }
            } while ($chunkDeleted > 0);

            $this->info("  ✓ Deleted {$deleted} logs for {$company->name}");
        }

        return $count;
    }
}
