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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('dunning_action_id')->nullable();
            $table->unsignedBigInteger('payment_plan_id')->nullable();
            $table->string('hold_reference')->nullable();
            $table->string('hold_type')->nullable();
            $table->string('status')->default('active');
            $table->string('severity')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('reason')->nullable();
            $table->decimal('amount_threshold', 15, 2)->default(0);
            $table->string('days_overdue')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->string('grace_period_hours')->nullable();
            $table->timestamp('grace_period_expires_at')->nullable();
            $table->string('services_affected')->nullable();
            $table->string('essential_services_maintained')->nullable();
            $table->string('graceful_suspension')->nullable();
            $table->string('suspension_notice_hours')->nullable();
            $table->string('partial_suspension')->nullable();
            $table->string('restrict_outbound_calls')->nullable();
            $table->string('restrict_long_distance')->nullable();
            $table->string('restrict_international')->nullable();
            $table->string('maintain_e911')->nullable();
            $table->string('maintain_inbound_calls')->nullable();
            $table->string('prevent_number_porting')->nullable();
            $table->string('allowed_numbers')->nullable();
            $table->string('restrict_equipment_changes')->nullable();
            $table->string('prevent_new_orders')->nullable();
            $table->string('require_equipment_return')->nullable();
            $table->string('equipment_to_recover')->nullable();
            $table->string('equipment_return_deadline')->nullable();
            $table->string('credit_hold')->nullable();
            $table->string('credit_limit_override')->nullable();
            $table->string('require_prepayment')->nullable();
            $table->string('stop_recurring_billing')->nullable();
            $table->string('prevent_service_changes')->nullable();
            $table->string('regulatory_requirements')->nullable();
            $table->string('customer_notification_sent')->nullable();
            $table->timestamp('customer_notified_at')->nullable();
            $table->string('notification_methods')->nullable();
            $table->string('regulatory_filing_required')->nullable();
            $table->string('regulatory_filing_completed')->nullable();
            $table->string('can_be_overridden')->nullable();
            $table->string('override_permissions')->nullable();
            $table->string('requires_approval')->nullable();
            $table->string('approval_status')->default('active');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('lift_conditions')->nullable();
            $table->decimal('payment_required_amount', 15, 2)->default(0);
            $table->string('full_payment_required')->nullable();
            $table->string('payment_plan_acceptable')->nullable();
            $table->string('manager_approval_required')->nullable();
            $table->string('revenue_impact')->nullable();
            $table->string('affected_services_count')->nullable();
            $table->string('affected_users_count')->nullable();
            $table->string('business_impact_assessment')->nullable();
            $table->string('communication_log')->nullable();
            $table->string('customer_contacted')->nullable();
            $table->string('last_customer_contact')->nullable();
            $table->string('customer_response')->nullable();
            $table->string('customer_feedback')->nullable();
            $table->string('restoration_method')->nullable();
            $table->string('restoration_time_minutes')->nullable();
            $table->string('restoration_steps')->nullable();
            $table->string('restoration_verification_required')->nullable();
            $table->string('restoration_completed')->nullable();
            $table->timestamp('restoration_verified_at')->nullable();
            $table->string('legal_action_pending')->nullable();
            $table->string('collection_agency_involved')->nullable();
            $table->string('collection_agency')->nullable();
            $table->timestamp('legal_action_date')->nullable();
            $table->text('legal_notes')->nullable();
            $table->string('effectiveness_score')->nullable();
            $table->string('resulted_in_payment')->nullable();
            $table->decimal('payment_amount_received', 15, 2)->default(0);
            $table->string('days_to_resolution')->nullable();
            $table->string('resolution_type')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
            $table->string('lifted_by')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
