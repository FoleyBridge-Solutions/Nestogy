<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->insert([
            'id' => 1,
            'company_id' => 1,
            'current_database_version' => '1.0.0',
            'start_page' => 'dashboard',

            // SMTP Configuration (defaults)
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => null,
            'smtp_password' => null,
            'mail_from_email' => 'admin@nestogy.com',
            'mail_from_name' => 'Nestogy Demo Company',

            // IMAP Configuration (defaults)
            'imap_host' => null,
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => null,
            'imap_password' => null,

            // Default Account Settings
            'default_transfer_from_account' => 1,
            'default_transfer_to_account' => 2,
            'default_payment_account' => 1,
            'default_expense_account' => 1,
            'default_payment_method' => 'Check',
            'default_expense_payment_method' => 'Credit Card',
            'default_calendar' => null,
            'default_net_terms' => 30,
            'default_hourly_rate' => 125.00,

            // Invoice Settings
            'invoice_prefix' => 'INV-',
            'invoice_next_number' => 1001,
            'invoice_footer' => 'Thank you for your business!',
            'invoice_from_name' => 'Nestogy Demo Company',
            'invoice_from_email' => 'billing@nestogy.com',
            'invoice_late_fee_enable' => true,
            'invoice_late_fee_percent' => 1.50,

            // Quote Settings
            'quote_prefix' => 'QUO-',
            'quote_next_number' => 2001,
            'quote_footer' => 'We look forward to working with you!',
            'quote_from_name' => 'Nestogy Demo Company',
            'quote_from_email' => 'quotes@nestogy.com',

            // Ticket Settings
            'ticket_prefix' => 'TKT-',
            'ticket_next_number' => 3001,
            'ticket_from_name' => 'Nestogy Support',
            'ticket_from_email' => 'support@nestogy.com',
            'ticket_email_parse' => false,
            'ticket_client_general_notifications' => true,
            'ticket_autoclose' => true,
            'ticket_autoclose_hours' => 72,
            'ticket_new_ticket_notification_email' => 'admin@nestogy.com',

            // System Settings
            'enable_cron' => false,
            'cron_key' => null,
            'recurring_auto_send_invoice' => true,
            'enable_alert_domain_expire' => true,
            'send_invoice_reminders' => true,
            'invoice_overdue_reminders' => '7,14,30',
            'theme' => 'blue',
            'telemetry' => false,
            'timezone' => 'America/New_York',
            'destructive_deletes_enable' => false,

            // Module Settings
            'module_enable_itdoc' => true,
            'module_enable_accounting' => true,
            'module_enable_ticketing' => true,
            'client_portal_enable' => true,

            // Security Settings
            'login_message' => 'Welcome to Nestogy Demo',
            'login_key_required' => false,
            'login_key_secret' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
