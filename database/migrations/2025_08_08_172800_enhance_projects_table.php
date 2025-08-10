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
        Schema::table('projects', function (Blueprint $table) {
            // Add company_id if not exists
            if (!Schema::hasColumn('projects', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            // Add or modify project code
            if (!Schema::hasColumn('projects', 'project_code')) {
                $table->string('project_code')->after('company_id')->unique();
            }
            
            // Enhanced status and priority fields
            if (!Schema::hasColumn('projects', 'status')) {
                $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled', 'archived'])
                      ->default('planning')->after('description');
            }
            
            if (!Schema::hasColumn('projects', 'priority')) {
                $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])
                      ->default('normal')->after('status');
            }
            
            // Enhanced date fields
            if (!Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable()->after('manager_id');
            }
            
            // Rename 'due' to 'due_date' if needed
            if (Schema::hasColumn('projects', 'due') && !Schema::hasColumn('projects', 'due_date')) {
                $table->renameColumn('due', 'due_date');
            }
            
            if (!Schema::hasColumn('projects', 'actual_start_date')) {
                $table->date('actual_start_date')->nullable()->after('due_date');
            }
            
            if (!Schema::hasColumn('projects', 'actual_end_date')) {
                $table->date('actual_end_date')->nullable()->after('actual_start_date');
            }
            
            // Budget and cost tracking
            if (!Schema::hasColumn('projects', 'budget')) {
                $table->decimal('budget', 12, 2)->nullable()->after('actual_end_date');
            }
            
            if (!Schema::hasColumn('projects', 'actual_cost')) {
                $table->decimal('actual_cost', 12, 2)->nullable()->after('budget');
            }
            
            if (!Schema::hasColumn('projects', 'budget_currency')) {
                $table->string('budget_currency', 3)->default('USD')->after('actual_cost');
            }
            
            // Progress tracking
            if (!Schema::hasColumn('projects', 'progress_percentage')) {
                $table->integer('progress_percentage')->default(0)->after('budget_currency');
            }
            
            // Categorization and metadata
            if (!Schema::hasColumn('projects', 'category')) {
                $table->enum('category', ['development', 'design', 'marketing', 'maintenance', 'consulting', 'support', 'research', 'other'])
                      ->nullable()->after('progress_percentage');
            }
            
            if (!Schema::hasColumn('projects', 'tags')) {
                $table->json('tags')->nullable()->after('category');
            }
            
            if (!Schema::hasColumn('projects', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('tags');
            }
            
            if (!Schema::hasColumn('projects', 'notes')) {
                $table->text('notes')->nullable()->after('custom_fields');
            }
            
            // Project type and template support
            if (!Schema::hasColumn('projects', 'is_billable')) {
                $table->boolean('is_billable')->default(true)->after('notes');
            }
            
            if (!Schema::hasColumn('projects', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('is_billable');
            }
            
            if (!Schema::hasColumn('projects', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('is_template');
                $table->foreign('template_id')->references('id')->on('projects')->onDelete('set null');
            }
            
            // Indexes for performance (only add if they don't exist)
            // Note: Some indexes may already exist from the main migration
            try {
                $table->index(['company_id', 'status'], 'projects_company_status_index');
            } catch (Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index(['company_id', 'priority'], 'projects_company_priority_index');
            } catch (Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index(['company_id', 'category'], 'projects_company_category_index');
            } catch (Exception $e) {
                // Index already exists, skip
            }
            
            // Skip manager_id index as it already exists from main migration
            // Skip client_id index as it already exists from main migration
            
            try {
                $table->index(['due_date'], 'projects_due_date_index');
            } catch (Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index(['status', 'due_date'], 'projects_status_due_date_index');
            } catch (Exception $e) {
                // Index already exists, skip
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Remove added columns (be careful with existing data)
            $columnsToRemove = [
                'project_code', 'status', 'priority', 'start_date', 'actual_start_date', 
                'actual_end_date', 'budget', 'actual_cost', 'budget_currency', 
                'progress_percentage', 'category', 'tags', 'custom_fields', 'notes',
                'is_billable', 'is_template', 'template_id'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    if ($column === 'template_id') {
                        $table->dropForeign(['template_id']);
                    }
                    if ($column === 'company_id') {
                        $table->dropForeign(['company_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};