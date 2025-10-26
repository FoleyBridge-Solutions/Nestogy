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
            $table->boolean('is_active')->default(true)->after('alert_severity');
        });

        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('rollover_enabled');
        });

        Schema::table('auto_payments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('next_processing_date');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('status')->default('active')->after('plan_amount');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('type');
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable()->after('refund_method');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained('dunning_campaigns')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('auto_payments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn('requested_at');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
    }
};
