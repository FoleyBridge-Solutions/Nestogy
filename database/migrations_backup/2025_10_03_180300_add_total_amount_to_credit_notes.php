<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_notes') && !Schema::hasColumn('credit_notes', 'total_amount')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('credit_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_notes', 'total_amount')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropColumn('total_amount');
            });
        }
    }
};
