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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('report_type'); // 'daily', 'weekly', 'monthly', 'quarterly', 'annual', 'custom'
            $table->string('report_name');
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled'); // 'scheduled', 'generating', 'completed', 'failed'
            $table->string('frequency'); // 'once', 'daily', 'weekly', 'monthly', 'quarterly', 'annually'
            $table->json('schedule_config')->nullable(); // Cron expression, time preferences
            $table->json('filters')->nullable(); // Date ranges, client filters, etc.
            $table->json('metrics')->nullable(); // Which KPIs to include
            $table->string('format')->default('pdf'); // 'pdf', 'excel', 'csv', 'json'
            $table->json('recipients')->nullable(); // Email addresses for automated delivery
            $table->json('parameters')->nullable(); // Additional report parameters
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->string('file_path')->nullable(); // Path to generated report file
            $table->bigInteger('file_size')->nullable(); // Size in bytes
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_deliver')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'report_type', 'status']);
            $table->index(['next_generation_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};