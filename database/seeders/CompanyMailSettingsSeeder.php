<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyMailSettings;
use Illuminate\Database\Seeder;

class CompanyMailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies without mail settings
        $companies = Company::whereDoesntHave('mailSettings')->get();

        foreach ($companies as $company) {
            CompanyMailSettings::create([
                'company_id' => $company->id,
                'driver' => env('MAIL_MAILER', 'smtp'),
                'is_active' => true,

                // SMTP Settings (use ENV as defaults)
                'smtp_host' => env('MAIL_HOST', 'smtp.mailgun.org'),
                'smtp_port' => env('MAIL_PORT', 587),
                'smtp_encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'smtp_username' => env('MAIL_USERNAME'),
                'smtp_password' => env('MAIL_PASSWORD'),

                // From Settings
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', $company->name),

                // Features
                'track_opens' => true,
                'track_clicks' => true,
                'auto_retry_failed' => true,
                'max_retry_attempts' => 3,

                // Rate limits
                'rate_limit_per_minute' => 30,
                'rate_limit_per_hour' => 500,
                'rate_limit_per_day' => 5000,
            ]);

            $this->command->info("Created mail settings for company: {$company->name}");
        }

        $this->command->info('Company mail settings seeded successfully.');
    }
}
