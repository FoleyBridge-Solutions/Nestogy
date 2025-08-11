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
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            
            $table->enum('role', ['manager', 'lead', 'developer', 'designer', 'tester', 'analyst', 'consultant', 'coordinator', 'client', 'observer'])
                  ->default('developer');
            
            // Billing and rates
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Permissions
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_manage_tasks')->default(false);
            $table->boolean('can_manage_time')->default(false);
            $table->boolean('can_view_reports')->default(true);
            
            // Membership status
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint - one membership per user per project
            $table->unique(['project_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['project_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};