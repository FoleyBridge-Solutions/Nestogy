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
        Schema::create('contract_template_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('contract_templates')->onDelete('cascade');
            $table->foreignId('clause_id')->constrained('contract_clauses')->onDelete('cascade');
            $table->integer('sort_order')->default(0); // Order clauses appear in the contract
            $table->boolean('is_required')->nullable(); // Override clause default requirement
            $table->json('conditions')->nullable(); // Template-specific conditions for conditional clauses
            $table->json('variable_overrides')->nullable(); // Template-specific variable overrides
            $table->json('metadata')->nullable(); // Additional template-specific clause metadata
            $table->timestamps();

            // Unique constraint to prevent duplicate clause assignments
            $table->unique(['template_id', 'clause_id']);

            // Indexes for performance
            $table->index(['template_id', 'sort_order']);
            $table->index(['clause_id', 'template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_template_clauses');
    }
};
