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
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('report_type'); // executive_dashboard, qbr, client_health, sla_report, etc.
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly']);
            $table->json('parameters')->nullable(); // Report-specific parameters
            $table->json('recipients'); // Array of email addresses or user IDs
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at');
            $table->string('timezone', 50)->default('UTC');
            $table->json('delivery_options')->nullable(); // Email subject, etc.
            $table->timestamp('last_run_at')->nullable();
            $table->json('last_run_result')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['next_run_at', 'is_active']);
            $table->index(['report_type']);
            $table->index(['frequency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};