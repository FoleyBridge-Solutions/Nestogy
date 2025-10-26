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
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->foreignId('approver_id')->nullable()->after('credit_note_id');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->string('name')->nullable()->after('credit_note_id');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->foreignId('quote_id')->nullable()->after('company_id');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->string('currency_code')->default('USD')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropColumn('approver_id');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->dropColumn('quote_id');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('currency_code');
        });
    }
};
