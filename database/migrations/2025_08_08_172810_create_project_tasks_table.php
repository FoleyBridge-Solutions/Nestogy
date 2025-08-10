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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('task_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'blocked', 'completed', 'closed', 'cancelled'])
                  ->default('todo');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])
                  ->default('normal');
            
            // Assignment and ownership
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable();
            
            // Dates and timeline
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Time tracking
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->integer('progress_percentage')->default(0);
            
            // Categorization and metadata
            $table->enum('category', ['development', 'design', 'testing', 'documentation', 'research', 'meeting', 'review', 'other'])
                  ->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            // Task properties
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_pattern')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_task_id')->references('id')->on('project_tasks')->onDelete('set null');
            $table->foreign('milestone_id')->references('id')->on('project_milestones')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
            $table->index(['project_id', 'assigned_to']);
            $table->index(['project_id', 'parent_task_id']);
            $table->index(['project_id', 'milestone_id']);
            $table->index(['due_date']);
            $table->index(['status', 'due_date']);
            $table->index(['assigned_to', 'status']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};