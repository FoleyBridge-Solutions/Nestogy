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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('current_database_version', 10);
            $table->string('start_page')->default('clients.php');
            
            // SMTP Configuration
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('mail_from_email')->nullable();
            $table->string('mail_from_name')->nullable();
            
            // IMAP Configuration
            $table->string('imap_host')->nullable();
            $table->integer('imap_port')->nullable();
            $table->string('imap_encryption')->nullable();
            $table->string('imap_username')->nullable();
            $table->string('imap_password')->nullable();
            
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
            
            // Security Settings
            $table->text('login_message')->nullable();
            $table->boolean('login_key_required')->default(false);
            $table->string('login_key_secret')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->unique('company_id'); // One settings record per company
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