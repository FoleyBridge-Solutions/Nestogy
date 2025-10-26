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
            $table->foreignId('requested_by')->nullable()->after('approver_id');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->string('item_type')->default('product')->after('name');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('quote_id');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('refund_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropColumn('requested_by');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->dropColumn('invoice_id');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
