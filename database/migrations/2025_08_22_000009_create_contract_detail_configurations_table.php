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
        Schema::create('contract_detail_configurations', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Detail view configuration
            $table->string('contract_type_slug')->index();
            $table->json('sections_config')->nullable(); // Sections to show and their configuration
            $table->json('tabs_config')->nullable(); // Tab configuration
            $table->json('sidebar_config')->nullable(); // Sidebar widgets and info
            $table->json('actions_config')->nullable(); // Available actions
            $table->json('related_data_config')->nullable(); // Related data to show
            $table->json('timeline_config')->nullable(); // Timeline/activity configuration
            $table->boolean('show_timeline')->default(true);
            $table->boolean('show_related_records')->default(true);
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
        Schema::dropIfExists('contract_detail_configurations');
    }
};