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
        Schema::table('company_mail_settings', function (Blueprint $table) {
            // Add generic API fields for simplified configuration
            $table->text('api_key')->nullable()->after('driver');
            $table->text('api_secret')->nullable()->after('api_key');
            $table->string('api_domain')->nullable()->after('api_secret');
            
            // Add simplified reply_to field
            $table->string('reply_to')->nullable()->after('from_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_mail_settings', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_secret', 'api_domain', 'reply_to']);
        });
    }
};
