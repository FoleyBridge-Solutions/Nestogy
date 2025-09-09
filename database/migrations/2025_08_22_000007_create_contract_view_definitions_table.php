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
        Schema::create('contract_view_definitions', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // View definition
            $table->string('contract_type_slug')->index();
            $table->string('view_type')->index(); // index, show, edit, create
            $table->json('layout_config')->nullable(); // Layout configuration
            $table->json('fields_config')->nullable(); // Which fields to show and how
            $table->json('actions_config')->nullable(); // Available actions
            $table->json('permissions')->nullable(); // Required permissions
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'contract_type_slug']);
            $table->index(['company_id', 'view_type']);
            $table->index(['company_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['company_id', 'contract_type_slug', 'view_type'], 'unique_contract_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_view_definitions');
    }
};