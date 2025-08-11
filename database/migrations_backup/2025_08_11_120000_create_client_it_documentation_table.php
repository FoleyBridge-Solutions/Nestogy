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
        Schema::create('client_it_documentation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('authored_by')->constrained('users')->onDelete('cascade');
            
            // Basic information
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('it_category', [
                'runbook',
                'troubleshooting', 
                'architecture',
                'backup_recovery',
                'monitoring',
                'change_management',
                'business_continuity',
                'user_guide',
                'compliance',
                'vendor_procedure'
            ]);
            
            // IT-specific fields
            $table->json('system_references')->nullable(); // Related assets, networks, services
            $table->json('ip_addresses')->nullable(); // Associated IP ranges/addresses
            $table->json('software_versions')->nullable(); // Version tracking
            $table->json('compliance_requirements')->nullable(); // Regulatory/audit requirements
            $table->enum('review_schedule', ['monthly', 'quarterly', 'annually', 'as_needed'])->default('annually');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->enum('access_level', ['public', 'confidential', 'restricted', 'admin_only'])->default('confidential');
            $table->json('procedure_steps')->nullable(); // Step-by-step instructions
            $table->json('related_entities')->nullable(); // Links to other client entities
            $table->json('tags')->nullable(); // Searchable tags
            
            // Version control
            $table->string('version', 20)->default('1.0');
            $table->foreignId('parent_document_id')->nullable()->constrained('client_it_documentation')->onDelete('cascade');
            
            // File handling (for attachments)
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('filename')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_hash')->nullable();
            
            // Audit fields
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'it_category']);
            $table->index(['tenant_id', 'access_level']);
            $table->index(['next_review_at']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_it_documentation');
    }
};