<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting Model
 * 
 * Represents system-wide configuration settings for each company.
 * Includes SMTP, IMAP, invoice, ticket, and system preferences.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $current_database_version
 * @property string $start_page
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_encryption
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property string|null $mail_from_email
 * @property string|null $mail_from_name
 * @property string|null $imap_host
 * @property int|null $imap_port
 * @property string|null $imap_encryption
 * @property string|null $imap_username
 * @property string|null $imap_password
 * @property int|null $default_transfer_from_account
 * @property int|null $default_transfer_to_account
 * @property int|null $default_payment_account
 * @property int|null $default_expense_account
 * @property string|null $default_payment_method
 * @property string|null $default_expense_payment_method
 * @property int|null $default_calendar
 * @property int|null $default_net_terms
 * @property float $default_hourly_rate
 * @property string|null $invoice_prefix
 * @property int|null $invoice_next_number
 * @property string|null $invoice_footer
 * @property string|null $invoice_from_name
 * @property string|null $invoice_from_email
 * @property bool $invoice_late_fee_enable
 * @property float $invoice_late_fee_percent
 * @property string|null $quote_prefix
 * @property int|null $quote_next_number
 * @property string|null $quote_footer
 * @property string|null $quote_from_name
 * @property string|null $quote_from_email
 * @property string|null $ticket_prefix
 * @property int|null $ticket_next_number
 * @property string|null $ticket_from_name
 * @property string|null $ticket_from_email
 * @property bool $ticket_email_parse
 * @property bool $ticket_client_general_notifications
 * @property bool $ticket_autoclose
 * @property int $ticket_autoclose_hours
 * @property string|null $ticket_new_ticket_notification_email
 * @property bool $enable_cron
 * @property string|null $cron_key
 * @property bool $recurring_auto_send_invoice
 * @property bool $enable_alert_domain_expire
 * @property bool $send_invoice_reminders
 * @property string|null $invoice_overdue_reminders
 * @property string $theme
 * @property bool $telemetry
 * @property string $timezone
 * @property bool $destructive_deletes_enable
 * @property bool $module_enable_itdoc
 * @property bool $module_enable_accounting
 * @property bool $module_enable_ticketing
 * @property bool $client_portal_enable
 * @property string|null $login_message
 * @property bool $login_key_required
 * @property string|null $login_key_secret
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'current_database_version',
        'start_page',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'mail_from_email',
        'mail_from_name',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'default_transfer_from_account',
        'default_transfer_to_account',
        'default_payment_account',
        'default_expense_account',
        'default_payment_method',
        'default_expense_payment_method',
        'default_calendar',
        'default_net_terms',
        'default_hourly_rate',
        'invoice_prefix',
        'invoice_next_number',
        'invoice_footer',
        'invoice_from_name',
        'invoice_from_email',
        'invoice_late_fee_enable',
        'invoice_late_fee_percent',
        'quote_prefix',
        'quote_next_number',
        'quote_footer',
        'quote_from_name',
        'quote_from_email',
        'ticket_prefix',
        'ticket_next_number',
        'ticket_from_name',
        'ticket_from_email',
        'ticket_email_parse',
        'ticket_client_general_notifications',
        'ticket_autoclose',
        'ticket_autoclose_hours',
        'ticket_new_ticket_notification_email',
        'enable_cron',
        'cron_key',
        'recurring_auto_send_invoice',
        'enable_alert_domain_expire',
        'send_invoice_reminders',
        'invoice_overdue_reminders',
        'theme',
        'telemetry',
        'timezone',
        'destructive_deletes_enable',
        'module_enable_itdoc',
        'module_enable_accounting',
        'module_enable_ticketing',
        'client_portal_enable',
        'login_message',
        'login_key_required',
        'login_key_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'smtp_password',
        'imap_password',
        'cron_key',
        'login_key_secret',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'smtp_port' => 'integer',
        'imap_port' => 'integer',
        'default_transfer_from_account' => 'integer',
        'default_transfer_to_account' => 'integer',
        'default_payment_account' => 'integer',
        'default_expense_account' => 'integer',
        'default_calendar' => 'integer',
        'default_net_terms' => 'integer',
        'default_hourly_rate' => 'decimal:2',
        'invoice_next_number' => 'integer',
        'invoice_late_fee_enable' => 'boolean',
        'invoice_late_fee_percent' => 'decimal:2',
        'quote_next_number' => 'integer',
        'ticket_next_number' => 'integer',
        'ticket_email_parse' => 'boolean',
        'ticket_client_general_notifications' => 'boolean',
        'ticket_autoclose' => 'boolean',
        'ticket_autoclose_hours' => 'integer',
        'enable_cron' => 'boolean',
        'recurring_auto_send_invoice' => 'boolean',
        'enable_alert_domain_expire' => 'boolean',
        'send_invoice_reminders' => 'boolean',
        'telemetry' => 'boolean',
        'destructive_deletes_enable' => 'boolean',
        'module_enable_itdoc' => 'boolean',
        'module_enable_accounting' => 'boolean',
        'module_enable_ticketing' => 'boolean',
        'client_portal_enable' => 'boolean',
        'login_key_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if SMTP is configured.
     */
    public function hasSmtpConfiguration(): bool
    {
        return !empty($this->smtp_host) && !empty($this->smtp_port);
    }

    /**
     * Check if IMAP is configured.
     */
    public function hasImapConfiguration(): bool
    {
        return !empty($this->imap_host) && !empty($this->imap_port);
    }

    /**
     * Check if cron is enabled.
     */
    public function isCronEnabled(): bool
    {
        return $this->enable_cron === true;
    }

    /**
     * Check if module is enabled.
     */
    public function isModuleEnabled(string $module): bool
    {
        $field = 'module_enable_' . $module;
        return $this->$field === true;
    }

    /**
     * Get next invoice number and increment.
     */
    public function getNextInvoiceNumber(): int
    {
        $number = $this->invoice_next_number ?: 1;
        $this->update(['invoice_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get next quote number and increment.
     */
    public function getNextQuoteNumber(): int
    {
        $number = $this->quote_next_number ?: 1;
        $this->update(['quote_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get next ticket number and increment.
     */
    public function getNextTicketNumber(): int
    {
        $number = $this->ticket_next_number ?: 1;
        $this->update(['ticket_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get formatted hourly rate.
     */
    public function getFormattedHourlyRate(): string
    {
        return '$' . number_format($this->default_hourly_rate, 2) . '/hr';
    }

    /**
     * Get available themes.
     */
    public static function getAvailableThemes(): array
    {
        return [
            'blue' => 'Blue',
            'green' => 'Green',
            'red' => 'Red',
            'purple' => 'Purple',
            'orange' => 'Orange',
            'dark' => 'Dark',
        ];
    }

    /**
     * Get available timezones.
     */
    public static function getAvailableTimezones(): array
    {
        return [
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time',
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
            'UTC' => 'UTC',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];
    }

    /**
     * Get validation rules for settings.
     */
    public static function getValidationRules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'current_database_version' => 'required|string|max:10',
            'start_page' => 'required|string|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'imap_host' => 'nullable|string|max:255',
            'imap_port' => 'nullable|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:tls,ssl',
            'imap_username' => 'nullable|string|max:255',
            'imap_password' => 'nullable|string|max:255',
            'default_net_terms' => 'nullable|integer|min:0|max:365',
            'default_hourly_rate' => 'numeric|min:0',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_next_number' => 'nullable|integer|min:1',
            'invoice_late_fee_enable' => 'boolean',
            'invoice_late_fee_percent' => 'numeric|min:0|max:100',
            'quote_prefix' => 'nullable|string|max:10',
            'quote_next_number' => 'nullable|integer|min:1',
            'ticket_prefix' => 'nullable|string|max:10',
            'ticket_next_number' => 'nullable|integer|min:1',
            'ticket_autoclose_hours' => 'integer|min:1|max:8760',
            'theme' => 'required|string|in:blue,green,red,purple,orange,dark',
            'timezone' => 'required|string|max:255',
            'telemetry' => 'boolean',
            'destructive_deletes_enable' => 'boolean',
            'module_enable_itdoc' => 'boolean',
            'module_enable_accounting' => 'boolean',
            'module_enable_ticketing' => 'boolean',
            'client_portal_enable' => 'boolean',
            'login_key_required' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate cron key if cron is enabled
        static::saving(function ($setting) {
            if ($setting->enable_cron && empty($setting->cron_key)) {
                $setting->cron_key = bin2hex(random_bytes(32));
            }
        });
    }
}