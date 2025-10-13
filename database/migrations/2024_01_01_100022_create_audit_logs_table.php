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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('event_type', 50); // login, logout, create, update, delete, security, api
            $table->string('model_type')->nullable(); // Model class name
            $table->unsignedBigInteger('model_id')->nullable(); // Model ID
            $table->string('action'); // Specific action performed
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for creates/updates
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->decimal('execution_time', 10, 3)->nullable(); // in seconds
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('company_id');
            $table->index('event_type');
            $table->index('model_type');
            $table->index('model_id');
            $table->index('created_at');
            $table->index('ip_address');
            $table->index('severity');
            $table->index(['model_type', 'model_id']);
            $table->index(['company_id', 'event_type']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
