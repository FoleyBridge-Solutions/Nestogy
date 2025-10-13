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
        Schema::create('tax_api_query_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('api_provider', 50); // 'vatcomply', 'nominatim', 'fcc', 'taxcloud', etc.
            $table->string('query_type', 50); // 'geocoding', 'vat_validation', 'tax_rate', etc.
            $table->string('query_hash', 64)->index(); // SHA256 hash of query parameters
            $table->json('query_parameters'); // Original query parameters
            $table->json('api_response'); // Cached API response
            $table->timestamp('api_called_at'); // When the API was actually called
            $table->timestamp('expires_at'); // When cache expires (default 30 days)
            $table->string('status', 20)->default('success'); // 'success', 'error', 'rate_limited'
            $table->text('error_message')->nullable(); // Error details if failed
            $table->decimal('response_time_ms', 8, 2)->nullable(); // API response time
            $table->timestamps();

            // Indexes for efficient lookups
            $table->index(['company_id', 'api_provider', 'query_type']);
            $table->index(['query_hash', 'expires_at']);
            $table->index(['api_provider', 'status']);
            $table->index('expires_at'); // For cleanup jobs

            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_api_query_cache');
    }
};
