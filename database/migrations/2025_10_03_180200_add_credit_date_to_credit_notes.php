<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_notes') && !Schema::hasColumn('credit_notes', 'credit_date')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->date('credit_date')->nullable()->after('number');
            });
        }
        
        if (Schema::hasTable('credit_notes') && !Schema::hasColumn('credit_notes', 'remaining_balance')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->decimal('remaining_balance', 15, 2)->default(0)->after('credit_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_notes', 'credit_date')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropColumn('credit_date');
            });
        }
        
        if (Schema::hasColumn('credit_notes', 'remaining_balance')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropColumn('remaining_balance');
            });
        }
    }
};
