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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
                        $table->string('session_token')->unique();
                        $table->string('refresh_token')->unique();
                        $table->string('device_id')->nullable();
                        $table->string('device_name')->nullable();
                        $table->string('device_type')->nullable();
                        $table->string('browser_name')->nullable();
                        $table->string('browser_version')->nullable();
                        $table->string('os_name')->nullable();
                        $table->string('os_version')->nullable();
                        $table->string('ip_address')->nullable();
                        $table->text('user_agent')->nullable();
                        $table->json('location_data')->nullable();
                        $table->boolean('is_mobile')->default(false);
                        $table->boolean('is_trusted_device')->default(false);
                        $table->boolean('two_factor_verified')->default(false);
                        $table->string('two_factor_method')->nullable();
                        $table->timestamp('two_factor_verified_at')->nullable();
                        $table->timestamp('last_activity_at')->nullable();
                        $table->timestamp('expires_at');
                        $table->timestamp('refresh_expires_at');
                        $table->json('session_data')->nullable();
                        $table->json('security_flags')->nullable();
                        $table->string('status')->default('active');
                        $table->string('revocation_reason')->nullable();
                        $table->timestamp('revoked_at')->nullable();
                        $table->timestamps();
                        $table->softDeletes();
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
