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
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name')->index();
            $table->timestamp('run_date')->index();
            $table->enum('status', ['success', 'failed', 'completed_with_errors', 'running'])->default('running');
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('execution_time')->nullable()->comment('Execution time in seconds');
            $table->string('triggered_by')->nullable()->comment('User, system, or schedule');
            $table->timestamps();
            
            // Index for finding recent runs
            $table->index(['job_name', 'run_date']);
            $table->index(['job_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};