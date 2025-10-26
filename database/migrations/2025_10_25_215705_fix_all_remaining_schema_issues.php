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
            $table->string('approval_type')->default('manual')->after('requested_by');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->default(1)->after('item_type');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->string('conversion_type')->default('full')->after('invoice_id');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->timestamp('initiated_at')->nullable()->after('transaction_id');
        });

        Schema::table('tax_api_query_cache', function (Blueprint $table) {
            $table->decimal('response_time_ms', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropColumn('approval_type');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->dropColumn('conversion_type');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('initiated_at');
        });

        Schema::table('tax_api_query_cache', function (Blueprint $table) {
            $table->integer('response_time_ms')->nullable()->change();
        });
    }
};
