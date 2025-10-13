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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('service_type', ['consulting', 'support', 'maintenance', 'development', 'training', 'implementation', 'custom'])->default('custom');

            // Service Details
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->integer('sla_days')->nullable(); // Service level agreement days
            $table->integer('response_time_hours')->nullable();
            $table->integer('resolution_time_hours')->nullable();

            // Deliverables and Dependencies
            $table->json('deliverables')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('requirements')->nullable();

            // Scheduling
            $table->boolean('requires_scheduling')->default(false);
            $table->integer('min_notice_hours')->default(24);
            $table->integer('duration_minutes')->nullable();
            $table->json('availability_schedule')->nullable(); // Days/hours available

            // Team and Resources
            $table->foreignId('default_assignee_id')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('required_resources')->nullable();

            // Pricing Options
            $table->boolean('has_setup_fee')->default(false);
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->boolean('has_cancellation_fee')->default(false);
            $table->decimal('cancellation_fee', 10, 2)->nullable();
            $table->integer('cancellation_notice_hours')->default(24);

            // Contract Terms
            $table->integer('minimum_commitment_months')->nullable();
            $table->integer('maximum_duration_months')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_notice_days')->default(30);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
