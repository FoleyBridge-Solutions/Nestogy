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
            $table->foreignId('credit_note_id')->after('company_id')->constrained('credit_notes');
        });

        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->foreignId('credit_note_id')->after('company_id')->constrained('credit_notes');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('action_type');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropForeign(['credit_note_id']);
            $table->dropColumn('credit_note_id');
        });

        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropForeign(['credit_note_id']);
            $table->dropColumn('credit_note_id');
        });

        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
