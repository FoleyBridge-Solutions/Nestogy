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
        Schema::create('client_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('trip_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('trip_type', ['onsite_visit', 'maintenance', 'consultation', 'training', 'meeting', 'installation', 'support', 'audit'])->default('onsite_visit');
            $table->enum('trip_purpose', ['scheduled_maintenance', 'emergency_support', 'project_work', 'client_meeting', 'training_delivery', 'system_installation', 'audit_compliance', 'general_support'])->default('scheduled_maintenance');
            $table->date('departure_date');
            $table->date('return_date');
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->integer('total_days')->nullable();
            $table->decimal('total_hours', 8, 2)->nullable();
            $table->enum('status', ['planned', 'approved', 'in_progress', 'completed', 'cancelled', 'rescheduled', 'pending_approval'])->default('planned');
            $table->string('destination_city');
            $table->string('destination_state')->nullable();
            $table->string('destination_country')->default('US');
            $table->text('destination_address')->nullable();
            $table->text('client_site_details')->nullable();
            $table->json('site_contacts')->nullable(); // On-site contact information
            $table->enum('transportation_mode', ['flight', 'car', 'train', 'bus', 'other'])->default('car');
            $table->text('transportation_details')->nullable();
            $table->decimal('transportation_cost', 10, 2)->default(0);
            $table->decimal('mileage', 8, 2)->nullable();
            $table->decimal('mileage_rate', 5, 3)->default(0.655); // IRS standard rate
            $table->decimal('mileage_reimbursement', 10, 2)->default(0);
            $table->string('accommodation_type')->nullable(); // hotel, client_facility, none
            $table->text('accommodation_details')->nullable();
            $table->decimal('accommodation_cost', 10, 2)->default(0);
            $table->integer('accommodation_nights')->default(0);
            $table->decimal('meal_allowance', 10, 2)->default(0);
            $table->decimal('meal_expenses', 10, 2)->default(0);
            $table->json('expense_categories')->nullable(); // Breakdown by category
            $table->decimal('other_expenses', 10, 2)->default(0);
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            $table->decimal('total_actual_cost', 10, 2)->default(0);
            $table->decimal('cost_variance', 10, 2)->default(0);
            $table->decimal('cost_variance_percentage', 5, 2)->default(0);
            $table->json('expense_receipts')->nullable(); // Receipt file attachments
            $table->boolean('billable_to_client')->default(true);
            $table->decimal('billable_amount', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->text('billing_notes')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'requires_revision'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('approval_history')->nullable();
            $table->boolean('requires_reimbursement')->default(true);
            $table->enum('reimbursement_status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->decimal('reimbursement_amount', 10, 2)->default(0);
            $table->timestamp('reimbursement_requested')->nullable();
            $table->timestamp('reimbursement_paid')->nullable();
            $table->foreignId('reimbursed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('reimbursement_notes')->nullable();
            $table->string('payment_method')->nullable(); // check, direct_deposit, expense_account
            $table->string('payment_reference')->nullable();
            $table->json('work_performed')->nullable(); // Tasks completed during trip
            $table->json('objectives_met')->nullable(); // Trip objectives status
            $table->json('deliverables')->nullable(); // Items delivered to client
            $table->text('trip_summary')->nullable();
            $table->text('client_feedback')->nullable();
            $table->enum('trip_outcome', ['successful', 'partially_successful', 'unsuccessful', 'rescheduled'])->nullable();
            $table->json('follow_up_items')->nullable(); // Required follow-up actions
            $table->date('follow_up_date')->nullable();
            $table->json('issues_encountered')->nullable();
            $table->json('lessons_learned')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('time_tracking')->nullable(); // Detailed time breakdown
            $table->decimal('productive_hours', 8, 2)->nullable();
            $table->decimal('travel_hours', 8, 2)->nullable();
            $table->decimal('wait_time_hours', 8, 2)->nullable();
            $table->json('daily_reports')->nullable(); // Daily activity reports
            $table->json('photos')->nullable(); // Trip photos/documentation
            $table->json('documents')->nullable(); // Related documents
            $table->json('equipment_used')->nullable(); // Tools/equipment taken
            $table->json('materials_used')->nullable(); // Materials consumed
            $table->boolean('safety_incident')->default(false);
            $table->text('safety_notes')->nullable();
            $table->json('safety_reports')->nullable();
            $table->json('weather_conditions')->nullable();
            $table->json('site_conditions')->nullable();
            $table->json('client_requirements')->nullable();
            $table->boolean('recurring_trip')->default(false);
            $table->string('recurring_schedule')->nullable();
            $table->string('parent_trip_id')->nullable();
            $table->json('team_members')->nullable(); // Multiple travelers
            $table->foreignId('trip_lead')->nullable()->constrained('users')->onDelete('set null');
            $table->json('emergency_contacts')->nullable();
            $table->json('travel_authorizations')->nullable();
            $table->json('insurance_information')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('integration_data')->nullable(); // External system sync
            $table->string('external_id')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('alerts')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('trip_number');
            $table->index('trip_type');
            $table->index('departure_date');
            $table->index('return_date');
            $table->index(['departure_date', 'return_date']);
            $table->index('approval_status');
            $table->index('reimbursement_status');
            $table->index('trip_lead');
            $table->index('billable_to_client');
            $table->index('requires_reimbursement');
            $table->index('external_id');
            $table->index('follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_trips');
    }
};