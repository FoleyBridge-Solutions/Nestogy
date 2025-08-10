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
        Schema::create('contract_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Company and contract relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Audit details
            $table->string('action', 100); // action performed
            $table->enum('category', [
                'general', 
                'approval', 
                'signature', 
                'compliance', 
                'financial', 
                'milestone', 
                'document', 
                'system'
            ])->default('general');
            $table->text('description'); // human-readable description
            $table->json('details')->nullable(); // structured data about the action
            
            // Request tracking
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Timing
            $table->timestamp('occurred_at');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'occurred_at']);
            $table->index(['contract_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['category', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['occurred_at']);
            
            // Composite indexes for common queries
            $table->index(['company_id', 'category', 'occurred_at']);
            $table->index(['contract_id', 'category', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_audit_logs');
    }
};