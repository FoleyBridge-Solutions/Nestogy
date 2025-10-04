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
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            // Add description
            $table->text('description')->nullable()->after('name');
            
            // Status and type
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'archived'])->default('draft')->after('description');
            $table->string('campaign_type')->default('standard')->after('status');
            
            // Trigger criteria
            $table->integer('trigger_days_overdue')->default(0)->after('campaign_type');
            $table->decimal('minimum_amount', 15, 2)->nullable()->after('trigger_days_overdue');
            $table->decimal('maximum_amount', 15, 2)->nullable()->after('minimum_amount');
            
            // Filters
            $table->json('client_status_filters')->nullable()->after('maximum_amount');
            $table->json('service_type_filters')->nullable()->after('client_status_filters');
            $table->json('jurisdiction_filters')->nullable()->after('service_type_filters');
            
            // Risk and history
            $table->string('risk_level')->nullable()->after('jurisdiction_filters');
            $table->integer('payment_history_threshold')->nullable()->after('risk_level');
            $table->integer('max_failed_payments')->nullable()->after('payment_history_threshold');
            $table->boolean('consider_contract_status')->default(true)->after('max_failed_payments');
            
            // Sequence settings
            $table->integer('max_sequence_cycles')->default(1)->after('consider_contract_status');
            $table->boolean('auto_escalate')->default(false)->after('max_sequence_cycles');
            $table->integer('escalation_days')->nullable()->after('auto_escalate');
            $table->foreignId('escalation_campaign_id')->nullable()->after('escalation_days')->constrained('dunning_campaigns')->onDelete('set null');
            
            // Service suspension
            $table->boolean('enable_service_suspension')->default(false)->after('escalation_campaign_id');
            $table->integer('suspension_days_overdue')->nullable()->after('enable_service_suspension');
            $table->json('essential_services_to_maintain')->nullable()->after('suspension_days_overdue');
            
            // Compliance
            $table->boolean('fdcpa_compliant')->default(true)->after('essential_services_to_maintain');
            $table->boolean('tcpa_compliant')->default(true)->after('fdcpa_compliant');
            $table->json('state_law_compliance')->nullable()->after('tcpa_compliant');
            $table->boolean('require_dispute_resolution')->default(true)->after('state_law_compliance');
            
            // Performance metrics
            $table->decimal('success_rate', 5, 2)->default(0)->after('require_dispute_resolution');
            $table->integer('average_collection_time')->default(0)->after('success_rate');
            $table->decimal('cost_per_collection', 15, 2)->default(0)->after('average_collection_time');
            $table->integer('total_campaigns_run')->default(0)->after('cost_per_collection');
            $table->decimal('total_collected', 15, 2)->default(0)->after('total_campaigns_run');
            
            // Scheduling
            $table->boolean('auto_start')->default(false)->after('total_collected');
            $table->time('preferred_contact_time_start')->nullable()->after('auto_start');
            $table->time('preferred_contact_time_end')->nullable()->after('preferred_contact_time_start');
            $table->json('blackout_dates')->nullable()->after('preferred_contact_time_end');
            $table->string('time_zone_settings')->nullable()->after('blackout_dates');
            
            // User tracking
            $table->foreignId('created_by')->nullable()->after('time_zone_settings')->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['trigger_days_overdue']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            $table->dropForeign(['escalation_campaign_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'description', 'status', 'campaign_type', 'trigger_days_overdue',
                'minimum_amount', 'maximum_amount', 'client_status_filters',
                'service_type_filters', 'jurisdiction_filters', 'risk_level',
                'payment_history_threshold', 'max_failed_payments',
                'consider_contract_status', 'max_sequence_cycles', 'auto_escalate',
                'escalation_days', 'escalation_campaign_id', 'enable_service_suspension',
                'suspension_days_overdue', 'essential_services_to_maintain',
                'fdcpa_compliant', 'tcpa_compliant', 'state_law_compliance',
                'require_dispute_resolution', 'success_rate', 'average_collection_time',
                'cost_per_collection', 'total_campaigns_run', 'total_collected',
                'auto_start', 'preferred_contact_time_start', 'preferred_contact_time_end',
                'blackout_dates', 'time_zone_settings', 'created_by', 'updated_by'
            ]);
        });
    }
};
