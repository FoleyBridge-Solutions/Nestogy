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
        // Add to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable()->after('chargeback_date')->constrained('bank_transactions')->nullOnDelete();
        });

        // Add to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable()->after('metadata')->constrained('bank_transactions')->nullOnDelete();
            $table->string('plaid_transaction_id')->nullable()->after('bank_transaction_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bank_transaction_id']);
            $table->dropColumn('bank_transaction_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_transaction_id']);
            $table->dropColumn(['bank_transaction_id', 'plaid_transaction_id']);
        });
    }
};
