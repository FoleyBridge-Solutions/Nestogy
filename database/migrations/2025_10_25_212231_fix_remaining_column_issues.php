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
        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->string('action_type')->default('email')->after('step_number');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->foreignId('processed_by')->nullable()->after('refund_request_id');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropColumn('action_type');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('processed_by');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};
