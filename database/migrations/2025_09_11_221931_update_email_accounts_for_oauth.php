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
            $table->enum('connection_type', ['manual', 'oauth'])->default('manual')->after('provider');
            $table->string('oauth_provider')->nullable()->after('connection_type');
            $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['connection_type', 'oauth_provider', 'oauth_token_expires_at']);
        });
    }
};
