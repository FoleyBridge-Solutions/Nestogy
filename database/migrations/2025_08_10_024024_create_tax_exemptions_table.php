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
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('tax_jurisdiction_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            
            // Exemption identification
            $table->enum('exemption_type', [
                'resale', 'non_profit', 'government', 'educational', 'religious',
                'agricultural', 'manufacturing', 'medical', 'disability',
                'interstate', 'international', 'wholesale', 'carrier_access', 'custom'
            ]);
            $table->string('exemption_name');
            
            // Certificate information
            $table->string('certificate_number', 100)->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issuing_state', 2)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Exemption configuration
            $table->boolean('is_blanket_exemption')->default(false);
            $table->json('applicable_tax_types')->nullable();
            $table->json('applicable_services')->nullable();
            $table->json('exemption_conditions')->nullable();
            $table->decimal('exemption_percentage', 5, 2)->nullable();
            $table->decimal('maximum_exemption_amount', 12, 2)->nullable();
            
            // Status tracking
            $table->enum('status', [
                'active', 'expired', 'suspended', 'revoked', 'pending'
            ])->default('pending');
            $table->enum('verification_status', [
                'pending', 'verified', 'rejected', 'expired', 'needs_renewal'
            ])->default('pending');
            $table->datetime('last_verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Documentation
            $table->string('certificate_file_path')->nullable();
            $table->json('supporting_documents')->nullable();
            
            // Automation and priority
            $table->boolean('auto_apply')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'status']);
            $table->index(['exemption_type', 'status']);
            $table->index(['verification_status', 'status']);
            $table->index('expiry_date');
            $table->index(['auto_apply', 'status']);
            $table->index('priority');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('tax_jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('set null');
            $table->foreign('tax_category_id')->references('id')->on('tax_categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_exemptions');
    }
};
