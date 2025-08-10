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
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('depends_on_task_id');
            
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])
                  ->default('finish_to_start');
            $table->integer('lag_days')->default(0);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('task_id')->references('id')->on('project_tasks')->onDelete('cascade');
            $table->foreign('depends_on_task_id')->references('id')->on('project_tasks')->onDelete('cascade');
            
            // Unique constraint - prevent duplicate dependencies
            $table->unique(['task_id', 'depends_on_task_id']);
            
            // Indexes for performance
            $table->index(['task_id']);
            $table->index(['depends_on_task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};