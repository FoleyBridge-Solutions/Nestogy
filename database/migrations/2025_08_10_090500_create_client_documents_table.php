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
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->string('type'); // invoice, contract, manual, certificate, report, receipt, statement
            $table->string('category')->nullable(); // billing, technical, legal, marketing, support
            $table->string('subcategory')->nullable(); // More specific categorization
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size'); // In bytes
            $table->string('storage_path'); // Path in storage system
            $table->string('storage_disk')->default('local'); // Storage disk name
            $table->string('file_hash', 64)->nullable(); // SHA-256 hash for integrity
            $table->string('checksum', 32)->nullable(); // MD5 checksum
            
            // Document Classification
            $table->boolean('is_system_generated')->default(false); // Auto-generated vs uploaded
            $table->boolean('is_template_based')->default(false); // Generated from template
            $table->string('template_id')->nullable(); // Template used for generation
            $table->boolean('is_signed')->default(false); // Digitally signed
            $table->json('signatures')->nullable(); // Digital signature information
            $table->boolean('is_encrypted')->default(false); // File encryption status
            $table->string('encryption_method')->nullable(); // Encryption algorithm used
            
            // Access Control
            $table->string('visibility')->default('private'); // private, shared, public
            $table->boolean('requires_authentication')->default(true);
            $table->boolean('download_enabled')->default(true);
            $table->boolean('view_enabled')->default(true);
            $table->boolean('print_enabled')->default(true);
            $table->boolean('share_enabled')->default(false);
            $table->json('access_permissions')->nullable(); // Granular permissions
            $table->json('download_restrictions')->nullable(); // Download limitations
            
            // Security and Compliance
            $table->integer('security_level')->default(1); // 1-5, 5 being highest
            $table->boolean('contains_pii')->default(false); // Contains personally identifiable info
            $table->boolean('contains_phi')->default(false); // Contains protected health info
            $table->boolean('contains_financial_data')->default(false);
            $table->json('compliance_tags')->nullable(); // GDPR, HIPAA, SOX, etc.
            $table->boolean('requires_retention')->default(false);
            $table->date('retention_until')->nullable();
            $table->boolean('auto_delete_enabled')->default(false);
            
            // Versioning and History
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('parent_document_id')->nullable(); // Previous version
            $table->boolean('is_current_version')->default(true);
            $table->json('version_notes')->nullable(); // Change log
            $table->timestamp('superseded_at')->nullable(); // When this version was replaced
            $table->unsignedBigInteger('superseded_by')->nullable(); // User who created new version
            
            // Related Objects
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('related_model_type')->nullable(); // Polymorphic relation
            $table->unsignedBigInteger('related_model_id')->nullable();
            
            // Document Processing
            $table->string('processing_status')->default('ready'); // ready, processing, failed, corrupted
            $table->text('processing_error')->nullable();
            $table->boolean('text_extracted')->default(false); // OCR or text extraction completed
            $table->longText('extracted_text')->nullable(); // Searchable text content
            $table->json('metadata_extracted')->nullable(); // Document metadata
            $table->boolean('thumbnail_generated')->default(false);
            $table->string('thumbnail_path')->nullable();
            $table->integer('page_count')->nullable(); // For PDF documents
            
            // Delivery and Notifications
            $table->boolean('email_delivery_enabled')->default(false);
            $table->json('email_recipients')->nullable(); // Who should receive via email
            $table->timestamp('last_emailed_at')->nullable();
            $table->boolean('portal_notification_sent')->default(false);
            $table->timestamp('portal_notification_sent_at')->nullable();
            $table->boolean('requires_acknowledgment')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgment_method')->nullable();
            
            // Usage Analytics
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->integer('print_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->timestamp('last_downloaded_at')->nullable();
            $table->json('access_history')->nullable(); // Detailed access log
            
            // Integration and Sync
            $table->string('external_id')->nullable(); // ID in external system
            $table->string('external_source')->nullable(); // Source system name
            $table->json('external_metadata')->nullable(); // Metadata from external system
            $table->boolean('sync_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            
            // Tags and Labels
            $table->json('tags')->nullable(); // Document tags for organization
            $table->json('labels')->nullable(); // System labels
            $table->string('reference_number')->nullable(); // Business reference
            $table->date('document_date')->nullable(); // Date on the document itself
            $table->date('received_date')->nullable(); // When we received it
            $table->date('effective_date')->nullable(); // When it becomes effective
            $table->date('expiry_date')->nullable(); // When it expires
            
            // Client Portal Settings
            $table->boolean('show_in_portal')->default(true);
            $table->integer('portal_sort_order')->nullable();
            $table->string('portal_display_name')->nullable(); // Override display name
            $table->text('portal_description')->nullable(); // Portal-specific description
            $table->string('portal_icon')->nullable(); // Icon for portal display
            $table->json('portal_settings')->nullable(); // Portal-specific configurations
            
            // Workflow and Approval
            $table->string('workflow_status')->nullable(); // pending, approved, rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_workflow')->nullable(); // Approval process definition
            
            // Custom Fields and Metadata
            $table->json('custom_fields')->nullable(); // Custom document fields
            $table->json('metadata')->nullable(); // Additional metadata
            $table->text('notes')->nullable(); // Internal notes
            $table->text('client_notes')->nullable(); // Notes visible to client
            
            // Lifecycle Management
            $table->string('status')->default('active'); // active, archived, deleted, expired
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable(); // Soft delete
            $table->text('deletion_reason')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('type');
            $table->index('category');
            $table->index('filename');
            $table->index('mime_type');
            $table->index('file_hash');
            $table->index('is_system_generated');
            $table->index('visibility');
            $table->index('status');
            $table->index('version');
            $table->index('is_current_version');
            $table->index('parent_document_id');
            $table->index('invoice_id');
            $table->index('payment_id');
            $table->index('contract_id');
            $table->index('ticket_id');
            $table->index('project_id');
            $table->index('processing_status');
            $table->index('show_in_portal');
            $table->index('document_date');
            $table->index('expiry_date');
            $table->index('archived_at');
            $table->index('deleted_at');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'show_in_portal']);
            $table->index(['client_id', 'status']);
            $table->index(['type', 'category']);
            $table->index(['status', 'show_in_portal']);
            $table->index(['related_model_type', 'related_model_id']);
            $table->index('uploaded_by');
            $table->index('updated_by');
            $table->index('approved_by');
            $table->index('superseded_by');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('parent_document_id')->references('id')->on('client_documents')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('superseded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};