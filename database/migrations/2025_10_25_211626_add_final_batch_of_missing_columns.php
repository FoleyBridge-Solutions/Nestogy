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
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->timestamp('next_reset_date')->nullable()->after('bucket_status');
        });

        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->timestamp('alert_created_date')->nullable()->after('alert_severity');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->decimal('remaining_balance', 10, 2)->default(0)->after('status');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->integer('step_number')->default(1)->after('campaign_id');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('status');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->foreignId('refund_request_id')->nullable()->after('company_id');
        });

        Schema::table('compliance_requirements', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable()->after('check_frequency');
        });

        Schema::table('bouncer_roles', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        Schema::table('bouncer_abilities', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->dropColumn('next_reset_date');
        });

        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->dropColumn('alert_created_date');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('remaining_balance');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropColumn('step_number');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('refund_request_id');
        });

        Schema::table('compliance_requirements', function (Blueprint $table) {
            $table->dropColumn('last_checked_at');
        });

        Schema::table('bouncer_roles', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('bouncer_abilities', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
