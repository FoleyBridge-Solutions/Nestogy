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
            $table->timestamp('sla_deadline')->nullable()->after('approval_type');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('conversion_type');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->integer('max_retries')->default(3)->after('initiated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropColumn('sla_deadline');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('max_retries');
        });
    }
};
