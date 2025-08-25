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
        Schema::create('contract_list_configurations', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // List configuration
            $table->string('contract_type_slug')->index();
            $table->json('columns_config')->nullable(); // Which columns to show, order, width, etc.
            $table->json('filters_config')->nullable(); // Available filters
            $table->json('search_config')->nullable(); // Search configuration
            $table->json('sorting_config')->nullable(); // Default sorting and available sort options
            $table->json('pagination_config')->nullable(); // Pagination settings
            $table->json('bulk_actions_config')->nullable(); // Available bulk actions
            $table->json('export_config')->nullable(); // Export options
            $table->boolean('show_row_actions')->default(true);
            $table->boolean('show_bulk_actions')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'contract_type_slug']);
            $table->index(['company_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['company_id', 'contract_type_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_list_configurations');
    }
};