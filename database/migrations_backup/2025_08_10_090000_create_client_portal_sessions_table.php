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
        Schema::create('client_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->string('session_token', 128)->unique();
            $table->string('refresh_token', 128)->unique();
            $table->string('device_id', 64)->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable(); // web, mobile, tablet
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->json('location_data')->nullable(); // Country, region, city
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_trusted_device')->default(false);
            $table->boolean('two_factor_verified')->default(false);
            $table->string('two_factor_method')->nullable(); // sms, email, authenticator
            $table->timestamp('two_factor_verified_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('refresh_expires_at');
            $table->json('session_data')->nullable(); // Additional session information
            $table->json('security_flags')->nullable(); // Security-related flags
            $table->string('status')->default('active'); // active, expired, revoked, suspended
            $table->text('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('session_token');
            $table->index('refresh_token');
            $table->index('device_id');
            $table->index('ip_address');
            $table->index('status');
            $table->index('expires_at');
            $table->index('last_activity_at');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['device_id', 'client_id']);
            $table->index('is_trusted_device');
            $table->index('two_factor_verified');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portal_sessions');
    }
};