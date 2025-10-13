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
            // JSON column for branding settings
            $table->json('branding')->nullable()->after('logo');
            
            // JSON column for extended company information
            $table->json('company_info')->nullable()->after('branding');
            
            // JSON column for social media links
            $table->json('social_links')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['branding', 'company_info', 'social_links']);
        });
    }
};
