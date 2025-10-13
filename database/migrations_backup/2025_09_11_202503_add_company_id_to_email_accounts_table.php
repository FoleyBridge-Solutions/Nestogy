<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->foreignId('company_id')->after('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'user_id']);
        });

        // Populate company_id for existing records
        DB::statement('
            UPDATE email_accounts
            SET company_id = users.company_id
            FROM users
            WHERE email_accounts.user_id = users.id
            AND email_accounts.company_id IS NULL
        ');

        // Make company_id not null after populating data
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
