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
        Schema::table('email_accounts', function (Blueprint $table) {
            // Make IMAP/SMTP fields nullable for OAuth accounts
            $table->string('imap_host')->nullable()->change();
            $table->string('imap_username')->nullable()->change();
            $table->text('imap_password')->nullable()->change();
            $table->string('smtp_host')->nullable()->change();
            $table->string('smtp_username')->nullable()->change();
            $table->text('smtp_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            // Revert to non-nullable (but this could cause issues if OAuth accounts exist)
            $table->string('imap_host')->nullable(false)->change();
            $table->string('imap_username')->nullable(false)->change();
            $table->text('imap_password')->nullable(false)->change();
            $table->string('smtp_host')->nullable(false)->change();
            $table->string('smtp_username')->nullable(false)->change();
            $table->text('smtp_password')->nullable(false)->change();
        });
    }
};
