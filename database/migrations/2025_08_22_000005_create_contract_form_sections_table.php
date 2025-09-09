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
        Schema::create('contract_form_sections', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Form section definition
            $table->string('section_slug')->index();
            $table->string('section_name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->json('fields_order')->nullable(); // Array of field slugs in order
            $table->json('conditional_logic')->nullable(); // When to show/hide this section
            $table->json('layout_config')->nullable(); // Layout configuration (columns, etc.)
            $table->boolean('is_collapsible')->default(false);
            $table->boolean('is_collapsed_by_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'section_slug']);
            $table->index(['company_id', 'sort_order']);
            $table->index(['company_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['company_id', 'section_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_form_sections');
    }
};