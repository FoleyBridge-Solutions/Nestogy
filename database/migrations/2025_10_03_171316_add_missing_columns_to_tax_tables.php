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
        if (Schema::hasTable('tax_exemptions')) {
            Schema::table('tax_exemptions', function (Blueprint $table) {
                if (!Schema::hasColumn('tax_exemptions', 'priority')) {
                    $table->integer('priority')->default(0)->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('tax_categories')) {
            Schema::table('tax_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('tax_categories', 'priority')) {
                    $table->integer('priority')->default(0)->after('company_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tax_exemptions')) {
            Schema::table('tax_exemptions', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
        
        if (Schema::hasTable('tax_categories')) {
            Schema::table('tax_categories', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
    }
};
