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
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            
            // Version identification
            $table->string('version_number'); // v1.0, v1.1, v2.0, etc.
            $table->string('version_type')->default('revision'); // initial, revision, amendment, renewal
            $table->string('status')->default('draft'); // draft, review, approved, rejected, final
            
            // Version metadata
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('change_summary')->nullable(); // Summary of changes from previous version
            $table->json('changes')->nullable(); // Detailed change tracking
            
            // Snapshot of contract data at this version
            $table->json('contract_data'); // Full contract data snapshot
            $table->json('components'); // Component assignments at this version
            $table->json('pricing_snapshot'); // Pricing at this version
            
            // Approval and workflow
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->json('approvals')->nullable(); // Approval history
            $table->text('rejection_reason')->nullable();
            
            // Negotiation context
            $table->unsignedBigInteger('negotiation_id')->nullable();
            $table->string('branch')->nullable(); // For branching negotiations
            $table->boolean('is_client_visible')->default(false);
            $table->boolean('is_final')->default(false);
            
            // Audit fields
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['contract_id', 'version_number']);
            $table->index(['contract_id', 'status']);
            $table->index(['negotiation_id']);
            $table->unique(['contract_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};
