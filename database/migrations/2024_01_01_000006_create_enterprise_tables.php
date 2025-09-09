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
        // Asset management tables
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('maintenance_type');
            $table->text('description');
            $table->dateTime('scheduled_date');
            $table->dateTime('completed_date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'scheduled_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('asset_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('warranty_provider');
            $table->string('warranty_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['manufacturer', 'extended', 'service_contract'])->default('manufacturer');
            $table->text('coverage_details')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['active', 'expired', 'claimed'])->default('active');
            $table->timestamps();
            
            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'end_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('residual_value', 12, 2)->default(0);
            $table->integer('useful_life_years');
            $table->enum('method', ['straight_line', 'declining_balance', 'sum_of_years'])->default('straight_line');
            $table->decimal('annual_depreciation', 12, 2);
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->decimal('current_book_value', 12, 2);
            $table->date('depreciation_start_date');
            $table->timestamps();
            
            $table->index(['company_id', 'asset_id']);
        });

        // Enhanced project management tables
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->integer('sort_order')->default(0);
            $table->json('deliverables')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->onDelete('set null');
            $table->foreignId('parent_task_id')->nullable()->constrained('project_tasks')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])->default('not_started');
            $table->integer('completion_percentage')->default(0);
            $table->timestamps();
            
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'milestone_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['manager', 'member', 'viewer'])->default('member');
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('can_log_time')->default(true);
            $table->boolean('can_edit_tasks')->default(false);
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'user_id']);
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->json('default_milestones')->nullable();
            $table->json('default_tasks')->nullable();
            $table->integer('estimated_duration_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained('project_tasks')->onDelete('cascade');
            $table->foreignId('depends_on_task_id')->constrained('project_tasks')->onDelete('cascade');
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])->default('finish_to_start');
            $table->integer('lag_days')->default(0);
            $table->timestamps();
            
            $table->index(['company_id', 'task_id']);
            $table->index(['company_id', 'depends_on_task_id']);
            $table->unique(['task_id', 'depends_on_task_id']);
        });

        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained('project_tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'task_id']);
            $table->index(['company_id', 'user_id']);
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained('project_tasks')->onDelete('cascade');
            $table->string('description');
            $table->boolean('is_completed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['company_id', 'task_id']);
            $table->index(['company_id', 'is_completed']);
        });

        // Advanced reporting tables
        Schema::create('report_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sort_order']);
        });

        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('report_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('configuration');
            $table->json('default_filters')->nullable();
            $table->enum('type', ['table', 'chart', 'summary', 'dashboard'])->default('table');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('report_templates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('filters');
            $table->json('configuration');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'template_id']);
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('report_name');
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('parameters');
            $table->timestamps();
            
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'expires_at']);
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('widget_type');
            $table->string('title');
            $table->json('configuration');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(1);
            $table->integer('height')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'widget_type']);
        });

        Schema::create('report_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('report_templates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->json('recipients');
            $table->json('filters');
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->time('delivery_time')->default('09:00:00');
            $table->dateTime('next_run');
            $table->dateTime('last_run')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'template_id']);
            $table->index(['company_id', 'next_run']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('report_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->string('metric_type');
            $table->decimal('value', 15, 4);
            $table->json('dimensions')->nullable();
            $table->date('metric_date');
            $table->timestamps();
            
            $table->index(['company_id', 'metric_name']);
            $table->index(['company_id', 'metric_date']);
            $table->index(['company_id', 'metric_type']);
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('kpi_name');
            $table->string('kpi_type');
            $table->decimal('target_value', 15, 4);
            $table->enum('comparison_operator', ['>', '<', '>=', '<=', '='])->default('>=');
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'kpi_name']);
            $table->index(['company_id', 'period']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('report_metrics');
        Schema::dropIfExists('report_subscriptions');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_categories');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('project_templates');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('asset_warranties');
        Schema::dropIfExists('asset_maintenance');
    }
};