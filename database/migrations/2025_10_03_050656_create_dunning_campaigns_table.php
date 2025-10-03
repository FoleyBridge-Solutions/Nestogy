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
        Schema::create('dunning_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('campaign_type')->nullable();
            $table->string('trigger_days_overdue')->nullable();
            $table->decimal('minimum_amount', 15, 2)->default(0);
            $table->decimal('maximum_amount', 15, 2)->default(0);
            $table->string('client_status_filters')->default('active');
            $table->string('service_type_filters')->nullable();
            $table->string('jurisdiction_filters')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('payment_history_threshold')->nullable();
            $table->string('max_failed_payments')->nullable();
            $table->string('consider_contract_status')->default('active');
            $table->string('max_sequence_cycles')->nullable();
            $table->string('auto_escalate')->nullable();
            $table->string('escalation_days')->nullable();
            $table->unsignedBigInteger('escalation_campaign_id')->nullable();
            $table->string('enable_service_suspension')->nullable();
            $table->string('suspension_days_overdue')->nullable();
            $table->string('essential_services_to_maintain')->nullable();
            $table->string('fdcpa_compliant')->nullable();
            $table->string('tcpa_compliant')->nullable();
            $table->string('state_law_compliance')->nullable();
            $table->string('require_dispute_resolution')->nullable();
            $table->string('success_rate')->nullable();
            $table->string('average_collection_time')->nullable();
            $table->string('cost_per_collection')->nullable();
            $table->decimal('total_campaigns_run', 15, 2)->default(0);
            $table->decimal('total_collected', 15, 2)->default(0);
            $table->string('auto_start')->nullable();
            $table->string('preferred_contact_time_start')->nullable();
            $table->string('preferred_contact_time_end')->nullable();
            $table->timestamp('blackout_dates')->nullable();
            $table->string('time_zone_settings')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
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
        Schema::dropIfExists('dunning_campaigns');
    }
};
