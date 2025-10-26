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
        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->string('alert_name')->nullable()->after('name');
            $table->string('alert_code')->nullable()->after('alert_name');
            $table->string('alert_type')->default('threshold')->after('alert_code');
            $table->string('usage_type')->default('voice')->after('alert_type');
            $table->string('threshold_type')->default('percentage')->after('usage_type');
            $table->decimal('threshold_value', 10, 2)->default(80)->after('threshold_type');
            $table->string('threshold_unit')->default('percent')->after('threshold_value');
            $table->string('alert_status')->default('normal')->after('threshold_unit');
        });
        
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->string('bucket_name')->nullable()->after('name');
            $table->string('bucket_code')->nullable()->after('bucket_name');
            $table->string('bucket_type')->default('included')->after('bucket_code');
            $table->string('usage_type')->default('voice')->after('bucket_type');
            $table->decimal('bucket_capacity', 15, 4)->default(0)->after('usage_type');
            $table->decimal('allocated_amount', 15, 4)->default(0)->after('bucket_capacity');
            $table->decimal('used_amount', 15, 4)->default(0)->after('allocated_amount');
            $table->string('capacity_unit')->default('minutes')->after('used_amount');
            $table->string('bucket_status')->default('active')->after('capacity_unit');
        });
        
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->string('type')->default('invoice_auto_pay')->after('payment_method_id');
            $table->string('trigger_type')->default('invoice_due')->after('type');
            $table->integer('trigger_days_offset')->default(0)->after('trigger_type');
            $table->time('trigger_time')->default('09:00:00')->after('trigger_days_offset');
            $table->string('currency_code')->default('USD')->after('trigger_time');
            $table->timestamp('next_processing_date')->nullable()->after('currency_code');
        });
        
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('plan_type')->default('custom')->after('plan_number');
            $table->decimal('original_amount', 15, 2)->default(0)->after('plan_type');
            $table->decimal('plan_amount', 15, 2)->default(0)->after('original_amount');
        });
        
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('client_id')->constrained('users')->onDelete('set null');
            $table->string('number')->unique()->nullable()->after('created_by');
            $table->string('type')->default('manual')->after('number');
        });
        
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->foreignId('requested_by')->nullable()->after('client_id')->constrained('users')->onDelete('set null');
            $table->string('refund_type')->default('credit_refund')->after('requested_by');
            $table->string('refund_method')->default('bank_transfer')->after('refund_type');
            $table->decimal('requested_amount', 15, 2)->default(0)->after('refund_method');
        });
        
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            $table->string('campaign_type')->default('automatic')->after('status');
            $table->foreignId('created_by')->nullable()->after('campaign_type')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->dropColumn(['alert_name', 'alert_code', 'alert_type', 'usage_type', 'threshold_type', 'threshold_value', 'threshold_unit', 'alert_status']);
        });
        
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->dropColumn(['bucket_name', 'bucket_code', 'bucket_type', 'usage_type', 'bucket_capacity', 'allocated_amount', 'used_amount', 'capacity_unit', 'bucket_status']);
        });
        
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->dropColumn(['type', 'trigger_type', 'trigger_days_offset', 'trigger_time', 'currency_code', 'next_processing_date']);
        });
        
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn(['plan_type', 'original_amount', 'plan_amount']);
        });
        
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'number', 'type']);
        });
        
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['requested_by', 'refund_type', 'refund_method', 'requested_amount']);
        });
        
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['campaign_type', 'created_by']);
        });
    }
};
