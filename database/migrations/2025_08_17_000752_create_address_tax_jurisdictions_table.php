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
        Schema::create('address_tax_jurisdictions', function (Blueprint $table) {
            $table->id();

            // Geographic identifiers
            $table->char('state_code', 2)->index();
            $table->string('county_code', 10)->index();

            // Address range components
            $table->unsignedInteger('address_from')->index();
            $table->unsignedInteger('address_to')->index();
            $table->enum('address_parity', ['even', 'odd', 'both'])->default('both');

            // Street components (indexed for fast lookup)
            $table->char('street_pre_dir', 2)->nullable();
            $table->string('street_name', 50)->index();
            $table->char('street_suffix', 6)->nullable();
            $table->char('street_post_dir', 2)->nullable();

            // Postal information
            $table->char('zip_code', 5)->index();
            $table->char('zip_plus4', 4)->nullable();

            // Primary jurisdiction IDs (indexed for fast tax calculation)
            $table->unsignedInteger('state_jurisdiction_id')->nullable()->index();
            $table->unsignedInteger('county_jurisdiction_id')->nullable()->index();
            $table->unsignedInteger('city_jurisdiction_id')->nullable()->index();
            $table->unsignedInteger('primary_transit_id')->nullable()->index();

            // Additional jurisdictions (JSON for flexibility)
            $table->json('additional_jurisdictions')->nullable();

            // Data source tracking
            $table->string('data_source', 30)->index();
            $table->timestamp('imported_at')->useCurrent();

            $table->timestamps();

            // Composite indexes for optimal performance
            $table->index(['state_code', 'zip_code', 'street_name', 'address_from', 'address_to'], 'idx_fast_address_lookup');
            $table->index(['state_jurisdiction_id', 'county_jurisdiction_id', 'city_jurisdiction_id'], 'idx_jurisdiction_lookup');
            $table->index(['state_code', 'county_code'], 'idx_state_county');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_tax_jurisdictions');
    }
};
