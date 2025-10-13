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
        Schema::table('compliance_checks', function (Blueprint $table) {
            // The original migration had 'name' column, but it was dropped when we added other columns
            // Check if it exists before adding
            if (!Schema::hasColumn('compliance_checks', 'name')) {
                $table->string('name')->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_checks', function (Blueprint $table) {
            // Don't drop name as it was in original migration
        });
    }
};
