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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->enum('category', ['development', 'design', 'marketing', 'consulting', 'maintenance', 'research', 'general', 'custom'])
                  ->default('general');
            
            // Template configuration
            $table->json('default_settings')->nullable();
            $table->json('task_templates')->nullable();
            $table->json('milestone_templates')->nullable();
            $table->json('role_templates')->nullable();
            
            // Estimates
            $table->integer('estimated_duration_days')->nullable();
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->string('budget_currency', 3)->default('USD');
            
            // Template properties
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['category']);
            $table->index(['is_public', 'is_active']);
            $table->index(['usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};