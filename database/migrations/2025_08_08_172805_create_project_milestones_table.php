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
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue', 'cancelled'])
                  ->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])
                  ->default('normal');
            
            // Deliverables and acceptance criteria
            $table->json('deliverables')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            
            // Milestone properties
            $table->boolean('is_critical')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'due_date']);
            $table->index(['due_date']);
            $table->index(['status', 'due_date']);
            $table->index(['is_critical']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};