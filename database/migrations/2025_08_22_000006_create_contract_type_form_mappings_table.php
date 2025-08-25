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
        Schema::create('contract_type_form_mappings', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Mapping configuration
            $table->string('contract_type_slug')->index();
            $table->string('section_slug')->index();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('conditional_logic')->nullable(); // When to show this section for this contract type
            $table->json('field_overrides')->nullable(); // Override field settings for this contract type
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'contract_type_slug']);
            $table->index(['company_id', 'section_slug']);
            $table->index(['company_id', 'sort_order']);
            
            // Unique constraint
            $table->unique(['company_id', 'contract_type_slug', 'section_slug'], 'unique_contract_type_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_type_form_mappings');
    }
};