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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('session_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('device_name');
            $table->string('device_type')->nullable();
            $table->string('browser_name');
            $table->string('browser_version')->nullable();
            $table->string('os_name');
            $table->string('os_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('location_data')->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_trusted_device')->default(false);
            $table->string('two_factor_verified')->nullable();
            $table->string('two_factor_method')->nullable();
            $table->timestamp('two_factor_verified_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->string('session_data')->nullable();
            $table->string('security_flags')->nullable();
            $table->string('status')->default('active');
            $table->string('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
