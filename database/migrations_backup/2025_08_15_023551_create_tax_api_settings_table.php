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
        Schema::create('tax_api_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // API Provider identification
            $table->string('provider')->index(); // 'taxcloud', 'vat_comply', 'fcc', etc.
            $table->boolean('enabled')->default(false);

            // API Credentials (encrypted)
            $table->text('credentials')->nullable(); // JSON encrypted credentials

            // Configuration options
            $table->json('configuration')->nullable(); // Provider-specific config

            // Usage tracking
            $table->integer('monthly_api_calls')->default(0);
            $table->integer('monthly_limit')->nullable();
            $table->timestamp('last_api_call')->nullable();
            $table->decimal('monthly_cost', 10, 2)->default(0);

            // Status and health
            $table->enum('status', ['active', 'inactive', 'error', 'quota_exceeded'])->default('inactive');
            $table->text('last_error')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->json('health_data')->nullable(); // Provider status, rates, etc.

            // Audit trail
            $table->json('audit_log')->nullable(); // Track configuration changes
            $table->timestamps();

            // Indexes for performance
            $table->unique(['company_id', 'provider']);
            $table->index(['provider', 'enabled']);
            $table->index(['status', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_api_settings');
    }
};
