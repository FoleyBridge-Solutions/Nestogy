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
        Schema::create('tax_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            
            // Category identification
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->enum('category_type', [
                'telecommunications', 'internet', 'data_services', 'equipment', 
                'installation', 'maintenance', 'hosting', 'software'
            ]);
            $table->text('description')->nullable();
            
            // Service configuration
            $table->json('service_types')->nullable(); // Array of applicable service types
            $table->json('tax_rules')->nullable(); // Specific tax rules for this category
            
            // Tax behavior
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_interstate')->default(false);
            $table->boolean('is_international')->default(false);
            $table->boolean('requires_jurisdiction_detection')->default(true);
            $table->enum('default_tax_treatment', [
                'standard', 'exempt', 'reduced', 'special'
            ])->default('standard');
            
            // Exemption rules
            $table->json('exemption_rules')->nullable();
            
            // Status and configuration
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['category_type', 'is_active']);
            $table->index(['is_taxable', 'is_active']);
            $table->index('priority');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_categories');
    }
};
