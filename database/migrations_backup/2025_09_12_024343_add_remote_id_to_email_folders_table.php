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
        Schema::table('email_folders', function (Blueprint $table) {
            $table->string('remote_id')->nullable()->after('path');
            $table->index(['email_account_id', 'remote_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_folders', function (Blueprint $table) {
            $table->dropIndex(['email_account_id', 'remote_id']);
            $table->dropColumn('remote_id');
        });
    }
};
