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
        // tax_exemptions - add user tracking
        if (Schema::hasTable('tax_exemptions')) {
            Schema::table('tax_exemptions', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('priority')->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            });
        }
        
        // tax_categories - add user tracking
        if (Schema::hasTable('tax_categories')) {
            Schema::table('tax_categories', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('priority')->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
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
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropColumn(['created_by', 'updated_by']);
            });
        }
        
        if (Schema::hasTable('tax_categories')) {
            Schema::table('tax_categories', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropColumn(['created_by', 'updated_by']);
            });
        }
    }
};
