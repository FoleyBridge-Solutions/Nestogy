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
        Schema::create('contract_field_definitions', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Field definition
            $table->string('field_slug')->index(); // Field identifier
            $table->string('field_type'); // text, select, date, client_selector, etc.
            $table->string('label'); // Display label
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('ui_config')->nullable(); // UI-specific configuration
            $table->json('options')->nullable(); // For select fields, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'field_slug']);
            $table->index(['company_id', 'field_type']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sort_order']);
            
            // Unique constraint
            $table->unique(['company_id', 'field_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_field_definitions');
    }
};