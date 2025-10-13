<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_categories') && !Schema::hasColumn('expense_categories', 'metadata')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('expense_categories', 'metadata')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
