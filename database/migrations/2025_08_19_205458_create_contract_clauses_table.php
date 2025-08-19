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
        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Human-readable name like "Payment Terms", "Liability Clause"
            $table->string('slug')->unique(); // URL-friendly identifier like "payment-terms"
            $table->string('category'); // definitions, services, financial, legal, admin, etc.
            $table->enum('clause_type', ['required', 'conditional', 'optional'])->default('required');
            $table->longText('content'); // The actual clause content with template variables
            $table->json('variables')->nullable(); // Array of required template variables
            $table->json('conditions')->nullable(); // JSON for conditional logic like {{#if service_section_a}}
            $table->text('description')->nullable(); // Description of what this clause covers
            $table->integer('sort_order')->default(0); // Default order for clause positioning
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->string('version', 20)->default('1.0'); // Version tracking for clause updates
            $table->boolean('is_system')->default(false); // System clauses vs user-created
            $table->boolean('is_required')->default(false); // Whether clause is mandatory for contract type
            $table->json('applicable_contract_types')->nullable(); // Which contract types can use this clause
            $table->json('metadata')->nullable(); // Additional metadata for clause behavior
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'category', 'status']);
            $table->index(['company_id', 'clause_type', 'status']);
            $table->index(['slug', 'status']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_clauses');
    }
};