<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating settings for each company...');

        $companies = Company::all();
        $timezones = [
            'America/New_York',     // EST - Company 2
            'America/Chicago',      // CST - Company 3
            'America/Los_Angeles',  // PST - Company 4
            'America/Denver',       // MST - Company 5
        ];

        foreach ($companies as $index => $company) {
            // Determine timezone based on company
            $timezone = $company->id == 1 ? 'America/Los_Angeles' : ($timezones[$company->id - 2] ?? 'America/New_York');

            $settings = Setting::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'current_database_version' => '1.0.0',
                    'start_page' => 'dashboard',
                    'theme' => 'light',
                    'timezone' => $timezone,

                    // SMTP Settings (using example values)
                    'smtp_host' => 'smtp.mailgun.org',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'smtp_username' => "noreply@{$company->email}",
                    'smtp_password' => 'smtp_password_placeholder',
                    'mail_from_email' => "noreply@{$company->email}",
                    'mail_from_name' => $company->name,

                    // IMAP Settings
                    'imap_host' => 'imap.gmail.com',
                    'imap_port' => 993,
                    'imap_encryption' => 'ssl',
                    'imap_username' => "support@{$company->email}",
                    'imap_password' => 'imap_password_placeholder',

                    // Default Settings
                    'default_net_terms' => 30,
                    'default_hourly_rate' => $company->default_standard_rate ?? 150.00,

                    // Invoice Settings
                    'invoice_prefix' => 'INV-',
                    'invoice_next_number' => 1000 + ($company->id * 1000),
                    'invoice_footer' => "Thank you for your business!\nPayment due within NET terms.",
                    'invoice_from_name' => $company->name,
                    'invoice_from_email' => "billing@{$company->email}",
                    'invoice_late_fee_enable' => true,
                    'invoice_late_fee_percent' => 1.5,

                    // Quote Settings
                    'quote_prefix' => 'QTE-',
                    'quote_next_number' => 1000 + ($company->id * 1000),
                    'quote_footer' => 'This quote is valid for 30 days from the date of issue.',
                    'quote_from_name' => $company->name,
                    'quote_from_email' => "sales@{$company->email}",

                    // Ticket Settings
                    'ticket_prefix' => 'TKT-',
                    'ticket_next_number' => 1000 + ($company->id * 1000),
                    'ticket_from_name' => $company->name.' Support',
                    'ticket_from_email' => "support@{$company->email}",
                    'ticket_email_parse' => true,
                    'ticket_client_general_notifications' => true,
                    'ticket_autoclose' => true,
                    'ticket_autoclose_hours' => 72,
                    'ticket_new_ticket_notification_email' => "support@{$company->email}",

                    // Cron Settings
                    'enable_cron' => true,
                    'cron_key' => bin2hex(random_bytes(16)),

                    // Module Settings
                    'module_enable_itdoc' => true,
                    'module_enable_accounting' => true,
                    'module_enable_ticketing' => true,

                    // Client Portal
                    'client_portal_enable' => true,
                    'login_message' => "Welcome to {$company->name} Client Portal",
                    'login_key_required' => false,

                    // Business Settings
                    'recurring_auto_send_invoice' => true,
                    'enable_alert_domain_expire' => true,
                    'send_invoice_reminders' => true,
                    'invoice_overdue_reminders' => '3,7,14,30',
                    'telemetry' => false,
                    'destructive_deletes_enable' => false,

                    // Business Hours (Monday to Friday 9 AM to 6 PM)
                    'business_hours' => [
                        'monday' => ['start' => '09:00', 'end' => '18:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                        'thursday' => ['start' => '09:00', 'end' => '18:00'],
                        'friday' => ['start' => '09:00', 'end' => '18:00'],
                        'saturday' => ['start' => 'closed', 'end' => 'closed'],
                        'sunday' => ['start' => 'closed', 'end' => 'closed'],
                    ],

                    // Company Colors (Brand colors)
                    'company_colors' => [
                        'primary' => $company->id == 1 ? '#3B82F6' : // Blue for Nestogy
                                    ($company->id == 2 ? '#10B981' : // Green for TechGuard
                                    ($company->id == 3 ? '#8B5CF6' : // Purple for CloudFirst
                                    ($company->id == 4 ? '#F59E0B' : // Amber for Digital Shield
                                    '#EF4444'))),                     // Red for Mountain Peak
                        'secondary' => '#6B7280',
                        'accent' => '#EC4899',
                    ],

                    // Holidays (US Federal Holidays)
                    'company_holidays' => [
                        ['date' => '2024-01-01', 'name' => 'New Year\'s Day'],
                        ['date' => '2024-01-15', 'name' => 'Martin Luther King Jr. Day'],
                        ['date' => '2024-02-19', 'name' => 'Presidents Day'],
                        ['date' => '2024-05-27', 'name' => 'Memorial Day'],
                        ['date' => '2024-06-19', 'name' => 'Juneteenth'],
                        ['date' => '2024-07-04', 'name' => 'Independence Day'],
                        ['date' => '2024-09-02', 'name' => 'Labor Day'],
                        ['date' => '2024-10-14', 'name' => 'Columbus Day'],
                        ['date' => '2024-11-11', 'name' => 'Veterans Day'],
                        ['date' => '2024-11-28', 'name' => 'Thanksgiving Day'],
                        ['date' => '2024-12-25', 'name' => 'Christmas Day'],
                    ],
                ]
            );

            $this->command->info("  ✓ Created settings for: {$company->name}");
        }

        $this->command->info('Settings created successfully.');
    }
}
