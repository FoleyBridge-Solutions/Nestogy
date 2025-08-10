<?php

namespace App\Console\Commands\VoipTax;

use App\Services\VoIPTaxScheduledReportService;
use Illuminate\Console\Command;

/**
 * Cleanup Old VoIP Tax Reports
 * 
 * Artisan command to cleanup old tax reports based on retention policy.
 */
class CleanupReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voip-tax:cleanup-reports 
                            {--retention-days=90 : Number of days to retain reports}
                            {--dry-run : Run without actually deleting files}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old VoIP tax reports based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting VoIP Tax Reports Cleanup...');

        try {
            $retentionDays = (int) $this->option('retention-days');
            $dryRun = $this->option('dry-run');
            $force = $this->option('force');

            $this->info("Retention period: {$retentionDays} days");

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No files will be actually deleted');
            }

            if (!$force && !$dryRun) {
                if (!$this->confirm("Are you sure you want to delete reports older than {$retentionDays} days?")) {
                    $this->info('Cleanup cancelled by user.');
                    return Command::SUCCESS;
                }
            }

            $reportService = new VoIPTaxScheduledReportService([
                'retention_days' => $retentionDays,
            ]);

            if ($dryRun) {
                $this->info('Would cleanup old reports based on retention policy.');
                return Command::SUCCESS;
            }

            // Perform cleanup
            $results = $reportService->cleanupOldReports();

            $this->displayCleanupResults($results, $retentionDays);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to cleanup reports: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display cleanup results.
     */
    protected function displayCleanupResults(array $results, int $retentionDays): void
    {
        $this->line('');
        $this->info('Cleanup Results:');
        $this->line("Files deleted: {$results['files_deleted']}");
        $this->line('Total size freed: ' . $this->formatBytes($results['total_size_freed']));

        if ($results['files_deleted'] > 0) {
            $this->line('');
            $this->line('Deleted files (showing first 10):');
            
            foreach (array_slice($results['deleted_files'], 0, 10) as $file) {
                $this->line("  - {$file['file']} ({$this->formatBytes($file['size'])}, last modified: {$file['last_modified']})");
            }

            if (count($results['deleted_files']) > 10) {
                $remaining = count($results['deleted_files']) - 10;
                $this->line("  ... and {$remaining} more files");
            }

            $this->info("Successfully cleaned up {$results['files_deleted']} old tax reports.");
        } else {
            $this->info('No old reports found to cleanup.');
        }
    }

    /**
     * Format bytes into human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}