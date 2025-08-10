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
            
            // Contract reference
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('company_id')->index();
            
            // Signatory information
            $table->enum('signatory_type', ['client', 'company', 'witness', 'notary']);
            $table->string('signatory_name');
            $table->string('signatory_email');
            $table->string('signatory_title')->nullable();
            $table->string('signatory_company')->nullable();
            
            // Signature details
            $table->enum('signature_type', ['electronic', 'digital', 'wet', 'docusign', 'hellosign', 'adobe_sign']);
            $table->enum('status', ['pending', 'sent', 'viewed', 'signed', 'declined', 'expired', 'voided']);
            $table->text('signature_data')->nullable(); // Base64 encoded signature or provider reference
            $table->string('signature_hash')->nullable(); // For verification
            $table->string('provider_reference_id')->nullable(); // Third-party signature provider ID
            
            // Digital signature provider details
            $table->string('provider', 50)->nullable(); // docusign, hellosign, adobe_sign
            $table->json('provider_metadata')->nullable();
            $table->string('envelope_id')->nullable(); // Provider's envelope/document ID
            $table->string('recipient_id')->nullable(); // Provider's recipient ID
            
            // Legal and verification
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable(); // Geographic location
            $table->json('biometric_data')->nullable(); // Touch pressure, speed, etc.
            $table->string('verification_code')->nullable();
            $table->boolean('identity_verified')->default(false);
            
            // Timestamps and tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Decline/void reasons
            $table->text('decline_reason')->nullable();
            $table->text('void_reason')->nullable();
            
            // Notifications and reminders
            $table->timestamp('last_reminder_sent')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->json('notification_settings')->nullable();
            
            // Legal compliance
            $table->boolean('legally_binding')->default(true);
            $table->string('compliance_standard')->nullable(); // ESIGN, UETA, eIDAS
            $table->json('audit_trail')->nullable();
            $table->string('certificate_id')->nullable(); // Digital certificate ID
            
            // Order and requirements
            $table->unsignedTinyInteger('signing_order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->json('required_fields')->nullable(); // Fields that must be filled
            $table->json('custom_fields')->nullable(); // Additional form fields
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['contract_id', 'signatory_type']);
            $table->index(['company_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('provider_reference_id');
            $table->index('envelope_id');
            $table->index(['signed_at', 'legally_binding']);
            $table->index('signatory_email');
            
            // Foreign key constraints
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
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