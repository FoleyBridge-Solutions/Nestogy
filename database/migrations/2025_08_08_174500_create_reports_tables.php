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
        // Create report_templates table
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'financial',
                'operational', 
                'performance',
                'compliance',
                'executive',
                'custom'
            ])->default('custom');
            $table->enum('report_type', [
                'financial',
                'tickets', 
                'assets',
                'clients',
                'projects',
                'users',
                'custom',
                'dashboard'
            ]);
            $table->json('template_config')->nullable();
            $table->json('default_filters')->nullable();
            $table->json('default_metrics');
            $table->json('chart_config')->nullable();
            $table->json('layout_config')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->decimal('version', 3, 1)->default(1.0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['report_type', 'category']);
            $table->index(['is_public', 'is_system']);
        });

        // Create reports table
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('report_type', [
                'financial',
                'tickets',
                'assets', 
                'clients',
                'projects',
                'users',
                'custom',
                'dashboard'
            ]);
            $table->json('report_config');
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_scheduled')->default(false);
            $table->enum('schedule_type', [
                'daily',
                'weekly', 
                'monthly',
                'quarterly',
                'annually'
            ])->nullable();
            $table->json('schedule_config')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->json('recipients')->nullable();
            $table->enum('export_format', ['pdf', 'csv', 'xlsx', 'json'])->default('pdf');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['report_type', 'status']);
            $table->index(['is_scheduled', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['next_generation_at']);
        });

        // Create report_generations table
        Schema::create('report_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->enum('status', ['pending', 'generating', 'completed', 'failed']);
            $table->json('generation_config')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['report_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Create report_shares table  
        Schema::create('report_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('share_token')->unique()->nullable();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['report_id', 'is_active']);
            $table->index(['shared_with', 'is_active']);
            $table->index(['share_token']);
            $table->index(['expires_at']);
        });

        // Create report_subscriptions table for email notifications
        Schema::create('report_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('frequency', [
                'daily',
                'weekly',
                'monthly',
                'quarterly', 
                'annually'
            ]);
            $table->json('delivery_config')->nullable(); // time, day of week, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();
            
            $table->unique(['report_id', 'user_id']);
            $table->index(['is_active', 'next_send_at']);
        });

        // Create report_bookmarks table for user bookmarks
        Schema::create('report_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->string('custom_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'report_id']);
        });

        // Create report_audit_logs table for tracking access
        Schema::create('report_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // view, generate, export, share, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['report_id', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
        Schema::dropIfExists('report_bookmarks');
        Schema::dropIfExists('report_subscriptions');
        Schema::dropIfExists('report_shares');
        Schema::dropIfExists('report_generations');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_templates');
    }
};