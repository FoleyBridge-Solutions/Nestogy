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
        Schema::create('suspicious_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->index();
            $table->string('verification_token', 64)->unique();
            $table->enum('status', ['pending', 'approved', 'denied', 'expired'])->default('pending')->index();
            $table->json('location_data')->nullable();
            $table->json('device_fingerprint')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('trusted_location_requested')->default(false);
            $table->unsignedTinyInteger('risk_score')->default(0)->index();
            $table->json('detection_reasons')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('approval_ip', 45)->nullable();
            $table->text('approval_user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['risk_score', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspicious_login_attempts');
    }
};
