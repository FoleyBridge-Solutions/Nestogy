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
        // Create contract_templates table first
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('template_name');
            $table->string('template_type'); // 'service_agreement', 'maintenance', etc.
            $table->text('template_content');
            $table->json('template_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'template_type', 'is_active']);
        });

        // Now create contracts table with proper foreign key
        Schema::create('contracts_consolidated', function (Blueprint $table) {
            $table->id();
            
            // Company and identification
            $table->unsignedBigInteger('company_id')->index();
            $table->string('prefix', 10)->nullable();
            $table->unsignedInteger('number');
            $table->string('contract_number')->unique();
            
            // Contract basic info
            $table->enum('contract_type', [
                'service_agreement',
                'equipment_lease',
                'installation_contract',
                'maintenance_agreement',
                'sla_contract',
                'international_service',
                'master_service',
                'data_processing',
                'professional_services',
                'support_contract'
            ]);
            
            $table->enum('status', [
                'draft',
                'pending_review',
                'under_negotiation',
                'pending_signature',
                'signed',
                'active',
                'suspended',
                'terminated',
                'expired',
                'cancelled'
            ])->default('draft');
            
            $table->enum('signature_status', [
                'not_required',
                'pending',
                'client_signed',
                'company_signed',
                'fully_executed',
                'declined',
                'expired'
            ])->default('pending');
            
            // Contract details
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('term_months')->nullable();
            
            // Financial information
            $table->decimal('contract_value', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            
            // Template and generation info
            $table->string('template_type')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('url_key', 32)->unique()->nullable();
            $table->json('metadata')->nullable();
            
            // References
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('contract_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'contract_type']);
            $table->index(['company_id', 'client_id']);
            $table->index(['start_date', 'end_date']);
            $table->index('signature_status');
            $table->index('quote_id');
            $table->index('template_id');
            $table->index(['company_id', 'number']);
            
            // Unique constraints
            $table->unique(['company_id', 'prefix', 'number']);
        });

        // Create contract_signatures table
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('company_id')->index();
            $table->string('signer_type'); // 'client', 'company'
            $table->string('signer_name');
            $table->string('signer_email');
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_method'); // 'electronic', 'physical', 'digital'
            $table->text('signature_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts_consolidated')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['contract_id', 'signer_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
        Schema::dropIfExists('contracts_consolidated');
        Schema::dropIfExists('contract_templates');
    }
};