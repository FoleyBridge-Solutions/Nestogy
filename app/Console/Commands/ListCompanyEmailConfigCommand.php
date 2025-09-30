<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class ListCompanyEmailConfigCommand extends Command
{
    // Class constants to reduce duplication
    private const CONFIG_SMTP = 'smtp';

    private const CONFIG_IMAP = 'imap';

    private const MSG_LIST_START = 'Listing company email configurations...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:list-companies
                            {--detailed : Show detailed email configuration}
                            {--only-configured : Only show companies with email configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all companies and their email configuration status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $detailed = $this->option('detailed');
        $onlyConfigured = $this->option('only-configured');

        $companies = Company::with('setting')->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies found in the database.');

            return Command::SUCCESS;
        }

        $this->info('📊 Company Email Configuration Status');
        $this->line(str_repeat('=', 60));

        $configuredCount = 0;
        $tableData = [];

        foreach ($companies as $company) {
            $setting = $company->setting;
            $hasConfig = $this->hasValidSmtpConfig($setting);

            if ($hasConfig) {
                $configuredCount++;
            }

            // Skip unconfigured companies if filter is active
            if ($onlyConfigured && ! $hasConfig) {
                continue;
            }

            $status = $hasConfig ? '✅ Configured' : '❌ Not Configured';
            $smtpHost = $setting?->smtp_host ?? 'Not Set';
            $fromEmail = $setting?->mail_from_email ?? $setting?->smtp_username ?? 'Not Set';

            if ($detailed) {
                $tableData[] = [
                    $company->id,
                    $company->name,
                    $status,
                    $smtpHost,
                    $setting?->smtp_port ?? 'N/A',
                    $setting?->smtp_username ?? 'Not Set',
                    $fromEmail,
                ];
            } else {
                $tableData[] = [
                    $company->id,
                    $company->name,
                    $status,
                    $smtpHost,
                ];
            }
        }

        // Display table
        if ($detailed) {
            $this->table(
                ['ID', 'Company Name', 'Status', 'SMTP Host', 'Port', 'Username', 'From Email'],
                $tableData
            );
        } else {
            $this->table(
                ['ID', 'Company Name', 'Status', 'SMTP Host'],
                $tableData
            );
        }

        // Summary
        $totalCompanies = $companies->count();
        $displayedCompanies = count($tableData);

        $this->line(str_repeat('=', 60));
        $this->info('📈 Summary:');
        $this->line("   Total Companies: {$totalCompanies}");
        $this->line("   Configured for Email: {$configuredCount}");
        $this->line("   Displayed: {$displayedCompanies}");

        if ($configuredCount > 0) {
            $this->line('');
            $this->info('💡 To test email for a company, run:');
            $this->line('   php artisan email:test-send {company_id} {email@example.com}');
        }

        return Command::SUCCESS;
    }

    /**
     * Check if setting has valid SMTP configuration
     */
    protected function hasValidSmtpConfig($setting): bool
    {
        return $setting
            && ! empty($setting->smtp_host)
            && ! empty($setting->smtp_port)
            && ! empty($setting->smtp_username)
            && ! empty($setting->smtp_password);
    }
}
