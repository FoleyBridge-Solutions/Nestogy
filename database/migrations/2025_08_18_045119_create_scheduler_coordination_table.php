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
        Schema::create('scheduler_coordination', function (Blueprint $table) {
            $table->id();
            $table->string('job_name', 100);
            $table->string('schedule_key', 150); // e.g., "2025-08-18-recurring-billing"
            $table->string('server_id', 100);
            $table->timestamp('started_at');
            $table->timestamp('heartbeat_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Ensure only one server can run a specific job at a time
            $table->unique(['job_name', 'schedule_key'], 'scheduler_unique_execution');

            // Indexes for performance
            $table->index(['job_name', 'heartbeat_at'], 'scheduler_heartbeat_idx');
            $table->index('created_at', 'scheduler_cleanup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduler_coordination');
    }
};
