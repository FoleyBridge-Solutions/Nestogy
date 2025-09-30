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
        Schema::create('rmm_client_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('integration_id');
            $table->string('rmm_client_id'); // RMM system's client identifier
            $table->string('rmm_client_name'); // RMM system's client name for reference
            $table->json('rmm_client_data')->nullable(); // Store additional RMM client data
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            // Indexes and constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('integration_id')->references('id')->on('rmm_integrations')->onDelete('cascade');

            // Ensure unique mapping per integration
            $table->unique(['integration_id', 'client_id'], 'unique_integration_client_mapping');
            $table->unique(['integration_id', 'rmm_client_id'], 'unique_integration_rmm_client_mapping');

            // Index for company scoping
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rmm_client_mappings');
    }
};
