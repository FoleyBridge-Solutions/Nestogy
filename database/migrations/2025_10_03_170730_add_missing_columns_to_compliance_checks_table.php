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
        Schema::table('compliance_checks', function (Blueprint $table) {
            // Foreign keys - contract_id and compliance_requirement_id are optional
            $table->foreignId('contract_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('compliance_requirement_id')->nullable()->after('contract_id');
            
            // Check details
            $table->enum('check_type', ['manual', 'automated', 'periodic', 'triggered', 'audit'])->default('manual')->after('compliance_requirement_id');
            $table->enum('status', ['pending', 'in_progress', 'passed', 'failed', 'needs_review', 'remediated'])->default('pending')->after('check_type');
            
            // Results
            $table->text('findings')->nullable()->after('status');
            $table->json('recommendations')->nullable()->after('findings');
            $table->json('evidence_documents')->nullable()->after('recommendations');
            
            // User and timing
            $table->foreignId('checked_by')->nullable()->after('evidence_documents')->constrained('users')->onDelete('set null');
            $table->timestamp('checked_at')->nullable()->after('checked_by');
            $table->date('next_check_date')->nullable()->after('checked_at');
            
            // Scoring
            $table->decimal('compliance_score', 5, 2)->nullable()->after('next_check_date');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable()->after('compliance_score');
            
            // Metadata
            $table->json('metadata')->nullable()->after('risk_level');
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['contract_id']);
            $table->index(['check_type']);
            $table->index(['next_check_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_checks', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['checked_by']);
            
            $table->dropColumn([
                'contract_id', 'compliance_requirement_id', 'check_type', 'status',
                'findings', 'recommendations', 'evidence_documents', 'checked_by',
                'checked_at', 'next_check_date', 'compliance_score', 'risk_level', 'metadata'
            ]);
        });
    }
};
