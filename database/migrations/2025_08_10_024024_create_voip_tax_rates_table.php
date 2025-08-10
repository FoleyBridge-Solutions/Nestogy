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
        Schema::create('voip_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('tax_jurisdiction_id');
            $table->unsignedBigInteger('tax_category_id');
            
            // Tax identification
            $table->enum('tax_type', [
                'federal', 'state', 'local', 'municipal', 'county', 'special_district'
            ]);
            $table->string('tax_name');
            $table->string('authority_name');
            $table->string('tax_code', 50)->nullable();
            $table->text('description')->nullable();
            
            // Rate configuration
            $table->enum('rate_type', [
                'percentage', 'fixed', 'tiered', 'per_line', 'per_minute'
            ]);
            $table->decimal('percentage_rate', 8, 4)->nullable();
            $table->decimal('fixed_amount', 12, 4)->nullable();
            $table->decimal('minimum_threshold', 12, 2)->nullable();
            $table->decimal('maximum_amount', 12, 2)->nullable();
            
            // Calculation settings
            $table->enum('calculation_method', [
                'standard', 'compound', 'additive', 'inclusive', 'exclusive'
            ])->default('standard');
            $table->json('service_types')->nullable();
            $table->json('conditions')->nullable();
            
            // Status and configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recoverable')->default(false);
            $table->boolean('is_compound')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            
            // Effective dates
            $table->datetime('effective_date');
            $table->datetime('expiry_date')->nullable();
            
            // External integration
            $table->string('external_id', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->datetime('last_updated_from_source')->nullable();
            
            // Metadata and audit
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['tax_jurisdiction_id', 'tax_category_id']);
            $table->index(['tax_type', 'is_active']);
            $table->index(['effective_date', 'expiry_date']);
            $table->index('priority');
            $table->index('external_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('cascade');
            $table->foreign('tax_category_id')->references('id')->on('tax_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voip_tax_rates');
    }
};
