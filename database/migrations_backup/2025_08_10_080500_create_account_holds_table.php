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
        Schema::create('account_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('dunning_action_id')->nullable();
            $table->unsignedBigInteger('payment_plan_id')->nullable();
            $table->string('hold_reference')->unique(); // AH-0001, etc.
            
            // Hold classification
            $table->enum('hold_type', [
                'service_suspension', 'credit_hold', 'billing_hold', 'compliance_hold',
                'legal_hold', 'fraud_hold', 'payment_plan_violation', 'dispute_hold',
                'regulatory_hold', 'equipment_recovery', 'porting_restriction'
            ]);
            $table->enum('status', [
                'pending', 'active', 'partial', 'lifted', 'expired', 'overridden'
            ])->default('pending');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Hold details
            $table->string('title');
            $table->text('description');
            $table->text('reason');
            $table->decimal('amount_threshold', 15, 2)->nullable(); // amount that triggered hold
            $table->integer('days_overdue')->nullable();
            
            // Timing
            $table->timestamp('scheduled_at')->nullable(); // when hold should take effect
            $table->timestamp('effective_at')->nullable(); // when hold actually took effect
            $table->timestamp('expires_at')->nullable(); // automatic expiration
            $table->timestamp('lifted_at')->nullable();
            $table->integer('grace_period_hours')->default(0);
            $table->timestamp('grace_period_expires_at')->nullable();
            
            // Service suspension details
            $table->json('services_affected')->nullable(); // which services to suspend/restrict
            $table->json('essential_services_maintained')->nullable(); // E911, emergency services
            $table->boolean('graceful_suspension')->default(true); // gradual vs immediate
            $table->integer('suspension_notice_hours')->default(24); // notice period
            $table->boolean('partial_suspension')->default(false); // partial vs complete
            
            // VoIP-specific holds
            $table->boolean('restrict_outbound_calls')->default(false);
            $table->boolean('restrict_long_distance')->default(false);
            $table->boolean('restrict_international')->default(false);
            $table->boolean('maintain_e911')->default(true);
            $table->boolean('maintain_inbound_calls')->default(false);
            $table->boolean('prevent_number_porting')->default(false);
            $table->json('allowed_numbers')->nullable(); // emergency numbers, etc.
            
            // Equipment and asset holds
            $table->boolean('restrict_equipment_changes')->default(false);
            $table->boolean('prevent_new_orders')->default(false);
            $table->boolean('require_equipment_return')->default(false);
            $table->json('equipment_to_recover')->nullable(); // list of equipment
            $table->date('equipment_return_deadline')->nullable();
            
            // Credit and billing restrictions
            $table->boolean('credit_hold')->default(false);
            $table->decimal('credit_limit_override', 15, 2)->nullable();
            $table->boolean('require_prepayment')->default(false);
            $table->boolean('stop_recurring_billing')->default(false);
            $table->boolean('prevent_service_changes')->default(false);
            
            // Compliance and regulatory
            $table->json('regulatory_requirements')->nullable(); // specific regulations
            $table->boolean('customer_notification_sent')->default(false);
            $table->timestamp('customer_notified_at')->nullable();
            $table->json('notification_methods')->nullable(); // email, sms, mail
            $table->boolean('regulatory_filing_required')->default(false);
            $table->boolean('regulatory_filing_completed')->default(false);
            
            // Override and approval
            $table->boolean('can_be_overridden')->default(true);
            $table->json('override_permissions')->nullable(); // roles that can override
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', [
                'not_required', 'pending', 'approved', 'rejected'
            ])->default('not_required');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Removal conditions
            $table->json('lift_conditions')->nullable(); // conditions to automatically lift
            $table->decimal('payment_required_amount', 15, 2)->nullable();
            $table->boolean('full_payment_required')->default(false);
            $table->boolean('payment_plan_acceptable')->default(true);
            $table->boolean('manager_approval_required')->default(false);
            
            // Impact tracking
            $table->decimal('revenue_impact', 15, 2)->default(0); // lost revenue
            $table->integer('affected_services_count')->default(0);
            $table->integer('affected_users_count')->default(0);
            $table->json('business_impact_assessment')->nullable();
            
            // Customer communication
            $table->json('communication_log')->nullable(); // record of notifications
            $table->boolean('customer_contacted')->default(false);
            $table->timestamp('last_customer_contact')->nullable();
            $table->enum('customer_response', [
                'no_response', 'acknowledged', 'disputed', 'payment_promised', 
                'hardship_claimed', 'legal_threat', 'escalated'
            ])->nullable();
            $table->text('customer_feedback')->nullable();
            
            // Restoration process
            $table->enum('restoration_method', [
                'automatic', 'manual', 'staged', 'gradual'
            ])->default('automatic');
            $table->integer('restoration_time_minutes')->nullable();
            $table->json('restoration_steps')->nullable(); // ordered steps for restoration
            $table->boolean('restoration_verification_required')->default(true);
            $table->boolean('restoration_completed')->default(false);
            $table->timestamp('restoration_verified_at')->nullable();
            
            // Legal and escalation
            $table->boolean('legal_action_pending')->default(false);
            $table->boolean('collection_agency_involved')->default(false);
            $table->string('collection_agency')->nullable();
            $table->date('legal_action_date')->nullable();
            $table->text('legal_notes')->nullable();
            
            // Performance metrics
            $table->integer('effectiveness_score')->nullable(); // 1-10 scale
            $table->boolean('resulted_in_payment')->default(false);
            $table->decimal('payment_amount_received', 15, 2)->default(0);
            $table->integer('days_to_resolution')->nullable();
            $table->enum('resolution_type', [
                'payment', 'payment_plan', 'writeoff', 'legal_action', 'settlement'
            ])->nullable();
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('lifted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'hold_type', 'status'], 'comp_hold_stat_idx');
            $table->index(['status', 'effective_at']);
            $table->index(['expires_at', 'status']);
            $table->index(['hold_type', 'severity']);
            $table->index(['requires_approval', 'approval_status']);
            $table->index(['customer_contacted', 'last_customer_contact']);
            $table->index('hold_reference');
            
            // Foreign key constraints
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('dunning_action_id')->references('id')->on('dunning_actions')->onDelete('set null');
            // Foreign key for payment_plans will be added after payment_plans table is created
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('lifted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_holds');
    }
};