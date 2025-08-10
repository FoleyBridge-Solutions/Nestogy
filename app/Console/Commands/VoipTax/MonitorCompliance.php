<?php

namespace App\Console\Commands\VoipTax;

use App\Services\VoIPTaxScheduledReportService;
use Illuminate\Console\Command;

/**
 * Monitor VoIP Tax Compliance
 * 
 * Artisan command to monitor compliance status and send alerts.
 */
class MonitorCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voip-tax:monitor-compliance 
                            {--company= : Specific company ID to monitor}
                            {--send-alerts : Send alert notifications}
                            {--critical-only : Only show critical alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor VoIP tax compliance status and generate alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting VoIP Tax Compliance Monitoring...');

        try {
            $companyId = $this->option('company');
            $sendAlerts = $this->option('send-alerts');
            $criticalOnly = $this->option('critical-only');

            if ($companyId) {
                $this->info("Monitoring company ID: {$companyId}");
            }

            if ($criticalOnly) {
                $this->info("Showing only critical alerts");
            }

            $reportService = new VoIPTaxScheduledReportService([
                'email_notifications' => $sendAlerts,
            ]);

            // Monitor compliance alerts
            $alerts = $reportService->monitorComplianceAlerts();

            // Filter by company if specified
            if ($companyId) {
                $alerts = array_filter($alerts, fn($key) => $key == $companyId, ARRAY_FILTER_USE_KEY);
            }

            // Filter by severity if critical only
            if ($criticalOnly) {
                $alerts = array_map(function ($companyAlerts) {
                    $companyAlerts['alerts'] = array_filter(
                        $companyAlerts['alerts'], 
                        fn($alert) => $alert['severity'] === 'critical'
                    );
                    $companyAlerts['alert_count'] = count($companyAlerts['alerts']);
                    return $companyAlerts;
                }, $alerts);

                // Remove companies with no critical alerts
                $alerts = array_filter($alerts, fn($company) => $company['alert_count'] > 0);
            }

            $this->displayAlerts($alerts, $criticalOnly);

            $totalAlerts = array_sum(array_column($alerts, 'alert_count'));
            $companiesWithAlerts = count($alerts);

            if ($totalAlerts === 0) {
                $this->info('✅ No compliance alerts found - all systems are compliant!');
                return Command::SUCCESS;
            }

            $this->warn("Found {$totalAlerts} compliance alerts across {$companiesWithAlerts} companies");

            if ($sendAlerts) {
                $this->info('✉️ Alert notifications have been sent');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to monitor compliance: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display alerts in a formatted way.
     */
    protected function displayAlerts(array $alerts, bool $criticalOnly): void
    {
        if (empty($alerts)) {
            $this->info($criticalOnly ? 'No critical alerts found' : 'No alerts found');
            return;
        }

        foreach ($alerts as $companyId => $companyAlerts) {
            $this->line('');
            $this->line("<fg=cyan>Company: {$companyAlerts['company_name']} (ID: {$companyId})</>");
            $this->line("<fg=yellow>Alert Count: {$companyAlerts['alert_count']}</>");

            foreach ($companyAlerts['alerts'] as $alert) {
                $icon = match ($alert['severity']) {
                    'critical' => '🚨',
                    'high' => '⚠️',
                    'medium' => '⚡',
                    default => 'ℹ️',
                };

                $color = match ($alert['severity']) {
                    'critical' => 'red',
                    'high' => 'yellow',
                    'medium' => 'blue',
                    default => 'white',
                };

                $this->line("  {$icon} <fg={$color}>[{$alert['severity']}]</> {$alert['message']}");

                if (isset($alert['count'])) {
                    $this->line("     Count: {$alert['count']}");
                }

                if (isset($alert['action_required'])) {
                    $this->line("     <fg=red>Action Required:</> {$alert['action_required']}");
                }

                if (isset($alert['recommendation'])) {
                    $this->line("     <fg=green>Recommendation:</> {$alert['recommendation']}");
                }

                if (isset($alert['details']) && !empty($alert['details'])) {
                    $this->line('     Details:');
                    foreach (array_slice($alert['details'], 0, 3) as $detail) {
                        $line = "       - ";
                        if (isset($detail['client_name'])) {
                            $line .= "Client: {$detail['client_name']}";
                        }
                        if (isset($detail['exemption_name'])) {
                            $line .= ", Exemption: {$detail['exemption_name']}";
                        }
                        if (isset($detail['expired_date'])) {
                            $line .= ", Expired: {$detail['expired_date']}";
                        }
                        if (isset($detail['expires_date'])) {
                            $line .= ", Expires: {$detail['expires_date']} ({$detail['days_until_expiry']} days)";
                        }
                        $this->line($line);
                    }

                    if (count($alert['details']) > 3) {
                        $remaining = count($alert['details']) - 3;
                        $this->line("       ... and {$remaining} more");
                    }
                }

                $this->line('');
            }
        }
    }
}