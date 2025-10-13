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
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'imap_auth_method')) {
                    $table->enum('imap_auth_method', ['password', 'oauth', 'token'])->nullable()->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('expense_categories')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('expense_categories', 'code')) {
                    $table->string('code')->unique()->after('company_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('imap_auth_method');
            });
        }
        
        if (Schema::hasTable('expense_categories')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
