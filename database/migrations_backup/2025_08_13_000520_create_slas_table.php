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
        Schema::create('slas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Response Times (in minutes)
            $table->integer('critical_response_minutes')->default(60);
            $table->integer('high_response_minutes')->default(240); // 4 hours
            $table->integer('medium_response_minutes')->default(480); // 8 hours
            $table->integer('low_response_minutes')->default(1440); // 24 hours

            // Resolution Times (in minutes)
            $table->integer('critical_resolution_minutes')->default(240); // 4 hours
            $table->integer('high_resolution_minutes')->default(1440); // 24 hours
            $table->integer('medium_resolution_minutes')->default(4320); // 72 hours
            $table->integer('low_resolution_minutes')->default(10080); // 7 days

            // Business Hours & Coverage
            $table->time('business_hours_start')->default('09:00');
            $table->time('business_hours_end')->default('17:00');
            $table->json('business_days')->default('["monday","tuesday","wednesday","thursday","friday"]');
            $table->string('timezone')->default('UTC');
            $table->enum('coverage_type', ['24/7', 'business_hours', 'custom'])->default('business_hours');
            $table->boolean('holiday_coverage')->default(false);
            $table->boolean('exclude_weekends')->default(true);

            // Escalation Settings
            $table->boolean('escalation_enabled')->default(true);
            $table->json('escalation_levels')->nullable(); // Array of escalation rules
            $table->integer('breach_warning_percentage')->default(80);

            // Performance Targets
            $table->decimal('uptime_percentage', 5, 2)->default(99.50);
            $table->decimal('first_call_resolution_target', 5, 2)->default(75.00);
            $table->decimal('customer_satisfaction_target', 5, 2)->default(90.00);

            // Notifications
            $table->boolean('notify_on_breach')->default(true);
            $table->boolean('notify_on_warning')->default(true);
            $table->json('notification_emails')->nullable();

            // Validity Period
            $table->date('effective_from')->default(now()->toDateString());
            $table->date('effective_to')->nullable();

            $table->timestamps();

            // Indexes
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'is_default']);
            $table->index(['company_id', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slas');
    }
};
