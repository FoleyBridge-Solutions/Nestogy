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
        Schema::create('compliance_checks', function (Blueprint $table) {
            $table->id();
            
            // Company and relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compliance_requirement_id')->constrained()->cascadeOnDelete();
            
            // Check details
            $table->enum('check_type', ['manual', 'automated', 'scheduled', 'triggered'])->default('manual');
            $table->enum('status', [
                'compliant', 
                'non_compliant', 
                'partial_compliant', 
                'needs_review', 
                'pending'
            ]);
            
            // Findings and recommendations
            $table->text('findings')->nullable(); // detailed findings
            $table->json('recommendations')->nullable(); // array of recommendations
            $table->json('evidence_documents')->nullable(); // array of document references
            
            // Check metadata
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->timestamp('next_check_date')->nullable();
            
            // Scoring and risk assessment
            $table->decimal('compliance_score', 5, 2)->nullable(); // 0.00 to 100.00
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'checked_at']);
            $table->index(['contract_id', 'checked_at']);
            $table->index(['compliance_requirement_id', 'checked_at']);
            $table->index(['status', 'checked_at']);
            $table->index(['risk_level', 'checked_at']);
            $table->index(['check_type', 'status']);
            $table->index(['next_check_date']); // for scheduling
            
            // Composite indexes for common queries
            $table->index(['company_id', 'status', 'risk_level']);
            $table->index(['contract_id', 'status', 'checked_at']);
            $table->index(['compliance_requirement_id', 'status', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_checks');
    }
};