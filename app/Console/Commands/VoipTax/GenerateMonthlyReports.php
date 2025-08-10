<?php

namespace App\Console\Commands\VoipTax;

use App\Services\VoIPTaxScheduledReportService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Generate Monthly VoIP Tax Reports
 * 
 * Artisan command to generate monthly compliance reports for all companies.
 */
class GenerateMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voip-tax:generate-monthly-reports 
                            {--month= : Specific month to generate reports for (YYYY-MM format)}
                            {--company= : Specific company ID to generate report for}
                            {--dry-run : Run without actually generating reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly VoIP tax compliance reports for all companies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting VoIP Tax Monthly Report Generation...');
        
        try {
            // Parse month parameter
            $month = $this->option('month') 
                ? Carbon::createFromFormat('Y-m', $this->option('month'))
                : Carbon::now()->subMonth();

            $companyId = $this->option('company');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No reports will be actually generated');
            }

            $this->info("Generating reports for: {$month->format('F Y')}");

            if ($companyId) {
                $this->info("Filtering to company ID: {$companyId}");
            }

            $reportService = new VoIPTaxScheduledReportService();
            
            if ($dryRun) {
                $this->info('Would generate monthly reports for specified period.');
                return Command::SUCCESS;
            }

            // Generate reports
            $results = $reportService->generateMonthlyComplianceReports($month);

            // Filter results if specific company requested
            if ($companyId) {
                $results = array_filter($results, fn($key) => $key == $companyId, ARRAY_FILTER_USE_KEY);
            }

            // Display results
            $this->displayResults($results, $month);

            $successCount = count(array_filter($results, fn($result) => $result['success'] ?? false));
            $totalCount = count($results);

            $this->info("Completed: {$successCount}/{$totalCount} reports generated successfully");

            return $successCount === $totalCount ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Failed to generate monthly reports: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Display results in a formatted table.
     */
    protected function displayResults(array $results, Carbon $month): void
    {
        if (empty($results)) {
            $this->warn('No results to display');
            return;
        }

        $tableData = [];
        foreach ($results as $companyId => $result) {
            if ($result['success']) {
                $tableData[] = [
                    'Company ID' => $companyId,
                    'Status' => '✅ Success',
                    'Tax Collected' => '$' . number_format($result['report_summary']['total_tax_collected'], 2),
                    'Invoices' => $result['report_summary']['invoice_count'],
                    'Compliance Score' => $result['report_summary']['compliance_score'] . '%',
                    'Action Items' => $result['report_summary']['action_items_count'],
                    'File' => $result['filename'],
                ];
            } else {
                $tableData[] = [
                    'Company ID' => $companyId,
                    'Status' => '❌ Failed',
                    'Tax Collected' => 'N/A',
                    'Invoices' => 'N/A',
                    'Compliance Score' => 'N/A',
                    'Action Items' => 'N/A',
                    'File' => 'Error: ' . ($result['error'] ?? 'Unknown'),
                ];
            }
        }

        $this->table([
            'Company ID', 'Status', 'Tax Collected', 'Invoices', 
            'Compliance Score', 'Action Items', 'File'
        ], $tableData);
    }
}