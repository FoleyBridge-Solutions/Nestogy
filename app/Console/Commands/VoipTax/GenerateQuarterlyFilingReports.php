<?php

namespace App\Console\Commands\VoipTax;

use App\Services\VoIPTaxScheduledReportService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Generate Quarterly VoIP Tax Filing Reports
 *
 * Artisan command to generate quarterly tax filing reports for regulatory compliance.
 */
class GenerateQuarterlyFilingReports extends Command
{
    private const DEFAULT_TIMEOUT = 30;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voip-tax:generate-quarterly-filing-reports
                            {--quarter= : Specific quarter to generate reports for (YYYY-Q format, e.g., 2024-1)}
                            {--company= : Specific company ID to generate report for}
                            {--dry-run : Run without actually generating reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate quarterly VoIP tax filing reports for regulatory compliance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting VoIP Tax Quarterly Filing Report Generation...');

        try {
            // Parse quarter parameter
            $quarter = $this->parseQuarterParameter();
            $companyId = $this->option('company');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No reports will be actually generated');
            }

            $this->info("Generating filing reports for: Q{$quarter->quarter} {$quarter->year}");
            $this->info("Period: {$quarter->startOfQuarter()->format('M j')} - {$quarter->endOfQuarter()->format('M j, Y')}");

            if ($companyId) {
                $this->info("Filtering to company ID: {$companyId}");
            }

            $reportService = new VoIPTaxScheduledReportService();

            if ($dryRun) {
                $this->info('Would generate quarterly filing reports for specified period.');
                return Command::SUCCESS;
            }

            // Generate quarterly filing reports
            $results = $reportService->generateQuarterlyFilingReports($quarter);

            // Filter results if specific company requested
            if ($companyId) {
                $results = array_filter($results, fn($key) => $key == $companyId, ARRAY_FILTER_USE_KEY);
            }

            // Display results
            $this->displayFilingReports($results, $quarter);

            $successCount = count(array_filter($results, fn($result) => $result['success'] ?? false));
            $totalCount = count($results);

            $this->info("Completed: {$successCount}/{$totalCount} quarterly filing reports generated successfully");

            return $successCount === $totalCount ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Failed to generate quarterly filing reports: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Parse quarter parameter from command line.
     */
    protected function parseQuarterParameter(): Carbon
    {
        $quarterParam = $this->option('quarter');

        if ($quarterParam) {
            // Parse YYYY-Q format (e.g., "2024-1")
            if (preg_match('/^(\d{4})-([1-4])$/', $quarterParam, $matches)) {
                $year = (int)$matches[1];
                $quarterNum = (int)$matches[2];

                // Create Carbon instance for the start of the specified quarter
                return Carbon::create($year)->quarter($quarterNum);
            } else {
                throw new \InvalidArgumentException('Quarter must be in YYYY-Q format (e.g., 2024-1)');
            }
        }

        // Default to previous quarter
        return Carbon::now()->subQuarter();
    }

    /**
     * Display filing reports in a formatted way.
     */
    protected function displayFilingReports(array $results, Carbon $quarter): void
    {
        if (empty($results)) {
            $this->warn('No filing reports to display');
            return;
        }

        foreach ($results as $companyId => $result) {
            $this->line('');
            $this->line("<fg=cyan>═══ Company ID: {$companyId} ═══</>");

            if (!$result['success']) {
                $this->error("❌ Failed: {$result['error']}");
                continue;
            }

            $this->info("✅ Successfully generated filing reports for Q{$result['quarter']}");

            if (empty($result['filing_reports'])) {
                $this->warn('   No jurisdictions with quarterly filing requirements found');
                continue;
            }

            // Display each jurisdiction's filing report
            foreach ($result['filing_reports'] as $jurisdictionId => $filingReport) {
                $this->line('');
                $this->line("  <fg=yellow>Jurisdiction: {$filingReport['jurisdiction']}</>");
                $this->line("  Authority: {$filingReport['authority']}");
                $this->line("  Filing Due Date: <fg=red>{$filingReport['filing_due_date']}</>");

                // Display tax collection summary
                $taxCollected = '$' . number_format($filingReport['tax_collected'], 2);
                $this->line("  Tax Collected: <fg=green>{$taxCollected}</>");

                // Display return data
                $returnData = $filingReport['return_data'];
                $this->line('');
                $this->line('  <fg=cyan>Filing Data Summary:</>');
                $this->line('    Gross Receipts: $' . number_format($returnData['gross_receipts'], 2));
                $this->line('    Taxable Receipts: $' . number_format($returnData['taxable_receipts'], 2));
                $this->line('    Tax Due: $' . number_format($returnData['tax_due'], 2));
                $this->line('    Exemptions Claimed: $' . number_format($returnData['exemptions_claimed'], 2));
                $this->line('    Net Tax Due: $' . number_format($returnData['net_tax_due'], 2));

                // Display required forms
                if (!empty($filingReport['forms_required'])) {
                    $this->line('');
                    $this->line('  <fg=magenta>Required Forms:</> ' . implode(', ', $filingReport['forms_required']));
                }

                // Calculate days until due
                $dueDate = Carbon::parse($filingReport['filing_due_date']);
                $daysUntilDue = now()->diffInDays($dueDate, false);

                if ($daysUntilDue < 0) {
                    $this->line("  <fg=red>⚠️ OVERDUE by " . abs($daysUntilDue) . " days!</>");
                } elseif ($daysUntilDue <= 7) {
                    $this->line("  <fg=yellow>⚡ Due in {$daysUntilDue} days - file soon!</>");
                } else {
                    $this->line("  <fg=green>✓ Due in {$daysUntilDue} days</>");
                }
            }
        }

        // Display summary statistics
        $this->displaySummaryStatistics($results, $quarter);
    }

    /**
     * Display summary statistics for all filing reports.
     */
    protected function displaySummaryStatistics(array $results, Carbon $quarter): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══ QUARTERLY FILING SUMMARY ═══</>');

        $totalJurisdictions = 0;
        $totalTaxCollected = 0;
        $overdueFilings = 0;
        $upcomingFilings = 0;

        foreach ($results as $result) {
            if (!$result['success'] || empty($result['filing_reports'])) {
                continue;
            }

            foreach ($result['filing_reports'] as $filingReport) {
                $totalJurisdictions++;
                $totalTaxCollected += $filingReport['tax_collected'];

                $dueDate = Carbon::parse($filingReport['filing_due_date']);
                $daysUntilDue = now()->diffInDays($dueDate, false);

                if ($daysUntilDue < 0) {
                    $overdueFilings++;
                } elseif ($daysUntilDue <= 30) {
                    $upcomingFilings++;
                }
            }
        }

        $this->line("Quarter: Q{$quarter->quarter} {$quarter->year}");
        $this->line("Total Jurisdictions: {$totalJurisdictions}");
        $this->line('Total Tax Collected: $' . number_format($totalTaxCollected, 2));

        if ($overdueFilings > 0) {
            $this->line("<fg=red>Overdue Filings: {$overdueFilings}</>");
        }

        if ($upcomingFilings > 0) {
            $this->line("<fg=yellow>Upcoming Filings (30 days): {$upcomingFilings}</>");
        }

        if ($overdueFilings === 0 && $upcomingFilings === 0) {
            $this->line('<fg=green>All filings are current!</>');
        }
    }
}
