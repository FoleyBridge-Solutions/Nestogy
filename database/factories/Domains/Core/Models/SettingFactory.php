<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'current_database_version' => '1.0',
            'start_page' => 'dashboard',
            'smtp_host' => $this->faker->optional()->domainName(),
            'smtp_port' => $this->faker->optional()->randomElement([25, 465, 587]),
            'smtp_encryption' => $this->faker->optional()->randomElement(['tls', 'ssl']),
            'smtp_username' => $this->faker->optional()->userName(),
            'smtp_password' => $this->faker->optional()->password(),
            'mail_from_email' => $this->faker->safeEmail(),
            'mail_from_name' => $this->faker->company(),
            'imap_host' => $this->faker->optional()->domainName(),
            'imap_port' => $this->faker->optional()->randomElement([143, 993]),
            'imap_encryption' => $this->faker->optional()->randomElement(['tls', 'ssl']),
            'imap_username' => $this->faker->optional()->userName(),
            'imap_password' => $this->faker->optional()->password(),
            'default_transfer_from_account' => $this->faker->optional()->randomNumber(),
            'default_transfer_to_account' => $this->faker->optional()->randomNumber(),
            'default_payment_account' => $this->faker->optional()->randomNumber(),
            'default_expense_account' => $this->faker->optional()->randomNumber(),
            'default_payment_method' => $this->faker->optional()->word(),
            'default_expense_payment_method' => $this->faker->optional()->word(),
            'default_calendar' => $this->faker->optional()->randomNumber(),
            'default_net_terms' => $this->faker->optional()->numberBetween(15, 90),
            'default_hourly_rate' => $this->faker->randomFloat(2, 50, 250),
            'invoice_prefix' => 'INV-',
            'invoice_next_number' => 1000,
            'invoice_footer' => $this->faker->optional()->sentence(),
            'invoice_from_name' => $this->faker->company(),
            'invoice_from_email' => $this->faker->safeEmail(),
            'invoice_late_fee_enable' => $this->faker->boolean(),
            'invoice_late_fee_percent' => $this->faker->randomFloat(2, 0, 10),
            'quote_prefix' => 'QUO-',
            'quote_next_number' => 1000,
            'quote_footer' => $this->faker->optional()->sentence(),
            'quote_from_name' => $this->faker->company(),
            'quote_from_email' => $this->faker->safeEmail(),
            'ticket_prefix' => 'TKT-',
            'ticket_next_number' => 1000,
            'ticket_from_name' => $this->faker->company(),
            'ticket_from_email' => $this->faker->safeEmail(),
            'ticket_email_parse' => $this->faker->boolean(),
            'ticket_client_general_notifications' => $this->faker->boolean(),
            'ticket_autoclose' => $this->faker->boolean(),
            'ticket_autoclose_hours' => 72,
            'ticket_new_ticket_notification_email' => $this->faker->safeEmail(),
            'enable_cron' => false,
            'cron_key' => $this->faker->optional()->uuid(),
            'recurring_auto_send_invoice' => true,
            'enable_alert_domain_expire' => true,
            'send_invoice_reminders' => true,
            'invoice_overdue_reminders' => $this->faker->optional()->sentence(),
            'theme' => $this->faker->randomElement(['light', 'dark', 'blue']),
            'telemetry' => false,
            'timezone' => $this->faker->timezone(),
            'destructive_deletes_enable' => false,
            'module_enable_itdoc' => true,
            'module_enable_accounting' => true,
            'module_enable_ticketing' => true,
            'client_portal_enable' => true,
            'portal_self_service_tickets' => true,
            'portal_knowledge_base_access' => true,
            'portal_invoice_access' => true,
            'portal_payment_processing' => false,
            'portal_asset_visibility' => false,
            'date_format' => $this->faker->randomElement(['Y-m-d', 'm/d/Y', 'd/m/Y']),
        ];
    }
}
