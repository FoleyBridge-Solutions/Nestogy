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
        // Add priority to tax_exemptions
        if (Schema::hasTable('tax_exemptions')) {
            Schema::table('tax_exemptions', function (Blueprint $table) {
                $table->integer('priority')->default(0)->after('company_id');
            });
        }
        
        // Add priority to tax_categories
        if (Schema::hasTable('tax_categories')) {
            Schema::table('tax_categories', function (Blueprint $table) {
                $table->integer('priority')->default(0)->after('company_id');
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
