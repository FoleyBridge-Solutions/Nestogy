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
        // Add columns to refund_requests
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('request_number')->nullable()->after('company_id');
                $table->string('number')->nullable()->after('request_number');
            });
        }
        
        // Add columns to refund_transactions
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->foreignId('processed_by')->nullable()->after('company_id')->constrained('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropColumn(['request_number', 'number']);
            });
        }
        
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->dropForeign(['processed_by']);
                $table->dropColumn(['processed_by']);
            });
        }
    }
};
