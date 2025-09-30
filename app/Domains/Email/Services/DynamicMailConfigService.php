<?php

namespace App\Domains\Email\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DynamicMailConfigService
{
    /**
     * Configure mail settings for the authenticated user's company
     */
    public function configureMailForCompany(?Company $company = null): bool
    {
        try {
            // Get company - use provided or from authenticated user
            if (! $company) {
                $user = Auth::user();
                if (! $user || ! $user->company) {
                    Log::warning('No company found for mail configuration, trying platform company');

                    return $this->configureMailForPlatformCompany();
                }
                $company = $user->company;
            }

            // Get company settings
            $setting = $company->setting;
            if (! $setting || ! $this->hasValidSmtpConfig($setting)) {
                Log::info('Company has no valid SMTP configuration, trying platform company', [
                    'company_id' => $company->id,
                ]);

                return $this->configureMailForPlatformCompany();
            }

            // Configure Laravel mail with company SMTP settings
            $this->setMailConfiguration($setting);

            Log::info('Mail configured for company', [
                'company_id' => $company->id,
                'smtp_host' => $setting->smtp_host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to configure mail for company', [
                'error' => $e->getMessage(),
                'company_id' => $company?->id,
            ]);

            // Try platform company as fallback
            return $this->configureMailForPlatformCompany();
        }
    }

    /**
     * Configure mail settings for the platform company (root company)
     */
    public function configureMailForPlatformCompany(): bool
    {
        try {
            // Get platform company (root company with no parent)
            $platformCompany = Company::whereNull('parent_company_id')
                ->where('company_type', 'root')
                ->orWhereNull('parent_company_id')
                ->first();

            if (! $platformCompany) {
                Log::warning('No platform company found, using default mail settings');

                return false;
            }

            // Get platform company settings
            $setting = $platformCompany->setting;
            if (! $setting || ! $this->hasValidSmtpConfig($setting)) {
                Log::info('Platform company has no valid SMTP configuration, using default mail settings', [
                    'platform_company_id' => $platformCompany->id,
                ]);

                return false;
            }

            // Configure Laravel mail with platform company SMTP settings
            $this->setMailConfiguration($setting);

            Log::info('Mail configured for platform company', [
                'platform_company_id' => $platformCompany->id,
                'company_name' => $platformCompany->name,
                'smtp_host' => $setting->smtp_host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to configure mail for platform company', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if setting has valid SMTP configuration
     *
     * @param  \App\Models\Setting  $setting
     */
    protected function hasValidSmtpConfig($setting): bool
    {
        return ! empty($setting->smtp_host)
            && ! empty($setting->smtp_port)
            && ! empty($setting->smtp_username)
            && ! empty($setting->smtp_password);
    }

    /**
     * Set Laravel mail configuration
     *
     * @param  \App\Models\Setting  $setting
     */
    protected function setMailConfiguration($setting): void
    {
        // Decrypt password
        $password = ! empty($setting->smtp_password) ? decrypt($setting->smtp_password) : '';

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $setting->smtp_host,
                'port' => $setting->smtp_port,
                'encryption' => $setting->smtp_encryption ?? 'tls',
                'username' => $setting->smtp_username,
                'password' => $password,
                'timeout' => 30,
                'local_domain' => config('mail.mailers.smtp.local_domain'),
            ],
            'mail.from' => [
                'address' => $setting->mail_from_email ?? $setting->smtp_username,
                'name' => $setting->mail_from_name ?? config('app.name'),
            ],
        ]);

        // Clear any cached mail manager instances
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }

    /**
     * Reset mail configuration to defaults
     */
    public function resetToDefaultMailConfig(): void
    {
        // Reload mail config from cached config values
        Config::set([
            'mail.default' => config('mail.default'),
            'mail.mailers.smtp' => config('mail.mailers.smtp'),
            'mail.from' => config('mail.from'),
        ]);

        // Clear cached instances
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }

    /**
     * Force mail to use log driver for development/testing
     */
    public function forceLogDriver(): void
    {
        Config::set([
            'mail.default' => 'log',
            'mail.mailers.log' => [
                'transport' => 'log',
                'channel' => null,
            ],
            'mail.from' => [
                'address' => config('mail.from.address'),
                'name' => config('mail.from.name'),
            ],
        ]);

        // Clear cached instances
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');

        Log::info('Mail driver forced to log for development');
    }

    /**
     * Test if current mail configuration is working
     */
    public function testCurrentMailConfig(): array
    {
        try {
            $mailer = app('mailer');
            $transport = $mailer->getSymfonyTransport();

            // Test connection
            $transport->start();

            return [
                'success' => true,
                'message' => 'Mail configuration is working',
                'config' => [
                    'driver' => Config::get('mail.default'),
                    'host' => Config::get('mail.mailers.smtp.host'),
                    'port' => Config::get('mail.mailers.smtp.port'),
                    'from' => Config::get('mail.from.address'),
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Mail configuration test failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current mail configuration status
     */
    public function getMailConfigStatus(): array
    {
        $user = Auth::user();
        $company = $user?->company;
        $setting = $company?->setting;

        return [
            'has_company' => ! is_null($company),
            'company_name' => $company?->name,
            'has_settings' => ! is_null($setting),
            'has_smtp_config' => $setting ? $this->hasValidSmtpConfig($setting) : false,
            'current_driver' => Config::get('mail.default'),
            'current_host' => Config::get('mail.mailers.smtp.host'),
            'current_from' => Config::get('mail.from.address'),
            'smtp_settings' => $setting ? [
                'host' => $setting->smtp_host,
                'port' => $setting->smtp_port,
                'username' => $setting->smtp_username,
                'from_email' => $setting->mail_from_email,
                'from_name' => $setting->mail_from_name,
            ] : null,
        ];
    }
}
