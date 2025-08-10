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
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            
            // Contract reference
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('company_id')->index();
            
            // Milestone identification
            $table->string('milestone_number', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('milestone_type', [
                'project_phase',
                'deliverable',
                'payment_milestone',
                'service_activation',
                'equipment_delivery',
                'installation_complete',
                'testing_complete',
                'go_live',
                'training_complete',
                'acceptance_criteria',
                'custom'
            ]);
            
            // Milestone status and tracking
            $table->enum('status', [
                'not_started',
                'in_progress',
                'pending_review',
                'pending_approval',
                'completed',
                'delayed',
                'cancelled',
                'blocked'
            ])->default('not_started');
            
            // Scheduling and dependencies
            $table->date('planned_start_date')->nullable();
            $table->date('planned_completion_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->unsignedInteger('estimated_duration_days')->nullable();
            $table->unsignedInteger('actual_duration_days')->nullable();
            
            // Dependencies and prerequisites
            $table->json('prerequisites')->nullable(); // Required conditions before milestone can start
            $table->json('dependencies')->nullable(); // Other milestones this depends on
            $table->json('blocks')->nullable(); // What this milestone blocks
            
            // Financial information
            $table->decimal('milestone_value', 15, 2)->default(0);
            $table->decimal('invoice_amount', 15, 2)->default(0);
            $table->boolean('billable')->default(false);
            $table->enum('billing_trigger', [
                'milestone_start',
                'milestone_completion',
                'manual_trigger',
                'client_approval'
            ])->nullable();
            
            // Progress tracking
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->json('progress_metrics')->nullable(); // Custom progress indicators
            $table->json('kpis')->nullable(); // Key performance indicators
            
            // Deliverables and requirements
            $table->json('deliverables')->nullable(); // Expected outputs
            $table->json('acceptance_criteria')->nullable(); // Criteria for milestone completion
            $table->json('quality_requirements')->nullable(); // Quality standards
            $table->json('testing_requirements')->nullable(); // Testing procedures
            
            // VoIP-specific milestone data
            $table->json('voip_requirements')->nullable(); // VoIP service requirements
            $table->json('equipment_requirements')->nullable(); // Equipment needed
            $table->json('installation_requirements')->nullable(); // Installation procedures
            $table->json('porting_requirements')->nullable(); // Number porting requirements
            $table->json('compliance_requirements')->nullable(); // Regulatory compliance
            
            // Resource allocation
            $table->json('assigned_resources')->nullable(); // Team members, equipment
            $table->json('resource_requirements')->nullable(); // Required resources
            $table->decimal('budget_allocated', 15, 2)->nullable();
            $table->decimal('budget_spent', 15, 2)->default(0);
            
            // Risk management
            $table->json('risk_factors')->nullable();
            $table->json('mitigation_strategies')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            
            // Communication and notifications
            $table->json('stakeholders')->nullable(); // People to notify about milestone
            $table->json('notification_settings')->nullable();
            $table->timestamp('last_notification_sent')->nullable();
            
            // Approval and sign-off
            $table->boolean('requires_client_approval')->default(false);
            $table->boolean('requires_internal_approval')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Documentation and attachments
            $table->json('attached_documents')->nullable();
            $table->json('completion_evidence')->nullable(); // Evidence of completion
            $table->text('completion_notes')->nullable();
            
            // Integration and automation
            $table->json('integration_triggers')->nullable(); // External system triggers
            $table->json('automation_rules')->nullable(); // Automated actions
            $table->boolean('auto_invoice_generation')->default(false);
            $table->boolean('auto_service_activation')->default(false);
            
            // Performance and analytics
            $table->json('performance_metrics')->nullable();
            $table->decimal('client_satisfaction_score', 3, 2)->nullable();
            $table->text('lessons_learned')->nullable();
            
            // Milestone ordering and grouping
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->string('milestone_group')->nullable(); // Group related milestones
            $table->boolean('is_critical_path')->default(false);
            
            // User tracking
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['contract_id', 'status']);
            $table->index(['company_id', 'milestone_type']);
            $table->index(['planned_completion_date', 'status']);
            $table->index(['billable', 'billing_trigger']);
            $table->index('assigned_to');
            $table->index(['is_critical_path', 'planned_completion_date']);
            $table->index(['milestone_group', 'sort_order']);
            
            // Unique constraints
            $table->unique(['contract_id', 'milestone_number']);
            
            // Foreign key constraints
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};