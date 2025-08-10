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
        Schema::create('client_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('certificate_type')->default('ssl'); // ssl, code_signing, email, ca
            $table->text('domains'); // JSON array of domains
            $table->string('issuer');
            $table->string('serial_number')->nullable();
            $table->text('fingerprint')->nullable();
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->integer('validity_days')->nullable();
            $table->enum('key_size', ['1024', '2048', '3072', '4096'])->default('2048');
            $table->string('algorithm')->default('RSA'); // RSA, ECC, DSA
            $table->enum('status', ['active', 'expired', 'revoked', 'pending', 'renewed'])->default('active');
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_days_before')->default(30);
            $table->date('last_renewed')->nullable();
            $table->date('next_renewal_check')->nullable();
            $table->string('renewal_method')->nullable(); // manual, acme, api
            $table->text('renewal_notes')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->string('order_number')->nullable();
            $table->text('csr')->nullable(); // Certificate Signing Request
            $table->longText('certificate_data')->nullable(); // PEM format
            $table->longText('private_key')->nullable(); // Encrypted private key
            $table->longText('intermediate_certificates')->nullable();
            $table->string('installation_server')->nullable();
            $table->text('installation_path')->nullable();
            $table->text('installation_notes')->nullable();
            $table->json('security_scan_results')->nullable();
            $table->timestamp('last_security_scan')->nullable();
            $table->json('validation_history')->nullable();
            $table->boolean('wildcard')->default(false);
            $table->boolean('extended_validation')->default(false);
            $table->text('organization_name')->nullable();
            $table->text('organizational_unit')->nullable();
            $table->string('country_code')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->json('san_domains')->nullable(); // Subject Alternative Names
            $table->json('alerts')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('expiry_date');
            $table->index('certificate_type');
            $table->index('issuer');
            $table->index('serial_number');
            $table->index('next_renewal_check');
            $table->index(['auto_renew', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_certificates');
    }
};