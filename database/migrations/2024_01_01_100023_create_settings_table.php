<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('current_database_version', 10);
            $table->string('start_page')->default('clients.php');

            // SMTP Configuration
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('mail_from_email')->nullable();
            $table->string('mail_from_name')->nullable();

            // IMAP Configuration
            $table->string('imap_host')->nullable();
            $table->integer('imap_port')->nullable();
            $table->string('imap_encryption')->nullable();
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();

            // Default Account Settings
            $table->integer('default_transfer_from_account')->nullable();
            $table->integer('default_transfer_to_account')->nullable();
            $table->integer('default_payment_account')->nullable();
            $table->integer('default_expense_account')->nullable();
            $table->string('default_payment_method')->nullable();
            $table->string('default_expense_payment_method')->nullable();
            $table->integer('default_calendar')->nullable();
            $table->integer('default_net_terms')->nullable();
            $table->decimal('default_hourly_rate', 15, 2)->default(0.00);

            // Invoice Settings
            $table->string('invoice_prefix')->nullable();
            $table->integer('invoice_next_number')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('invoice_from_name')->nullable();
            $table->string('invoice_from_email')->nullable();
            $table->boolean('invoice_late_fee_enable')->default(false);
            $table->decimal('invoice_late_fee_percent', 5, 2)->default(0.00);

            // Quote Settings
            $table->string('quote_prefix')->nullable();
            $table->integer('quote_next_number')->nullable();
            $table->text('quote_footer')->nullable();
            $table->string('quote_from_name')->nullable();
            $table->string('quote_from_email')->nullable();

            // Ticket Settings
            $table->string('ticket_prefix')->nullable();
            $table->integer('ticket_next_number')->nullable();
            $table->string('ticket_from_name')->nullable();
            $table->string('ticket_from_email')->nullable();
            $table->boolean('ticket_email_parse')->default(false);
            $table->boolean('ticket_client_general_notifications')->default(true);
            $table->boolean('ticket_autoclose')->default(false);
            $table->integer('ticket_autoclose_hours')->default(72);
            $table->string('ticket_new_ticket_notification_email')->nullable();

            // System Settings
            $table->boolean('enable_cron')->default(false);
            $table->string('cron_key')->nullable();
            $table->boolean('recurring_auto_send_invoice')->default(true);
            $table->boolean('enable_alert_domain_expire')->default(true);
            $table->boolean('send_invoice_reminders')->default(true);
            $table->string('invoice_overdue_reminders')->nullable();
            $table->string('theme')->default('blue');
            $table->boolean('telemetry')->default(false);
            $table->string('timezone')->default('America/New_York');
            $table->boolean('destructive_deletes_enable')->default(false);

            // Module Settings
            $table->boolean('module_enable_itdoc')->default(true);
            $table->boolean('module_enable_accounting')->default(true);
            $table->boolean('module_enable_ticketing')->default(true);
            $table->boolean('client_portal_enable')->default(true);
            $table->json('portal_branding_settings')->nullable();
            $table->json('portal_customization_settings')->nullable();
            $table->json('portal_access_controls')->nullable();
            $table->json('portal_feature_toggles')->nullable();
            $table->boolean('portal_self_service_tickets')->default(true);
            $table->boolean('portal_knowledge_base_access')->default(true);
            $table->boolean('portal_invoice_access')->default(true);
            $table->boolean('portal_payment_processing')->default(false);
            $table->boolean('portal_asset_visibility')->default(false);
            $table->json('portal_sso_settings')->nullable();
            $table->json('portal_mobile_settings')->nullable();
            $table->json('portal_dashboard_settings')->nullable();

            // Security Settings
            $table->text('login_message')->nullable();
            $table->boolean('login_key_required')->default(false);
            $table->string('login_key_secret')->nullable();

            $table->timestamps();
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('company_logo')->nullable();
            $table->json('company_colors')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_state')->nullable();
            $table->string('company_zip')->nullable();
            $table->string('company_country')->default('US');
            $table->string('company_phone')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_tax_id')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('company_holidays')->nullable();
            $table->string('company_language')->default('en');
            $table->string('company_currency')->default('USD');
            $table->json('custom_fields')->nullable();
            $table->json('localization_settings')->nullable();
            $table->integer('password_min_length')->default(8);
            $table->boolean('password_require_special')->default(true);
            $table->boolean('password_require_numbers')->default(true);
            $table->boolean('password_require_uppercase')->default(true);
            $table->integer('password_expiry_days')->default(90);
            $table->integer('password_history_count')->default(5);
            $table->boolean('two_factor_enabled')->default(false);
            $table->json('two_factor_methods')->nullable();
            $table->integer('session_timeout_minutes')->default(480);
            $table->boolean('force_single_session')->default(false);
            $table->integer('max_login_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(15);
            $table->json('allowed_ip_ranges')->nullable();
            $table->json('blocked_ip_ranges')->nullable();
            $table->boolean('geo_blocking_enabled')->default(false);
            $table->json('allowed_countries')->nullable();
            $table->json('sso_settings')->nullable();
            $table->boolean('audit_logging_enabled')->default(true);
            $table->integer('audit_retention_days')->default(365);
            $table->boolean('smtp_auth_required')->default(true);
            $table->boolean('smtp_use_tls')->default(true);
            $table->integer('smtp_timeout')->default(30);
            $table->integer('email_retry_attempts')->default(3);
            $table->json('email_templates')->nullable();
            $table->json('email_signatures')->nullable();
            $table->boolean('email_tracking_enabled')->default(false);
            $table->json('sms_settings')->nullable();
            $table->json('voice_settings')->nullable();
            $table->json('slack_settings')->nullable();
            $table->json('teams_settings')->nullable();
            $table->json('discord_settings')->nullable();
            $table->json('video_conferencing_settings')->nullable();
            $table->json('communication_preferences')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->boolean('multi_currency_enabled')->default(false);
            $table->json('supported_currencies')->nullable();
            $table->string('exchange_rate_provider')->nullable();
            $table->boolean('auto_update_exchange_rates')->default(true);
            $table->json('tax_calculation_settings')->nullable();
            $table->string('tax_engine_provider')->nullable();
            $table->json('payment_gateway_settings')->nullable();
            $table->json('stripe_settings')->nullable();
            $table->json('square_settings')->nullable();
            $table->json('paypal_settings')->nullable();
            $table->json('authorize_net_settings')->nullable();
            $table->json('ach_settings')->nullable();
            $table->boolean('recurring_billing_enabled')->default(true);
            $table->json('recurring_billing_settings')->nullable();
            $table->json('late_fee_settings')->nullable();
            $table->json('collection_settings')->nullable();
            $table->json('accounting_integration_settings')->nullable();
            $table->json('quickbooks_settings')->nullable();
            $table->json('xero_settings')->nullable();
            $table->json('sage_settings')->nullable();
            $table->boolean('revenue_recognition_enabled')->default(false);
            $table->json('revenue_recognition_settings')->nullable();
            $table->json('purchase_order_settings')->nullable();
            $table->json('expense_approval_settings')->nullable();
            $table->json('connectwise_automate_settings')->nullable();
            $table->json('datto_rmm_settings')->nullable();
            $table->json('ninja_rmm_settings')->nullable();
            $table->json('kaseya_vsa_settings')->nullable();
            $table->json('auvik_settings')->nullable();
            $table->json('prtg_settings')->nullable();
            $table->json('solarwinds_settings')->nullable();
            $table->json('monitoring_alert_thresholds')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->json('asset_discovery_settings')->nullable();
            $table->json('patch_management_settings')->nullable();
            $table->json('remote_access_settings')->nullable();
            $table->boolean('auto_create_tickets_from_alerts')->default(false);
            $table->json('alert_to_ticket_mapping')->nullable();
            $table->json('ticket_categorization_rules')->nullable();
            $table->json('ticket_priority_rules')->nullable();
            $table->json('sla_definitions')->nullable();
            $table->json('sla_escalation_policies')->nullable();
            $table->json('auto_assignment_rules')->nullable();
            $table->json('routing_logic')->nullable();
            $table->json('approval_workflows')->nullable();
            $table->boolean('time_tracking_enabled')->default(true);
            $table->json('time_tracking_settings')->nullable();
            $table->boolean('customer_satisfaction_enabled')->default(false);
            $table->json('csat_settings')->nullable();
            $table->json('ticket_templates')->nullable();
            $table->json('ticket_automation_rules')->nullable();
            $table->json('multichannel_settings')->nullable();
            $table->json('queue_management_settings')->nullable();
            $table->boolean('remember_me_enabled')->default(true);
            $table->json('wire_settings')->nullable();
            $table->json('check_settings')->nullable();
            $table->enum('imap_auth_method', ['password', 'oauth', 'token'])->nullable();

            // Indexes
            $table->index('company_id');
            $table->unique('company_id'); // One settings record per company

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
