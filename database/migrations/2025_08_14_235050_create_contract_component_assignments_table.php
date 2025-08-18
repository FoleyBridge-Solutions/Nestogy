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
        Schema::create('contract_component_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('component_id');
            
            // Assignment configuration
            $table->json('configuration'); // Component-specific configuration for this contract
            $table->json('pricing_override')->nullable(); // Override component pricing
            $table->json('variable_values')->nullable(); // Variable values for this assignment
            
            // Status and ordering
            $table->string('status')->default('active'); // active, inactive, pending
            $table->integer('sort_order')->default(0);
            
            // Audit fields
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('contract_components')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['contract_id', 'status']);
            $table->index(['component_id']);
            $table->unique(['contract_id', 'component_id']); // Prevent duplicate assignments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_component_assignments');
    }
};
