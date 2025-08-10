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
        Schema::create('client_portal_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->boolean('portal_enabled')->default(true);
            $table->string('access_level')->default('full'); // full, limited, billing_only, view_only
            $table->json('permissions')->nullable(); // Granular permissions array
            $table->json('allowed_features')->nullable(); // Which portal features are accessible
            $table->json('restricted_features')->nullable(); // Specifically restricted features
            $table->boolean('invoice_access')->default(true);
            $table->boolean('payment_access')->default(true);
            $table->boolean('document_access')->default(true);
            $table->boolean('support_access')->default(true);
            $table->boolean('contract_access')->default(true);
            $table->boolean('billing_history_access')->default(true);
            $table->boolean('service_history_access')->default(true);
            $table->boolean('usage_analytics_access')->default(false);
            $table->boolean('auto_pay_management')->default(true);
            $table->boolean('payment_method_management')->default(true);
            $table->boolean('profile_management')->default(true);
            $table->boolean('notification_management')->default(true);
            $table->boolean('can_download_invoices')->default(true);
            $table->boolean('can_download_documents')->default(true);
            $table->boolean('can_submit_tickets')->default(true);
            $table->boolean('can_view_ticket_history')->default(true);
            $table->boolean('can_schedule_payments')->default(false);
            $table->boolean('can_setup_payment_plans')->default(false);
            $table->boolean('can_dispute_charges')->default(true);
            $table->boolean('can_request_service_changes')->default(false);
            $table->json('ip_whitelist')->nullable(); // IP address restrictions
            $table->json('ip_blacklist')->nullable(); // Blocked IP addresses
            $table->json('allowed_countries')->nullable(); // Geolocation restrictions
            $table->json('blocked_countries')->nullable(); // Blocked countries
            $table->json('time_restrictions')->nullable(); // Access time restrictions
            $table->boolean('require_two_factor')->default(false);
            $table->boolean('require_device_verification')->default(false);
            $table->integer('max_concurrent_sessions')->default(3);
            $table->integer('session_timeout_minutes')->default(120);
            $table->boolean('auto_logout_on_inactivity')->default(true);
            $table->integer('password_expiry_days')->nullable();
            $table->boolean('require_password_change')->default(false);
            $table->json('security_settings')->nullable(); // Additional security configurations
            $table->string('custom_domain')->nullable(); // Custom portal domain
            $table->json('branding_settings')->nullable(); // Custom branding configuration
            $table->json('notification_preferences')->nullable(); // Notification settings
            $table->string('preferred_language', 5)->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('currency_preference', 3)->nullable();
            $table->json('dashboard_layout')->nullable(); // Custom dashboard configuration
            $table->json('metadata')->nullable(); // Additional configuration data
            $table->timestamp('access_granted_at')->nullable();
            $table->timestamp('access_revoked_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_password_change_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('account_locked_until')->nullable();
            $table->text('access_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('portal_enabled');
            $table->index('access_level');
            $table->index('require_two_factor');
            $table->index('last_login_at');
            $table->index('account_locked_until');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'portal_enabled']);
            $table->index(['portal_enabled', 'access_level']);
            $table->index('created_by');
            $table->index('updated_by');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portal_access');
    }
};