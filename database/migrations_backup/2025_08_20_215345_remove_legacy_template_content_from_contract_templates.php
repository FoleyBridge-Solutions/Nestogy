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
        // First make the column nullable, then clear it, then drop it
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->longText('template_content')->nullable()->change();
        });

        // Clear any existing template_content
        // All templates now use the modern clause-based system
        \DB::table('contract_templates')->update(['template_content' => null]);

        Schema::table('contract_templates', function (Blueprint $table) {
            // Remove the legacy template_content column
            $table->dropColumn('template_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            // Add back the template_content column
            $table->longText('template_content')->nullable()->after('is_default');
        });
    }
};
