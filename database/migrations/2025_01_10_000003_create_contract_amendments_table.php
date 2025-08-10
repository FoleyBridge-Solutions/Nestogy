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
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            
            // Contract reference
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('company_id')->index();
            
            // Amendment identification
            $table->string('amendment_number', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('amendment_type', [
                'pricing_change',
                'term_extension',
                'scope_modification',
                'service_addition',
                'service_removal',
                'sla_modification',
                'payment_terms',
                'compliance_update',
                'general_modification'
            ]);
            
            // Amendment status and workflow
            $table->enum('status', [
                'draft',
                'pending_review',
                'under_negotiation',
                'pending_approval',
                'pending_signature',
                'executed',
                'rejected',
                'cancelled'
            ])->default('draft');
            
            // Amendment content
            $table->json('changes'); // Detailed changes being made
            $table->longText('change_summary')->nullable();
            $table->text('reason'); // Business reason for amendment
            $table->text('impact_analysis')->nullable(); // Impact on existing terms
            
            // Original and new values
            $table->json('original_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('affected_clauses')->nullable(); // Which contract clauses are affected
            
            // Financial impact
            $table->decimal('financial_impact', 15, 2)->nullable(); // Net change in contract value
            $table->enum('financial_impact_type', ['increase', 'decrease', 'neutral'])->nullable();
            $table->json('pricing_changes')->nullable();
            
            // Effective dates
            $table->date('effective_date');
            $table->date('expiration_date')->nullable();
            $table->boolean('retroactive')->default(false);
            $table->date('retroactive_date')->nullable();
            
            // Legal and compliance
            $table->text('legal_review_notes')->nullable();
            $table->boolean('requires_client_approval')->default(true);
            $table->boolean('requires_legal_review')->default(false);
            $table->json('compliance_impact')->nullable();
            
            // Approval workflow
            $table->json('approval_workflow')->nullable();
            $table->json('approval_history')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Signature requirements
            $table->boolean('requires_signature')->default(true);
            $table->enum('signature_status', [
                'not_required',
                'pending',
                'client_signed',
                'company_signed',
                'fully_executed'
            ])->default('pending');
            
            // Document management
            $table->json('attached_documents')->nullable(); // Related documents
            $table->string('amendment_document_path')->nullable(); // Generated amendment PDF
            $table->string('url_key', 32)->unique()->nullable(); // For public access
            
            // Version control
            $table->string('version', 20)->default('1.0');
            $table->unsignedBigInteger('parent_amendment_id')->nullable(); // If this is a revision
            $table->json('version_history')->nullable();
            
            // Integration and automation
            $table->json('integration_data')->nullable(); // Data for external systems
            $table->boolean('auto_apply')->default(false); // Auto-apply when conditions met
            $table->json('auto_apply_conditions')->nullable();
            
            // Tracking and notifications
            $table->timestamp('client_notified_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->json('notification_history')->nullable();
            
            // User tracking
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['contract_id', 'status']);
            $table->index(['company_id', 'amendment_type']);
            $table->index(['effective_date', 'status']);
            $table->index('signature_status');
            $table->index('parent_amendment_id');
            $table->index(['company_id', 'amendment_number']);
            
            // Unique constraints
            $table->unique(['contract_id', 'amendment_number']);
            
            // Foreign key constraints
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_amendment_id')->references('id')->on('contract_amendments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_amendments');
    }
};