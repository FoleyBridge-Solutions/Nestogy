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
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Contract relationship
            $table->unsignedBigInteger('contract_id')->index();
            
            // Signer information
            $table->string('signer_type'); // 'client', 'company', 'witness', 'third_party'
            $table->string('signer_role')->nullable(); // 'authorized_signatory', 'ceo', 'manager', etc.
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_title')->nullable();
            $table->string('signer_company')->nullable();
            
            // Signature status and tracking
            $table->string('status')->default('pending')->index();
            // Statuses: pending, sent, viewed, signed, declined, expired, voided
            
            // Signature details
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_method')->nullable(); // 'electronic', 'physical', 'digital_certificate'
            $table->text('signature_data')->nullable(); // Base64 encoded signature image or digital signature
            $table->string('signature_hash')->nullable(); // For verification
            
            // Authentication and verification
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Document versioning
            $table->string('document_version')->nullable();
            $table->string('document_hash')->nullable(); // Hash of the document at signing time
            
            // Consent and agreement
            $table->boolean('consent_to_electronic_signature')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->text('additional_terms_accepted')->nullable();
            
            // Notification and reminders
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            
            // Rejection or decline details
            $table->text('decline_reason')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // Audit trail
            $table->json('audit_trail')->nullable(); // Track all actions related to this signature
            
            // Order for multiple signers
            $table->unsignedInteger('signing_order')->default(1);
            $table->boolean('requires_previous_signatures')->default(false);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            
            // Composite indexes
            $table->index(['company_id', 'contract_id']);
            $table->index(['contract_id', 'signer_type']);
            $table->index(['contract_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['contract_id', 'signing_order']);
            
            // Unique constraint to prevent duplicate signatures
            $table->unique(['contract_id', 'signer_email', 'signer_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
    }
};