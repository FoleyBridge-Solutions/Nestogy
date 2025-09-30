<?php

namespace App\Console\Commands;

use App\Domains\Contract\Services\ContractLifecycleService;
use App\Mail\ContractRenewalSummary;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ProcessContractRenewals Command
 *
 * Daily job to process auto-renewals and send renewal notifications.
 * Critical for MSP revenue protection and contract lifecycle management.
 */
class ProcessContractRenewals extends Command
{
    private const DEFAULT_TIMEOUT = 30;

    // Class constants to reduce duplication
    private const STATUS_ACTIVE = 'active';

    private const STATUS_RENEWED = 'renewed';

    private const DEFAULT_DAYS_AHEAD = 30;

    private const MSG_RENEWAL_START = 'Processing contract renewals...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:process-renewals
                            {--company= : Process for specific company ID}
                            {--dry-run : Run without making changes}
                            {--notification-days=90,60,self::DEFAULT_TIMEOUT : Comma-separated days before expiry for notifications}
                            {--force : Process even if already run today}
                            {--verbose-logging : Enable detailed logging}
                            {--email-summary-to= : Email address to send processing summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process contract auto-renewals, price escalations, and send renewal notifications';

    /**
     * The contract lifecycle service.
     */
    protected ContractLifecycleService $contractService;

    /**
     * Tracking for detailed logging
     */
    protected array $processingLog = [];

    /**
     * Create a new command instance.
     */
    public function __construct(ContractLifecycleService $contractService)
    {
        parent::__construct();
        $this->contractService = $contractService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Starting contract renewal processing at '.Carbon::now()->toDateTimeString());

        $isDryRun = $this->option('dry-run');
        $companyId = $this->option('company');
        $forceRun = $this->option('force');
        $verboseLogging = $this->option('verbose-logging');
        $emailSummaryTo = $this->option('email-summary-to');
        $notificationDays = explode(',', $this->option('notification-days'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Check if already run today (unless forced)
        if (! $forceRun && ! $isDryRun) {
            $lastRun = DB::table('job_runs')
                ->where('job_name', 'process_contract_renewals')
                ->whereDate('run_date', Carbon::today())
                ->first();

            if ($lastRun) {
                $this->info('Contract renewals already processed today. Use --force to run again.');

                return Command::SUCCESS;
            }
        }

        try {
            // Process renewals for specific company or all
            if ($companyId) {
                $companies = Company::where('id', $companyId)->get();
                if ($companies->isEmpty()) {
                    $this->error("Company with ID {$companyId} not found.");

                    return Command::FAILURE;
                }
            } else {
                $companies = Company::where('is_active', true)->get();
            }

            $this->info("Processing {$companies->count()} companies...\n");

            $totalResults = [
                'companies_processed' => 0,
                'contracts_checked' => 0,
                'renewed' => 0,
                'escalated' => 0,
                'failed' => 0,
                'notifications_90' => 0,
                'notifications_60' => 0,
                'notifications_30' => 0,
                'revenue_impact' => 0,
                'errors' => [],
            ];

            foreach ($companies as $company) {
                $this->info("Processing company: {$company->name} (ID: {$company->id})");
                $companyResults = $this->processCompany($company, $isDryRun, $notificationDays, $verboseLogging);

                // Aggregate results
                $totalResults['companies_processed']++;
                $totalResults['contracts_checked'] += $companyResults['contracts_checked'];
                $totalResults['renewed'] += $companyResults['renewed'];
                $totalResults['escalated'] += $companyResults['escalated'];
                $totalResults['failed'] += $companyResults['failed'];
                $totalResults['notifications_90'] += $companyResults['notifications_90'];
                $totalResults['notifications_60'] += $companyResults['notifications_60'];
                $totalResults['notifications_30'] += $companyResults['notifications_30'];
                $totalResults['revenue_impact'] += $companyResults['revenue_impact'];

                if (! empty($companyResults['errors'])) {
                    $totalResults['errors'] = array_merge($totalResults['errors'], $companyResults['errors']);
                }
            }

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);
            $totalResults['execution_time'] = $executionTime;

            // Display summary
            $this->displaySummary($totalResults, $isDryRun);

            // Record job run (unless dry run)
            if (! $isDryRun) {
                $this->recordJobRun($totalResults);
            }

            // Send email summary if requested
            if ($emailSummaryTo) {
                $this->sendEmailSummary($emailSummaryTo, $totalResults, $isDryRun);
            }

            // Log comprehensive summary
            Log::info('Contract renewal processing completed', $totalResults);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error processing contract renewals: '.$e->getMessage());
            Log::error('Contract renewal processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process contracts for a single company
     */
    protected function processCompany(Company $company, bool $isDryRun, array $notificationDays, bool $verboseLogging): array
    {
        $results = [
            'contracts_checked' => 0,
            'renewed' => 0,
            'escalated' => 0,
            'failed' => 0,
            'notifications_90' => 0,
            'notifications_60' => 0,
            'notifications_30' => 0,
            'revenue_impact' => 0,
            'errors' => [],
        ];

        try {
            // Process auto-renewals with price escalation
            $renewalResults = $this->contractService->processAutoRenewals($company, $isDryRun);

            foreach ($renewalResults as $result) {
                $results['contracts_checked']++;

                if ($result['status'] === 'renewed' || ($isDryRun && $result['dry_run'])) {
                    $results['renewed']++;

                    // Track escalation
                    if ($result['new_value'] > $result['original_value']) {
                        $results['escalated']++;
                        $results['revenue_impact'] += ($result['new_value'] - $result['original_value']);
                    }

                    if ($verboseLogging) {
                        $this->info("  ✓ Renewed contract {$result['contract_id']}: \${$result['original_value']} → \${$result['new_value']}");
                    }
                } elseif ($result['status'] === 'failed') {
                    $results['failed']++;
                    $results['errors'][] = [
                        'company_id' => $company->id,
                        'contract_id' => $result['contract_id'],
                        'error' => $result['error'] ?? 'Unknown error',
                    ];

                    if ($verboseLogging) {
                        $this->error("  ✗ Failed to renew contract {$result['contract_id']}");
                    }
                }
            }

            // Send renewal notifications at specified intervals
            foreach ($notificationDays as $days) {
                $days = (int) trim($days);
                $notificationResults = $this->contractService->sendRenewalNotifications($days, $company);

                foreach ($notificationResults as $notification) {
                    if ($notification['status'] === 'sent') {
                        // Track by interval
                        switch ($days) {
                            case 90:
                                $results['notifications_90']++;
                                break;
                            case 60:
                                $results['notifications_60']++;
                                break;
                            case self::DEFAULT_TIMEOUT:
                                $results['notifications_30']++;
                                break;
                            default:
                                // No action needed
                                break;
                        }

                        if ($verboseLogging) {
                            $this->info("  ✉ Sent {$days}-day notification for contract {$notification['contract_id']} to {$notification['recipient']}");
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ];
            $this->error("  Error processing company {$company->id}: ".$e->getMessage());
        }

        return $results;
    }

    /**
     * Display processing summary
     */
    protected function displaySummary(array $results, bool $isDryRun): void
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║           CONTRACT RENEWAL PROCESSING SUMMARY            ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');

        if ($isDryRun) {
            $this->warn('  [DRY RUN - No actual changes were made]');
        }

        $this->info('  Companies Processed: '.$results['companies_processed']);
        $this->info('  Contracts Checked:   '.$results['contracts_checked']);
        $this->newLine();

        $this->info('  RENEWALS:');
        $this->info('    • Renewed:         '.$results['renewed']);
        $this->info('    • Price Escalated: '.$results['escalated']);
        $this->info('    • Failed:          '.$results['failed']);
        $this->info('    • Revenue Impact:  $'.number_format($results['revenue_impact'], 2));
        $this->newLine();

        $this->info('  NOTIFICATIONS:');
        $this->info('    • 90-day notices:  '.$results['notifications_90']);
        $this->info('    • 60-day notices:  '.$results['notifications_60']);
        $this->info('    • self::DEFAULT_TIMEOUT-day notices:  '.$results['notifications_30']);
        $this->newLine();

        if (! empty($results['errors'])) {
            $this->error('  ERRORS ('.count($results['errors']).'):');
            foreach (array_slice($results['errors'], 0, 5) as $error) {
                $this->error('    • Contract '.$error['contract_id'].': '.$error['error']);
            }
            if (count($results['errors']) > 5) {
                $this->error('    ... and '.(count($results['errors']) - 5).' more errors');
            }
        }

        $this->info('  Execution Time: '.$results['execution_time'].' seconds');
        $this->info('═══════════════════════════════════════════════════════════');
    }

    /**
     * Record job run in database
     */
    protected function recordJobRun(array $results): void
    {
        DB::table('job_runs')->insert([
            'job_name' => 'process_contract_renewals',
            'run_date' => Carbon::now(),
            'status' => empty($results['errors']) ? 'success' : 'completed_with_errors',
            'results' => json_encode($results),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Send email summary of processing results
     */
    protected function sendEmailSummary(string $email, array $results, bool $isDryRun): void
    {
        try {
            Mail::to($email)->send(new ContractRenewalSummary($results, $isDryRun));
            $this->info("Summary email sent to {$email}");
        } catch (\Exception $e) {
            $this->error('Failed to send summary email: '.$e->getMessage());
        }
    }
}
