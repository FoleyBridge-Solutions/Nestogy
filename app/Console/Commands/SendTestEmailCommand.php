<?php

namespace App\Console\Commands;

use App\Domains\Email\Services\DynamicMailConfigService;
use App\Domains\Email\Services\EmailService;
use App\Models\Company;
use Exception;
use Illuminate\Console\Command;

class SendTestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-send
                            {company_id : The ID of the company to send as}
                            {email : The email address to send to}
                            {--subject= : Custom email subject}
                            {--message= : Custom email message}
                            {--show-config : Show mail configuration details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email using a specific company\'s SMTP configuration';

    protected DynamicMailConfigService $mailConfigService;

    protected EmailService $emailService;

    public function __construct(DynamicMailConfigService $mailConfigService, EmailService $emailService)
    {
        parent::__construct();
        $this->mailConfigService = $mailConfigService;
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');
        $email = $this->argument('email');
        $subject = $this->option('subject') ?? 'Nestogy Email Test - Company #'.$companyId;
        $message = $this->option('message') ?? $this->getDefaultMessage($companyId);

        try {
            // Find company
            $company = Company::with('setting')->find($companyId);
            if (! $company) {
                $this->error("Company with ID {$companyId} not found.");

                return Command::FAILURE;
            }

            $this->info("📧 Testing email for company: {$company->name} (ID: {$companyId})");
            $this->info("📮 Sending to: {$email}");

            // Check if company has email settings
            if (! $company->setting || ! $this->hasValidSmtpConfig($company->setting)) {
                $this->error('❌ Company does not have valid SMTP configuration.');
                $this->line('   Please configure SMTP settings in the admin panel first.');

                return Command::FAILURE;
            }

            // Configure mail for this company
            $this->info('🔧 Configuring mail settings...');
            $configured = $this->mailConfigService->configureMailForCompany($company);

            if (! $configured) {
                $this->error('❌ Failed to configure mail settings for company.');

                return Command::FAILURE;
            }

            // Show configuration if requested
            if ($this->option('show-config')) {
                $this->showMailConfiguration($company);
            }

            // Test mail configuration
            $this->info('🔍 Testing mail configuration...');
            $testResult = $this->mailConfigService->testCurrentMailConfig();

            if (! $testResult['success']) {
                $this->error('❌ Mail configuration test failed:');
                $this->line('   '.$testResult['message']);

                return Command::FAILURE;
            }

            $this->info('✅ Mail configuration test passed');

            // Send the test email
            $this->info('📤 Sending test email...');
            $result = $this->emailService->send($email, $subject, $this->formatMessage($message, $company));

            if ($result) {
                $this->info('✅ Test email sent successfully!');
                $this->line("📧 Subject: {$subject}");
                $this->line("📮 To: {$email}");
                $this->line("🏢 From Company: {$company->name}");
                $this->line('⏰ Sent at: '.now()->format('Y-m-d H:i:s'));

                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send test email');

                return Command::FAILURE;
            }

        } catch (Exception $e) {
            $this->error('❌ Error occurred: '.$e->getMessage());
            $this->line('📍 Stack trace:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Check if setting has valid SMTP configuration
     */
    protected function hasValidSmtpConfig($setting): bool
    {
        return ! empty($setting->smtp_host)
            && ! empty($setting->smtp_port)
            && ! empty($setting->smtp_username)
            && ! empty($setting->smtp_password);
    }

    /**
     * Show mail configuration details
     */
    protected function showMailConfiguration(Company $company): void
    {
        $setting = $company->setting;

        $this->info('📋 Mail Configuration Details:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['SMTP Host', $setting->smtp_host],
                ['SMTP Port', $setting->smtp_port],
                ['SMTP Username', $setting->smtp_username],
                ['SMTP Encryption', $setting->smtp_encryption ?? 'none'],
                ['From Email', $setting->mail_from_email ?? $setting->smtp_username],
                ['From Name', $setting->mail_from_name ?? $company->name],
                ['Current Laravel Driver', config('mail.default')],
                ['Current Laravel Host', config('mail.mailers.smtp.host')],
            ]
        );
    }

    /**
     * Get default test message
     */
    protected function getDefaultMessage(int $companyId): string
    {
        return "This is a test email sent from the Nestogy MSP platform.\n\n".
               "Test Details:\n".
               "- Company ID: {$companyId}\n".
               "- Sent via: Console Command\n".
               '- Date: '.now()->format('Y-m-d H:i:s T')."\n".
               "- Purpose: Email system verification\n\n".
               'If you received this email, the email configuration is working correctly!';
    }

    /**
     * Format message with HTML
     */
    protected function formatMessage(string $message, Company $company): string
    {
        return "
            <h2>🧪 Nestogy Email System Test</h2>
            <p><strong>Company:</strong> {$company->name}</p>
            <p><strong>Test Message:</strong></p>
            <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0;'>
                ".nl2br(htmlspecialchars($message)).'
            </div>
            <hr>
            <p><small>This email was sent via console command for testing purposes.</small></p>
        ';
    }
}
