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
        Schema::create('service_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('tax_jurisdiction_id');
            $table->unsignedBigInteger('tax_category_id')->nullable();
            
            // Service and tax type classification
            $table->string('service_type', 50); // voip, telecom, cloud, saas, etc.
            $table->string('tax_type', 50); // federal, state, local, regulatory, etc.
            $table->string('tax_name');
            $table->string('authority_name');
            $table->string('tax_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('regulatory_code', 50)->nullable(); // e911, usf, etc.
            
            // Rate configuration
            $table->enum('rate_type', ['percentage', 'fixed', 'tiered', 'per_line', 'per_minute', 'per_unit']);
            $table->decimal('percentage_rate', 8, 4)->nullable(); // For percentage rates
            $table->decimal('fixed_amount', 10, 4)->nullable(); // For fixed rates
            $table->decimal('minimum_threshold', 10, 4)->nullable();
            $table->decimal('maximum_amount', 10, 4)->nullable();
            
            // Calculation settings
            $table->enum('calculation_method', ['standard', 'compound', 'additive', 'inclusive', 'exclusive'])->default('standard');
            $table->json('service_types')->nullable(); // Array of applicable service types
            $table->json('conditions')->nullable(); // Additional conditions for tax application
            
            // Status and control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recoverable')->default(false); // Can this tax be recovered
            $table->boolean('is_compound')->default(false); // Does this tax compound on other taxes
            $table->unsignedSmallInteger('priority')->default(100); // Order of application
            
            // Effective dates
            $table->datetime('effective_date');
            $table->datetime('expiry_date')->nullable();
            
            // External integration
            $table->string('external_id')->nullable();
            $table->string('source')->nullable(); // Source of tax rate (manual, api, etc.)
            $table->datetime('last_updated_from_source')->nullable();
            
            // Metadata for complex scenarios
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['service_type', 'is_active']);
            $table->index(['tax_jurisdiction_id', 'is_active']);
            $table->index(['tax_type', 'is_active']);
            $table->index(['regulatory_code', 'is_active']);
            $table->index('priority');
            $table->index(['effective_date', 'expiry_date']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tax_rates');
    }
};