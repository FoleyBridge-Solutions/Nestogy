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
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'paused', 'draft'])->default('draft');
            $table->enum('campaign_type', ['gentle', 'standard', 'aggressive', 'legal', 'settlement'])->default('standard');
            
            // Trigger criteria
            $table->integer('trigger_days_overdue')->default(1);
            $table->decimal('minimum_amount', 10, 2)->default(0);
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->json('client_status_filters')->nullable(); // active, inactive, suspended
            $table->json('service_type_filters')->nullable(); // VoIP service types
            $table->json('jurisdiction_filters')->nullable(); // state/country filters
            
            // Risk assessment criteria
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('payment_history_threshold')->nullable(); // days
            $table->integer('max_failed_payments')->nullable();
            $table->boolean('consider_contract_status')->default(false);
            
            // Campaign settings
            $table->integer('max_sequence_cycles')->default(1);
            $table->boolean('auto_escalate')->default(false);
            $table->integer('escalation_days')->nullable();
            $table->string('escalation_campaign_id')->nullable();
            
            // Service suspension settings
            $table->boolean('enable_service_suspension')->default(false);
            $table->integer('suspension_days_overdue')->nullable();
            $table->json('essential_services_to_maintain')->nullable(); // E911, etc.
            
            // Legal and compliance
            $table->boolean('fdcpa_compliant')->default(true);
            $table->boolean('tcpa_compliant')->default(true);
            $table->json('state_law_compliance')->nullable();
            $table->boolean('require_dispute_resolution')->default(true);
            
            // Performance tracking
            $table->decimal('success_rate', 5, 2)->default(0); // percentage
            $table->decimal('average_collection_time', 8, 2)->default(0); // days
            $table->decimal('cost_per_collection', 10, 2)->default(0);
            $table->integer('total_campaigns_run')->default(0);
            $table->decimal('total_collected', 15, 2)->default(0);
            
            // Automation settings
            $table->boolean('auto_start')->default(false);
            $table->time('preferred_contact_time_start')->nullable();
            $table->time('preferred_contact_time_end')->nullable();
            $table->json('blackout_dates')->nullable(); // holidays, etc.
            $table->json('time_zone_settings')->nullable();
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'status']);
            $table->index(['campaign_type', 'risk_level']);
            $table->index('trigger_days_overdue');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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