<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyMailSettings;
use App\Models\SettingsConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateSettingsToUnified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:migrate-to-unified {--company=all : Company ID or "all" for all companies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing settings to unified settings configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting settings migration to unified structure...');

        $companyOption = $this->option('company');

        if ($companyOption === 'all') {
            $companies = Company::all();
        } else {
            $companies = Company::where('id', $companyOption)->get();
        }

        if ($companies->isEmpty()) {
            $this->error('No companies found to migrate.');

            return 1;
        }

        $this->info("Found {$companies->count()} companies to migrate.");

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        foreach ($companies as $company) {
            $this->migrateCompanySettings($company);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Settings migration completed successfully!');

        return 0;
    }

    /**
     * Migrate settings for a specific company
     */
    private function migrateCompanySettings(Company $company)
    {
        DB::beginTransaction();

        try {
            // Migrate email settings from CompanyMailSettings
            $this->migrateEmailSettings($company);

            // Migrate company general settings
            $this->migrateCompanyGeneralSettings($company);

            // Set default settings for other domains
            $this->setDefaultSettings($company);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to migrate settings for company', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            $this->error("Failed to migrate settings for company {$company->name}: ".$e->getMessage());
        }
    }

    /**
     * Migrate email settings from CompanyMailSettings
     */
    private function migrateEmailSettings(Company $company)
    {
        $mailSettings = CompanyMailSettings::where('company_id', $company->id)->first();

        if (! $mailSettings) {
            return;
        }

        $settings = [
            'driver' => $mailSettings->driver ?? 'smtp',
            'from_email' => $mailSettings->from_email,
            'from_name' => $mailSettings->from_name,
            'reply_to' => $mailSettings->reply_to ?? $mailSettings->reply_to_email,

            // SMTP settings
            'smtp_host' => $mailSettings->smtp_host,
            'smtp_port' => $mailSettings->smtp_port,
            'smtp_username' => $mailSettings->smtp_username,
            'smtp_password' => $mailSettings->smtp_password,
            'smtp_encryption' => $mailSettings->smtp_encryption,

            // API settings
            'api_key' => $mailSettings->api_key
                ?? $mailSettings->mailgun_secret
                ?? $mailSettings->sendgrid_api_key
                ?? $mailSettings->postmark_token,
            'api_domain' => $mailSettings->api_domain
                ?? $mailSettings->mailgun_domain
                ?? $mailSettings->ses_region,

            // Features
            'track_opens' => $mailSettings->track_opens ?? true,
            'track_clicks' => $mailSettings->track_clicks ?? true,
            'auto_retry_failed' => $mailSettings->auto_retry_failed ?? true,
            'max_retry_attempts' => $mailSettings->max_retry_attempts ?? 3,
        ];

        // Remove null values
        $settings = array_filter($settings, function ($value) {
            return ! is_null($value);
        });

        SettingsConfiguration::updateOrCreate(
            [
                'company_id' => $company->id,
                'domain' => SettingsConfiguration::DOMAIN_COMMUNICATION,
                'category' => 'email',
            ],
            [
                'settings' => $settings,
                'is_active' => $mailSettings->is_active ?? true,
                'last_modified_at' => $mailSettings->updated_at,
            ]
        );
    }

    /**
     * Migrate company general settings
     */
    private function migrateCompanyGeneralSettings(Company $company)
    {
        $settings = [
            'company_name' => $company->name,
            'legal_name' => $company->legal_name,
            'tax_id' => $company->tax_id,
            'website' => $company->website,
            'phone' => $company->phone,
            'email' => $company->email,
            'address_line1' => $company->address,
            'address_line2' => $company->address_line_2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
        ];

        // Remove null values
        $settings = array_filter($settings, function ($value) {
            return ! is_null($value);
        });

        SettingsConfiguration::updateOrCreate(
            [
                'company_id' => $company->id,
                'domain' => SettingsConfiguration::DOMAIN_COMPANY,
                'category' => 'general',
            ],
            [
                'settings' => $settings,
                'is_active' => true,
                'last_modified_at' => $company->updated_at,
            ]
        );
    }

    /**
     * Set default settings for other domains
     */
    private function setDefaultSettings(Company $company)
    {
        // Default localization settings
        SettingsConfiguration::firstOrCreate(
            [
                'company_id' => $company->id,
                'domain' => SettingsConfiguration::DOMAIN_COMPANY,
                'category' => 'localization',
            ],
            [
                'settings' => [
                    'timezone' => $company->timezone ?? 'America/New_York',
                    'date_format' => 'Y-m-d',
                    'time_format' => '12',
                    'currency' => $company->currency ?? 'USD',
                    'currency_position' => 'before',
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                    'decimal_places' => 2,
                    'language' => 'en',
                    'week_starts_on' => 0,
                ],
                'is_active' => true,
            ]
        );

        // Default branding settings
        SettingsConfiguration::firstOrCreate(
            [
                'company_id' => $company->id,
                'domain' => SettingsConfiguration::DOMAIN_COMPANY,
                'category' => 'branding',
            ],
            [
                'settings' => [
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#1E40AF',
                    'portal_theme' => 'light',
                ],
                'is_active' => true,
            ]
        );

        // Default billing settings
        SettingsConfiguration::firstOrCreate(
            [
                'company_id' => $company->id,
                'domain' => SettingsConfiguration::DOMAIN_FINANCIAL,
                'category' => 'billing',
            ],
            [
                'settings' => [
                    'billing_cycle' => 'monthly',
                    'payment_terms' => 30,
                    'late_fee_enabled' => false,
                    'auto_charge_enabled' => false,
                    'send_invoice_automatically' => true,
                    'send_payment_reminders' => true,
                    'reminder_days_before' => [7, 3, 1],
                    'reminder_days_after' => [1, 7, 14, 30],
                ],
                'is_active' => true,
            ]
        );
    }
}
