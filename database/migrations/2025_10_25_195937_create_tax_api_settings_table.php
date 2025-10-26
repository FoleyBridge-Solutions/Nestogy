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
            $table->string('provider');
            $table->boolean('enabled')->default(false);
            $table->json('credentials');
            $table->json('configuration');
            $table->integer('monthly_api_calls')->default(0);
            $table->integer('monthly_limit')->nullable();
            $table->timestamp('last_api_call')->nullable();
            $table->decimal('monthly_cost', 10, 2)->default(0);
            $table->string('status');
            $table->text('last_error')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->json('health_data')->nullable();
            $table->json('audit_log')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'provider']);
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
