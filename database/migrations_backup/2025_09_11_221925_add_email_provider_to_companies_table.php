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
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('email_provider_type', ['manual', 'microsoft365', 'google_workspace', 'exchange', 'custom_oauth'])
                ->default('manual')
                ->after('currency');
            $table->json('email_provider_config')->nullable()->after('email_provider_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['email_provider_type', 'email_provider_config']);
        });
    }
};
