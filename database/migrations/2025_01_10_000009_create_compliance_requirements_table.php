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
        Schema::create('compliance_requirements', function (Blueprint $table) {
            $table->id();
            
            // Company and contract relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            // Requirement details
            $table->string('requirement_type', 100); // gdpr_compliance, sox_compliance, etc.
            $table->string('title');
            $table->text('description')->nullable();
            
            // Classification
            $table->enum('category', [
                'legal', 
                'regulatory', 
                'business', 
                'technical', 
                'financial', 
                'data_protection'
            ])->default('legal');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Timeline
            $table->date('due_date')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending', 
                'compliant', 
                'non_compliant', 
                'under_review', 
                'exempt'
            ])->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            
            // Structured data
            $table->json('requirements_data')->nullable(); // specific requirement details
            $table->json('compliance_criteria')->nullable(); // criteria for compliance
            
            // Audit tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['contract_id', 'status']);
            $table->index(['requirement_type', 'status']);
            $table->index(['category', 'priority']);
            $table->index(['due_date']);
            $table->index(['status', 'due_date']); // for overdue requirements
            $table->index(['priority', 'status']); // for high priority items
            
            // Composite indexes for common queries
            $table->index(['company_id', 'category', 'status']);
            $table->index(['contract_id', 'priority', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_requirements');
    }
};