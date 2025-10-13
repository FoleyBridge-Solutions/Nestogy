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
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('device_fingerprint');
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('location_data')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedTinyInteger('trust_level')->default(50)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->enum('verification_method', ['email', 'sms', 'manual', 'suspicious_login'])->default('email');
            $table->boolean('created_from_suspicious_login')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['trust_level', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index(['last_used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
