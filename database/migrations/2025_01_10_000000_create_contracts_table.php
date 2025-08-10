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
        Schema::create('contracts', function (Blueprint $table) {
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
            
            $table->string('title');
            $table->text('description')->nullable();
            
            // Contract term and dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('term_months')->nullable();
            
            // Renewal configuration
            $table->enum('renewal_type', [
                'none',
                'manual',
                'automatic',
                'negotiated'
            ])->default('manual');
            $table->unsignedInteger('renewal_notice_days')->nullable();
            $table->boolean('auto_renewal')->default(false);
            
            // Financial terms
            $table->decimal('contract_value', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->json('pricing_structure')->nullable();
            
            // VoIP and service specifications
            $table->json('sla_terms')->nullable();
            $table->json('voip_specifications')->nullable();
            $table->json('compliance_requirements')->nullable();
            
            // Contract content and clauses
            $table->longText('terms_and_conditions')->nullable();
            $table->json('custom_clauses')->nullable();
            $table->text('termination_clause')->nullable();
            $table->text('liability_clause')->nullable();
            $table->text('confidentiality_clause')->nullable();
            $table->text('dispute_resolution')->nullable();
            
            // Project management
            $table->json('milestones')->nullable();
            $table->json('deliverables')->nullable();
            $table->json('penalties')->nullable();
            
            // Legal information
            $table->string('governing_law')->nullable();
            $table->string('jurisdiction')->nullable();
            
            // Template and generation info
            $table->string('template_type')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('url_key', 32)->unique()->nullable();
            $table->json('metadata')->nullable();
            
            // Status tracking dates
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->date('next_review_date')->nullable();
            
            // Relationships
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            
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
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('contract_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('signed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};