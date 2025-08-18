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
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            
            // Jurisdiction identification
            $table->enum('jurisdiction_type', [
                'federal', 'state', 'county', 'city', 'municipality', 'special_district', 'zip_code'
            ]);
            $table->string('name');
            $table->string('code', 10)->unique();
            
            // Geographic codes
            $table->string('fips_code', 10)->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('county_code', 10)->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->json('zip_codes')->nullable(); // Array of ZIP codes
            $table->json('boundaries')->nullable(); // Geographic boundaries (polygons, etc.)
            
            // Hierarchy
            $table->unsignedBigInteger('parent_jurisdiction_id')->nullable();
            
            // Authority information
            $table->string('authority_name');
            $table->string('authority_contact')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            
            // Filing requirements
            $table->json('filing_requirements')->nullable();
            
            // Status and configuration
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(100);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['jurisdiction_type', 'is_active']);
            $table->index(['state_code', 'is_active']);
            $table->index('fips_code');
            $table->index('zip_code');
            $table->index('parent_jurisdiction_id');
            $table->index('priority');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_jurisdictions');
    }
};
