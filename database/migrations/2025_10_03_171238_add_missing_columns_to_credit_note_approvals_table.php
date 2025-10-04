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
            // Just add the most critical timestamp that's causing the error
            $table->timestamp('requested_at')->nullable()->after('company_id');
            $table->timestamp('reviewed_at')->nullable()->after('requested_at');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('expired_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_approvals', function (Blueprint $table) {
            $table->dropColumn(['requested_at', 'reviewed_at', 'approved_at', 'rejected_at', 'expired_at']);
        });
    }
};
