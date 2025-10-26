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
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->decimal('line_total', 10, 2)->default(0)->after('unit_price');
            $table->decimal('remaining_credit', 10, 2)->default(0)->after('line_total');
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->string('activation_status')->default('not_required')->after('status');
            $table->integer('current_step')->default(1)->after('activation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn(['line_total', 'remaining_credit']);
        });

        Schema::table('quote_invoice_conversions', function (Blueprint $table) {
            $table->dropColumn(['activation_status', 'current_step']);
        });
    }
};
