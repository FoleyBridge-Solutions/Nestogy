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
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('custom_clauses')->nullable()->after('terms_and_conditions');
            $table->string('dispute_resolution')->nullable()->after('custom_clauses');
            $table->string('governing_law')->nullable()->after('dispute_resolution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['custom_clauses', 'dispute_resolution', 'governing_law']);
        });
    }
};
