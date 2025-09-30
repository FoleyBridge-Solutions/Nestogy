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
        Schema::create('rmm_integrations', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy column (required by BelongsToCompany trait)
            $table->unsignedBigInteger('company_id');

            // RMM Type (TRMM for TacticalRMM, extensible for other RMMs)
            $table->enum('rmm_type', ['TRMM'])->default('TRMM');

            // User-friendly name for the integration
            $table->string('name');

            // Encrypted API URL (TacticalRMM server URL)
            $table->text('api_url_encrypted');

            // Encrypted API Key for authentication
            $table->text('api_key_encrypted');

            // Integration status
            $table->boolean('is_active')->default(true);

            // Last successful synchronization timestamp
            $table->timestamp('last_sync_at')->nullable();

            // JSON field for RMM-specific configurations and settings
            $table->json('settings')->nullable();

            // Sync statistics
            $table->integer('total_agents')->default(0);
            $table->integer('last_alerts_count')->default(0);

            // Standard Laravel timestamps
            $table->timestamps();

            // Soft deletes for safe removal
            $table->softDeletes();

            // Indexes for performance
            $table->index('company_id', 'idx_rmm_integrations_company');
            $table->index('rmm_type', 'idx_rmm_integrations_type');
            $table->index('is_active', 'idx_rmm_integrations_active');
            $table->index('last_sync_at', 'idx_rmm_integrations_sync');

            // Unique constraint to prevent multiple integrations of same type per company
            $table->unique(['company_id', 'rmm_type'], 'unique_company_rmm_type');

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade'); // Delete integrations when company is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rmm_integrations');
    }
};
