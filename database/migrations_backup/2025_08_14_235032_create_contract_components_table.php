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
        Schema::create('contract_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            // Component identification
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // 'service', 'billing', 'sla', 'legal'
            $table->string('component_type'); // 'msp_monitoring', 'backup_service', 'per_device_billing', etc.

            // Component configuration
            $table->json('configuration'); // Flexible configuration options
            $table->json('pricing_model')->nullable(); // Pricing structure for this component
            $table->json('dependencies')->nullable(); // Required/incompatible components

            // Template and content
            $table->text('template_content')->nullable(); // Contract text template
            $table->json('variables')->nullable(); // Available variables for this component

            // Status and metadata
            $table->string('status')->default('active'); // active, inactive, deprecated
            $table->boolean('is_system')->default(false); // System vs custom components
            $table->integer('sort_order')->default(0);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_components');
    }
};
