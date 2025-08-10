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
        Schema::create('client_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('domain_name');
            $table->string('tld'); // Top Level Domain (.com, .org, etc.)
            $table->enum('domain_type', ['primary', 'subdomain', 'addon', 'parked'])->default('primary');
            $table->string('registrar');
            $table->string('registrar_account')->nullable();
            $table->date('registration_date');
            $table->date('expiry_date');
            $table->integer('registration_years')->default(1);
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_days_before')->default(30);
            $table->date('last_renewed')->nullable();
            $table->date('next_renewal_check')->nullable();
            $table->decimal('registration_cost', 10, 2)->default(0);
            $table->decimal('renewal_cost', 10, 2)->default(0);
            $table->enum('status', ['active', 'expired', 'pending_transfer', 'suspended', 'locked', 'redemption'])->default('active');
            $table->boolean('privacy_protection')->default(false);
            $table->boolean('domain_lock')->default(true);
            $table->string('auth_code')->nullable(); // EPP code for transfers
            $table->json('nameservers')->nullable(); // Array of nameservers
            $table->json('dns_records')->nullable(); // DNS record configuration
            $table->string('hosting_provider')->nullable();
            $table->text('hosting_account')->nullable();
            $table->json('email_settings')->nullable(); // MX records, email forwarding
            $table->json('ssl_certificates')->nullable(); // Associated certificates
            $table->boolean('cdn_enabled')->default(false);
            $table->string('cdn_provider')->nullable();
            $table->json('security_settings')->nullable(); // Security configurations
            $table->json('monitoring_settings')->nullable(); // Uptime monitoring
            $table->timestamp('last_dns_check')->nullable();
            $table->json('dns_check_results')->nullable();
            $table->timestamp('last_uptime_check')->nullable();
            $table->decimal('uptime_percentage', 5, 2)->nullable();
            $table->json('whois_data')->nullable(); // WHOIS information
            $table->timestamp('last_whois_update')->nullable();
            $table->text('registrant_name')->nullable();
            $table->text('registrant_email')->nullable();
            $table->text('registrant_phone')->nullable();
            $table->text('registrant_organization')->nullable();
            $table->text('registrant_address')->nullable();
            $table->string('registrant_city')->nullable();
            $table->string('registrant_state')->nullable();
            $table->string('registrant_postal_code')->nullable();
            $table->string('registrant_country')->nullable();
            $table->text('admin_contact')->nullable();
            $table->text('tech_contact')->nullable();
            $table->text('billing_contact')->nullable();
            $table->json('transfer_history')->nullable();
            $table->json('alerts')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('domain_name');
            $table->index('registrar');
            $table->index('expiry_date');
            $table->index('domain_type');
            $table->index('next_renewal_check');
            $table->index(['auto_renew', 'expiry_date']);
            $table->index('tld');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_domains');
    }
};